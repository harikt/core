<?php

namespace Iddigital\Cms\Core\Tests\Table\DataSource;

use Iddigital\Cms\Core\Form\Field\Builder\Field;
use Iddigital\Cms\Core\Model\Criteria\Condition\ConditionOperator;
use Iddigital\Cms\Core\Model\ObjectCollection;
use Iddigital\Cms\Core\Table\Builder\Column;
use Iddigital\Cms\Core\Table\DataSource\Definition\FinalizedObjectTableDefinition;
use Iddigital\Cms\Core\Table\DataSource\Definition\ObjectTableDefinition;
use Iddigital\Cms\Core\Table\DataSource\ObjectTableDataSource;
use Iddigital\Cms\Core\Table\ITableDataSource;
use Iddigital\Cms\Core\Table\ITableStructure;
use Iddigital\Cms\Core\Tests\Table\DataSource\Fixtures\TestParentPerson;
use Iddigital\Cms\Core\Tests\Table\DataSource\Fixtures\TestPerson;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class ObjectTableDataSourceWithNestedPropertiesTest extends TableDataSourceTest
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
        $map = new ObjectTableDefinition(TestParentPerson::definition());

        $map->column(Column::name('child_name')->label('Child Name')->components([
                Field::name('first_name')->label('First Name')->string(),
                Field::name('last_name')->label('Last Name')->string(),
        ]));

        $map->property('child.firstName')->toComponent('child_name.first_name');
        $map->property('child.lastName')->toComponent('child_name.last_name');

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
        return new ObjectTableDataSource($this->definition, TestParentPerson::collection([
                new TestParentPerson(new TestPerson('Joe', 'Go', 29)),
                new TestParentPerson(new TestPerson('Harold', 'Php', 38)),
                new TestParentPerson(new TestPerson('Samantha', 'Sharp', 20)),
                new TestParentPerson(new TestPerson('Joe', 'Java', 32)),
                new TestParentPerson(new TestPerson('Kelly', 'Rust', 18)),
        ]));
    }

    public function testLoad()
    {
        $this->assertLoadsSections([
                [
                        ['child_name' => ['first_name' => 'Joe', 'last_name' => 'Go']],
                        ['child_name' => ['first_name' => 'Harold', 'last_name' => 'Php']],
                        ['child_name' => ['first_name' => 'Samantha', 'last_name' => 'Sharp']],
                        ['child_name' => ['first_name' => 'Joe', 'last_name' => 'Java']],
                        ['child_name' => ['first_name' => 'Kelly', 'last_name' => 'Rust']],
                ]
        ]);
    }

    public function testWhere()
    {
        $this->assertLoadsSections([
                [
                        ['child_name' => ['first_name' => 'Harold', 'last_name' => 'Php']],
                        ['child_name' => ['first_name' => 'Samantha', 'last_name' => 'Sharp']],
                ]
        ], $this->dataSource->criteria()
                ->loadAll()
                ->where('child_name.first_name', ConditionOperator::STRING_CONTAINS_CASE_INSENSITIVE, 'a')
        );
    }
}