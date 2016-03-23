<?php declare(strict_types = 1);

namespace Dms\Core\Form\Field\Options;

use Dms\Core\Exception\InvalidArgumentException;
use Dms\Core\Form\IFieldOption;
use Dms\Core\Form\IFieldOptions;

/**
 * The field options class
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class ArrayFieldOptions implements IFieldOptions
{
    /**
     * @var IFieldOption[]
     */
    private $options;

    public function __construct(array $options)
    {
        InvalidArgumentException::verifyAllInstanceOf(__METHOD__, 'options', $options, IFieldOption::class);
        $this->options = $options;
    }

    /**
     * Constructs an array field options collections with the option
     * values as the array keys and the labels as the array values.
     *
     * @param array  $keyValueOptions
     * @param string $valueType
     *
     * @return ArrayFieldOptions
     */
    public static function fromAssocArray(array $keyValueOptions, string $valueType = 'string') : ArrayFieldOptions
    {
        $options = [];

        foreach ($keyValueOptions as $value => $label) {
            settype($value, $valueType);
            $options[] = $label instanceof IFieldOption
                ? $label
                : new FieldOption($value, $label);
        }

        return new self($options);
    }

    /**
     * {@inheritDoc}
     */
    public function getAll() : array
    {
        return $this->options;
    }

    /**
     * {@inheritDoc}
     */
    public function getAllValues() : array
    {
        $values = [];

        foreach ($this->options as $option) {
            $values[] = $option->getValue();
        }

        return $values;
    }

    /**
     * {@inheritDoc}
     */
    public function getEnabledValues() : array
    {
        $values = [];

        foreach ($this->options as $option) {
            if (!$option->isDisabled()) {
                $values[] = $option->getValue();
            }
        }

        return $values;
    }
}