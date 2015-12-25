<?php

namespace Dms\Core\Form\Field\Builder;

use Dms\Core\Form\Field\Processor\Validator\ExactLengthValidator;
use Dms\Core\Form\Field\Processor\Validator\MaxLengthValidator;
use Dms\Core\Form\Field\Processor\Validator\MinLengthValidator;
use Dms\Core\Form\Field\Type\StringType;

/**
 * The string field builder class.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class StringFieldBuilder extends FieldBuilderBase
{
    /**
     * Trims the input string of the supplied characters.
     *
     * @param string $characters
     *
     * @return static
     */
    public function trim($characters = " \t\n\r\0\x0B")
    {
        return $this->attr(StringType::ATTR_TRIM_CHARACTERS, $characters);
    }

    /**
     * Validates the input as an email address.
     *
     * @return static
     */
    public function email()
    {
        return $this->attr(StringType::ATTR_STRING_TYPE, StringType::TYPE_EMAIL);
    }

    /**
     * Validates the input as an url.
     *
     * @return static
     */
    public function url()
    {
        return $this->attr(StringType::ATTR_STRING_TYPE, StringType::TYPE_URL);
    }

    /**
     * Sets the field type as a password.
     *
     * @return static
     */
    public function password()
    {
        return $this->attr(StringType::ATTR_STRING_TYPE, StringType::TYPE_PASSWORD);
    }

    /**
     * Sets the field type as html.
     *
     * @return static
     */
    public function html()
    {
        return $this->attr(StringType::ATTR_STRING_TYPE, StringType::TYPE_HTML);
    }

    /**
     * Sets the field type as an ip address.
     *
     * @return static
     */
    public function ipAddress()
    {
        return $this->attr(StringType::ATTR_STRING_TYPE, StringType::TYPE_IP_ADDRESS);
    }

    /**
     * Validates the input has an exact string length.
     *
     * @param int $length
     *
     * @return static
     */
    public function exactLength($length)
    {
        return $this->attr(StringType::ATTR_EXACT_LENGTH, $length);
    }

    /**
     * Validates the input has an exact string length.
     *
     * @param int $length
     *
     * @return static
     */
    public function minLength($length)
    {
        return $this->attr(StringType::ATTR_MIN_LENGTH, $length);
    }

    /**
     * Validates the input has an exact string length.
     *
     * @param int $length
     *
     * @return static
     */
    public function maxLength($length)
    {
        return $this->attr(StringType::ATTR_MAX_LENGTH, $length);
    }
}