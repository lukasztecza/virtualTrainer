<?php
namespace AppBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use AppBundle\Exception\SwitchUserNotAllowedException;
use AppBundle\Exception\ModifyUserNotAllowedException;
use AppBundle\Exception\RemoveUserNotAllowedException;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Sonata\CoreBundle\FlashMessage\FlashManager;

class SuspiciousBehaviourSubscriber implements EventSubscriberInterface
{
    private $router;
    private $sonataFlashManager;

    public function __construct(Router $router, FlashManager $sonataFlashManager)
    {
        $this->router = $router;
        $this->sonataFlashManager = $sonataFlashManager;
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
            case ($event->getException() instanceof ModifyUserNotAllowedException):
            case ($event->getException() instanceof RemoveUserNotAllowedException):
                $this->sonataFlashManager->getSession()->getFlashBag()->add("error", $event->getException()->getMessage());
                $url = $this->router->generate('sonata_admin_dashboard');
                $response = new RedirectResponse($url);
                $event->setResponse($response);
                break;
        }
    }
}
