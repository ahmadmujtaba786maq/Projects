<?php

/**
 * Role hook for activation
 */

namespace AmeliaBooking\Infrastructure\WP\InstallActions;

use AmeliaBooking\Infrastructure\WP\config\Roles;
use AmeliaBooking\Infrastructure\WP\UserRoles\UserRoles;

/**
 * Class ActivationRolesHook
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions
 */
class ActivationRolesHook
{
    /**
     * Add new custom roles and add capabilities to administrator role
     */
    public static function init()
    {
        $roles = new Roles();

        UserRoles::init($roles());

        $adminRole = get_role('administrator');
        if ($adminRole !== null) {
            foreach (Roles::$rolesList as $role) {
                $adminRole->add_cap($role);
            }
        }
    }
}
