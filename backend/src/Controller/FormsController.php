<?php
/**
 * CAPS - Compilazione Assistita Piani di Studio
 * Copyright (C) 2014 - 2021 E. Paolini, J. Notarstefano, L. Robol
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * This program is based on the CakePHP framework, which is released under
 * the MIT license, and whose copyright is held by the Cake Software
 * Foundation. See https://cakephp.org/ for further details.
 */
namespace App\Controller;

use App\Model\Entity\Form;
use App\Controller\AppController;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\I18n\Time;
use Cake\Mailer\Email;
use Cake\Validation\Validation;
use App\Form\FormsFilterForm;


class FormsController extends AppController
{    
    public function index()
    {
        $forms = $this->Forms->find();

        $filterForm = new FormsFilterForm($forms);
        $forms = $filterForm->validate_and_execute($this->request->getQuery());

        $this->set('data', $forms);
        $this->viewBuilder()->setOption('serialize', 'data');
    }  

    public function edit($form_id = null)
    {
        if (!$this->user) throw new ForbiddenException();

        if ($form_id == null) {
            $form = new Form();
            $form->user = $this->user;
            $form->state = 'draft';
        } else {
            $form = $this->Forms->find()->contain([
                'Users'])
                ->where(['Forms.id' => $form_id])
                ->firstOrFail();

            // Check if the user has permission
            if ($form['user']['id'] != $this->user['id']) {
                throw new ForbiddenException();
            }
        }

        if ($form['state'] != 'draft') {
            throw new ForbiddenException();
        }

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $form = $this->Forms->patchEntity($form, $data);
            $form->user_id = $this->user['id'];

            $form_template = $this->Forms->FormTemplates->get($form['form_template_id']);
            $form->template_text = $form_template['text'];

            if ($data['action'] == 'submit') {
                $form->date_submitted = Time::now();
                $form->state = "submitted";
            } else {
                $form->state = "draft";
            }

            if ($this->Forms->save($form)) {
                if ($form['state'] == "submitted") {
                    if ($this->notifySubmission($form['id'])) {
                        $this->Flash->success("Modulo inviato correttamente.");
                    } else {
                        $this->Flash->error("Non sono riuscito ad inviare l'email di notifica.");
                    }
                }
                return $this->redirect([ 'controller' => 'users', 'action' => 'view' ]);
            } else {
                foreach ($form->errors() as $field => $errors) {
                    foreach ($errors as $error) {
                        $this->Flash->error('Errore nel campo "' . $field . '" del modulo: ' . $error);
                    }
                }
                return $this->redirect(['action' => 'edit', $form['id']]);
            }
        }   
        $this->set('form', $form);
    }

    /**
     * Check if a user can view a specific form. At the moment we
     * grant view rights to the folloing classes of users:
     *
     * 1. The user who submit the form.
     * 2. System administrators.
     * 3. Any user who has been notified for the form.
     *
     * The function returns true if the user is allowed to view the form,
     * false otherwise.
     */
    private function canView($user, $form) : bool {
        if ($form['user_id'] == $user['id']) {
            return true;
        }

        if ($user['admin']) {
            return true;
        }

        $notified_emails = explode(',', $form['form_template']['notify_emails']);

        return ($user['email'] && in_array($user['email'], $notified_emails));
    }

    public function view($id) {
        $query = $this->request->getQuery();
        if (!$id) {
            throw new NotFoundException(__('Richiesta non valida: manca l\'id.'));
        }
        $form = $this->Forms->get($id, ['contain' => ['FormTemplates', 'Users']]);

        if (! $this->canView($this->user, $form)) {
            throw new ForbiddenException();
        }

        $this->set('form', $form);
        $_serialize = [ 'form' ];
        $this->set('_serialize', $_serialize);
    }

    public function delete($id)
    {
        if (! $id) {
            throw new NotFoundException();
        }

        $form = $this->Forms->get($id, ['contain' => [ 'Users' ]]);

        // Check that the user matches, otherwise he/she may not be allowed to see, let alone delete
        // the given form.
        if ($form['user']['id'] != $this->user['id'] && !$this->user['admin']) {
            throw new ForbiddenException('Utente non autorizzato a eliminare questo modulo');
        }

        if ($form['state'] != 'draft') {
            throw new ForbiddenException('Impossibile eliminare un modulo se non è in stato \'bozza\'');
        }

        if ($this->Forms->delete($form)) {
            $this->Flash->success('Modulo eliminato');
        } else {
            $this->log('Errore nella cancellazione del modulo con ID = ' . $form['id']);
            $this->Flash->error('Impossibile eliminare il modulo');
        }

        return $this->redirect([ 'controller' => 'users', 'action' => 'view' ]);
    }

    private function get_form($id)
    {
        $form = $this->Forms->get($id, [
            "contain" => [ 'Users', 'FormTemplates']
            ]);
        $form['data_expanded'] = json_decode($form['data']);
        return $form;
    }

    private function createEmail($form)
    {
        $email = new Email();

        $form_data = json_decode($form['data'], true);

        // Find the address that need to be notified in Cc, if any
        $cc_addresses = array_map(
            function ($address) use ($form_data) {
                $address = trim($address);

                if ($address && $address[0] == '$') {
                    // Try to find the field in the form data for the substituion
                    $key = substr($address, 1);
                    if (array_key_exists($key, $form_data)) {
                        return trim($form_data[$key]);
                    }
                }

                return $address;
            },
            explode(',', $form['form_template']['notify_emails'])
        );

        // We only select valid email addresses, the remaining are ignored.
        $cc_addresses = array_filter($cc_addresses, function ($address) {
            return Validation::email($address);
        });

        if (count($cc_addresses) > 0) {
            $email->addCc($cc_addresses);
        }

        $email->setViewVars([ 'settings' => $this->getSettings(), 'form' => $form ])
            ->setEmailFormat('html');

        return $email;
    }

    private function notify($form_id, $template_name, $subject): bool
    {
        $form = $this->get_form($form_id);

        if ($form['user']['email'] == "" || $form['user']['email'] == null) {
            $this->log("User " . $form['user']['username'] . " has no email");
            return False;
        }
        $email = $this->createEmail($form)        
            ->setTo($form['user']['email'])
            ->setSubject($subject . ": " . $form['form_template']['name']);
        $email->viewBuilder()->setTemplate($template_name);
        try {
            $email->send();
            return True;
        } catch (\Exception $e) {
            $this->log("Could not send email: " . $e->getMessage());
            return False;
        }
    }

    private function notifySubmission($form_id): bool {
        return $this->notify($form_id, 'form_submission', 'Modulo inviato');
    }

    private function notifyApproval($form_id): bool {
        return $this->notify($form_id, 'form_approval', 'Richiesta approvata');
    }

    private function notifyRejection($form_id): bool {
        return $this->notify($form_id, 'form_rejection', 'Richiesta negata');
    }

}