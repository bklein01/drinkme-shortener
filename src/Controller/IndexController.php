<?php

namespace App\Controller;

use App\Document\Link;
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

        $data['error'] = '';
        $data['url'] = '';
        $data['shortCode'] = '';

        if ($request->get('submitted') == 1) {
            $link = new Link();
            if ($request->get('url') == '') {
                $data['error'] = 'Please specify URL to shorten.';
            }
            if (!preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i",$request->get('url'))) {
                $data['error'] = "The URL you entered was invalid";
            }
            if ($data['error'] != '') {
                $data['url'] = $request->get('url');
                return $this->render('index.html.twig', $data);
            }
            $currentTime = new \Datetime();
            $shortCode = 'bk'.$currentTime->format('His').rand(0,9);
            $link->setUrl($request->get('url'));
            $link->setShortCode($shortCode);
            $link->setRedirects(0);
            $link->setCreatedAt(date('m-d-Y'));
            $response = $link->create();
            return new RedirectResponse('/view/' . $response['shortCode']);
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

        $params = [
            'shortCode' => $shortCode
        ];
        $link = Link::getOneBy('link', $params);
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
        $link = Link::getOneBy('link', $params);
        if ($link == '') {
            return new RedirectResponse('/error/404');
        }
        $redirects = (int)$link->getRedirects();
        $link->setRedirects($redirects + 1);
        $link->update();
        return new RedirectResponse($link->getUrl());
    }

	/**
     * @Route("/error/404", name="index_invalid", methods={"GET"})
     */
    public function invalidUrl()
    {
        return $this->render('404.html.twig', []);
    }
    
}
