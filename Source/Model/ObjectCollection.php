<?php

namespace Iddigital\Cms\Core\Model;

use Iddigital\Cms\Core\Exception;
use Iddigital\Cms\Core\Model\Criteria\PartialLoadCriteria;
use Iddigital\Cms\Core\Model\Object\FinalizedClassDefinition;
use Iddigital\Cms\Core\Model\Object\TypedObject;
use Iddigital\Cms\Core\Model\Type\Builder\Type;
use Iddigital\Cms\Core\Model\Type\ObjectType;
use Pinq\Iterators\IIteratorScheme;

/**
 * The object collection class.
 *
 * @author Elliot Levin <elliot@aanet.com.au>
 */
class ObjectCollection extends TypedCollection implements ITypedObjectCollection
{
    /**
     * @var ObjectType
     */
    protected $elementType;

    /**
     * @var FinalizedClassDefinition
     */
    protected $classDefinition;

    /**
     * @param string               $objectType
     * @param ITypedObject[]       $objects
     * @param IIteratorScheme|null $scheme
     * @param Collection|null      $source
     *
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(
            $objectType,
            $objects = [],
            IIteratorScheme $scheme = null,
            Collection $source = null
    ) {
        if (!is_a($objectType, ITypedObject::class, true)) {
            throw Exception\InvalidArgumentException::format(
                    'Invalid object class: expecting instance of %s, %s given',
                    ITypedObject::class, $objectType
            );
        }

        parent::__construct(Type::object($objectType), $objects, $scheme, $source);

        if (is_a($objectType, TypedObject::class, true)) {
            /** @var string|TypedObject $objectType */
            $objectType            = $this->getObjectType();
            $this->classDefinition = $objectType::definition();
        }
    }

    protected function constructScopedSelf($elements)
    {
        return new TypedCollection(Type::mixed(), $elements, $this->scheme, $this->source ?: $this);
    }

    public function getAll()
    {
        return $this->toOrderedMap()->values();
    }

    /**
     * {@inheritDoc}
     */
    public function getObjectType()
    {
        return $this->elementType->getClass();
    }

    public function contains($object)
    {
        $objectType = $this->getObjectType();

        if (!($object instanceof $objectType)) {
            throw Exception\TypeMismatchException::argument(__METHOD__, 'object', $objectType, $object);
        }

        return $this->doesContainsObjects([$object]);
    }

    /**
     * @inheritDoc
     */
    public function containsAll(array $objects)
    {
        Exception\TypeMismatchException::verifyAllInstanceOf(__METHOD__, 'objects', $objects, $this->getObjectType());

        return $this->doesContainsObjects($objects);
    }

    /**
     * @param object[] $objects
     *
     * @return bool
     */
    protected function doesContainsObjects(array $objects)
    {
        $objectsLookup = new \SplObjectStorage();

        foreach ($this->elements as $object) {
            $objectsLookup[$object] = true;
        }

        foreach ($objects as $object) {
            if (!isset($objectsLookup[$object])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function criteria()
    {
        /** @var string|TypedObject $objectType */
        $objectType = $this->getObjectType();

        return $objectType::criteria();
    }

    /**
     * @inheritDoc
     */
    public function partialCriteria()
    {
        return new PartialLoadCriteria($this->classDefinition);
    }


    public function countMatching(ICriteria $criteria)
    {
        return count($this->matching($criteria));
    }

    /**
     * @inheritDoc
     */
    public function matching(ICriteria $criteria)
    {
        $criteria->verifyOfClass($this->getObjectType());

        $objects = $this->asArray();

        if ($criteria->hasCondition()) {
            $objects = call_user_func($criteria->getCondition()->getArrayFilterCallable(), $objects);
        }

        if ($criteria->hasOrderings()) {
            $multisortArgs = [];

            foreach ($criteria->getOrderings() as $ordering) {
                $direction    = $ordering->isAsc() ? \SORT_ASC : \SORT_DESC;
                $memberGetter = $ordering->getArrayOrderCallable();

                $multisortArgs[] = $memberGetter($objects);
                $multisortArgs[] = $direction;
            }

            $multisortArgs[] =& $objects;
            call_user_func_array('array_multisort', $multisortArgs);
        }

        return array_slice($objects, $criteria->getStartOffset(), $criteria->getLimitAmount());
    }

    /**
     * {@inheritDoc}
     */
    public function satisfying(ISpecification $specification)
    {
        $specification->verifyOfClass($this->getObjectType());

        return $specification->filter($this->asArray());
    }

    /**
     * @inheritDoc
     */
    public function loadPartial(IPartialLoadCriteria $criteria)
    {
        $criteria->verifyOfClass($this->getObjectType());

        $objects    = array_values($this->matching($criteria));
        $loadedData = array_fill_keys(array_keys($objects), []);

        foreach ($criteria->getAliasNestedMemberMap() as $index => $nestedMember) {
            $getter       = $nestedMember->makeArrayGetterCallable();
            $memberValues = $getter($objects);

            foreach ($memberValues as $key => $memberValue) {
                $loadedData[$key][$index] = $memberValue;
            }
        }

        return $loadedData;
    }

}
