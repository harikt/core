<?php

namespace Dms\Core\Tests\Persistence\Db\Integration\Mapping\Fixtures\PropertyTypes;

use Dms\Core\Model\Object\ClassDefinition;
use Dms\Core\Model\Object\Entity;
use Dms\Core\Model\Type\Builder\Type;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class PropertyTypesEntity extends Entity
{
    /**
     * @var string
     */
    public $value;

    /**
     * PropertyTypesEntity constructor.
     *
     * @param int|null $id
     * @param string   $value
     */
    public function __construct($id, $value)
    {
        parent::__construct($id);
        $this->value = $value;
    }

    /**
     * Defines the structure of this entity.
     *
     * @param ClassDefinition $class
     */
    protected function defineEntity(ClassDefinition $class)
    {
        $class->property($this->value)->asString();
    }

    /**
     * @return string
     */
    public function getValueUpperCase()
    {
        return strtoupper($this->value);
    }
}