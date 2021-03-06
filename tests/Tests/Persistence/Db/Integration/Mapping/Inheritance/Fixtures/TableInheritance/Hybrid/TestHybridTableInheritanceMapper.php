<?php

namespace Dms\Core\Tests\Persistence\Db\Integration\Mapping\Inheritance\Fixtures\TableInheritance\Hybrid;

use Dms\Core\Persistence\Db\Mapping\Definition\MapperDefinition;
use Dms\Core\Persistence\Db\Mapping\EntityMapper;
use Dms\Core\Tests\Persistence\Db\Integration\Mapping\Inheritance\Fixtures\TableInheritance\TestSubclassEntity1;
use Dms\Core\Tests\Persistence\Db\Integration\Mapping\Inheritance\Fixtures\TableInheritance\TestSubclassEntity2;
use Dms\Core\Tests\Persistence\Db\Integration\Mapping\Inheritance\Fixtures\TableInheritance\TestSubclassEntity3;
use Dms\Core\Tests\Persistence\Db\Integration\Mapping\Inheritance\Fixtures\TableInheritance\TestSuperclassEntity;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class TestHybridTableInheritanceMapper extends EntityMapper
{

    /**
     * Defines the entity mapper
     *
     * @param MapperDefinition $map
     *
     * @return void
     */
    protected function define(MapperDefinition $map)
    {
        $map->type(TestSuperclassEntity::class);
        $map->toTable('parent_entities');

        $map->idToPrimaryKey('id');

        $map->column('class_type')->nullable()->asEnum(['subclass1', 'subclass3']);
        $map->property('baseProp')->to('base_prop')->asVarchar(255);

        // Single-table
        $map->subclass()->withTypeInColumn('class_type', 'subclass1')->define(function (MapperDefinition $map) {
            $map->type(TestSubclassEntity1::class);

            $map->property('subClass1Prop')->to('subclass1_prop')->asInt();

            $map->subclass()->withTypeInColumn('class_type', 'subclass3')->define(function (MapperDefinition $map) {
                $map->type(TestSubclassEntity3::class);

                $map->property('subClass3Prop')->to('subclass3_prop')->asVarchar(255);
            });
        });

        // Class table
        $map->subclass()->asSeparateTable('subclass2_table')->define(function (MapperDefinition $map) {
            $map->type(TestSubclassEntity2::class);

            $map->primaryKey('id');
            $map->property('subClass2Prop')->to('subclass2_prop')->asInt();
            $map->property('subClass2Prop2')->to('subclass2_prop2')->asBool();
        });
    }
}