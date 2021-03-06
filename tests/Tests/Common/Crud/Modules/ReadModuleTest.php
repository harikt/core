<?php

namespace Dms\Core\Tests\Common\Crud\Modules;

use Dms\Core\Auth\IPermission;
use Dms\Core\Auth\Permission;
use Dms\Core\Common\Crud\Action\Crud\ViewDetailsAction;
use Dms\Core\Common\Crud\Definition\Form\CrudFormDefinition;
use Dms\Core\Common\Crud\Definition\ReadModuleDefinition;
use Dms\Core\Common\Crud\Definition\Table\SummaryTableDefinition;
use Dms\Core\Common\Crud\IReadModule;
use Dms\Core\Model\IEntitySet;
use Dms\Core\Model\IIdentifiableObjectSet;
use Dms\Core\Module\ActionNotFoundException;
use Dms\Core\Table\DataSource\ObjectTableDataSource;
use Dms\Core\Tests\Module\Mock\MockAuthSystem;
use Dms\Core\Tests\Module\ModuleTestBase;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
abstract class ReadModuleTest extends ModuleTestBase
{
    /**
     * @var IIdentifiableObjectSet
     */
    protected $dataSource;

    /**
     * @var IReadModule
     */
    protected $module;

    /**
     * @return IEntitySet
     */
    abstract protected function buildDataSource();

    /**
     * @inheritDoc
     */
    final protected function buildModule(MockAuthSystem $authSystem)
    {
        $this->dataSource = $this->buildDataSource();

        return $this->buildReadModule($this->dataSource, $authSystem);
    }

    /**
     * @param IIdentifiableObjectSet $dataSource
     * @param MockAuthSystem         $authSystem
     *
     * @return IReadModule
     */
    abstract protected function buildReadModule(IIdentifiableObjectSet $dataSource, MockAuthSystem $authSystem) : IReadModule;

    /**
     * @return IPermission[]
     */
    final protected function expectedPermissions()
    {
        return array_merge($this->expectedReadModulePermissions(), [Permission::named(IReadModule::VIEW_PERMISSION)]);
    }

    /**
     * @return IPermission[]
     */
    final protected function expectedRequiredPermissions()
    {
        return array_merge($this->expectedReadModuleRequiredPermissions(), [Permission::named(IReadModule::VIEW_PERMISSION)]);
    }

    /**
     * @return IPermission[]
     */
    abstract protected function expectedReadModulePermissions();

    /**
     * @return IPermission[]
     */
    abstract protected function expectedReadModuleRequiredPermissions();

    public function testSummaryTableHasCorrectDataSource()
    {
        /** @var ObjectTableDataSource $tableDataSource */
        $tableDataSource = $this->module->getSummaryTable()->getDataSource();

        $this->assertInstanceOf(ObjectTableDataSource::class, $tableDataSource);
        $this->assertSame($this->dataSource, $tableDataSource->getObjectDataSource());
    }

    public function testObjectActions()
    {
        if ($this->module->allowsDetails()) {
            $this->assertTrue($this->module->hasObjectAction(IReadModule::DETAILS_ACTION));
            $this->assertInstanceOf(ViewDetailsAction::class, $this->module->getObjectAction(IReadModule::DETAILS_ACTION));
        }

        $this->assertFalse($this->module->hasObjectAction('non-existent'));
        $this->assertThrows(function () {
            $this->module->getObjectAction('non-existent');
        }, ActionNotFoundException::class);
    }

    protected function expectedDefineEvents()
    {
        return [
            ['some-package.' . $this->module->getName() . '.define', ReadModuleDefinition::class],
            // Details action
            ['some-package.' . $this->module->getName() . '.define-form', CrudFormDefinition::class],
            ['some-package.' . $this->module->getName() . '.defined-form', CrudFormDefinition::class],
            // Summary table
            ['some-package.' . $this->module->getName() . '.define-summary-table', SummaryTableDefinition::class],
            ['some-package.' . $this->module->getName() . '.defined-summary-table', SummaryTableDefinition::class],
            //
            ['some-package.' . $this->module->getName() . '.defined', ReadModuleDefinition::class],
        ];
    }
}