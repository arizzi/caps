<?php

namespace App\Controller\Api\v1;

use App\Controller\AppController;
use \Cake\View\JsonView;

enum ResponseCode : int {
    case Ok = 200;
    case Forbidden = 403;
    case NotFound = 404;
    case MethodNotAllowed = 405;
    case Error = 500;
}

class RestController extends AppController {

    // A list of columns on which filtered queries are permitted. Override 
    // this in classes that inherit from RestController and then use 
    // RestController::applyFilters($query) to apply the filters automatically
    public $allowedFilters = [];

    protected function applyFilters($query) {
        foreach ($this->allowedFilters as $field) {
            $value = $this->request->getQuery($field);
            if ($value !== null) {
                $value = json_decode($value);
                $query = $query->where([ $field => $value ]);
            }
        }

        return $query;
    }

    protected function JSONResponse(ResponseCode $code, mixed $data = null, string $message = null) : void {
        $this->viewBuilder()->setOption('serialize', 'response');
        $this->viewBuilder()->setClassName('\Cake\View\JsonView');

        if ($message == null) {
            switch ($code) {
                case ResponseCode::Ok:
                    $message = "OK";
                    break;
                case ResponseCode::Forbidden:
                    $message = "Forbidden";
                    break;
                case ResponseCode::NotFound:
                    $message = "Not Found";
                    break;
                case ResponseCode::MethodNotAllowed:
                    $message = "Method not allowed";
                    break;
                case ResponseCode::Error:
                    $message = "Internal server error";
                    break;
            }
        }

        $this->set('response', [
            'data' => $data, 
            'code' => $code->value, 
            'message' => $message
        ]);

        $this->response = $this->response->withStatus($code->value);
    }

    function status() {
        /* We only expose the settings that are safe to view from the client-side */
        $settings = $this->getSettings();
        $safe_settings = [ 
            'user-instructions' => $settings['user-instructions'], 
            'cds' => $settings['cds'], 
            'disclaimer' => $settings['disclaimer'],
            'department' => $settings['department'], 
            'approval-signature-text' => $settings['approval-signature-text']
        ];

        $this->JSONResponse(ResponseCode::Ok, [
            'settings' => $safe_settings, 
            'user' => $this->user,
            'form_templates_enabled' => $this->form_templates_enabled
        ]);
    }

    protected function paginateQuery($query) {
        $limit = $this->request->getQuery('limit');
        $offset = $this->request->getQuery('offset');

        if ($limit !== null) {
            $query = $query->limit($limit);
        }

        if ($offset != null) {
            $query = $query->offset($offset);
        }

        return $query;
    }

}

?>
