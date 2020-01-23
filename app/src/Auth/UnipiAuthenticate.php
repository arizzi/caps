<?php


// namespace App\Controller\Component\Auth;
namespace App\Auth;

use Cake\Auth\BaseAuthenticate;
use Cake\Http\Response;
use Cake\Http\ServerRequest;

class UnipiAuthenticate extends BaseAuthenticate {

    private $auth_config  = [
    	'fields' => [
        	'username' => 'username',
	        'password' => 'password'
	    ],
	    'userModel' => 'Users',
	    'contain' => null,
	    'passwordHasher' => 'Default'
    ];

    public function __construct() {
        $this->setConfig($this->auth_config);
    }

    public function getConfig($key = NULL, $default = NULL) {
        $config = parse_ini_file (APP . DS . ".." . DS . "unipi.ini");
        return $config;
    }

    public function authenticate(ServerRequest $request, Response $response) {
        $config = $this->getConfig();
        $data = $request->getData();

        // Allow admins to browse as a student.
        if (in_array($data['username'], $config['fakes']) && $data['password'] == $data['username']) {
            return array (
                'ldap_dn' => '',
                'user' => $data['username'],
                'name' => 'Utente Dimostrativo',
                'role' => 'student',
                'number' => '000000',
                'admin' => in_array($data['username'], $config['admins'])
            );
        }

        // Terrible hack because the SSL certificate on the Unipi side is not
        // validated by a publicy available CA.
        putenv("LDAPTLS_REQCERT=never");

        // We need to connect to the LDAP Unipi server to authenticate the user.
        $ds = ldap_connect($config['ldap_server_uri']);
        ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);

        if ($ds) {
            // Try first to authenticate as a professor
            $role = 'professor';
            $base_dn = $config['admins_base_dn'];
            $bind_dn = "uid=" . $data['username'] . "," . $base_dn;

            $r = @ldap_bind($ds, $bind_dn, $data['password']);

            if (! $r) {
                $ds = ldap_connect($config['ldap_server_uri']);
                ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);

                // As a fallback, try to authenticate as a student
                $base_dn = $config['students_base_dn'];
                $bind_dn = "uid=" . $data['username'] . "," . $base_dn;
                $r = @ldap_bind($ds, $bind_dn, $data['password']);

                $role = 'student';

                if (! $r)
                    return false;
            }

            // Search for the user in the tree
            $results = ldap_search($ds, "dc=unipi,dc=it", "uid=" . $data['username']);

            if (ldap_count_entries ($ds, $results) == 0) {
                return false;
            }

            $matches = ldap_get_entries ($ds, $results);
            $m = $matches[0];

            // If we got till here then the user can be logged in. Just make sure that we build up
            // an array of its properties so we can use them after now.
            return array (
                'ldap_dn' => $m['dn'],
                'user' => $data['username'],
                'name' => $m['cn'][0],
                'number' => ($role == 'student') ? $m['unipistudentematricola'][0] : "",
                'role' => $role,
                'admin' => in_array($data['username'], $config['admins'])
            );
        }

        return false;
    }

}
