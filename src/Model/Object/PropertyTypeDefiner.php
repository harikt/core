<?php declare(strict_types = 1);

namespace Dms\Core\Model\Object;

use Dms\Core\Exception;
use Dms\Core\Model\ITypedCollection;
use Dms\Core\Model\Type\Builder\Type;
use Dms\Core\Model\Type\IType;

/**
 * The fluent property type definer class class.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class PropertyTypeDefiner
{
    /**
     * @var PropertyDefinition
     */
    private $definition;

    /**
     * @var bool
     */
    private $nullable = false;

    public function __construct(PropertyDefinition $definition)
    {
        $this->definition = $definition;
    }

    /**
     * @return PropertyDefinition
     */
    public function getDefinition() : PropertyDefinition
    {
        return $this->definition;
    }

    /**
     * Ignores the property in the class definition.
     *
     * @return void
     */
    public function ignore()
    {
        $this->definition->setIgnored(true);
    }

    /**
     * Sets the property as immutable.
     *
     * The value of the property will not be changeable
     * after being set.
     *
     * @return static
     */
    public function immutable()
    {
        $this->definition->setImmutable(true);

        return $this;
    }

    /**
     * Sets the property type as nullable.
     *
     * @return static
     */
    public function nullable()
    {
        $this->nullable = true;

        return $this;
    }

    /**
     * @param IType $type
     *
     * @return void
     */
    public function asType(IType $type)
    {
        $this->defineType($type);
    }

    /**
     * @return void
     */
    public function asMixed()
    {
        $this->defineType(Type::mixed());
    }

    /**
     * @return void
     */
    public function asInt()
    {
        $this->defineType(Type::int());
    }

    /**
     * @return void
     */
    public function asString()
    {
        $this->defineType(Type::string());
    }

    /**
     * @return void
     */
    public function asBool()
    {
        $this->defineType(Type::bool());
    }

    /**
     * @return void
     */
    public function asFloat()
    {
        $this->defineType(Type::float());
    }

    /**
     * @return void
     */
    public function asNumber()
    {
        $this->defineType(Type::number());
    }

    /**
     * @param string|null $class
     *
     * @return void
     */
    public function asObject(string $class = null)
    {
        $this->defineType(Type::object($class));
    }

    /**
     * @param IType $elementType
     *
     * @return void
     */
    public function asArrayOf(IType $elementType)
    {
        $this->defineType(Type::arrayOf($elementType));
    }

    /**
     * @param IType  $elementType
     * @param string $collectionClass
     *
     * @return void
     */
    public function asCollectionOf(IType $elementType, string $collectionClass = ITypedCollection::class)
    {
        $this->defineType(Type::collectionOf($elementType, $collectionClass));
    }

    /**
     * @param IType $type
     *
     * @return void
     */
    protected function defineType(IType $type)
    {
        $type = $this->nullable ? $type->nullable() : $type;
        $this->definition->setType($type);
    }
}