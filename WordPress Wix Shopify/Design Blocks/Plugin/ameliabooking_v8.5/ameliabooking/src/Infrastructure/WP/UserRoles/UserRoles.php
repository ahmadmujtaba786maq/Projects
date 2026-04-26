<?php

namespace AmeliaBooking\Infrastructure\WP\UserRoles;

/**
 * Class UserRoles
 *
 * @package AmeliaBooking\Infrastructure\WP
 */
class UserRoles
{
    /**
     * @param $roles
     */
    public static function init($roles)
    {
        /** @var array $roles */
        foreach ($roles as $role) {
            if (!wp_roles()->is_role($role['name'])) {
                add_role($role['name'], $role['label'], $role['capabilities']);
            }
        }
    }

    /**
     * Return the current user amelia role
     *
     * @param $wpUser
     * @return string|null
     */
    public static function getUserAmeliaRole($wpUser)
    {
        if (in_array('administrator', $wpUser->roles, true) || is_super_admin($wpUser->ID)) {
            return 'admin';
        }

        if (in_array('wpamelia-manager', $wpUser->roles, true)) {
            return 'manager';
        }

        if (in_array('wpamelia-provider', $wpUser->roles, true)) {
            return 'provider';
        }

        if (in_array('wpamelia-customer', $wpUser->roles, true)) {
            return 'customer';
        }

        return null;
    }
}
