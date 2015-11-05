<?php

namespace Iddigital\Cms\Core\Common\Crud\Definition\Form;

use Iddigital\Cms\Core\Common\Crud\Form\FormWithBinding;
use Iddigital\Cms\Core\Exception\InvalidArgumentException;
use Iddigital\Cms\Core\Exception\InvalidOperationException;
use Iddigital\Cms\Core\Form\Binding\IFieldBinding;
use Iddigital\Cms\Core\Form\Field\Builder\FieldBuilderBase;
use Iddigital\Cms\Core\Form\FormSection;
use Iddigital\Cms\Core\Form\IField;
use Iddigital\Cms\Core\Form\IFormSection;
use Iddigital\Cms\Core\Form\IFormStage;
use Iddigital\Cms\Core\Form\Stage\DependentFormStage;
use Iddigital\Cms\Core\Form\Stage\IndependentFormStage;
use Iddigital\Cms\Core\Model\Object\FinalizedClassDefinition;
use Iddigital\Cms\Core\Util\Debug;

/**
 * The CRUD form definition class.
 *
 * Provides a readable API for definition forms bound to
 * objects, for creation, viewing and updating.
 *
 * This constructs a staged form contains instances of
 * @see    FormWithBinding
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class CrudFormDefinition
{
    const MODE_DETAILS = 'details';
    const MODE_CREATE = 'create';
    const MODE_EDIT = 'edit';

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
     * @var callable[]
     */
    protected $onSubmitCallbacks = [];

    /**
     * CrudFormDefinition constructor.
     *
     * @param FinalizedClassDefinition $class
     * @param string                   $mode
     * @param bool                     $isDependent
     *
     * @throws InvalidArgumentException
     */
    public function __construct(FinalizedClassDefinition $class, $mode, $isDependent = false)
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
    }

    /**
     * Returns whether this is the form definition for viewing
     * an object from the module data source.
     *
     * @return bool
     */
    public function isDetailsForm()
    {
        return $this->mode === self::MODE_DETAILS;
    }

    /**
     * Returns whether this is the form definition for creating
     * a new object and saving it to the module data source.
     *
     * @return bool
     */
    public function isCreateForm()
    {
        return $this->mode === self::MODE_CREATE;
    }

    /**
     * Returns whether this is the form definition for updating
     * an existing object and saving it to the module data source.
     *
     * @return bool
     */
    public function isEditForm()
    {
        return $this->mode === self::MODE_EDIT;
    }

    /**
     * Defines a form section with the supplied form field bindings.
     *
     * Standard fields can be passed if there is no binding.
     *
     * Example:
     * <code>
     * $form->section('Details', [
     *      $form->field(Field::name('name')->label('Name')->string()->required())
     *              ->bindToProperty('name'),
     *      Field::name('age')->label('Age')->int(), // Field without binding
     * ]);
     * </code>
     *
     * @param string                                                   $title
     * @param FormFieldBindingDefinition[]|IField[]|FieldBuilderBase[] $fieldBindings
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function section($title, array $fieldBindings)
    {
        InvalidArgumentException::verifyAllInstanceOf(__METHOD__, 'fieldBindings', $fieldBindings, FormFieldBindingDefinition::class);

        $fields = [];
        foreach ($fieldBindings as $fieldBinding) {
            if ($fieldBinding instanceof FormFieldBindingDefinition) {
                $fields[] = $fieldBinding->getField();

                if ($fieldBinding->hasBinding()) {
                    $this->currentStageFieldBindings[] = $fieldBinding->getBinding();
                }
            } elseif ($fieldBinding instanceof FieldBuilderBase) {
                $fields[] = $fieldBinding->build();
            } elseif ($fieldBinding instanceof IField) {
                $fields[] = $fieldBinding;
            } else {
                throw InvalidArgumentException::format(
                        'Invalid call to %s: parameter $fieldBindings must only contain instances of %s, %s found',
                        __METHOD__, implode('|', [FormFieldBindingDefinition::class, FieldBuilderBase::class, IField::class]),
                        Debug::getType($fieldBinding)
                );
            }
        }

        $this->currentStageSections[] = new FormSection($title, $fields);
    }

    /**
     * Defines a field in the current form section.
     *
     * @param IField|FieldBuilderBase $field
     *
     * @return FormFieldBindingDefiner
     */
    public function field($field)
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
     * The supplied callback will be passed the values for the dependent fields.
     *
     * Example:
     * <code>
     * $form->depdendentOn(['name'], function (CrudFormDefinition $form, array $input) use ($object) {
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
     *
     * @return void
     * @throws InvalidOperationException
     */
    public function dependentOn(array $previousFieldNames, callable $dependentStageDefineCallback)
    {
        if ($this->isDependent) {
            throw InvalidOperationException::format(
                    'Invalid call to %s: cannot nest dependent form sections'
            );
        }

        $this->enterNewStage();

        $this->stages[] = new DependentFormStage(function (array $previousData) use ($dependentStageDefineCallback) {
            $this->isDependent = true;
            $dependentStageDefineCallback($this, $previousData);
            $this->isDependent = false;

            $form = $this->buildFormForCurrentStage();
            $this->exitStage();

            return $form;
        }, null, $previousFieldNames);
    }

    protected function enterNewStage()
    {
        if ($this->currentStageSections) {
            $this->stages[] = new IndependentFormStage($this->buildFormForCurrentStage());
            $this->exitStage();
        }
    }

    protected function buildFormForCurrentStage()
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
     * Defines an form submission callback.
     *
     * This will be executed when the form is submitted
     * after the form data has been bound to the object.
     *
     * Example:
     * <code>
     * $form->onSubmit(function (array $input) use ($object) {
     *      $object->doSomething($input['data']);
     * });
     * </code>
     *
     * @param callable $callback
     *
     * @return void
     */
    public function onSubmit(callable $callback)
    {
        $this->onSubmitCallbacks[] = $callback;
    }
}