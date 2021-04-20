<?php

namespace App\Controller;

use App\Document\Link;
use App\Document\User;
use App\Service\Config;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends BaseController
{
    /**
     * @Route("/", name="index_index", methods={"GET", "POST"})
     */
    public function submitLink()
    {
        $request = Request::createFromGlobals();
        $data = [];
        session_start();
        if (isset($_SESSION['user'])) {
            $data['user'] = $_SESSION['user'];
        } else {
            return new RedirectResponse('/login');
        }

        $data['error'] = '';

        $data['url'] = '';
        $data['shortCode'] = '';

        if ($request->get('submitted') == 1) {
            $submittedBy = $_SESSION['user']->getId();
            $link = new Link();
            if ($request->get('url') == '') {
                $data['error'] = 'Please specify URL to shorten.';
            }
            $website = test_input($_POST["website"]);
            if (!preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i",$request->get('url'))) {
                $data['error'] = "The URL you entered was invalid";
            }
            if ($data['error'] != '') {
                $data['url'] = $request->get('url');
                return $this->render('index.html.twig', $data);
            }
            $shortCode = md5(strtotime());
            $link->setUrl($request->get('url'));
            $link->setShortCode($shortCode);
            $link->setUserId($submittedBy);
            $link->setRedirects(0);
            $link->setCreatedAt(date('m-d-Y'));
            $link->create();

            return new RedirectResponse('/view/' . $link->getShortCode());
        }

        return $this->render('index.html.twig', $data);
    }

    /**
     * @Route("/view/{shortCode}", name="index_view", methods={"GET"})
     */
    public function view($shortCode)
    {
        $request = Request::createFromGlobals();
        $data = [];
        session_start();
        if (isset($_SESSION['user'])) {
            $data['user'] = $_SESSION['user'];
        } else {
            $data['user'] = null;
        }

        $params = [
            'shortCode' => $shortCode
        ];
        $link = Link::getOneBy($params);
        $data['link'] = $link;
        return $this->render('view.html.twig', $data);
    }

    /**
     * @Route("/{shortCode}", name="index_redirect", methods={"GET"})
     */
    public function redirectUrl($shortCode)
    {
        $params = [
            'shortCode' => $shortCode
        ];
        $link = Link::getOneBy($params);
        if ($link == '') {
            return new RedirectResponse('/404');
        }
        $redirects = (int)$link->getRedirects();
        $link->setRedirects($redirects + 1);
        $link->update();
        return new RedirectResponse($link->getUrl());
    }

    private function login($email, $password)
    {
        $password = sha1(md5($password));
        $user = User::getOneBy(['email' => $email, 'password' => $password]);
        if ($user != '') {
            $token = uniqid();
            $user->setToken($token);
            $params = [
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'email' => $user->getEmail(),
                'password' => $user->getPassword(),
                'createdAt' => $user->getCreatedAt(),
                'token' => $token,
            ];
            $insRec = new \MongoDB\Driver\BulkWrite();
            $insRec->update(['_id' => $user->getId()], $params);
            $writeConcern = new \MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY, 1000);
            $config = new Config();
            $mongo = new \MongoDB\Driver\Manager($config->getMongodbUrl());
            $result = $mongo->executeBulkWrite('rate-my-shopper.user', $insRec, $writeConcern);
            $_SESSION['user'] = $user;

            return true;
        } else {
            return false;
        }
    }

    /**
     * @Route("/login", name="index_login")
     */
    public function showLogin()
    {
        $request = Request::createFromGlobals();
        $data = [];
        session_start();
        $data['user'] = null;
        $data['message'] = '';
        $data['services'] = Service::getBy([]);
        if ($request->get('submitted') == 1) {
            $email = strtolower($request->get('email'));
            $password = $request->get('password');
            $loggedin = $this->login($email, $password);
            if (!$loggedin) {
                $data['message'] = 'The username and password you entered do not match a current active user. Please try again.';
            } else {
                return new RedirectResponse('/');
            }
        }

        return $this->render('login.html.twig', $data);
    }

    /**
     * @Route("/logout", name="index_logout")
     */
    public function logout()
    {
        // Initialize the session.
        // If you are using session_name("something"), don't forget it now!
        session_start();

        // Unset all of the session variables.
        $_SESSION = [];

        // If it's desired to kill the session, also delete the session cookie.
        // Note: This will destroy the session, and not just the session data!
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 42000, '/');
        }

        // Finally, destroy the session.
        session_destroy();

        return new RedirectResponse('/');
    }

    private function register($email, $password, $firstname, $lastname)
    {
        if ($email == '' || $password == '') {
            return 'You must enter all fields to register';
        }
        $password = sha1(md5($password));
        $user = User::getOneBy(['email' => $email]);
        if ($user != '') {
            return 'A user with that username already exists';
        } else {
            $token = uniqid();
            $data = [
                'firstname' => $firstname,
                'lastname' => $lastname,
                'email' => $email,
                'password' => $password,
                'token' => $token,
                'createdAt' => date('Y-m-d H:i:s'),
            ];
            // $post->create();
            $insRec = new \MongoDB\Driver\BulkWrite();
            $insRec->insert($data);
            $writeConcern = new \MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY, 1000);
            $config = new Config();
            $mongo = new \MongoDB\Driver\Manager($config->getMongodbUrl());
            $result = $mongo->executeBulkWrite('rate-my-shopper.user', $insRec, $writeConcern);

            $_SESSION['user'] = User::getOneBy(['email' => $email, 'password' => $password]);

            return '';
        }
    }

    /**
     * @Route("/register", name="index_register")
     */
    public function showRegister()
    {
        $request = Request::createFromGlobals();
        $data = [];
        session_start();
        $data['user'] = null;
        $data['message'] = '';
        $data['services'] = Service::getBy([]);
        if ($request->get('submitted') == 1) {
            $email = strtolower($request->get('email'));
            $firstname = $request->get('firstname');
            $lastname = $request->get('lastname');
            $password = $request->get('password');
            $loggedin = $this->register($email, $password, $firstname, $lastname);
            if ($loggedin != '') {
                $data['message'] = $loggedin;
            } else {
                $user = $_SESSION['user'];
                $email = $user->getEmail();
                $html = '
                    <html>
                    <body>
                    Thank you for your interest, and for taking the time to sign up for your account.  <br />
                    <br />
                    Your new login information is:<br />
                    User Name: '.$user->getEmail().'<br />
                    <br />
                    Once signing in at <a href="https://www.ratemyshopper.com">https://www.ratemyshopper.com</a> you can post ratings for shoppers that you have interacted with.<br />
                    <br />
                    Please feel free to contact us in case of further questions.<br />
                    <br />
                    Best Regards,<br />
                    Your Cognicio Team<br />
                    <img style="width:150px;" src="https://www.cognicio.com/landing/cognicio_logo.png" /><br />
                    </body>
                    </html>
                ';

                $params = [
                    'from' => 'Cognicio Administrator <noreply@cognicio.com>',
                    'to' => $email,
                    'subject' => 'Sign Up',
                    'html' => $html,
                ];
                $this->sendMail($params);
                return new RedirectResponse('/');
            }
        }

        return $this->render('register.html.twig', $data);
    }

    private function sendMail($data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.mailgun.net/v3/mg.withinspecs.com/messages');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_USERPWD, 'api:key-5fd7b5559a94d0bc8a3d0928176d8891');
        $o = curl_exec($ch);
        curl_close($ch);

        return $o;
    }
}
