<?php

namespace App\Controller;

use App\Auth\UnipiAuthenticate;
use App\Controller\Event;
use Cake\ORM\TableRegistry;
use Cake\Http\Exception\ForbiddenException;

class ExamsController extends AppController {

    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('Paginator');
        $this->loadComponent('RequestHandler');
    }

    private function exams()
    {
        return $this->Exams->find()
            ->limit(25)
            ->order([ 'Exams.name' => 'asc' ]);
    }

    public function beforeFilter ($event) {
        parent::beforeFilter($event);
        $this->Auth->deny();
    }

    /**
     * @brief Get all exams in JSON format. URL: caps/exams.json
     */
    public function index () {
        $exams = $this->Exams->find('all');
        $this->set('exams', $exams);
        $this->set('_serialize', [ 'exams' ]);
    }

    public function adminIndex () {
        $user = $this->Auth->user();
        if (!$user['admin']) {
            throw new ForbiddenException();
        }

        $exams = $this->Paginator->paginate($this->exams());
        $this->set('exams', $exams);
    }

    /**
     * @brief Get a single exam in JSON format. URL: caps/exams/view/1.json
     */
    public function view ($id = null) {
        if (!$id) {
            throw new NotFoundException(__('Richiesta non valida: manca l\'id.'));
        }

        $exam = $this->Exams->findById($id)->firstOrFail();
        if (!$exam) {
            throw new NotFoundException(__('Errore: esame non esistente.'));
        }
        $this->set('exam', $exam);
    }

    public function edit($id = null) {
        $user =  $this->Auth->user();
        if (!$user['admin']) {
            throw new ForbiddenException();
        }

        if ($id) { // edit
            $exam = $this->Exams->findById($id)->contain([ 'Groups' ])->firstOrFail();
            if (!$exam) {
                throw new NotFoundException(__('Errore: esame non esistente.'));
            }
            $success_message = __('Esame aggiornato con successo.');
            $failure_message = __('Errore: esame non aggiornato.');
            $then = 'adminIndex';
        } else { // new
            $exam = $this->Exams->newEntity();
            $success_message = __('Esame aggiunto con successo.');
            $failure_message = __('Errore: esame non aggiunto.');
            $then = 'edit'; // questionabile: forse meglio 'adminIndex'
        }

        if ($this->request->is(['post', 'put'])) {
            $exam = $this->Exams->patchEntity($exam, $this->request->getData());
            if ($this->Exams->save($exam)) {
                $this->Flash->success($success_message);
                return $this->redirect(['action' => $then]);
            }
            $this->Flash->error($failure_message);
        }

        $this->set('exam', $exam);
        $this->set('groups', $this->Exams->Groups->find('list'));
    }

    public function adminDelete ($id = null) {
        $user = $this->Auth->user();
        if (!$user['admin']) {
            throw new ForbiddenException();
        }

        if (!$id) {
            throw new NotFoundException(__('Richiesta non valida: manca l\'id.'));
        }

        $exam = $this->Exams->findById($id)->firstOrFail();
        if (!$exam) {
            throw new NotFoundException(__('Errore: esame non esistente.'));
        }

        if ($this->request->is(['post', 'put'])) {
            if ($this->Exams->delete($exam)) {
                $this->Flash->success(__('Esame cancellato con successo.'));
                return $this->redirect(['action' => 'admin_index']);
            }
        }

        $this->Flash->error(__('Error: esame non cancellato.'));
        $this->redirect(['action' => 'admin_index']);
    }

}

?>
