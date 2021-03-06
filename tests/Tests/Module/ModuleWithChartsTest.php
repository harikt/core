<?php

namespace Dms\Core\Tests\Module;

use Dms\Core\Auth\IPermission;
use Dms\Core\Exception\InvalidArgumentException;
use Dms\Core\Form\Field\Builder\Field;
use Dms\Core\Module\Chart\ChartDisplay;
use Dms\Core\Module\Chart\ChartView;
use Dms\Core\Module\Module;
use Dms\Core\Table\Chart\DataSource\ChartTableDataSourceAdapter;
use Dms\Core\Table\Chart\Structure\ChartAxis;
use Dms\Core\Table\Column\Component\ColumnComponent;
use Dms\Core\Tests\Module\Fixtures\ModuleWithCharts;
use Dms\Core\Tests\Module\Mock\MockAuthSystem;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class ModuleWithChartsTest extends ModuleTestBase
{

    /**
     * @param MockAuthSystem $authSystem
     *
     * @return Module
     */
    protected function buildModule(MockAuthSystem $authSystem)
    {
        return new ModuleWithCharts($authSystem);
    }

    /**
     * @return IPermission[]
     */
    protected function expectedPermissions()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    protected function expectedRequiredPermissions()
    {
        return [];
    }

    /**
     * @return string
     */
    protected function expectedName()
    {
        return 'test-module-with-charts';
    }

    public function testChartGetters()
    {
        $this->assertSame(true, $this->module->hasChart('line-chart'));
        $this->assertSame(true, $this->module->hasChart('pie-chart'));
        $this->assertSame(false, $this->module->hasChart('foo-chart'));

        $this->assertSame('line-chart', $this->module->getChart('line-chart')->getName());
        $this->assertSame('pie-chart', $this->module->getChart('pie-chart')->getName());

        $this->assertInstanceOf(ChartTableDataSourceAdapter::class, $this->module->getChart('line-chart')->getDataSource());
        $this->assertInstanceOf(ChartTableDataSourceAdapter::class, $this->module->getChart('pie-chart')->getDataSource());

        $this->assertSame(
            ['line-chart' => ChartDisplay::class, 'pie-chart' => ChartDisplay::class],
            array_map('get_class', $this->module->getCharts())
        );

        $this->assertThrows(function () {
            $this->module->getChart('non-existent-chart');
        }, InvalidArgumentException::class);
    }

    public function testLineChart()
    {
        /** @var ChartTableDataSourceAdapter $chartDataSource */
        $chart           = $this->module->getChart('line-chart');
        $chartDataSource = $chart->getDataSource();

        $dataTable = $this->module->getTable('data-table')->getDataSource();

        $this->assertSame($dataTable, $chartDataSource->getDefinition()->getTableDataSource());
        $this->assertSame('line-chart', $chart->getName());

        $this->assertEquals([
            'x' => new ChartAxis('x', 'X-Val', [
                $dataTable->getStructure()->getComponent('x'),
            ]),
            'y' => new ChartAxis('y', 'Y-Val', [
                $dataTable->getStructure()->getComponent('y'),
                $dataTable->getStructure()->getComponent('y2'),
            ]),
        ], $chartDataSource->getStructure()->getAxes());

        $this->assertCount(3, $chartDataSource->load()->getRows());

        $this->assertEquals(
            new ChartView('default', 'Default', true, $chartDataSource->criteria()->orderByAsc('x')),
            $chart->getDefaultView()
        );

        $this->assertEquals(
            new ChartView('reversed', 'Reversed', false, $chartDataSource->criteria()->orderByDesc('x')),
            $chart->getView('reversed')
        );

        $this->assertSame(['default', 'reversed'], array_keys($chart->getViews()));
    }

    public function testPieChart()
    {
        /** @var ChartTableDataSourceAdapter $chartDataSource */
        $chart           = $this->module->getChart('pie-chart');
        $chartDataSource = $chart->getDataSource();

        $dataTable = $this->module->getTable('data-table')->getDataSource();

        $this->assertSame($dataTable, $chartDataSource->getDefinition()->getTableDataSource());
        $this->assertSame('pie-chart', $chart->getName());

        $this->assertEquals([
            'is_even' => new ChartAxis('is_even', 'Is Even', [
                ColumnComponent::forField(Field::name('is_even')->label('Is Even')->bool()->build()),
            ]),
            'y'       => new ChartAxis('y', 'Y-Val', [
                $dataTable->getStructure()->getComponent('y'),
            ]),
        ], $chartDataSource->getStructure()->getAxes());

        $this->assertCount(3, $chartDataSource->load()->getRows());

        $this->assertEquals(ChartView::createDefault(), $chart->getDefaultView());
        $this->assertSame([], $chart->getViews());
    }
}