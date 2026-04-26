<?php

namespace AmeliaBooking\Infrastructure\WP\UserService;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Factory\User\UserFactory;
use AmeliaBooking\Domain\ValueObjects\String\Password;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\User\UserRepository;
use AmeliaBooking\Infrastructure\WP\UserRoles\UserRoles;
use Slim\Container;
use WP_Error;

/**
 * Class UserService
 *
 * @package AmeliaBooking\Infrastructure\WP\UserService
 */
class UserService
{
    /**
     * @var UserRepository $usersRepository
     */
    private $usersRepository;

    /**
     * UserService constructor.
     *
     * @param Container $container
     *
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function __construct($container)
    {
        $this->usersRepository = $container->get('domain.users.repository');
    }

    /**
     * Return the user entity for currently logged in user
     *
     * @return AbstractUser|bool|null
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function getCurrentUser()
    {
        // Return null user if WP user id is guest id (0)
        $uid = get_current_user_id();

        if ($uid === 0) {
            return null;
        }

        $userType = UserRoles::getUserAmeliaRole($wpUser = wp_get_current_user()) ?: 'customer';

        try {
            // First try to get from repository
            $currentUserEntity = $this->usersRepository->findByExternalId($uid);

            if (
                !($currentUserEntity instanceof AbstractUser) ||
                (
                    $userType === 'manager' &&
                    ($currentUserEntity->getType() === 'provider' || $currentUserEntity->getType() === 'customer')
                )
            ) {
                throw new NotFoundException('User not found');
            }

            return $currentUserEntity;
        } catch (NotFoundException $e) {
            // If user not found creating an entity based on WordPress user data
            return !empty($wpUser->ID) ? UserFactory::create(
                [
                    'type'       => $userType,
                    'firstName'  => $wpUser->get('first_name') !== ''
                        ? $wpUser->get('first_name')
                        : $wpUser->get('user_nicename'),
                    'lastName'   => $wpUser->get('last_name') !== ''
                        ? $wpUser->get('last_name')
                        : $wpUser->get('user_nicename'),
                    'email'      => $wpUser->get('user_email') ?: 'guest@example.com',
                    'externalId' => $wpUser->ID
                ]
            ) : null;
        }
    }

    /**
     * Return the user entity for currently logged in user
     *
     * @param $userId
     *
     * @return AbstractUser|bool|null
     * @throws InvalidArgumentException
     */
    public function getWpUserById($userId)
    {
        $userType = UserRoles::getUserAmeliaRole($wpUser = get_user_by('id', $userId)) ?: 'customer';

        if (!$wpUser || empty($wpUser->ID)) {
            return null;
        }

        return UserFactory::create(
            [
                'type'       => $userType,
                'firstName'  =>
                    $wpUser->get('first_name') !== '' ? $wpUser->get('first_name') : $wpUser->get('user_nicename'),
                'lastName'   =>
                    $wpUser->get('last_name') !== '' ? $wpUser->get('last_name') : $wpUser->get('user_nicename'),
                'email'      => $wpUser->get('user_email') ?: 'guest@example.com',
                'externalId' => $wpUser->ID
            ]
        );
    }

    /**
     * Return all amelia role user ids
     *
     * @param $roles
     *
     * @return array
     */
    public function getWpUserIdsByRoles($roles)
    {
        $ids = [];

        $wpUsers = get_users(['role__in' => $roles]);

        foreach ($wpUsers as $user) {
            $ids[] = $user->ID;
        }

        return $ids;
    }

    /**
     * Return authenticated user
     *
     * @param $username
     * @param $password
     *
     * @return mixed
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function getAuthenticatedUser($username, $password)
    {
        if (($wpUser = wp_authenticate($username, $password)) instanceof WP_Error) {
            return null;
        }

        if (empty($wpUser->ID)) {
            return null;
        }

        $currentUserEntity = $this->usersRepository->findByExternalId($wpUser->ID);

        if (!($currentUserEntity instanceof AbstractUser)) {
            return null;
        }

        return $currentUserEntity;
    }

    /**
     * login user
     *
     * @param $username
     * @param $password
     *
     */
    public function loginWordPressUser($username, $password)
    {
        $user = wp_signon(
            [
                'user_login'    => sanitize_user($username),
                'user_password' => trim($password),
                'remember'      => true,
            ],
            true
        );

        if (!($user instanceof WP_Error)) {
            clean_user_cache($user->ID);
            wp_clear_auth_cookie();
            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID);
            update_user_caches($user);
        }
    }

    /**
     * logout user
     *
     */
    public function logoutWordPressUser()
    {
        wp_logout();
    }


    /**
     * logout user
     *
     */
    public static function logoutAmeliaUser()
    {
        if (!empty($_COOKIE['ameliaToken'])) {
            setcookie('ameliaToken', '', time() - 3600, '/');
            setcookie('ameliaUserEmail', '', time() - 3600, '/');
        }
    }

    /**
     * update Amelia user
     *
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public static function updateAmeliaUser($userId, $oldUserData, $userData)
    {
        // prevent double update
        if (apply_filters('amelia_user_profile_updated', false)) {
            return;
        }

        /** @var Container $container */
        $container = require AMELIA_PATH . '/src/Infrastructure/ContainerConfig/container.php';

        /** @var UserRepository $userRepository */
        $userRepository = $container->get('domain.users.repository');

        $user = $userRepository->getByEntityId($userId, 'externalId');

        if (!$user || $user->length() === 0) {
            return;
        }

        $ameliaUserArray = $user->toArray()[0];

        $wpUserArray = [];
        if (isset($_POST['email'])) {
            $wpUserArray['email'] = $_POST['email'];
        }
        if (isset($_POST['first_name'])) {
            $wpUserArray['firstName'] = $_POST['first_name'];
        }
        if (isset($_POST['last_name'])) {
            $wpUserArray['lastName'] = $_POST['last_name'];
        }

        $newUserArray = array_merge($ameliaUserArray, $wpUserArray);
        $userRepository->update($ameliaUserArray['id'], $newUserArray);

        if (!empty($_POST['pass1'])) {
            $newPassword = new Password($_POST['pass1']);

            $userRepository->updateFieldById($ameliaUserArray['id'], $newPassword->getValue(), 'password');
        }
    }

    /**
     * remove WP user connection to Amelia user
     * @param int $id
     *
     * @throws QueryExecutionException
     */
    public static function removeWPUserConnection($id)
    {
        /** @var Container $container */
        $container = require AMELIA_PATH . '/src/Infrastructure/ContainerConfig/container.php';

        /** @var UserRepository $userRepository */
        $userRepository = $container->get('domain.users.repository');

        $userRepository->updateByEntityId($id, null, 'externalId');
    }
}
