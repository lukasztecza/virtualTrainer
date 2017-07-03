<?php
namespace AppBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\CoreBundle\Form\Type\EqualType;
use FOS\UserBundle\Util\LegacyFormHelper;
use FOS\UserBundle\Model\UserManager;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use AppBundle\Exception\UserNotAllowedModificationException;

class UserAdmin extends AbstractAdmin
{
    private $fosUserManager;
    private $roleHierarchy;

    protected $datagridValues = array(
        '_page' => 1,
        '_sort_order' => 'DESC',
        '_sort_by' => 'id'
    );

    public function __construct(
        $code,
        $class,
        $baseControllerName,
        UserManager $fosUserManager,
        RoleHierarchy $roleHierarchy
    ) {
        parent::__construct($code, $class, $baseControllerName);
        $this->fosUserManager = $fosUserManager;
        $this->roleHierarchy = $roleHierarchy;
    }

    public function preUpdate($user)
    {
        if (!$this->isUserModificationAllowed()) {
            throw new UserNotAllowedModificationException();
        }

        $this->fosUserManager->updatePassword($user);
    }

    public function preRemove($user)
    {
        if (!$this->isUserModificationAllowed()) {
            throw new UserNotAllowedModificationException();
        }
    }

    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id')
            ->add('email')
            ->add('enabled')
            ->add('lastLogin')
            ->add('roles', null, [
                'template' => 'SonataAdminBundle:UserShow:roles.html.twig'
            ])
        ;
    }

    protected function configureFormFields(FormMapper $formMapper)
    {
        if ($this->isUserModificationAllowed()) {
            $roles = (function() {return $this->hierarchy;})->bindTo($this->roleHierarchy, $this->roleHierarchy)();
            if ($this->isGranted('ROLE_SUPER_ADMIN')) {
                $filteredRoles = $this->getSuperAdminDropdownRoles($roles);
            } else {
                $filteredRoles = $this->getAdminDropdownRoles($roles);
            }
            $formMapper
                ->add('email')
                ->add('enabled')
                ->add('plainPassword', LegacyFormHelper::getType('Symfony\Component\Form\Extension\Core\Type\RepeatedType'), [
                    'type' => LegacyFormHelper::getType('Symfony\Component\Form\Extension\Core\Type\PasswordType'),
                    'options' => ['translation_domain' => 'FOSUserBundle'],
                    'first_options' => ['label' => 'form.password'],
                    'second_options' => ['label' => 'form.password_confirmation'],
                    'invalid_message' => 'fos_user.password.mismatch',
                    'required' => false
                ])
                ->add('roles', 'choice', [
                    'choices'  => $filteredRoles,
                    'multiple' => true
                ])
            ;
        } else {
            throw new UserNotAllowedModificationException();
        }
    }

    private function isUserModificationAllowed() {
        $isEditingAdmin = $this->getSubject()->hasRole('ROLE_ADMIN') || $this->getSubject()->hasRole('ROLE_SUPER_ADMIN');
        $isSuperAdmin = $this->isGranted('ROLE_SUPER_ADMIN');
        return $isSuperAdmin || !$isEditingAdmin ? true : false;
    }

    private function getSuperAdminDropdownRoles($roles) {
        $filteredRoles = array();
        foreach($roles as $role => $subRoles) {
            if(strpos($role, 'SONATA') === false && !isset($filteredRoles[$role])) {
                $filteredRoles[$role] = $role;
            }
        }
        return $filteredRoles;
    }

    private function getAdminDropdownRoles($roles) {
        $filteredRoles = array();
        foreach($roles as $role => $subRoles) {
            if(strpos($role, 'SONATA') === false && strpos($role, 'ADMIN') === false && !isset($filteredRoles[$role])) {
                $filteredRoles[$role] = $role;
            }
        }
        return $filteredRoles;
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('id')
            ->add('email')
            ->add('roles', CallbackFilter::class, [
                'callback' => array($this, 'filterRoles'),
                'field_type' => 'text'
            ])
        ;
    }

    public function filterRoles($queryBuilder, $alias, $field, $value)
    {
        if (!$value['value']) {
            return;
        } elseif (false !== strpos('user', strtolower($value['value']))) {
            $queryBuilder->andWhere(
                //only ROLE_USER which is not mentioned in fos_user table
                $queryBuilder->expr()->eq($alias .'.' . $field, $queryBuilder->expr()->literal('a:0:{}'))
            );
        } else {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->like($alias .'.' . $field, $queryBuilder->expr()->literal('%' . $value['value'] . '%'))
            );
        }
        return true;
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('id')
            ->add('email')
            ->add('enabled')
            ->add('roles', null, [
                'template' => 'SonataAdminBundle:UserList:roles.html.twig'
            ])
            ->add('_action', null, [
                'actions' => [
                    'show' => [],
                    'edit' => [
                        'template' => 'SonataAdminBundle:UserList:action_edit.html.twig'
                    ],
                    'delete' => [
                        'template' => 'SonataAdminBundle:UserList:action_delete.html.twig'
                    ],
                    'impersonate' => [
                        'template' => 'SonataAdminBundle:UserList:action_impersonate.html.twig'
                    ]
                ]
            ])
        ;
    }
}
