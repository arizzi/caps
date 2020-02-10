<?php
namespace App\Test\TestCase\Controller;

use App\Controller\ProposalsController;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestCase;
use Cake\View\Exception\MissingTemplateException;
use Cake\ORM\TableRegistry;

/**
 * UsersControllerTest class
 */
class ProposalsControllerTest extends IntegrationTestCase
{
    public $fixtures = [
			'app.Users',
			'app.Proposals',
			'app.Curricula',
			'app.CurriculaProposals',
			'app.Degrees',
			'app.Exams',
			'app.Groups'
	];

    public function setUp()
    {
      parent::setUp();
      $this->Users = TableRegistry::getTableLocator()->get('Users');
    }

    public function testProposalPage()
    {
        // test that page requires authentication
				foreach(['/proposals/add', '/exams.json'] as $url) {
	        $this->get($url);
	        $this->assertRedirect();
				}

        // Set session data
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
        $this->get('/proposals/add');
        $this->assertResponseOk();

				$this->get('/exams.json');
				$this->assertResponseOk();

				$this->get('/groups.json');
				$this->assertResponseOk();

				$this->get('/curricula.json');
				$this->assertResponseOk();
    }
}