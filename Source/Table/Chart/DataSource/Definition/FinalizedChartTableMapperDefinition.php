<?php

namespace Iddigital\Cms\Core\Table\Chart\DataSource\Definition;

use Iddigital\Cms\Core\Table\Chart\IChartStructure;
use Iddigital\Cms\Core\Table\ITableDataSource;

/**
 * The finalized chart table mapping definition.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class FinalizedChartTableMapperDefinition
{
    /**
     * @var ITableDataSource
     */
    protected $tableDataSource;

    /**
     * @var string[]
     */
    protected $tableColumnsToLoad;

    /**
     * @var IChartStructure
     */
    protected $structure;

    /**
     * @var string[]
     */
    protected $tableToChartComponentIdMap;

    /**
     * @var callable[]
     */
    protected $componentIdCallableMap;

    /**
     * FinalizedChartTableMapperDefinition constructor.
     *
     * @param ITableDataSource $tableDataSource
     * @param string[]         $tableColumnsToLoad
     * @param IChartStructure  $structure
     * @param string[]         $tableToChartComponentIdMap
     * @param callable[]       $componentIdCallableMap
     */
    public function __construct(
            ITableDataSource $tableDataSource,
            array $tableColumnsToLoad,
            IChartStructure $structure,
            array $tableToChartComponentIdMap,
            array $componentIdCallableMap
    ) {
        $this->tableDataSource            = $tableDataSource;
        $this->tableColumnsToLoad         = $tableColumnsToLoad;
        $this->structure                  = $structure;
        $this->tableToChartComponentIdMap = $tableToChartComponentIdMap;
        $this->componentIdCallableMap     = $componentIdCallableMap;
    }

    /**
     * @return ITableDataSource
     */
    public function getTableDataSource()
    {
        return $this->tableDataSource;
    }

    /**
     * @return string[]
     */
    public function getTableColumnNamesToLoad()
    {
        return $this->tableColumnsToLoad;
    }

    /**
     * @return IChartStructure
     */
    public function getStructure()
    {
        return $this->structure;
    }

    /**
     * @return string[]
     */
    public function getTableToChartComponentIdMap()
    {
        return $this->tableToChartComponentIdMap;
    }

    /**
     * @return callable[]
     */
    public function getComponentIdCallableMap()
    {
        return $this->componentIdCallableMap;
    }
}