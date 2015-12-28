<?php

namespace Dms\Core\Form\Field\Type;

use Dms\Core\Form\Field\Processor\TrimProcessor;
use Dms\Core\Form\Field\Processor\Validator\EmailValidator;
use Dms\Core\Form\Field\Processor\Validator\ExactLengthValidator;
use Dms\Core\Form\Field\Processor\Validator\IpAddressValidator;
use Dms\Core\Form\Field\Processor\Validator\MaxLengthValidator;
use Dms\Core\Form\Field\Processor\Validator\MinLengthValidator;
use Dms\Core\Form\Field\Processor\Validator\RequiredValidator;
use Dms\Core\Form\Field\Processor\Validator\UrlValidator;
use Dms\Core\Form\IFieldProcessor;

/**
 * The string type class.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class StringType extends ScalarType
{
    const ATTR_STRING_TYPE = 'string-type';
    const ATTR_MULTILINE = 'multiline';
    const ATTR_MIN_LENGTH = 'min-length';
    const ATTR_MAX_LENGTH = 'max-length';
    const ATTR_EXACT_LENGTH = 'exact-length';
    const ATTR_TRIM_CHARACTERS = 'trim-chars';

    const TYPE_PASSWORD = 'password';
    const TYPE_EMAIL = 'email';
    const TYPE_URL = 'url';
    const TYPE_HTML = 'html';
    const TYPE_IP_ADDRESS = 'ip-address';

    public function __construct()
    {
        parent::__construct(self::STRING);
    }

    /**
     * @inheritDoc
     */
    protected function hasTypeSpecificRequiredValidator()
    {
        return $this->has(self::ATTR_TRIM_CHARACTERS);
    }

    /**
     * @return IFieldProcessor[]
     */
    protected function buildProcessors()
    {
        $processors = parent::buildProcessors();

        $inputType = $this->getProcessedScalarType();

        if ($this->has(self::ATTR_TRIM_CHARACTERS)) {
            $processors[] = new TrimProcessor($this->get(self::ATTR_TRIM_CHARACTERS));

            if ($this->get(self::ATTR_REQUIRED)) {
                $processors[] = new RequiredValidator($inputType);
            }
        }

        if ($this->has(self::ATTR_MIN_LENGTH)) {
            $processors[] = new MinLengthValidator($inputType, $this->get(self::ATTR_MIN_LENGTH));
        }

        if ($this->has(self::ATTR_MAX_LENGTH)) {
            $processors[] = new MaxLengthValidator($inputType, $this->get(self::ATTR_MAX_LENGTH));
        }

        if ($this->has(self::ATTR_EXACT_LENGTH)) {
            $processors[] = new ExactLengthValidator($inputType, $this->get(self::ATTR_MAX_LENGTH));
        }

        if ($this->has(self::ATTR_STRING_TYPE)) {
            switch ($this->get(self::ATTR_STRING_TYPE)) {
                case self::TYPE_EMAIL:
                    $processors[] = new EmailValidator($inputType);
                    break;

                case self::TYPE_URL:
                    $processors[] = new UrlValidator($inputType);
                    break;

                case self::TYPE_IP_ADDRESS:
                    $processors[] = new IpAddressValidator($inputType);
                    break;
            }
        }

        return $processors;
    }
}