<?php

namespace Dms\Core\Common\Crud\Dream\Complex;

use Dms\Core\Persistence\Db\Mapping\Definition\MapperDefinition;
use Dms\Core\Persistence\Db\Mapping\IOrm;
use Dms\Core\Persistence\Db\Mapping\ValueObjectMapper;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class MoneyMapper extends ValueObjectMapper
{
    /**
     * @var string
     */
    protected $columnName;

    /**
     * MoneyMapper constructor.
     *
     * @param IOrm   $orm
     * @param string $columnName
     */
    public function __construct(IOrm $orm, $columnName)
    {
        parent::__construct($orm);
        $this->columnName = $columnName;
    }

    /**
     * Defines the value object mapper
     *
     * @param MapperDefinition $map
     *
     * @return void
     */
    protected function define(MapperDefinition $map)
    {
        $map->type(Money::class);

        $map->property('cents')->to($this->columnName)->asInt();
    }
}