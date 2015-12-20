<?php

namespace Dms\Core\Module;

use Dms\Core\Table\Criteria\RowCriteria;
use Dms\Core\Table\IRowCriteria;

/**
 * The table view interface.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
interface ITableView
{
    /**
     * Gets the name.
     *
     * @return string
     */
    public function getName();

    /**
     * Gets the label.
     *
     * @return string
     */
    public function getLabel();

    /**
     * Gets whether this is the default table view.
     *
     * @return bool
     */
    public function isDefault();

    /**
     * Gets whether the view contains criteria.
     *
     * @return bool
     */
    public function hasCriteria();

    /**
     * Gets the row criteria.
     *
     * @return IRowCriteria|null
     */
    public function getCriteria();

    /**
     * Gets a copy of the row criteria or null if there is no criteria.
     *
     * @return RowCriteria|null
     */
    public function getCriteriaCopy();
}