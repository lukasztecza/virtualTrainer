<?php

namespace AppBundle\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

class RefererManager
{
    private $requestStack;
    private $router;

    public function __construct(RequestStack $requestStack, Router $router) {
        $this->requestStack = $requestStack;
        $this->router = $router;
    }

    public function getRefererRoute() {
        $request = $this->requestStack->getCurrentRequest();
        $referer = $request->headers->get('referer');
        if (empty($referer)) {
            return null;
        }

        $baseUrl = $request->headers->get('host');
        $baseUrlPosition = strpos($referer, $baseUrl);
        if ($baseUrlPosition === false) {
            return null;
        }

        $lastPath = substr($referer, strpos($referer, $baseUrl) + strlen($baseUrl));
        $lastRoute = $this->router->getMatcher()->match($lastPath);
        return $lastRoute;
    }
}
