<?php

namespace Iddigital\Cms\Core\Form\Binding\Field;

use Iddigital\Cms\Core\Form\Binding\IFieldBinding;
use Iddigital\Cms\Core\Exception\TypeMismatchException;
use Iddigital\Cms\Core\Form\IField;

/**
 * The field binding base class.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
abstract class FieldBinding implements IFieldBinding
{
    /**
     * @var IField
     */
    protected $fieldName;

    /**
     * @var string
     */
    protected $objectType;

    /**
     * FieldBinding constructor.
     *
     * @param string $fieldName
     * @param string $objectType
     */
    public function __construct($fieldName, $objectType)
    {
        $this->fieldName      = $fieldName;
        $this->objectType = $objectType;
    }

    /**
     * @inheritDoc
     */
    final public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * @inheritDoc
     */
    final public function getObjectType()
    {
        return $this->objectType;
    }

    /**
     * @inheritDoc
     */
    final public function getFieldValueFromObject($object)
    {
        if (!($object instanceof $this->objectType)) {
            throw TypeMismatchException::argument(__METHOD__, 'object', $this->objectType, $object);
        }

        return $this->getFieldValueFrom($object);
    }

    /**
     * @param object $object
     *
     * @return mixed
     */
    abstract protected function getFieldValueFrom($object);

    /**
     * @inheritDoc
     */
    final public function bindFieldValueToObject($object, $processedFieldValue)
    {
        if (!($object instanceof $this->objectType)) {
            throw TypeMismatchException::argument(__METHOD__, 'object', $this->objectType, $object);
        }

        $this->bindFieldValueTo($object, $processedFieldValue);
    }

    /**
     * @param object $object
     * @param mixed  $processedFieldValue
     *
     * @return void
     */
    abstract protected function bindFieldValueTo($object, $processedFieldValue);
}