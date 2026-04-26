<?php

namespace AmeliaBooking\Domain\Services\Permissions;

use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Entity\User\Admin;
use AmeliaBooking\Infrastructure\Common\Container;

/**
 * Class PermissionsService
 *
 * @package AmeliaBooking\Domain\Services\Permissions
 */
class PermissionsService
{
    public const READ_PERMISSIONS         = 'read';
    public const READ_OTHERS_PERMISSIONS  = 'read_others';
    public const WRITE_PERMISSIONS        = 'write';
    public const DELETE_PERMISSIONS       = 'delete';
    public const WRITE_STATUS_PERMISSIONS = 'write_status';
    public const WRITE_TIME_PERMISSIONS   = 'write_time';
    public const WRITE_OTHERS_PERMISSIONS = 'write_others';

    /**
     * @var AbstractUser
     */
    private $currentUser;

    /**
     * @var PermissionsCheckerInterface
     */
    private $permissionsChecker;

    /**
     * PermissionsService constructor.
     *
     * @param Container                   $container
     * @param PermissionsCheckerInterface $permissionsChecker
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws \InvalidArgumentException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function __construct(Container $container, PermissionsCheckerInterface $permissionsChecker)
    {
        // Inject dependencies
        if (!($permissionsChecker instanceof PermissionsCheckerInterface)) {
            throw new \InvalidArgumentException('Permissions checker must implement PermissionsCheckerInterface!');
        }
        // Assign current user reference
        $this->currentUser        = $container->get('logged.in.user');
        $this->permissionsChecker = $permissionsChecker;
    }

    /**
     * @param $user
     * @param $object
     * @param $permission
     *
     * @return bool|mixed
     */
    public function userCan($user, $object, $permission)
    {
        if ($user instanceof Admin) {
            return true;
        }
        return $this->permissionsChecker->checkPermissions($user, $object, $permission);
    }

    /**
     * Checks if a given user (AbstractUser) can read a given entity
     *
     * @param AbstractUser $user
     * @param              $object
     *
     * @return bool
     */
    public function userCanRead(AbstractUser $user, $object)
    {
        return $this->userCan($user, $object, self::READ_PERMISSIONS);
    }

    /**
     * Checks if a given user (AbstractUser) can write a given entity
     *
     * @param AbstractUser $user
     * @param              $object
     *
     * @return bool
     */
    public function userCanWrite(AbstractUser $user, $object)
    {
        return $this->userCan($user, $object, self::WRITE_PERMISSIONS);
    }

    /**
     * Checks if a given user (AbstractUser) can delete a given entity
     *
     * @param AbstractUser $user
     * @param              $object
     *
     * @return bool
     */
    public function userCanDelete(AbstractUser $user, $object)
    {
        return $this->userCan($user, $object, self::DELETE_PERMISSIONS);
    }

    /**
     * @param $object
     *
     * @return bool
     */
    public function currentUserCanRead($object)
    {
        return $this->userCan($this->currentUser, $object, self::READ_PERMISSIONS);
    }

    /**
     * @param $object
     *
     * @return bool
     */
    public function currentUserCanReadOthers($object)
    {
        return $this->userCan($this->currentUser, $object, self::READ_OTHERS_PERMISSIONS);
    }

    /**
     * @param $object
     *
     * @return bool
     */
    public function currentUserCanWrite($object)
    {
        return $this->userCan($this->currentUser, $object, self::WRITE_PERMISSIONS);
    }

    /**
     * @param $object
     *
     * @return bool
     */
    public function currentUserCanWriteOthers($object)
    {
        return $this->userCan($this->currentUser, $object, self::WRITE_OTHERS_PERMISSIONS);
    }

    /**
     * @param $object
     *
     * @return bool
     */
    public function currentUserCanWriteStatus($object)
    {
        return $this->userCan($this->currentUser, $object, self::WRITE_STATUS_PERMISSIONS);
    }

    /**
     * @param $object
     *
     * @return bool
     */
    public function currentUserCanWriteTime($object)
    {
        return $this->userCan($this->currentUser, $object, self::WRITE_TIME_PERMISSIONS);
    }

    /**
     * Checks if current user can delete an entity
     *
     * @param $object
     *
     * @return bool
     */
    public function currentUserCanDelete($object)
    {
        return $this->userCan($this->currentUser, $object, self::DELETE_PERMISSIONS);
    }
}
