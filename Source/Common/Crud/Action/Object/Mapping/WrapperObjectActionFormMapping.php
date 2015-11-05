<?php

namespace Iddigital\Cms\Core\Common\Crud\Action\Object\Mapping;

use Iddigital\Cms\Core\Common\Crud\Action\Object\IObjectAction;
use Iddigital\Cms\Core\Common\Crud\Action\Object\ObjectActionParameter;
use Iddigital\Cms\Core\Form\Builder\StagedForm;
use Iddigital\Cms\Core\Form\InvalidFormSubmissionException;
use Iddigital\Cms\Core\Form\IStagedForm;
use Iddigital\Cms\Core\Model\IDataTransferObject;
use Iddigital\Cms\Core\Module\IStagedFormDtoMapping;

/**
 * The wrapper object action form mapping that wraps another form
 * dto mapping for the data dto.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class WrapperObjectActionFormMapping extends ObjectActionFormMapping
{
    /**
     * @var IStagedForm
     */
    private $objectForm;

    /**
     * @var IStagedFormDtoMapping|null
     */
    private $dataFormMapping;

    /**
     * @inheritDoc
     */
    public function __construct(IStagedForm $objectForm, IStagedFormDtoMapping $dataFormMapping = null)
    {
        if ($dataFormMapping) {
            $stagedForm  = StagedForm::fromExisting($objectForm)->embed($dataFormMapping->getStagedForm());
            $dataDtoType = $dataFormMapping->getDtoType();
        } else {
            $stagedForm  = $objectForm;
            $dataDtoType = null;
        }

        parent::__construct($stagedForm->build(), $dataDtoType);
        $this->dataFormMapping = $dataFormMapping;
        $this->objectForm      = $objectForm;
    }

    /**
     * Gets the supplied form submission data mapped to a dto.
     *
     * @param array $submission
     *
     * @return IDataTransferObject
     * @throws InvalidFormSubmissionException
     */
    public function mapFormSubmissionToDto(array $submission)
    {
        $objectData = $this->objectForm->process($submission);
        $dataDto    = $this->dataFormMapping
                ? $this->dataFormMapping->mapFormSubmissionToDto($submission)
                : null;

        return new ObjectActionParameter(
                $objectData[IObjectAction::OBJECT_FIELD_NAME],
                $dataDto
        );
    }
}