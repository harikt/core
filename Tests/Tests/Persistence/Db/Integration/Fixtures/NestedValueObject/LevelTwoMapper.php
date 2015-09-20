<?php

namespace Iddigital\Cms\Core\Tests\Persistence\Db\Integration\Fixtures\NestedValueObject;

use Iddigital\Cms\Core\Persistence\Db\Mapper\SimpleValueObjectMapper;
use Iddigital\Cms\Core\Persistence\Db\Mapping\Definition\MapperDefinition;
use Iddigital\Cms\Core\Persistence\Db\Mapping\ValueObjectMapper;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class LevelTwoMapper extends SimpleValueObjectMapper
{

    /**
     * Defines the value object mapper
     *
     * @param MapperDefinition $map
     *
     * @return void
     */
    protected function define(MapperDefinition $map)
    {
        $map->type(LevelTwo::class);

        $map->embedded('three')->withColumnsPrefixedBy('three_')->using(new LevelThreeMapper());
    }
}