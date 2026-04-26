<?php

namespace AmeliaBooking\Domain\Services\Permissions;

/**
 * Interface PermissionsCheckerInterface
 *
 * @package AmeliaBooking\Domain\Services\Permissions
 */
interface PermissionsCheckerInterface
{
    /**
     * @param $user
     * @param $object
     * @param $permission
     *
     * @return mixed
     */
    public function checkPermissions($user, $object, $permission);
}
