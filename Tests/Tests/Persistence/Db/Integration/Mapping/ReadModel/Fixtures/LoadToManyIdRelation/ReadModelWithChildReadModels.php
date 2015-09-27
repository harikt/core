<?php

namespace Iddigital\Cms\Core\Tests\Persistence\Db\Integration\Mapping\ReadModel\Fixtures\LoadToManyIdRelation;

use Iddigital\Cms\Core\Model\Object\ClassDefinition;
use Iddigital\Cms\Core\Model\Object\ReadModel;
use Iddigital\Cms\Core\Model\Type\Builder\Type;
use Iddigital\Cms\Core\Model\TypedCollection;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class ReadModelWithChildReadModels extends ReadModel
{
    /**
     * @var TypedCollection|ChildEntityReadModel[]
     */
    public $children;

    /**
     * ReadModelWithLoadedToOneRelation constructor.
     *
     * @param ChildEntityReadModel[] $children
     */
    public function __construct(array $children = [])
    {
        parent::__construct();
        $this->children = new TypedCollection(Type::object(ChildEntityReadModel::class), $children);
    }

    /**
     * Defines the structure of this class.
     *
     * @param ClassDefinition $class
     */
    protected function define(ClassDefinition $class)
    {
        $class->property($this->children)->asCollectionOf(Type::object(ChildEntityReadModel::class));
    }
}