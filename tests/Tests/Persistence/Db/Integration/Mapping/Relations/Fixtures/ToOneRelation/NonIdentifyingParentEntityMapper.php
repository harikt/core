<?php

namespace Dms\Core\Tests\Persistence\Db\Integration\Mapping\Relations\Fixtures\ToOneRelation;

use Dms\Core\Persistence\Db\Mapping\CustomOrm;
use Dms\Core\Persistence\Db\Mapping\Definition\MapperDefinition;
use Dms\Core\Persistence\Db\Mapping\EntityMapper;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class NonIdentifyingParentEntityMapper extends EntityMapper
{
    public static function orm()
    {
        return CustomOrm::from([
                ParentEntity::class => __CLASS__,
                SubEntity::class  => SubEntityMapper::class
        ]);
    }

    /**
     * Defines the entity mapper
     *
     * @param MapperDefinition $map
     *
     * @return void
     */
    protected function define(MapperDefinition $map)
    {
        $map->type(ParentEntity::class);
        $map->toTable('parent_entities');

        $map->idToPrimaryKey('id');

        $map->relation('child')
                ->to(SubEntity::class)
                ->toOne()
                ->withParentIdAs('parent_id');
    }
}