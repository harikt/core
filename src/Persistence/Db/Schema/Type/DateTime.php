<?php declare(strict_types = 1);

namespace Dms\Core\Persistence\Db\Schema\Type;

use Dms\Core\Model\Type\Builder\Type as PhpType;
use Dms\Core\Model\Type\IType;

/**
 * The db datetime type
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class DateTime extends Type
{
    /**
     * @inheritDoc
     */
    protected function loadPhpType() : IType
    {
        return PhpType::object(\DateTimeImmutable::class);
    }
}