<?php

namespace Dms\Core\Tests\Form\Field\Processor\Validator;

use Dms\Core\Form\Field\Processor\FieldValidator;
use Dms\Core\Form\Field\Processor\Validator\GreaterThanValidator;
use Dms\Core\Language\Message;
use Dms\Core\Model\Type\Builder\Type;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class GreaterThanValidatorTest extends FieldValidatorTest
{
    /**
     * @return FieldValidator
     */
    protected function validator()
    {
        return new GreaterThanValidator($this->processedType(), 5);
    }

    /**
     * @inheritDoc
     */
    protected function processedType()
    {
        return Type::mixed();
    }

    /**
     * @return array[]
     */
    public function successTests()
    {
        return [
            [null],
            [6],
            [700],
        ];
    }

    /**
     * @return array[]
     */
    public function failTests()
    {
        return [
            [5, new Message(GreaterThanValidator::MESSAGE, ['value' => 5])],
            ['5', new Message(GreaterThanValidator::MESSAGE, ['value' => 5])],
            [4, new Message(GreaterThanValidator::MESSAGE, ['value' => 5])],
            [-200, new Message(GreaterThanValidator::MESSAGE, ['value' => 5])],
        ];
    }
}