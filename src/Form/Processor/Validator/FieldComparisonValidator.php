<?php

namespace Dms\Core\Form\Processor\Validator;

use Dms\Core\Form\IField;
use Dms\Core\Form\Processor\FormValidator;
use Dms\Core\Language\Message;

/**
 * The field comparison form validator.
 *
 * @author Elliot Levin <elliot@aanet.com.au>
 */
abstract class FieldComparisonValidator extends FormValidator
{
    /**
     * @var IField
     */
    protected $field1;

    /**
     * @var IField
     */
    protected $field2;

    /**
     * MatchingFieldsValidator constructor.
     *
     * @param IField $field1
     * @param IField $field2
     */
    public function __construct(IField $field1, IField $field2)
    {
        $this->field1 = $field1;
        $this->field2 = $field2;
    }

    /**
     * @return IField
     */
    public function getField1()
    {
        return $this->field1;
    }

    /**
     * @return IField
     */
    public function getField2()
    {
        return $this->field2;
    }

    /**
     * @param array     $input
     * @param Message[] $messages
     *
     * @return void
     */
    protected function validate(array $input, array &$messages)
    {
        $field1Name = $this->field1->getName();
        $field2Name = $this->field2->getName();

        if (!isset($input[$field1Name]) && !isset($input[$field2Name])) {
            // If here, fields are optional and have not been supplied
            // so no validation is necessary
            return;
        }

        if (!$this->doValuesSatisfyComparison($input[$field1Name], $input[$field2Name])) {
            $messages[] = new Message(
                    $this->getMessageId(),
                    ['field1' => $this->field1->getLabel(), 'field2' => $this->field2->getLabel()]
            );
        }
    }

    /**
     * @return string
     */
    abstract protected function getMessageId();

    /**
     * @param mixed $value1
     * @param mixed $value2
     *
     * @return bool
     */
    abstract protected function doValuesSatisfyComparison($value1, $value2);
}
