<?php declare(strict_types = 1);

namespace Dms\Core\Exception;

/**
 * Base exception class.
 *
 * @author Elliot Levin <elliot@aanet.com.au>
 */
class BaseException extends \Exception
{
    public function __construct(string $message = '', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Constructs a new exception with the given message format,
     * the message is passed to sprintf to interpolate the values.
     *
     * @param string $message
     * @param mixed  $_ ...
     *
     * @return static
     */
    public static function format(string $message, $_ = null)
    {
        return new static(vsprintf($message, array_slice(func_get_args(), 1)));
    }

    /**
     * Constructs a new exception with the given message format,
     * the message is passed to sprintf to interpolate the values array.
     *
     * @param string  $message
     * @param mixed[] $values
     *
     * @return static
     */
    public static function formatArray(string $message, array $values = [])
    {
        return new static(vsprintf($message, $values));
    }

    /**
     * Constructs a new exception with the inner exception and
     * the given message format, the message is passed to sprintf
     * to interpolate the (optional) variables.
     *
     * @param \Exception $innerException
     * @param string     $message
     * @param mixed      $_ ...
     *
     * @return static
     */
    public static function wrapper(\Exception $innerException, string $message, $_ = null)
    {
        return new static(vsprintf($message, array_slice(func_get_args(), 2)), null, $innerException);
    }

    /**
     * Verifies the supplied condition. Upon failure the called exception
     * class will be thrown with the specified message format.
     *
     * @param boolean $condition The boolean to evaluate.
     * @param string  $message
     * @param mixed   $_         ...
     *
     * @return void
     * @throws static
     */
    public static function verify(bool $condition, string $message, $_ = null)
    {
        if (!$condition) {
            throw forward_static_call_array([__CLASS__, 'format'], array_slice(func_get_args(), 1));
        }
    }

    /**
     * Gets the string representation of the supplied value's type.
     *
     * @param mixed $value
     *
     * @return string
     */
    public static function getType($value) : string
    {
        return is_object($value) ? get_class($value) : gettype($value);
    }
}
