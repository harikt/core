<?php

namespace Dms\Core\Common\Crud\Action\Crud;

use Dms\Core\Auth\IAuthSystem;
use Dms\Core\Auth\IPermission;
use Dms\Core\Auth\Permission;
use Dms\Core\Common\Crud\Definition\Form\FinalizedCrudFormDefinition;
use Dms\Core\Common\Crud\ICrudModule;
use Dms\Core\Model\Object\ArrayDataObject;
use Dms\Core\Module\Action\SelfHandlingParameterizedAction;
use Dms\Core\Module\IStagedFormDtoMapping;
use Dms\Core\Module\Mapping\ArrayDataObjectFormMapping;
use Dms\Core\Persistence\IRepository;

/**
 * The create object action
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class CreateAction extends SelfHandlingParameterizedAction
{
    /**
     * @var IRepository
     */
    private $dataSource;

    /**
     * @var FinalizedCrudFormDefinition
     */
    private $form;

    /**
     * @inheritDoc
     */
    public function __construct(
            IRepository $dataSource,
            IAuthSystem $auth,
            FinalizedCrudFormDefinition $form
    ) {
        $this->dataSource = $dataSource;
        $this->form       = $form;

        parent::__construct($auth);
    }


    /**
     * Gets the action name.
     *
     * @return string
     */
    protected function name()
    {
        return ICrudModule::CREATE_ACTION;
    }

    /**
     * Gets the required permissions.
     *
     * @return IPermission[]
     */
    protected function permissions()
    {
        return [
                Permission::named(ICrudModule::VIEW_PERMISSION),
                Permission::named(ICrudModule::CREATE_PERMISSION)
        ];
    }

    /**
     * Gets the action form mapping.
     *
     * @return IStagedFormDtoMapping
     */
    protected function formMapping()
    {
        return new ArrayDataObjectFormMapping(
                $this->form->getStagedForm()
        );
    }

    /**
     * Gets the return dto type.
     *
     * @return string|null
     */
    protected function returnType()
    {
        return $this->dataSource->getObjectType();
    }

    /**
     * Runs the action handler.
     *
     * @param object $data
     *
     * @return object|null
     */
    protected function runHandler($data)
    {
        /** @var ArrayDataObject $data */
        $input       = $data->getArray();
        $constructor = $this->form->getCreateObjectCallback();
        $newObject   = $constructor($input);

        $this->form->bindToObject($newObject, $input);
        $this->form->invokeOnSubmitCallbacks($newObject, $input);

        $this->dataSource->save($newObject);

        $this->form->invokeOnSaveCallbacks($newObject, $input);

        return $newObject;
    }
}