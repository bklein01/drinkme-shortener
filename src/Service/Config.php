<?php

namespace App\Service;

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Documents\User;

class Config
{
    private $mongodbUrl;
    private $basePath;

    public function __construct()
    {
        $this->mongodbUrl = $_ENV['MONGODB_URL'];
        $this->basePath = $_ENV['BASE_PATH'];
    }

    private function getHeaders()
    {
        if (!is_array($_SERVER)) {
            return array();
        }

        $headers = array();
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }

        return $headers;
    }

    public function checkSession($token = '')
    {
        $request = Request::createFromGlobals();
        if ($token == '') {
            $token = $request->get('token');
        }
        if ($token != '') {
            $objUser = User::getOneBy(array('token' => $token));
            if (!$objUser) {
                return new JsonResponse(array('error' => 'Your session is no longer valid. Please sign in again or contact Reefered Support if you continue to get this error.'));
            } else {
                return $objUser;
            }
        } else {
            if (function_exists('apache_request_headers')) {
                $headers = apache_request_headers();
            } else {
                $headers = $this->getHeaders();
            }
            $matches = array();
            if (isset($headers['authorization']) && $headers['authorization'] != '') {
                preg_match('/Bearer (.*)/', $headers['authorization'], $matches);
            } elseif (isset($headers['Authorization']) && $headers['Authorization'] != '') {
                preg_match('/Bearer (.*)/', $headers['Authorization'], $matches);
            } else {
                return new JsonResponse(array('error' => 'Your token is missing. Please sign in again or contact Reefered Support if you continue to get this error..'));
            }
            if (isset($matches[1])) {
                $token = $matches[1];
                if ($token == '') {
                    return new JsonResponse(array('error' => 'Your token is missing. Please sign in again or contact Reefered Support if you continue to get this error..'));
                }
                $objUser = User::getOneBy(array('token' => $token));
                if (!$objUser) {
                    return new JsonResponse(array('error' => 'Your session is no longer valid. Please sign in again or contact Reefered Support if you continue to get this error.'));
                } else {
                    return $objUser;
                }
            } else {
                return new JsonResponse(array('error' => 'You did not properly authenticate. Please sign in again or contact Reefered Support if you continue to get this error.'));
            }
        }
    }

    /**
     * @return array|false|string
     */
    public function getMongodbUrl()
    {
        return $this->mongodbUrl;
    }

    /**
     * @return array|false|string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }
}
