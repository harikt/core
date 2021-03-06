<?php declare(strict_types = 1);

namespace Dms\Core\Module\Definition;

use Dms\Core\Auth\IPermission;
use Dms\Core\Exception\InvalidArgumentException;
use Dms\Core\Module\IAction;
use Dms\Core\Module\IChartDisplay;
use Dms\Core\Module\ITableDisplay;
use Dms\Core\Table\Chart\IChartDataSource;
use Dms\Core\Table\ITableDataSource;
use Dms\Core\Widget\IWidget;

/**
 * The finalized module definition.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class FinalizedModuleDefinition
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $metadata;

    /**
     * @var IPermission[]
     */
    private $requiredPermissions = [];

    /**
     * @var IPermission[]
     */
    private $permissions = [];

    /**
     * @var IAction[]
     */
    private $actions;

    /**
     * @var ITableDataSource[]
     */
    private $tables;

    /**
     * @var IChartDataSource[]
     */
    private $charts;

    /**
     * @var IWidget[]
     */
    private $widgets;

    /**
     * FinalizedModuleDefinition constructor.
     *
     * @param string          $name
     * @param array           $metadata
     * @param IPermission[]   $requiredPermissions
     * @param IAction[]       $actions
     * @param ITableDisplay[] $tables
     * @param IChartDisplay[] $charts
     * @param IWidget[]       $widgets
     */
    public function __construct(
        string $name,
        array $metadata,
        array $requiredPermissions,
        array $actions,
        array $tables,
        array $charts,
        array $widgets
    ) {
        InvalidArgumentException::verifyAllInstanceOf(__METHOD__, 'requiredPermissions', $requiredPermissions, IPermission::class);
        InvalidArgumentException::verifyAllInstanceOf(__METHOD__, 'actions', $actions, IAction::class);
        InvalidArgumentException::verifyAllInstanceOf(__METHOD__, 'tables', $tables, ITableDisplay::class);
        InvalidArgumentException::verifyAllInstanceOf(__METHOD__, 'charts', $charts, IChartDisplay::class);
        InvalidArgumentException::verifyAllInstanceOf(__METHOD__, 'widgets', $widgets, IWidget::class);

        $this->name                = $name;
        $this->metadata            = $metadata;
        $this->requiredPermissions = $requiredPermissions;
        $this->actions             = $actions;
        $this->tables              = $tables;
        $this->charts              = $charts;
        $this->widgets             = $widgets;

        foreach ($requiredPermissions as $permission) {
            $this->permissions[$permission->getName()] = $permission;
        }

        foreach ($actions as $action) {
            foreach ($action->getRequiredPermissions() as $permission) {
                $this->permissions[$permission->getName()] = $permission;
            }
        }

        foreach ($widgets as $widget) {
            foreach ($widget->getRequiredPermissions() as $permission) {
                $this->permissions[$permission->getName()] = $permission;
            }
        }
    }

    /**
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getMetadata() : array
    {
        return $this->metadata;
    }

    /**
     * @return IPermission[]
     */
    public function getRequiredPermissions() : array
    {
        return $this->requiredPermissions;
    }

    /**
     * @return IPermission[]
     */
    public function getPermissions() : array
    {
        return $this->permissions;
    }

    /**
     * @return IAction[]
     */
    public function getActions() : array
    {
        return $this->actions;
    }

    /**
     * @return ITableDisplay[]
     */
    public function getTables() : array
    {
        return $this->tables;
    }

    /**
     * @return IChartDisplay[]
     */
    public function getCharts() : array
    {
        return $this->charts;
    }

    /**
     * @return IWidget[]
     */
    public function getWidgets() : array
    {
        return $this->widgets;
    }

    /**
     * @return static
     */
    public function withoutRequiredPermissions()
    {
        $clone = clone $this;

        $clone->requiredPermissions = [];

        foreach ($clone->actions as $name => $action) {
            $clone->actions[$name] = $action->withoutRequiredPermissions();
        }

        foreach ($clone->widgets as $name => $widget) {
            $clone->widgets[$name] = $widget->withoutRequiredPermissions();
        }

        return $clone;
    }
}