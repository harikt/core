<?php

namespace Dms\Core\Tests\Common\Crud\Form;

use Dms\Common\Testing\CmsTestCase;
use Dms\Core\Common\Crud\Form\FormWithBinding;
use Dms\Core\Form\Binding\Field\FieldPropertyBinding;
use Dms\Core\Form\Field\Type\InnerFormType;
use Dms\Core\Form\InvalidFormSubmissionException;
use Dms\Core\Tests\Common\Crud\Form\Fixtures\TestValueObject;
use Dms\Core\Tests\Common\Crud\Form\Fixtures\TestValueObjectField;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class ValueObjectFieldTest extends CmsTestCase
{
    public function testNew()
    {
        $field = new TestValueObjectField('name', 'Label');

        $formWithBinding = new FormWithBinding(
            $field->getType()->getForm()->getSections(), [],
            TestValueObject::class,
            $fieldBindings = [
                new FieldPropertyBinding('string', TestValueObject::definition(), 'string'),
                new FieldPropertyBinding('int', TestValueObject::definition(), 'int'),
            ]
        );

        $this->assertSame('name', $field->getName());
        $this->assertSame('Label', $field->getLabel());
        $this->assertEquals($formWithBinding, $field->getFieldDefinition()->getForm());
        $this->assertEquals(null, $field->getInitialValue());
    }

    public function testNewWithInitialValue()
    {
        $field = new TestValueObjectField('name', 'Label', new TestValueObject('abc', 10));

        /** @var InnerFormType $fieldType */
        $fieldType = $field->getType();

        $this->assertEquals(new TestValueObject('abc', 10), $field->getInitialValue());
        $this->assertEquals('abc', $fieldType->getForm()->getField('string')->getInitialValue());
        $this->assertEquals(10, $fieldType->getForm()->getField('int')->getInitialValue());
        $this->assertEquals($field, (new TestValueObjectField('name', 'Label'))->withInitialValue(new TestValueObject('abc', 10)));
    }

    public function testProcess()
    {
        $field = new TestValueObjectField('name', 'Label');

        $this->assertEquals(
            new TestValueObject('abc', 10),
            $field->process(['string' => 'abc', 'int' => 10])
        );

        $this->assertThrows(function () use ($field) {
            $field->process(['string' => null, 'int' => null]);
        }, InvalidFormSubmissionException::class);
    }

    public function testUnprocess()
    {
        $field = new TestValueObjectField('name', 'Label');

        $this->assertSame([
            'string' => 'abc',
            'int'    => 10,
        ], $field->unprocess(new TestValueObject('abc', 10)));
    }
}