<?php declare(strict_types = 1);

namespace Dms\Core\Form\Object;

use Dms\Core\Form\Field\Builder\FieldNameBuilder;

/**
 * The form object field builder
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class FormObjectFieldNameBuilder extends FieldNameBuilder
{
    /**
     * @var callable
     */
    private $nameCallback;

    /**
     * @param callable $nameCallback
     *
     * @return self
     */
    public static function callback(callable $nameCallback) : self
    {
        $self               = new self();
        $self->nameCallback = $nameCallback;

        return $self;
    }

    /**
     * {@inheritDoc}
     */
    public function name($name) : \Dms\Core\Form\Field\Builder\FieldLabelBuilder
    {
        call_user_func($this->nameCallback, $name);

        return parent::name($name);
    }
}