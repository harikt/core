<?php

namespace Dms\Core\Tests\Form\Binding\Field;

use Dms\Core\Exception\InvalidArgumentException;
use Dms\Core\Exception\TypeMismatchException;
use Dms\Core\Form\Binding\Field\FieldPropertyBinding;
use Dms\Core\Form\Binding\Field\GetterSetterMethodBinding;
use Dms\Core\Form\Binding\IFieldBinding;
use Dms\Core\Form\Field\Builder\Field;
use Dms\Core\Form\IField;
use Dms\Core\Tests\Form\Binding\Fixtures\TestFormBoundClass;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class GetterSetterMethodBindingTest extends FieldBindingTest
{
    /**
     * @return IField
     */
    protected function buildField()
    {
        return Field::name('abc')->label('Abc')->int()->required()->build();
    }

    /**
     * @return string
     */
    protected function getObjectType()
    {
        return TestFormBoundClass::class;
    }

    /**
     * @param IField $field
     * @param string $objectType
     *
     * @return IFieldBinding
     */
    protected function buildFormBinding(IField $field, $objectType)
    {
        return new GetterSetterMethodBinding(
                $field->getName(),
                TestFormBoundClass::class,
                'getInt', 'setInt'
        );
    }

    public function testGet()
    {
        $object = new TestFormBoundClass('abc', 10, false);

        $this->assertSame(10, $this->binding->getFieldValueFromObject($object));
    }

    public function testSet()
    {
        $object = new TestFormBoundClass('abc', 10, false);

        $this->binding->bindFieldValueToObject($object, 123);

        $this->assertSame(123, $object->int);
    }

    public function testInvalidGetterMethod()
    {
        $this->setExpectedException(InvalidArgumentException::class);

        return new GetterSetterMethodBinding(
                $this->buildField()->getName(),
                TestFormBoundClass::class,
                'nonExistentMethod', 'setInt'
        );
    }

    public function testInvalidSetterMethod()
    {
        $this->setExpectedException(InvalidArgumentException::class);

        return new GetterSetterMethodBinding(
                $this->buildField()->getName(),
                TestFormBoundClass::class,
                'getInt', 'nonExistentMethod'
        );
    }
}