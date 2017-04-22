<?php

namespace AppBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

class MenuBuilder
{
    private $factory;
    private $appLocals;
    private $securityAuthorizationChecker;

    /**
     * @param FactoryInterface $factory
     * @param string $appLocales
     * @param AuthorizationChecker $securityAuthorizationChecker
     */
    public function __construct(FactoryInterface $factory, string $appLocales, AuthorizationChecker $securityAuthorizationChecker)
    {
        $this->factory = $factory;
        $this->appLocales = explode('|', $appLocales);
        $this->securityAuthorizationChecker = $securityAuthorizationChecker;
    }

    public function createMainMenu(array $options)
    {
        $menu = $this->factory->createItem('root');
//@TODO change it
        $menu->addChild('Home', ['route' => 'app_default_index']);
        $menu->addChild('Tester', ['route' => 'app_default_tester']);

        return $menu;
    }

    public function createAuthenticationMenu(array $options)
    {
        $menu = $this->factory->createItem('root');

        if ($this->securityAuthorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $menu->addChild('logout', ['route' => 'fos_user_security_logout', 'label' => 'layout.logout']);
        } else {
            $menu->addChild('login', ['route' => 'fos_user_security_login', 'label' => 'layout.login']);
            $menu->addChild('register', ['route' => 'fos_user_registration_register', 'label' => 'layout.register']);
        }

        return $menu;
    }

    public function createLocalesMenu(array $options)
    {
        $menu = $this->factory->createItem('root');

        foreach ($this->appLocales as $locale) {
            $menu->addChild($locale, ['route' => 'app_default_switchlanguage', 'routeParameters' => ['language' => $locale]]);
        }

        return $menu;
    }
}
