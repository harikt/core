<?php declare(strict_types = 1);

namespace Dms\Core\Auth;

use Dms\Core\Model\IValueObject;

interface IPermission extends IValueObject
{
    /**
     * Gets the permission name.
     *
     * @return string
     */
    public function getName() : string;

    /**
     * Returns an equivalent permission in the supplied namespace.
     *
     * Example:
     * <code>
     * $permission = Permission::named('view');
     *
     * echo $permission->getName(); // 'view'
     *
     * $permission = $permission->inNamespace('product');
     *
     * echo $permission->getName(); // 'product.view'
     * </code>
     *
     * @param string $namespace
     *
     * @return static
     */
    public function inNamespace(string $namespace);

    /**
     * Returns whether the permissions are equal
     *
     * @param IPermission $permission
     *
     * @return bool
     */
    public function equals(IPermission $permission) : bool;
}
