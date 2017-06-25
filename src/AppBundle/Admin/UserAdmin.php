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

class UserAdmin extends AbstractAdmin
{
    private $fosUserManager;

    protected $datagridValues = array(
        '_page' => 1,
        '_sort_order' => 'DESC',
        '_sort_by' => 'id'
    );

    public function __construct($code, $class, $baseControllerName, UserManager $fosUserManager)
    {
        parent::__construct($code, $class, $baseControllerName);
        $this->fosUserManager = $fosUserManager;
    }

    public function preUpdate($user)
    {
        $this->fosUserManager->updatePassword($user);
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
        ;
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
                    'edit' => [],
                    'delete' => [],
                    'impersonate' => [
                        'template' => 'SonataAdminBundle:UserList:action_impersonate.html.twig'
                    ]
                ]
            ])
        ;
    }
}
