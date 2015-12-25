<?php

namespace Dms\Core\Form\Processor\Validator;

/**
 * The field less than or equal another form validator.
 *
 * @author Elliot Levin <elliot@aanet.com.au>
 */
class FieldLessThanOrEqualAnotherValidator extends FieldComparisonValidator
{
    const MESSAGE = 'validation.field-less-than-or-equal-another';

    /**
     * @inheritDoc
     */
    protected function getMessageId()
    {
        return self::MESSAGE;
    }

    /**
     * @inheritDoc
     */
    protected function doValuesSatisfyComparison($value1, $value2)
    {
        return $value1 <= $value2;
    }
}