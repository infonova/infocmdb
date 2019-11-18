<?php

require_once APPLICATION_PATH . '/../library/composer/autoload.php'; // loading library with composer autoloader
require_once 'V2BaseController.php';

class ApiV2_DocController extends V2BaseController
{

    public function indexAction()
    {
        $openapi = \OpenApi\scan(APPLICATION_PATH . '/modules/apiV2');
        header('Content-Type: application/json');
        echo $openapi->toJson();
        exit;
    }
}