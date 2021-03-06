<?php declare(strict_types = 1);

namespace Dms\Core\Common\Crud\Definition\Form;

use Dms\Core\Common\Crud\Action\Object\IObjectAction;
use Dms\Core\Common\Crud\Form\FormWithBinding;
use Dms\Core\Common\Crud\Form\ObjectForm;
use Dms\Core\Common\Crud\ICrudModule;
use Dms\Core\Common\Crud\IReadModule;
use Dms\Core\Common\Crud\UnsupportedActionException;
use Dms\Core\Exception\InvalidArgumentException;
use Dms\Core\Exception\InvalidOperationException;
use Dms\Core\Exception\InvalidReturnValueException;
use Dms\Core\Form\Binding\IFieldBinding;
use Dms\Core\Form\Field\Builder\FieldBuilderBase;
use Dms\Core\Form\FormSection;
use Dms\Core\Form\IField;
use Dms\Core\Form\IFormSection;
use Dms\Core\Form\IFormStage;
use Dms\Core\Form\Stage\DependentFormStage;
use Dms\Core\Form\Stage\IndependentFormStage;
use Dms\Core\Form\StagedForm;
use Dms\Core\Model\IIdentifiableObjectSet;
use Dms\Core\Model\Object\FinalizedClassDefinition;
use Dms\Core\Model\Object\TypedObject;
use Dms\Core\Util\Debug;
use Dms\Core\Util\Reflection;

/**
 * The CRUD form definition class.
 *
 * Provides a readable API for defining forms bound to
 * objects, for creation, viewing and updating.
 *
 * This constructs a staged form contains instances of {@see FormWithBinding}
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class CrudFormDefinition
{
    const MODE_DETAILS = IReadModule::DETAILS_ACTION;
    const MODE_CREATE = ICrudModule::CREATE_ACTION;
    const MODE_EDIT = ICrudModule::EDIT_ACTION;

    protected static $modes = [self::MODE_DETAILS, self::MODE_CREATE, self::MODE_EDIT];

    /**
     * @var FinalizedClassDefinition
     */
    private $class;

    /**
     * @var string
     */
    protected $mode;

    /**
     * @var bool
     */
    protected $isDependent;

    /**
     * @var IFormStage[]
     */
    protected $stages = [];

    /**
     * @var IFormStage[]
     */
    protected $stageBindings = [];

    /**
     * @var IFormSection[]
     */
    protected $currentStageSections = [];

    /**
     * @var IFieldBinding[]
     */
    protected $currentStageFieldBindings = [];

    /**
     * @var callable
     */
    protected $createObjectCallback;

    /**
     * @var callable[]
     */
    protected $beforeSubmitCallbacks = [];

    /**
     * @var callable[]
     */
    protected $onSubmitCallbacks = [];

    /**
     * @var callable[]
     */
    protected $onSaveCallbacks = [];

    /**
     * @var string|null
     */
    protected $currentEditedObjectType;

    /**
     * CrudFormDefinition constructor.
     *
     * @param IIdentifiableObjectSet   $dataSource
     * @param FinalizedClassDefinition $class
     * @param string                   $mode
     * @param bool                     $isDependent
     *
     * @throws InvalidArgumentException
     */
    public function __construct(IIdentifiableObjectSet $dataSource, FinalizedClassDefinition $class, string $mode, bool $isDependent = false)
    {
        if (!in_array($mode, self::$modes, true)) {
            throw InvalidArgumentException::format(
                'Mode must must be one of (%s), \'%s\' given',
                Debug::formatValues(self::$modes), $mode
            );
        }

        $this->class       = $class;
        $this->mode        = $mode;
        $this->isDependent = $isDependent;

        if ($this->mode !== self::MODE_CREATE) {
            $this->stages[] = new IndependentFormStage(ObjectForm::build($dataSource));
        }

        // Throw exception inside callback as the class definition may have
        // changed via self::mapToSubClass() or this is an edit form and the
        // class will be updated automatically.
        $this->createObjectCallback = function () {
            if ($this->class->isAbstract()) {
                throw InvalidOperationException::format(
                    'Cannot instantiate object of type %s in crud form mode \'%s\': the class is abstract, did you forget to specify a subclass via %s?',
                    $this->class->getClassName(), $this->mode, '->createObjectType() or ->mapToSubClass()'
                );
            }

            return $this->class->newCleanInstance();
        };
    }

    /**
     * Returns whether this is the form definition for viewing
     * an object from the module data source.
     *
     * @return bool
     */
    public function isDetailsForm() : bool
    {
        return $this->mode === self::MODE_DETAILS;
    }

    /**
     * Returns whether this is the form definition for creating
     * a new object and saving it to the module data source.
     *
     * @return bool
     */
    public function isCreateForm() : bool
    {
        return $this->mode === self::MODE_CREATE;
    }

    /**
     * Returns whether this is the form definition for updating
     * an existing object and saving it to the module data source.
     *
     * @return bool
     */
    public function isEditForm() : bool
    {
        return $this->mode === self::MODE_EDIT;
    }

    /**
     * Marks that the supplied action action is unsupported.
     *
     * @return void
     * @throws UnsupportedActionException
     */
    public function unsupported()
    {
        throw new UnsupportedActionException();
    }

    /**
     * Defines a form section with the supplied form field bindings.
     *
     * Example:
     * <code>
     * $form->section('Details', [
     *      $form->field(
     *          Field::name('name')->label('Name')->string()->required()
     *      )->bindToProperty('name')
     * ]);
     * </code>
     *
     * @param string                       $title
     * @param FormFieldBindingDefinition[] $fieldBindings
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function section(string $title, array $fieldBindings)
    {
        InvalidArgumentException::verifyAllInstanceOf(__METHOD__, 'fieldBindings', $fieldBindings, FormFieldBindingDefinition::class);

        $fields = [];
        foreach ($fieldBindings as $fieldBinding) {
            $fields[] = $fieldBinding->getField();

            if ($fieldBinding->hasBinding()) {
                $this->currentStageFieldBindings[] = $fieldBinding->getBinding();
            }
        }

        $this->currentStageSections[] = new FormSection($title, $fields);
    }

    /**
     * Defines continues the preview form section with the supplied form field bindings.
     *
     * Example:
     * <code>
     * $form->section('Details', [
     *      $form->field(
     *          Field::name('name')->label('Name')->string()->required()
     *      )->bindToProperty('name')
     * ]);
     * </code>
     *
     * @param FormFieldBindingDefinition[] $fieldBindings
     *
     * @return void
     * @throws InvalidArgumentException
     * @throws InvalidOperationException
     */
    public function continueSection(array $fieldBindings)
    {
        if (empty($this->stages) && empty($this->currentStageSections)) {
            throw InvalidOperationException::format('Invalid call to %s: no previous sections have been defined', __METHOD__);
        }

        $this->section('', $fieldBindings);
    }

    /**
     * Defines a field in the current form section.
     *
     * @param IField|FieldBuilderBase $field
     *
     * @return FormFieldBindingDefiner
     */
    public function field($field) : FormFieldBindingDefiner
    {
        if ($field instanceof FieldBuilderBase) {
            $field = $field->build();
        }

        InvalidArgumentException::verifyInstanceOf(__METHOD__, 'field', $field, IField::class);

        return new FormFieldBindingDefiner($this->class, $field);
    }

    /**
     * Defines a section of the form that is dependent on other fields.
     *
     * The supplied callback will be passed the values for the dependent fields
     * as the second parameter and the object instance as the third parameter or
     * NULL if it is a create form.
     *
     * Example:
     * <code>
     * $form->dependentOn(['name'], function (CrudFormDefinition $form, array $input, Person $object = null) {
     *      if ($input['name'] === 'John') {
     *          // ...
     *      } else {
     *          // ...
     *      }
     * });
     * </code>
     *
     * @param string[] $previousFieldNames
     * @param callable $dependentStageDefineCallback
     * @param string[] $fieldNamesDefinedInStage
     *
     * @return void
     * @throws InvalidOperationException
     */
    public function dependentOn(array $previousFieldNames, callable $dependentStageDefineCallback, array $fieldNamesDefinedInStage = [])
    {
        if ($this->isDependent) {
            throw InvalidOperationException::format(
                'Invalid call to %s: cannot nest dependent form sections', __METHOD__
            );
        }

        $this->finishCurrentStage();

        if (!in_array(IObjectAction::OBJECT_FIELD_NAME, $previousFieldNames, true) && !$this->isCreateForm()) {
            $previousFieldNames[] = IObjectAction::OBJECT_FIELD_NAME;
        }

        $this->stages[] = new DependentFormStage(function (array $previousData) use ($dependentStageDefineCallback) {
            $this->isDependent = true;
            $objectInstance    = isset($previousData[IObjectAction::OBJECT_FIELD_NAME])
                ? $previousData[IObjectAction::OBJECT_FIELD_NAME]
                : null;

            if ($objectInstance) {
                /** @var TypedObject $objectInstance */
                $this->class                   = $objectInstance::definition();
                $this->currentEditedObjectType = get_class($objectInstance);
            }

            $dependentStageDefineCallback(
                $this,
                $previousData,
                $objectInstance
            );
            $this->isDependent = false;

            $form = $this->buildFormForCurrentStage();

            if ($objectInstance) {
                $form = $form->withInitialValuesFrom($objectInstance);
            }

            $this->exitStage();

            return $form;
        }, $fieldNamesDefinedInStage, in_array('*', $previousFieldNames, true) ? null : array_unique($previousFieldNames));
    }

    /**
     * Defines a section of the form that is dependent on the object which the form is bound to.
     *
     * The supplied callback will be passed the object instance as the second parameter.
     *
     * NOTE: This will ignore the fields defined in this section if it is a create form and
     * the object field is a required parameter, if you want to support this in create forms,
     * default the object parameter to null and handle this case.
     *
     * Example:
     * <code>
     * $form->dependentOnObject(function (CrudFormDefinition $form, Person $person = null) {
     *      if ($person === null) { // Equivalent to $form->isCreateForm()
     *          // ...
     *      } elseif ($person->isAdmin()) {
     *          // ...
     *      } else {
     *          // ...
     *      }
     * });
     * </code>
     *
     * @param callable $dependentStageDefineCallback
     * @param string[] $fieldNamesDefinedInStage
     *
     * @return void
     * @throws InvalidOperationException
     */
    public function dependentOnObject(callable $dependentStageDefineCallback, array $fieldNamesDefinedInStage = [])
    {
        $requiredParameters = Reflection::fromCallable($dependentStageDefineCallback)->getNumberOfRequiredParameters();

        if ($this->isCreateForm()) {
            if ($requiredParameters === 1) {
                $dependentStageDefineCallback($this);
            }

            return;
        }

        $this->dependentOn([], function (CrudFormDefinition $definition, array $previousData, $object) use ($dependentStageDefineCallback) {
            $dependentStageDefineCallback($definition, $object);
        }, $fieldNamesDefinedInStage);
    }

    protected function finishCurrentStage()
    {
        if ($this->currentStageSections) {
            if ($this->isCreateForm()) {
                $this->stages[] = new IndependentFormStage($this->buildFormForCurrentStage());
            } else {
                $formWithBinding = $this->buildFormForCurrentStage();

                $this->stages[] = new DependentFormStage(
                    function (array $input) use ($formWithBinding) {
                        $object = $input[IObjectAction::OBJECT_FIELD_NAME];

                        return $formWithBinding->withInitialValuesFrom($object);
                    },
                    $formWithBinding->getFieldNames(),
                    [IObjectAction::OBJECT_FIELD_NAME]
                );
            }
            $this->exitStage();
        }
    }

    protected function buildFormForCurrentStage() : FormWithBinding
    {
        return new FormWithBinding(
            $this->currentStageSections,
            [],
            $this->class->getClassName(),
            $this->currentStageFieldBindings
        );
    }

    protected function exitStage()
    {
        $this->currentStageSections      = [];
        $this->currentStageFieldBindings = [];
    }

    /**
     * Defines that the form should map to an instance of the supplied type.
     *
     * @param string $classType
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function mapToSubClass(string $classType)
    {
        if ($this->isEditForm() && $this->currentEditedObjectType) {
            if ($this->currentEditedObjectType !== $classType) {
                throw InvalidArgumentException::format(
                    'Invalid class type supplied to %s: cannot map to subclass %s, as the current object being edited is of type %s',
                    __METHOD__, $classType, $this->currentEditedObjectType
                );
            }
        }

        if (is_subclass_of($this->class->getClassName(), $classType, true)) {
            return;
        }

        /** @var string|TypedObject $classType */
        $this->class = $classType::definition();
        $this->createObjectType()->asClass($classType);
    }

    /**
     * Defines a callback to create new instances of the object.
     * The callback can either return an instance or the class
     * name of the object of which to construct.
     *
     * @return ObjectConstructorCallbackDefiner
     */
    public function createObjectType() : ObjectConstructorCallbackDefiner
    {
        return new ObjectConstructorCallbackDefiner($this->class, function (callable $typeCallback) {
            $this->createObjectCallback = function (array $input) use ($typeCallback) {
                $className = $this->class->getClassName();

                /** @var TypedObject|string $instanceOrType */
                $instanceOrType = $typeCallback($input);

                if (is_string($instanceOrType)) {
                    if (class_exists($instanceOrType) && is_a($instanceOrType, $className, true)) {
                        $instanceOrType = $instanceOrType::definition()->newCleanInstance();
                    }
                }

                if (!($instanceOrType instanceof $className)) {
                    throw InvalidReturnValueException::format(
                        'Invalid create object callback return value: expecting class compatible with %s, %s given',
                        $className, is_string($instanceOrType) ? $instanceOrType : Debug::getType($instanceOrType)
                    );
                }

                return $instanceOrType;
            };
        });
    }

    /**
     * Defines an form submission callback.
     *
     * This will be executed when the form is submitted
     * *before* the form data has been bound to the object.
     *
     * This will NOT be called on a details form.
     *
     * Example:
     * <code>
     * $form->beforeSubmit(function (Person $object, array $input) {
     *      $object->initializeSomething($input['data']);
     * });
     * </code>
     *
     * @param callable $callback
     *
     * @return void
     * @throws InvalidOperationException
     */
    public function beforeSubmit(callable $callback)
    {
        if ($this->isDependent) {
            throw InvalidOperationException::format(
                'Invalid call to %s: cannot add form callbacks in dependent form sections', __METHOD__
            );
        }

        $this->beforeSubmitCallbacks[] = $callback;
    }

    /**
     * Defines an form submission callback.
     *
     * This will be executed when the form is submitted
     * *after* the form data has been bound to the object.
     *
     * This will NOT be called on a details form.
     *
     * Example:
     * <code>
     * $form->onSubmit(function (Person $object, array $input) {
     *      $object->doSomething($input['data']);
     * });
     * </code>
     *
     * @param callable $callback
     *
     * @return void
     * @throws InvalidOperationException
     */
    public function onSubmit(callable $callback)
    {
        if ($this->isDependent) {
            throw InvalidOperationException::format(
                'Invalid call to %s: cannot add form callbacks in dependent form sections', __METHOD__
            );
        }

        $this->onSubmitCallbacks[] = $callback;
    }

    /**
     * Defines an object save callback.
     *
     * This will be executed when the form is submitted
     * after the object has been saved to the underlying data source.
     *
     * This will NOT be called on a details form.
     *
     * Example:
     * <code>
     * $form->onSave(function (Person $object, array $input) {
     *      $this->sendEmailToAdmin($object);
     * });
     * </code>
     *
     * @param callable $callback
     *
     * @return void
     * @throws InvalidOperationException
     */
    public function onSave(callable $callback)
    {
        if ($this->isDependent) {
            throw InvalidOperationException::format(
                'Invalid call to %s: cannot add form callbacks in dependent form sections', __METHOD__
            );
        }

        $this->onSaveCallbacks[] = $callback;
    }

    /**
     * @return FinalizedCrudFormDefinition
     * @throws InvalidArgumentException
     */
    public function finalize() : FinalizedCrudFormDefinition
    {
        if ($this->isCreateForm() && !$this->createObjectCallback) {
            throw InvalidArgumentException::format(
                'Cannot finalize crud form definition for class %s in mode \'%s\': object constructor has not been defined, use ->%s()',
                $this->class->getClassName(), $this->mode, 'createObjectType'
            );
        }

        $this->finishCurrentStage();

        $stages     = $this->stages;
        $firstStage = array_shift($stages);

        $stagedForm = new StagedForm($firstStage, $stages);

        return new FinalizedCrudFormDefinition(
            $this->mode,
            $stagedForm,
            $this->createObjectCallback,
            $this->beforeSubmitCallbacks,
            $this->onSubmitCallbacks,
            $this->onSaveCallbacks
        );
    }
}