<?php declare(strict_types = 1);

namespace Dms\Core\Model\Object;

use Dms\Core\Exception;

/**
 * The reference comparer class.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
final class ReferenceComparer
{
    /**
     * @var mixed
     */
    private static $secret;

    /**
     * Determines whether the supplied references are the same
     * reference.
     *
     * @param mixed $ref1
     * @param mixed $ref2
     *
     * @return bool
     */
    public static function areEqual(&$ref1, &$ref2) : bool
    {
        if ($ref1 !== $ref2) {
            return false;
        }

        if (!self::$secret) {
            self::$secret = new \stdClass();
        }

        $tempStorage = $ref1;

        $ref1     = self::$secret;
        $areEqual = $ref1 === $ref2;

        $ref1 = $tempStorage;

        return $areEqual;
    }
}