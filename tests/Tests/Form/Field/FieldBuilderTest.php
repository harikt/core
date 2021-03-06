<?php

namespace Dms\Core\Tests\Form\Field;

use Dms\Core\File\IUploadedFile;
use Dms\Core\File\IUploadedImage;
use Dms\Core\Form\Field\Builder\Field as Field;
use Dms\Core\Form\Field\Options\ArrayFieldOptions;
use Dms\Core\Form\Field\Options\CallbackFieldOptions;
use Dms\Core\Form\Field\Options\EntityIdOptions;
use Dms\Core\Form\Field\Options\FieldOption;
use Dms\Core\Form\Field\Options\ObjectIndexOptions;
use Dms\Core\Form\Field\Processor\ArrayAllProcessor;
use Dms\Core\Form\Field\Processor\BoolProcessor;
use Dms\Core\Form\Field\Processor\DateTimeProcessor;
use Dms\Core\Form\Field\Processor\DefaultValueProcessor;
use Dms\Core\Form\Field\Processor\ObjectArrayLoaderProcessor;
use Dms\Core\Form\Field\Processor\ObjectLoaderProcessor;
use Dms\Core\Form\Field\Processor\EnumProcessor;
use Dms\Core\Form\Field\Processor\InnerFormProcessor;
use Dms\Core\Form\Field\Processor\TypeProcessor;
use Dms\Core\Form\Field\Processor\Validator\DateFormatValidator;
use Dms\Core\Form\Field\Processor\Validator\ObjectIdArrayValidator;
use Dms\Core\Form\Field\Processor\Validator\ObjectIdValidator;
use Dms\Core\Form\Field\Processor\Validator\OneOfValidator;
use Dms\Core\Form\Field\Processor\Validator\RequiredValidator;
use Dms\Core\Form\Field\Processor\Validator\TypeValidator;
use Dms\Core\Form\Field\Type\ArrayOfObjectIdsType;
use Dms\Core\Form\Field\Type\ArrayOfType;
use Dms\Core\Form\Field\Type\CustomType;
use Dms\Core\Form\Field\Type\DateTimeType;
use Dms\Core\Form\Field\Type\DateType;
use Dms\Core\Form\Field\Type\ObjectIdType;
use Dms\Core\Form\Field\Type\EnumType;
use Dms\Core\Form\Field\Type\FieldType;
use Dms\Core\Form\Field\Type\FileType;
use Dms\Core\Form\Field\Type\ImageType;
use Dms\Core\Form\Field\Type\InnerFormType;
use Dms\Core\Form\Field\Type\ScalarType;
use Dms\Core\Form\Field\Type\StringType;
use Dms\Core\Form\Field\Type\TimeOfDayType;
use Dms\Core\Form\IForm;
use Dms\Core\Model\EntityCollection;
use Dms\Core\Model\EntityIdCollection;
use Dms\Core\Model\IEntity;
use Dms\Core\Model\IValueObject;
use Dms\Core\Model\Object\Entity;
use Dms\Core\Model\Type\Builder\Type;
use Dms\Core\Model\Type\Builder\Type as PhpType;
use Dms\Core\Model\Type\IType;
use Dms\Core\Model\Type\ObjectType;
use Dms\Core\Model\ValueObjectCollection;
use Dms\Core\Tests\Form\Field\Processor\Fixtures\StatusEnum;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class FieldBuilderTest extends FieldBuilderTestBase
{
    /**
     * @param string $name
     * @param string $label
     *
     * @return Field
     */
    protected function field($name = 'name', $label = 'Name')
    {
        return Field::name($name)->label($label);
    }

    public function testFieldNameAndLabel()
    {
        $field = $this->field()->string()->build();

        $this->assertSame('name', $field->getName());
        $this->assertSame('Name', $field->getLabel());
    }

    public function testInitialValue()
    {
        $field = $this->field()->value('abcdef')->string()->build();

        $this->assertSame('abcdef', $field->getInitialValue());
        $this->assertSame('abcdef', $field->getUnprocessedInitialValue());
    }

    public function testUnprocessedInitialValue()
    {
        $field = $this->field()->value(new \DateTimeImmutable('2000-01-01'))->date('Y-m-d')->build();

        $this->assertEquals(new \DateTimeImmutable('2000-01-01'), $field->getInitialValue());
        $this->assertSame('2000-01-01', $field->getUnprocessedInitialValue());
    }

    public function testRequiredField()
    {
        $field = $this->field()->string()->required()->build();

        $this->assertAttributes([FieldType::ATTR_REQUIRED => true, ScalarType::ATTR_TYPE => IType::STRING], $field);
        $this->assertHasProcessor(new RequiredValidator(PhpType::mixed()), $field);
        $this->assertEquals(PhpType::string(), $field->getProcessedType());
    }

    public function testDefaultValueField()
    {
        $field = $this->field()->string()->defaultTo('abc')->build();

        $this->assertAttributes([FieldType::ATTR_DEFAULT => 'abc', ScalarType::ATTR_TYPE => IType::STRING], $field);
        $this->assertHasProcessor(new DefaultValueProcessor(PhpType::string()->nullable(), 'abc'), $field);
        $this->assertEquals(PhpType::string(), $field->getProcessedType());
    }

    public function testOneOfOptionsField()
    {
        $field = $this->field()->string()->oneOf(['hi' => 'Hi', 'bye' => 'Bye'])->build();

        $this->assertAttributes(
            [
                ScalarType::ATTR_TYPE   => IType::STRING,
                FieldType::ATTR_OPTIONS => $fieldOptions = ArrayFieldOptions::fromAssocArray(['hi' => 'Hi', 'bye' => 'Bye']),
            ],
            $field
        );
        $this->assertHasProcessor(new OneOfValidator(PhpType::string()->nullable(), $fieldOptions), $field);
        $this->assertEquals(PhpType::string()->nullable(), $field->getProcessedType());
    }


    public function testOneOfOptionsFromCallbackField()
    {
        $field = $this->field()->string()->oneOfOptionsFromCallback(function (string $filter = null) {
            return [
                'value'         => 'Label',
                'another-value' => 'Another Label',
            ];
        })->build();

        $this->assertInstanceOf(CallbackFieldOptions::class, $field->getType()->get(FieldType::ATTR_OPTIONS));
        $this->assertEquals(PhpType::string()->nullable(), $field->getProcessedType());
    }

    public function testStringField()
    {
        $field = $this->field()->string()->build();
        $this->assertScalarType(ScalarType::STRING, $field);
        $this->assertHasProcessor(new TypeProcessor('string'), $field);
        $this->assertEquals(PhpType::string()->nullable(), $field->getProcessedType());
    }

    public function testIntField()
    {
        $field = $this->field()->int()->build();
        $this->assertScalarType(ScalarType::INT, $field);
        $this->assertHasProcessor(new TypeProcessor('int'), $field);
        $this->assertEquals(PhpType::int()->nullable(), $field->getProcessedType());
    }

    public function testBoolField()
    {
        $field = $this->field()->bool()->build();
        $this->assertScalarType(ScalarType::BOOL, $field);
        $this->assertHasProcessor(new BoolProcessor(), $field);
        $this->assertEquals(PhpType::bool(), $field->getProcessedType());
    }

    public function testDecimalField()
    {
        $field = $this->field()->decimal()->build();
        $this->assertScalarType(ScalarType::FLOAT, $field);
        $this->assertHasProcessor(new TypeProcessor('float'), $field);
        $this->assertEquals(PhpType::float()->nullable(), $field->getProcessedType());
    }

    public function testArrayOfField()
    {
        $field = $this->field()->arrayOf(Field::element()->bool())->build();

        /** @var ArrayOfType $type */
        $type = $field->getType();
        $this->assertInstanceOf(ArrayOfType::class, $type);
        $this->assertInstanceOf(ScalarType::class, $type->getElementType());
        $this->assertSame(ScalarType::BOOL, $type->getElementType()->getType());
        $this->assertHasProcessor(new TypeValidator(PhpType::arrayOf(PhpType::mixed())->nullable()), $field);
        $this->assertHasProcessor(new ArrayAllProcessor(Field::element()->bool()->build()), $field);
        $this->assertEquals(PhpType::arrayOf(PhpType::bool()), $field->getProcessedType());
    }

    public function testObjectField()
    {
        $objects = new ValueObjectCollection(IValueObject::class);
        $field    = $this->field()->objectFromIndex($objects)->build();

        /** @var ObjectIdType $type */
        $type = $field->getType();
        $this->assertInstanceOf(ObjectIdType::class, $type);
        $this->assertInstanceOf(ObjectIndexOptions::class, $type->getOptions());
        $this->assertSame($objects, $type->getOptions()->getObjects());
        $this->assertHasProcessor(new ObjectIdValidator(PhpType::string()->union(PhpType::int())->nullable(), $objects), $field);
        $this->assertHasProcessor(new ObjectLoaderProcessor($objects), $field);
        $this->assertEquals(PhpType::object(IValueObject::class)->nullable(), $field->getProcessedType());
    }

    public function testObjectArrayField()
    {
        $objects = new ValueObjectCollection(IValueObject::class);
        $field    = $this->field()->objectsFromIndexes($objects)->build();

        /** @var ArrayOfObjectIdsType $type */
        $type = $field->getType();
        $this->assertInstanceOf(ArrayOfObjectIdsType::class, $type);
        $this->assertSame(null, $type->get(ArrayOfObjectIdsType::ATTR_UNIQUE_ELEMENTS));

        $this->assertInstanceOf(ObjectIdType::class, $type->getElementType());
        $this->assertInstanceOf(ObjectIndexOptions::class, $type->getElementType()->getOptions());
        $this->assertSame($objects, $type->getElementType()->getOptions()->getObjects());
        $this->assertHasProcessor(new ObjectIdArrayValidator(PhpType::arrayOf(PhpType::int())->nullable(), $objects), $field);
        $this->assertHasProcessor(new ObjectArrayLoaderProcessor($objects), $field);
        $this->assertEquals(PhpType::arrayOf(PhpType::object(IValueObject::class)), $field->getProcessedType());
    }

    public function testEntityField()
    {
        $entities = new EntityCollection(IEntity::class);
        $field    = $this->field()->entityFrom($entities)->build();

        /** @var ObjectIdType $type */
        $type = $field->getType();
        $this->assertInstanceOf(ObjectIdType::class, $type);
        $this->assertInstanceOf(EntityIdOptions::class, $type->getOptions());
        $this->assertSame($entities, $type->getOptions()->getObjects());
        $this->assertHasProcessor(new ObjectIdValidator(PhpType::string()->union(PhpType::int())->nullable(), $entities), $field);
        $this->assertHasProcessor(new ObjectLoaderProcessor($entities), $field);
        $this->assertEquals(PhpType::object(IEntity::class)->nullable(), $field->getProcessedType());
    }

    public function testEntityIdField()
    {
        $entities = new EntityCollection(IEntity::class);
        $field    = $this->field()->entityIdFrom($entities)->build();

        /** @var ObjectIdType $type */
        $type = $field->getType();
        $this->assertInstanceOf(ObjectIdType::class, $type);
        $this->assertInstanceOf(EntityIdOptions::class, $type->getOptions());
        $this->assertSame($entities, $type->getOptions()->getObjects());
        $this->assertHasProcessor(new ObjectIdValidator(PhpType::string()->union(PhpType::int())->nullable(), $entities), $field);
        $this->assertEquals(PhpType::string()->union(PhpType::int())->nullable(), $field->getProcessedType());
    }

    public function testEntityLabelledByCallback()
    {
        $entity = $this->createMock(IEntity::class);
        $entity->method('getId')->willReturn(5);

        $entities = new EntityCollection(IEntity::class, [$entity]);
        $field    = $this->field()->entityIdFrom($entities)
            ->labelledByCallback(function (IEntity $entity) {
                return 'ID: ' . $entity->getId();
            })
            ->build();

        /** @var ObjectIdType $type */
        $type = $field->getType();

        $this->assertEquals([new FieldOption(5, 'ID: 5')], $type->getOptions()->getAll());
        $this->assertEquals(Type::mixed(), $type->getPhpTypeOfInput());
    }

    public function testEntityLabelledByMemberExpression()
    {
        $entity = $this->createMock(Entity::class);
        $entity->setId(5);

        $entities = new EntityCollection(Entity::class, [$entity]);
        $field    = $this->field()->entityFrom($entities)
            ->labelledBy(Entity::ID)
            ->build();

        /** @var ArrayOfType $type */
        $type = $field->getType();

        $this->assertEquals([new FieldOption(5, '5')], $type->getOptions()->getAll());
        $this->assertEquals(Type::mixed(), $type->getPhpTypeOfInput());
    }

    public function testEntityWithDisabledOptions()
    {
        $entity1 = $this->createMock(Entity::class);
        $entity1->setId(1);
        $entity2 = $this->createMock(Entity::class);
        $entity2->setId(2);

        $entities = new EntityCollection(Entity::class, [$entity1, $entity2]);
        $field    = $this->field()->entityFrom($entities)
            ->enabledWhen(function (Entity $entity) {
                return $entity->getId() > 1;
            })
            ->withDisabledLabels(function (string $label) {
                return 'Disabled: ' . $label;
            })
            ->build();

        /** @var ArrayOfType $type */
        $type = $field->getType();

        $this->assertEquals([new FieldOption(1, 'Disabled: 1', true), new FieldOption(2, '2')], $type->getOptions()->getAll());
    }

    public function testEntityArrayWithDisabledOptions()
    {
        $entity1 = $this->createMock(Entity::class);
        $entity1->setId(1);
        $entity2 = $this->createMock(Entity::class);
        $entity2->setId(2);

        $entities = new EntityCollection(Entity::class, [$entity1, $entity2]);
        $field    = $this->field()->entitiesFrom($entities)
            ->enabledWhen(function (Entity $entity) {
                return $entity->getId() > 1;
            })
            ->withDisabledLabels(function (string $label) {
                return 'Disabled: ' . $label;
            })
            ->build();

        /** @var ArrayOfType $type */
        $type = $field->getType();

        $this->assertEquals([new FieldOption(1, 'Disabled: 1', true), new FieldOption(2, '2')], $type->getElementType()->getOptions()->getAll());
    }

    public function testEntityArrayField()
    {
        $entities = new EntityCollection(IEntity::class);
        $field    = $this->field()->entitiesFrom($entities)->build();

        /** @var ArrayOfObjectIdsType $type */
        $type = $field->getType();
        $this->assertInstanceOf(ArrayOfObjectIdsType::class, $type);
        $this->assertSame(true, $type->get(ArrayOfObjectIdsType::ATTR_UNIQUE_ELEMENTS));

        $this->assertInstanceOf(ObjectIdType::class, $type->getElementType());
        $this->assertInstanceOf(EntityIdOptions::class, $type->getElementType()->getOptions());
        $this->assertSame($entities, $type->getElementType()->getOptions()->getObjects());
        $this->assertHasProcessor(new ObjectIdArrayValidator(PhpType::arrayOf(PhpType::int())->nullable(), $entities), $field);
        $this->assertHasProcessor(new ObjectArrayLoaderProcessor($entities), $field);
        $this->assertEquals(PhpType::arrayOf(PhpType::object(IEntity::class)), $field->getProcessedType());
    }

    public function testEntityArrayLabelledByMemberExpression()
    {
        $entity = $this->createMock(Entity::class);
        $entity->setId(5);

        $entities = new EntityCollection(Entity::class, [$entity]);
        $field    = $this->field()->entityIdsFrom($entities)
            ->labelledBy(Entity::ID)
            ->build();

        /** @var ArrayOfObjectIdsType $type */
        $type = $field->getType();

        $this->assertEquals([new FieldOption(5, '5')], $type->getElementType()->getOptions()->getAll());
        $this->assertEquals(Type::arrayOf(Type::mixed())->nullable(), $type->getPhpTypeOfInput());
    }

    public function testEntityArrayLabelledByCallback()
    {
        $entity = $this->createMock(IEntity::class);
        $entity->method('getId')->willReturn(5);

        $entities = new EntityCollection(IEntity::class, [$entity]);
        $field    = $this->field()->entityIdsFrom($entities)
            ->labelledByCallback(function (IEntity $entity) {
                return 'ID: ' . $entity->getId();
            })
            ->build();

        /** @var ArrayOfObjectIdsType $type */
        $type = $field->getType();

        $this->assertEquals([new FieldOption(5, 'ID: 5')], $type->getElementType()->getOptions()->getAll());
        $this->assertEquals(Type::arrayOf(Type::mixed())->nullable(), $type->getPhpTypeOfInput());
    }

    public function testEntityFieldMappedToObjectCollection()
    {
        $entity = $this->getMockBuilder(Entity::class)->getMockForAbstractClass();
        $entity->hydrate(['id' => 1]);

        $entities = new EntityCollection(Entity::class, [$entity]);
        $field    = $this->field()
            ->entitiesFrom($entities)
            ->mapToCollection(Entity::collectionType())
            ->build();

        /** @var ArrayOfObjectIdsType $type */
        $this->assertEquals(Entity::collectionType(), $field->getProcessedType());

        $this->assertEquals(Entity::collection([1 => $entity]), $field->process(['1']));
        $this->assertEquals([1], $field->unprocess(Entity::collection([$entity])));
    }

    public function testEntityIdsFromField()
    {
        $entities = new EntityCollection(IEntity::class);
        $field    = $this->field()->entityIdsFrom($entities)->build();

        /** @var ArrayOfObjectIdsType $type */
        $type = $field->getType();
        $this->assertInstanceOf(ArrayOfObjectIdsType::class, $type);
        $this->assertSame(true, $type->get(ArrayOfObjectIdsType::ATTR_UNIQUE_ELEMENTS));

        $this->assertInstanceOf(ObjectIdType::class, $type->getElementType());
        $this->assertInstanceOf(EntityIdOptions::class, $type->getElementType()->getOptions());
        $this->assertSame($entities, $type->getElementType()->getOptions()->getObjects());
        $this->assertHasProcessor(new ObjectIdArrayValidator(PhpType::arrayOf(PhpType::int())->nullable(), $entities), $field);
        $this->assertEquals(PhpType::arrayOf(PhpType::int()), $field->getProcessedType());
    }

    public function testEntityIdFieldMappedToCollection()
    {
        $entity = $this->createMock(IEntity::class);
        $entity->method('hasId')->willReturn(true);
        $entity->method('getId')->willReturn(1);

        $entities = new EntityCollection(IEntity::class, [$entity]);
        $field    = $this->field()->entityIdsFrom($entities)->mapToCollection(EntityIdCollection::type())->build();

        /** @var ArrayOfObjectIdsType $type */
        $this->assertEquals(PhpType::collectionOf(PhpType::int(), EntityIdCollection::class), $field->getProcessedType());

        $this->assertEquals(new EntityIdCollection([1]), $field->process(['1']));
        $this->assertEquals([1], $field->unprocess(new EntityIdCollection([1])));
    }

    public function testEntityIdFieldMappedToCollectionWithRequired()
    {
        $entities = new EntityCollection(IEntity::class, []);
        $field    = $this->field()->entityIdsFrom($entities)
            ->mapToCollection(EntityIdCollection::type())
            ->required()
            ->build();

        /** @var ArrayOfObjectIdsType $type */
        $this->assertEquals(PhpType::collectionOf(PhpType::int(), EntityIdCollection::class), $field->getProcessedType());
    }

    public function testEntityIdsFieldMappedToObjectCollection()
    {
        $entity = $this->getMockBuilder(Entity::class)->getMockForAbstractClass();
        $entity->hydrate(['id' => 1]);

        $entities = new EntityCollection(Entity::class, [$entity]);
        $field    = $this->field()->entityIdsFrom($entities)->mapToCollection(
            Entity::collectionType(),
            function (int $id) use ($entities) {
                return $entities->get($id);
            },
            function (Entity $entity) {
                return $entity->getId();
            }
        )->build();

        /** @var ArrayOfObjectIdsType $type */
        $this->assertEquals(Entity::collectionType(), $field->getProcessedType());

        $this->assertEquals(Entity::collection([$entity]), $field->process(['1']));
        $this->assertEquals([1], $field->unprocess(Entity::collection([$entity])));
    }

    public function testDateField()
    {
        $field = $this->field()->date('Y-m-d', new \DateTimeZone('UTC'))->build();

        /** @var DateType $type */
        $type = $field->getType();
        $this->assertInstanceOf(DateType::class, $type);
        $this->assertSame('Y-m-d', $type->get(DateType::ATTR_FORMAT));
        $this->assertHasProcessor(new DateFormatValidator(PhpType::string()->nullable(), 'Y-m-d'), $field);
        $this->assertHasProcessor(new DateTimeProcessor('Y-m-d', new \DateTimeZone('UTC'), DateTimeProcessor::MODE_ZERO_TIME), $field);
        $this->assertEquals(PhpType::object(\DateTimeImmutable::class)->nullable(), $field->getProcessedType());
    }

    public function testDateTime()
    {
        $field = $this->field()->datetime('Y-m-d H:i:s', new \DateTimeZone('UTC'))->build();

        /** @var DateTimeType $type */
        $type = $field->getType();
        $this->assertInstanceOf(DateTimeType::class, $type);
        $this->assertSame('Y-m-d H:i:s', $type->get(DateTimeType::ATTR_FORMAT));
        $this->assertHasProcessor(new DateFormatValidator(PhpType::string()->nullable(), 'Y-m-d H:i:s'), $field);
        $this->assertHasProcessor(new DateTimeProcessor('Y-m-d H:i:s', new \DateTimeZone('UTC')), $field);
        $this->assertEquals(PhpType::object(\DateTimeImmutable::class)->nullable(), $field->getProcessedType());
    }

    public function testTime()
    {
        $field = $this->field()->time('H:i:s', new \DateTimeZone('UTC'))->build();

        /** @var TimeOfDayType $type */
        $type = $field->getType();
        $this->assertInstanceOf(TimeOfDayType::class, $type);
        $this->assertSame('H:i:s', $type->get(TimeOfDayType::ATTR_FORMAT));
        $this->assertHasProcessor(new DateFormatValidator(PhpType::string()->nullable(), 'H:i:s'), $field);
        $this->assertHasProcessor(new DateTimeProcessor('H:i:s', new \DateTimeZone('UTC'), DateTimeProcessor::MODE_ZERO_DATE), $field);
        $this->assertEquals(PhpType::object(\DateTimeImmutable::class)->nullable(), $field->getProcessedType());
    }

    public function testFileField()
    {
        $field = $this->field()->file()->build();

        /** @var FileType $type */
        $type = $field->getType();
        $this->assertInstanceOf(FileType::class, $type);
        $this->assertEquals(PhpType::object(IUploadedFile::class)->nullable(), $field->getProcessedType());
    }

    public function testImageField()
    {
        $field = $this->field()->image()->build();

        /** @var ImageType $type */
        $type = $field->getType();
        $this->assertInstanceOf(ImageType::class, $type);
        $this->assertEquals(PhpType::object(IUploadedImage::class)->nullable(), $field->getProcessedType());
    }

    public function testInnerForm()
    {
        $form  = $this->getMockForAbstractClass(IForm::class);
        $form->method('withInitialValues')
            ->willReturnSelf();

        $field = $this->field()->form($form)->build();

        /** @var InnerFormType $type */
        $type = $field->getType();
        $this->assertInstanceOf(InnerFormType::class, $type);
        $this->assertHasProcessor(new TypeValidator(PhpType::arrayOf(PhpType::mixed())->nullable()), $field);
        $this->assertHasProcessor(new InnerFormProcessor($form), $field);
        $this->assertEquals(PhpType::arrayOf(PhpType::mixed())->nullable(), $field->getProcessedType());
    }

    public function testEnum()
    {
        $field = $this->field()->enum(StatusEnum::class, [
            StatusEnum::INACTIVE => 'Inactive',
            StatusEnum::ACTIVE   => 'Active',
        ])->build();

        /** @var StringType $type */
        $type = $field->getType();
        $this->assertInstanceOf(EnumType::class, $type);
        $this->assertEquals($fieldOptions = [
            new FieldOption(StatusEnum::INACTIVE, 'Inactive'),
            new FieldOption(StatusEnum::ACTIVE, 'Active'),
        ], $type->getOptions()->getAll());

        $this->assertHasProcessor(new OneOfValidator(PhpType::string()->nullable(), new ArrayFieldOptions($fieldOptions)), $field);
        $this->assertHasProcessor(new EnumProcessor(StatusEnum::class), $field);
        $this->assertEquals(PhpType::object(StatusEnum::class)->nullable(), $field->getProcessedType());
    }

    public function testCustom()
    {
        $field = $this->field()->custom(Type::object(\stdClass::class), [])->build();

        /** @var CustomType $type */
        $type = $field->getType();
        $this->assertInstanceOf(CustomType::class, $type);
        $this->assertEquals((new ObjectType(\stdClass::class))->nullable(), $type->getPhpTypeOfInput());
        $this->assertEquals(null, $type->getOptions());
        $this->assertEquals([new TypeValidator(PhpType::object(\stdClass::class)->nullable())], $field->getProcessors());
        $this->assertEquals(PhpType::object(\stdClass::class)->nullable(), $field->getProcessedType());
    }

    public function testHiddenField()
    {
        $normalField = $this->field()->string()->build();

        $this->assertEmpty($normalField->getType()->get(FieldType::ATTR_HIDDEN));

        $hiddenField = $this->field()->string()->hidden()->build();

        $this->assertSame(true, $hiddenField->getType()->get(FieldType::ATTR_HIDDEN));
    }
}