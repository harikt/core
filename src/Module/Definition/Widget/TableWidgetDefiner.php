<?php

namespace Dms\Core\Module\Definition\Widget;

use Dms\Core\Table\ITableDataSource;
use Dms\Core\Widget\TableWidget;

/**
 * The table widget definer class.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class TableWidgetDefiner extends WidgetDefinerBase
{
    /**
     * @var string
     */
    private $label;

    /**
     * @var ITableDataSource
     */
    private $table;

    /**
     * TableWidgetDefiner constructor.
     *
     * @param string           $name
     * @param string           $label
     * @param ITableDataSource $table
     * @param callable         $callback
     */
    public function __construct($name, $label, ITableDataSource $table, callable $callback)
    {
        parent::__construct($name, null, null, $callback);
        $this->label = $label;
        $this->table = $table;
    }

    /**
     * Defines the row criteria for the widget.
     *
     * Example:
     * <code>
     * ->matching(function (RowCriteria $criteria) {
     *      $criteria->where('column', '>', 500);
     * });
     * </code>
     *
     * @see RowCriteria
     * @param callable $criteriaDefinitionCallback
     *
     * @return void
     */
    public function matching(callable $criteriaDefinitionCallback)
    {
        $criteria = $this->table->criteria();
        $criteriaDefinitionCallback($criteria);

        call_user_func($this->callback, new TableWidget($this->name, $this->label, $this->table, $criteria));
    }

    /**
     * Defines the table to load all rows (empty criteria).
     *
     * @return void
     */
    public function allRows()
    {
        call_user_func($this->callback, new TableWidget($this->name, $this->label, $this->table));
    }
}