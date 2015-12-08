<?php

namespace Iddigital\Cms\Core\Tests\Common\Crud\Table;

use Iddigital\Cms\Common\Testing\CmsTestCase;
use Iddigital\Cms\Core\Common\Crud\Action\Object\IObjectAction;
use Iddigital\Cms\Core\Common\Crud\Action\Table\IReorderAction;
use Iddigital\Cms\Core\Common\Crud\Table\SummaryTable;
use Iddigital\Cms\Core\Exception\InvalidArgumentException;
use Iddigital\Cms\Core\Module\Table\TableView;
use Iddigital\Cms\Core\Table\ITableDataSource;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class SummaryTableTest extends CmsTestCase
{
    public function testNewWithNoViews()
    {
        $dataSource = $this->getMockForAbstractClass(ITableDataSource::class);

        $table = new SummaryTable('name', $dataSource, []);

        $this->assertSame('name', $table->getName());
        $this->assertSame($dataSource, $table->getDataSource());
        $this->assertSame([], $table->getViews());
        $this->assertSame([], $table->getReorderActions());

        $this->assertThrows(function () use ($table) {
            $table->getReorderAction('non-existent');
        }, InvalidArgumentException::class);
    }

    public function testNewWithReorderAction()
    {
        $dataSource = $this->getMockForAbstractClass(ITableDataSource::class);

        $table = new SummaryTable('name', $dataSource, [
                $view1 = new TableView('view-1', 'Label', true),
        ], [
            'view-1' => $reorderAction = $this->getMockForAbstractClass(IReorderAction::class),
        ]);

        $this->assertSame(['view-1' => $view1], $table->getViews());
        $this->assertSame(['view-1' => $reorderAction], $table->getReorderActions());

        $this->assertSame(['view-1' => $reorderAction], $table->getReorderActions());
        $this->assertSame(true, $table->hasReorderAction('view-1'));
        $this->assertSame(false, $table->hasReorderAction('view-2'));
        $this->assertSame($reorderAction, $table->getReorderAction('view-1'));
    }

    public function testReorderActionWithInvalidViewName()
    {
        $this->setExpectedException(InvalidArgumentException::class);

        new SummaryTable('name', $this->getMockForAbstractClass(ITableDataSource::class), [
                new TableView('view-1', 'Label', true),
        ], [
                'non-existent' => $this->getMockForAbstractClass(IReorderAction::class),
        ]);
    }

    public function testInvalidActionClass()
    {
        $this->setExpectedException(InvalidArgumentException::class);

        new SummaryTable('name', $this->getMockForAbstractClass(ITableDataSource::class), [
                new TableView('view-1', 'Label', true),
        ], [
                'view-1' => $this->getMockForAbstractClass(IObjectAction::class),
        ]);
    }
}