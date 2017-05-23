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
//@TODO change it and add translations
        $menu->addChild('home', ['route' => 'app_default_index', 'label' => 'translate me home']);
        $menu->addChild('Tester', ['route' => 'app_default_tester', 'label' => 'translate me teseter']);
        $menu->addChild('profile', ['route' => 'fos_user_profile_edit', 'label' => 'translate me edit profile']);
        $menu->addChild('password', ['route' => 'fos_user_change_password', 'label' => 'translate me change pass']);

        return $menu;
    }

    public function createAuthenticationMenu(array $options)
    {
        $menu = $this->factory->createItem('root');

        if ($this->securityAuthorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $menu->addChild('logout', ['route' => 'fos_user_security_logout', 'label' => 'layout.logout']);
        } else {
            $menu->addChild('register', ['route' => 'fos_user_registration_register', 'label' => 'layout.register']);
            $menu->addChild('login', ['route' => 'fos_user_security_login', 'label' => 'layout.login']);
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

    public function createAdminMenu(array $options)
    {
        $menu = $this->factory->createItem('root');

        if ($this->securityAuthorizationChecker->isGranted('ROLE_TRANSLATOR')) {
            $menu->addChild('trans', ['route' => 'jms_translation_index', 'label' => 'translation panel']);
        }
        if ($this->securityAuthorizationChecker->isGranted('ROLE_SONATA_ADMIN')) {
            $menu->addChild('admin', ['route' => 'sonata_admin_dashboard', 'label' => 'admin panel']);
        }
        if ($this->securityAuthorizationChecker->isGranted('ROLE_PREVIOUS_ADMIN')) {
            $menu->addChild('exit', [
                'route' => 'sonata_admin_dashboard',
                'routeParameters' => ['_switch_user' => '_exit'],
                'label' => 'exit impersonation'
            ]);
        }

        return $menu;
    }
}
