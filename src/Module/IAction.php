<?php

namespace Dms\Core\Module;

use Dms\Core\Auth\IPermission;
use Dms\Core\Exception\InvalidArgumentException;
use Dms\Core\Form;
use Dms\Core\Persistence;

/**
 * The action interface.
 *
 * @author Elliot Levin <elliot@aanet.com.au>
 */
interface IAction
{
    /**
     * Gets the name of the action.
     *
     * @return bool
     */
    public function getName();

    /**
     * Returns whether the action has a return type.
     *
     * @return bool
     */
    public function hasReturnType();

    /**
     * Gets the return type of data transfer object for this handler.
     *
     * @return string|null
     */
    public function getReturnTypeClass();

    /**
     * Gets the namespace of the required permissions
     *
     * @return string|null
     */
    public function getPermissionNamespace();

    /**
     * Adds a namespace of the required permissions
     *
     * @param string $namespace
     *
     * @return void
     */
    public function addPermissionNamespace($namespace);

    /**
     * Gets the permissions required to execute the action.
     *
     * @return IPermission[]
     */
    public function getRequiredPermissions();

    /**
     * Returns whether the action requires a permission with the supplied name.
     *
     * @param string $name
     *
     * @return bool
     */
    public function requiresPermission($name);

    /**
     * Gets required permission with the supplied name.
     *
     * @param string $name
     *
     * @return IPermission
     * @throws InvalidArgumentException
     */
    public function getRequiredPermission($name);

    /**
     * Returns whether the currently authenticated user is authorized.
     *
     * @return bool
     */
    public function isAuthorized();

    /**
     * Gets the action handler
     *
     * @return IActionHandler
     */
    public function getHandler();
}