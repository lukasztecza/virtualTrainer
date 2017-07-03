<?php
namespace AppBundle\EventListener;

use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use AppBundle\Exception\SwitchUserNotAllowedException;

class SwitchUserListener
{
    private $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    public function onSwitchUser(SwitchUserEvent $event)
    {
        $targetUserRoles = $event->getTargetUser()->getRoles();
        if (
            !$this->authorizationChecker->isGranted('ROLE_PREVIOUS_ADMIN') &&
            !$this->authorizationChecker->isGranted('ROLE_SUPER_ADMIN') &&
            (count($targetUserRoles) !== 1 || $targetUserRoles[0] !== 'ROLE_USER')
        ) {
            throw new SwitchUserNotAllowedException();
        }
    }
}
