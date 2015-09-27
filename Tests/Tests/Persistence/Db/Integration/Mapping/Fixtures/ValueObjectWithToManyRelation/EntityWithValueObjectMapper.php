<?php

namespace Iddigital\Cms\Core\Tests\Persistence\Db\Integration\Mapping\Fixtures\ValueObjectWithToManyRelation;

use Iddigital\Cms\Core\Persistence\Db\Mapping\CustomOrm;
use Iddigital\Cms\Core\Persistence\Db\Mapping\Definition\MapperDefinition;
use Iddigital\Cms\Core\Persistence\Db\Mapping\EntityMapper;
use Iddigital\Cms\Core\Tests\Persistence\Db\Integration\Mapping\Fixtures\ToManyIdRelation\ChildEntity;


/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class EntityWithValueObjectMapper extends EntityMapper
{
    public static function orm()
    {
        return CustomOrm::from([
                EntityWithValueObject::class => __CLASS__,
                ChildEntity::class           => ChildEntityMapper::class,
        ], [
                EmbeddedObject::class => EmbeddedObjectMapper::class
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
        $map->type(EntityWithValueObject::class);
        $map->toTable('entities');

        $map->idToPrimaryKey('id');

        $map->embedded('embedded')->to(EmbeddedObject::class);
    }
}