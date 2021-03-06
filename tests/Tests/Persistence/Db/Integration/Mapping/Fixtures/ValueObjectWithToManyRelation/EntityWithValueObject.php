<?php

namespace Dms\Core\Tests\Persistence\Db\Integration\Mapping\Fixtures\ValueObjectWithToManyRelation;

use Dms\Core\Model\Object\ClassDefinition;
use Dms\Core\Model\Object\Entity;


/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class EntityWithValueObject extends Entity
{
    /**
     * @var EmbeddedObject
     */
    public $embedded;

    /**
     * EntityWithValueObject constructor.
     *
     * @param int|null       $id
     * @param EmbeddedObject $embedded
     */
    public function __construct($id, EmbeddedObject $embedded)
    {
        parent::__construct($id);
        $this->embedded = $embedded;
    }

    /**
     * Defines the structure of this class.
     *
     * @param ClassDefinition $class
     */
    protected function defineEntity(ClassDefinition $class)
    {
        $class->property($this->embedded)->asObject(EmbeddedObject::class);
    }
}