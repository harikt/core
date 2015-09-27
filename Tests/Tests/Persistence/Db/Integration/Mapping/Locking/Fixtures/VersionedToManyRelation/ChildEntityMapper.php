<?php

namespace Iddigital\Cms\Core\Tests\Persistence\Db\Integration\Mapping\Locking\Fixtures\VersionedToManyRelation;

use Iddigital\Cms\Core\Persistence\Db\Mapping\Definition\MapperDefinition;
use Iddigital\Cms\Core\Persistence\Db\Mapping\EntityMapper;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class ChildEntityMapper extends EntityMapper
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
        $map->type(ChildEntity::class);
        $map->toTable('children');

        $map->idToPrimaryKey('id');
        $map->column('parent_id')->nullable()->asInt();

        $map->property('version')->to('version')->asVersionInteger();
    }
}