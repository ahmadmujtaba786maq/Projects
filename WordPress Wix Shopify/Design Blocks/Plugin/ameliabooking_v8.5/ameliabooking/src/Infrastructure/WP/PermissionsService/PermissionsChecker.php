<?php

/**
 * WP Infrastructure layer implementation of the permissions service.
 */

namespace AmeliaBooking\Infrastructure\WP\PermissionsService;

use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Entity\User\Admin;
use AmeliaBooking\Domain\Services\Permissions\PermissionsCheckerInterface;

/**
 * Class PermissionsChecker
 *
 * @package AmeliaBooking\Infrastructure\WP\PermissionsService
 */
class PermissionsChecker implements PermissionsCheckerInterface
{
    /**
     * @param AbstractUser $user
     * @param string       $object
     * @param string       $permission
     *
     * @return bool
     */
    public function checkPermissions($user, $object, $permission)
    {
        // Admin can do all
        if ($user instanceof Admin) {
            return true;
        }

        // Get the WP role name of the user, rollback to customer by default
        $wpRoleName = $user !== null ? 'wpamelia-' . $user->getType() : 'wpamelia-customer';
        // Get the wp name of capability we are looking for.
        $wpCapability = "amelia_{$permission}_{$object}";

        if ($user !== null && $user->getExternalId() !== null) {
            return user_can($user->getExternalId()->getValue(), $wpCapability);
        }

        // If user is guest check does it have capability
        $wpRole = get_role($wpRoleName);
        return $wpRole !== null && isset($wpRole->capabilities[$wpCapability]) ?
            (bool)$wpRole->capabilities[$wpCapability] : false;
    }
}
