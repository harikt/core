<?php

namespace Dms\Core\Tests\Table\DataSource;

use Dms\Common\Testing\CmsTestCase;
use Dms\Core\Exception\InvalidArgumentException;
use Dms\Core\Table\Builder\Table;
use Dms\Core\Table\Criteria\RowCriteria;
use Dms\Core\Table\IDataTable;
use Dms\Core\Table\IRowCriteria;
use Dms\Core\Table\ITableDataSource;
use Dms\Core\Table\ITableStructure;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
abstract class TableDataSourceTest extends CmsTestCase
{
    /**
     * @var ITableStructure
     */
    protected $structure;

    /**
     * @var ITableDataSource
     */
    protected $dataSource;

    /**
     * @return ITableStructure
     */
    abstract protected function buildStructure();

    /**
     * @param ITableStructure $structure
     *
     * @return ITableDataSource
     */
    abstract protected function buildDataSource(ITableStructure $structure);

    public function setUp()
    {
        $this->structure  = $this->buildStructure();
        $this->dataSource = $this->buildDataSource($this->structure);
    }

    public function testStructure()
    {
        $this->assertEquals($this->structure, $this->dataSource->getStructure());
    }

    public function testCriteria()
    {
        $criteria = $this->dataSource->criteria();

        $this->assertInstanceOf(RowCriteria::class, $criteria);
        $this->assertEquals($this->structure, $criteria->getStructure());
    }

    public function testLoadInvalidCriteria()
    {
        $this->setExpectedException(InvalidArgumentException::class);

        $invalidCriteria = new RowCriteria(Table::create([]));
        $this->dataSource->load($invalidCriteria);
    }

    public function testCountInvalidCriteria()
    {
        $this->setExpectedException(InvalidArgumentException::class);

        $invalidCriteria = new RowCriteria(Table::create([]));
        $this->dataSource->count($invalidCriteria);
    }

    /**
     * @param array[]           $expectedSections
     * @param IRowCriteria|null $criteria
     *
     * @return void
     */
    protected function assertLoadsSections(array $expectedSections, IRowCriteria $criteria = null)
    {
        $table = $this->dataSource->load($criteria);

        $this->assertSameDataTable($expectedSections, $table, $criteria);
    }

    protected function assertLoadsCount($expectedCount, IRowCriteria $criteria = null)
    {
        $this->assertSame($expectedCount, $this->dataSource->count($criteria));
    }

    protected function assertSameDataTable(array $expectedSections, IDataTable $actualTable, IRowCriteria $criteria = null)
    {
        $expectedStructure = $criteria ? $this->structure->withColumns($criteria->getColumnsToLoad()) : $this->structure;
        foreach ($actualTable->getSections() as $section) {
            $this->assertEquals($expectedStructure, $section->getStructure());
        }

        $actualSections = DataTableHelper::covertDataTableToNormalizedArray($actualTable);

        $this->assertEquals($expectedStructure, $actualTable->getStructure());
        $this->assertEquals($expectedSections, $actualSections);
    }
}