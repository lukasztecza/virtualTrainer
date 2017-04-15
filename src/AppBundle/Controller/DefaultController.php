<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Cookie;

class DefaultController extends Controller
{
    /**
     * This route is hit if no locale is set in url
     */
    public function pickLanguageAction(Request $request)
    {
        $cookies = $request->cookies;
        $language = $cookies->get('language') ?? $request->getLocale();
        return $this->redirectToRoute('app_default_index', ['_locale' => $language]);
    }

    /**
     * @Route("/switch-language/{language}", requirements={"language": "[a-z]{2}"})
     */
    public function switchLanguageAction(Request $request, string $language)
    {
        $refererRoute = $this->container->get('referer_manager')->getRefererRoute();
        $allowedLocales = explode('|', $this->container->getParameter('app.locales'));
        if (!in_array($language, $allowedLocales)) {
            $language = $request->getLocale();
        }

        $router = $this->container->get('router');
        if (empty($refererRoute['_route'])) {
            $response = new RedirectResponse($router->generate('app_default_index', ['_locale' => $language]));
        } else {
            $response =  new RedirectResponse($router->generate($refererRoute['_route'], ['_locale' => $language]));
        }

        $cookie = new Cookie('language', $language, time() + 604800); // One week
        $response->headers->setCookie($cookie);
        return $response;
    }

    /**
     * @Route("/")
     */
    public function indexAction(Request $request)
    {
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        ]);
    }

    /**
     * @Route("/tester")
     */
    public function testerAction(Request $request)
    {
        return $this->render('default/tester.html.twig', ['count' => 11] );
    }

}
