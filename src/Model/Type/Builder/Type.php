<?php

namespace Dms\Core\Model\Type\Builder;

use Dms\Core\Exception\InvalidArgumentException;
use Dms\Core\Model\ITypedCollection;
use Dms\Core\Model\Type\ArrayType;
use Dms\Core\Model\Type\CollectionType;
use Dms\Core\Model\Type\IType;
use Dms\Core\Model\Type\MixedType;
use Dms\Core\Model\Type\NullType;
use Dms\Core\Model\Type\ObjectType;
use Dms\Core\Model\Type\ScalarType;
use Dms\Core\Model\Type\UnionType;
use Dms\Core\Util\Debug;

/**
 * The type builder class.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class Type
{
    /**
     * @var MixedType
     */
    private static $mixed;

    /**
     * @var ScalarType[]
     */
    private static $scalars = [];

    /**
     * @var NullType
     */
    private static $null;

    /**
     * @var UnionType
     */
    private static $number;

    /**
     * @var ObjectType[]
     */
    private static $objects = [];

    /**
     * @var ArrayType[]
     */
    private static $arrays = [];

    /**
     * @var CollectionType[]
     */
    private static $collections = [];

    /**
     * @return MixedType
     */
    public static function mixed()
    {
        if (!self::$mixed) {
            self::$mixed = new MixedType();
        }

        return self::$mixed;
    }

    /**
     * The 'string' primitive type.
     *
     * @return ScalarType
     */
    public static function string()
    {
        return self::scalar(IType::STRING);
    }

    /**
     * The 'integer' primitive type.
     *
     * @return ScalarType
     */
    public static function int()
    {
        return self::scalar(IType::INT);
    }

    /**
     * The 'boolean' primitive type.
     *
     * @return ScalarType
     */
    public static function bool()
    {
        return self::scalar(IType::BOOL);
    }

    /**
     * The 'float' primitive type.
     *
     * @return ScalarType
     */
    public static function float()
    {
        return self::scalar(IType::FLOAT);
    }

    /**
     * The a union of the 'integer' and 'float' primitive types.
     *
     * @return UnionType
     */
    public static function number()
    {
        if (!self::$number) {
            self::$number = UnionType::create([self::scalar(IType::INT), self::scalar(IType::FLOAT)]);
        }

        return self::$number;
    }

    /**
     * The 'null' type.
     *
     * @return ScalarType
     */
    public static function null()
    {
        if (!self::$null) {
            self::$null = new NullType();
        }

        return self::$null;
    }

    /**
     * @param string $scalarType
     *
     * @return ScalarType
     */
    public static function scalar($scalarType)
    {
        if (!isset(self::$scalars[$scalarType])) {
            self::$scalars[$scalarType] = new ScalarType($scalarType);
        }

        return self::$scalars[$scalarType];
    }

    /**
     * An instance of any object if $class = null.
     * Or an instance of the supplied class.
     *
     * @param string|null $class
     *
     * @return ObjectType
     */
    public static function object($class = null)
    {
        if (!isset(self::$objects[$class])) {
            self::$objects[$class] = new ObjectType($class);
        }

        return self::$objects[$class];
    }

    /**
     * The 'array' native type. Contains only elements of the supplied type.
     *
     * @param IType $elementType
     *
     * @return ArrayType
     */
    public static function arrayOf(IType $elementType)
    {
        $elementTypeString = $elementType->asTypeString();

        if (!isset(self::$arrays[$elementTypeString])) {
            self::$arrays[$elementTypeString] = new ArrayType($elementType);
        }

        return self::$arrays[$elementTypeString];
    }

    /**
     * The typed collection type. Contains only elements of the
     * supplied class.
     *
     * @see ITypedCollection
     *
     * @param IType  $elementType
     * @param string $collectionClass
     *
     * @return CollectionType
     */
    public static function collectionOf(IType $elementType, $collectionClass = ITypedCollection::class)
    {
        $elementTypeString = $elementType->asTypeString();

        if (!isset(self::$collections[$collectionClass][$elementTypeString])) {
            self::$collections[$collectionClass][$elementTypeString] = new CollectionType($elementType, $collectionClass);
        }

        return self::$collections[$collectionClass][$elementTypeString];
    }

    /**
     * Loads the appropriate type from a value.
     *
     * @param mixed $default
     *
     * @return IType
     * @throws InvalidArgumentException
     */
    public static function from($default)
    {
        switch (gettype($default)) {
            case 'NULL':
                return self::null();
            case 'string':
                return self::string();
            case 'integer':
                return self::int();
            case 'double':
                return self::float();
            case 'boolean':
                return self::bool();
            case 'array':
                if (count($default) < 20) {
                    $types = [];
                    foreach ($default as $value) {
                        $types[] = self::from($value);
                    }
                } else {
                    $types = null;
                }

                return self::arrayOf($types ? UnionType::create($types) : self::mixed());
            case 'object':
                if ($default instanceof ITypedCollection) {
                    return self::collectionOf($default->getElementType(), get_class($default));
                } else {
                    return self::object(get_class($default));
                }
        }

        throw InvalidArgumentException::format(
                'Unknown type: %s', Debug::getType($default)
        );
    }
}