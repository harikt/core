<?php

namespace Dms\Core\Tests\Table\DataSource;

use Dms\Core\Form\Field\Builder\Field;
use Dms\Core\Model\ObjectCollection;
use Dms\Core\Table\Builder\Column;
use Dms\Core\Table\DataSource\Definition\FinalizedObjectTableDefinition;
use Dms\Core\Table\DataSource\Definition\ObjectTableDefinition;
use Dms\Core\Table\DataSource\ObjectTableDataSource;
use Dms\Core\Table\ITableDataSource;
use Dms\Core\Table\ITableStructure;
use Dms\Core\Tests\Table\DataSource\Fixtures\TestPerson;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class ObjectTableDataSourceTest extends PeopleTableDataSourceTest
{
    /**
     * @var FinalizedObjectTableDefinition
     */
    protected $definition;

    /**
     * @return ITableStructure
     */
    protected function buildStructure()
    {
        $map = new ObjectTableDefinition(TestPerson::definition());

        $map->column(Column::name('name')->label('Name')->components([
                Field::name('first_name')->label('First Name')->string(),
                Field::name('last_name')->label('Last Name')->string(),
        ]));

        $map->property('firstName')->toComponent('name.first_name');
        $map->property('lastName')->toComponent('name.last_name');
        $map->property('age')->to(Field::name('age')->label('Age')->int());

        $this->definition = $map->finalize();

        return $this->definition->getStructure();
    }

    /**
     * @param ITableStructure $structure
     *
     * @return ITableDataSource
     */
    protected function buildDataSource(ITableStructure $structure)
    {
        return new ObjectTableDataSource($this->definition, new ObjectCollection(TestPerson::class, [
                new TestPerson('Joe', 'Go', 29),
                new TestPerson('Harold', 'Php', 38),
                new TestPerson('Samantha', 'Sharp', 20),
                new TestPerson('Joe', 'Java', 32),
                new TestPerson('Kelly', 'Rust', 18),
        ]));
    }
}