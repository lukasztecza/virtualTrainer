<?php
namespace AppBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\CoreBundle\Form\Type\EqualType;

class UserAdmin extends AbstractAdmin
{

    protected $datagridValues = array(
        '_page' => 1,
        '_sort_order' => 'DESC',
        '_sort_by' => 'id'
    );

    public function configureDefaultFilterValues(array &$filterValues)
    {
//        $filterValues['id'] = array(
  //          'type'  => EqualType::TYPE_IS_EQUAL,
    //        'value' => 1,
      //  );
    }

    public function getFilterParameters()
    {
//        $this->datagridValues = array_merge(array(
 //           'id' => array (
   //             'type'  => 1,
     //           'value' => 2
       //     )
        //), $this->datagridValues);
        //@TODO create default filter by roles to filter out not ROLE_USER only users
        return parent::getFilterParameters();
    }


    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id')
            ->add('email')
            ->add('enabled')
            ->add('lastLogin')
            ->add('roles')
            //@TODO create custom query in callback https://sonata-project.org/bundles/admin/master/doc/reference/action_list.html#callback-filter
            /* default wrong query
SELECT count(DISTINCT f0_.id) AS sclr_0 FROM fos_user f0_ WHERE f0_.roles LIKE ?

Parameters: [0 => %ROLE_USER%]
            */
        ;
    }

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('email')
            ->add('enabled')
            ->add('password')
            //@TODO set custom password save function now it shows salt and hash
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('id')
            ->add('email')
            ->add('roles')
            //@TODO display it properly in show
        ;
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('id')
            ->add('email')
            ->add('enabled')
            ->add('roles')
            ->add('_action', null, array(
                'actions' => array(
                    'show' => array(),
                    'edit' => array(),
                    'delete' => array(),
                    'impersonate' => array(
                        'template' => 'SonataAdminBundle:UserList:action_impersonate.html.twig'
                    )
                )
            ))
        ;
    }
}
