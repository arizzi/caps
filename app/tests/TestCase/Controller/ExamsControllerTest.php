<?php
namespace App\Test\TestCase\Controller;

use App\Controller\ExamsController;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestCase;
use Cake\View\Exception\MissingTemplateException;
use Cake\ORM\TableRegistry;

/**
 * ExamsControllerTest class
 */
class ExamsControllerTest extends IntegrationTestCase
{
    public $fixtures = ['app.Users', 'app.Exams', 'app.Groups', 'app.ExamsGroups'];

    public function setUp()
    {
      parent::setUp();
      $this->Exams = TableRegistry::getTableLocator()->get('Exams');
    }

    public function testExamsPage()
    {
        $this->get('/exams');
        $this->assertRedirect();
        $this->assertRedirectContains('?redirect=%2Fexams');

        // test that page requires authentication
        $this->get('/exams/admin-index');
        $this->assertRedirect();
        $this->assertRedirectContains('?redirect=%2Fexams%2Fadmin-index');

        // test that students are not allowed to access
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 1,
                    'user' => 'mario.rossi', // see UsersFixture.php
                    'ldap_dn' => '',
                    'name' => 'MARIO ROSSI',
                    'role' => 'student',
                    'number' => '123456',
                    'admin' => false,
                    'surname' => '',
                    'givenname' => ''
                ]
            ]
        ]);
        $this->get('/exams/admin-index');
        $this->assertResponseError(); // ma dovrebbe essere ResponseFailure?

        // test that admin can access
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 2,
                    'user' => 'alice.verdi', // see UsersFixture.php
                    'ldap_dn' => '',
                    'name' => 'ALICE VERDI',
                    'role' => 'staff',
                    'number' => '24680',
                    'admin' => true,
                    'surname' => '',
                    'givenname' => ''
                ]
            ]
        ]);
        $this->get('/exams/admin-index');
        $this->assertResponseOk();

        // load page to add new axam
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->get('/exams/admin-add');
        $this->assertResponseOk();

        // add new exam
        $exam_count = $this->Exams->find()->count();
        $this->post('/exams/admin-add',[
          'name' => 'Analisi Matematica',
          'code' => '1111',
          'sector' => 'AAA',
          'credits' => 17
        ]);
        $this->assertRedirect();
        $this->assertFlashMessage('Esame aggiunto con successo.');
        $this->assertEquals($exam_count + 1, $this->Exams->find()->count());

        // edit exam
        $exam_id = $this->Exams->find()->first()['id'];
        $this->get("/exams/admin-edit/$exam_id");
        $this->assertResponseOk();

        // $this->disableErrorHandlerMiddleware();        // test that page requires authentication
        // fwrite(STDERR,'****'.print_r($this->_flashMessages,true));
    }
}