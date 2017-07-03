<?php
namespace AppBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use AppBundle\Exception\SwitchUserNotAllowedException;
use AppBundle\Exception\UserNotAllowedModificationException;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SuspiciousBehaviourSubscriber implements EventSubscriberInterface
{
    private $router;

    public function __construct($router)
    {
        $this->router = $router;
    }

    public static function getSubscribedEvents()
    {
        // return the subscribed events, their methods and priorities
        return array(
            KernelEvents::EXCEPTION => array(
                array('processException', 0)
            )
        );
    }

    public function processException(GetResponseForExceptionEvent $event)
    {
        switch (true) {
            case ($event->getException() instanceof SwitchUserNotAllowedException):
            case ($event->getException() instanceof UserNotAllowedModificationException):
                $url = $this->router->generate('fos_user_security_logout');
                $response = new RedirectResponse($url);
                $event->setResponse($response);
                break;
        }
    }
}
