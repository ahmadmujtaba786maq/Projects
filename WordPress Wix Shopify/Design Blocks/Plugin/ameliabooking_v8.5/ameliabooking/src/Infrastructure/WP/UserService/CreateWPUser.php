<?php

namespace AmeliaBooking\Infrastructure\WP\UserService;

use WP_Error;

/**
 * Class CreateWPUser
 *
 * @package AmeliaBooking\Infrastructure\WP\UserService
 */
class CreateWPUser
{
    /**
     * @param string      $email
     * @param string      $firstName
     * @param string      $lastName
     * @param string|null $role
     *
     * @return mixed
     */
    public function create($email, $firstName, $lastName, $role = null)
    {
        if (username_exists($email)) {
            $user = get_user_by('login', $email);
            if ($user) {
                $user->add_role($role);
                return $user->ID;
            }
            return null;
        } elseif (email_exists($email)) {
            $user = get_user_by('email', $email);
            if ($user) {
                $user->add_role($role);
                return $user->ID;
            }
            return null;
        }

        $userId = wp_create_user($email, wp_generate_password(), $email);

        wp_update_user(
            [
            'ID'         => $userId,
            'first_name' => $firstName,
            'last_name'  => $lastName,
            ]
        );

        if ($userId instanceof WP_Error) {
            return null;
        }

        $this->setRole($role, $userId);

        wp_new_user_notification($userId, null, 'user');

        return (int)$userId;
    }

    /**
     * @param int         $id
     * @param string|null $role
     *
     * @return mixed
     */
    public function update($id, $role = null)
    {
        $this->addRole($role, $id);
    }

    /**
     * @param string $role
     * @param int    $userId
     */
    private function setRole($role, $userId)
    {
        if ($role) {
            $user = new \WP_User($userId);
            if (get_role($role)) {
                $user->set_role($role);
            }
        }
    }

    /**
     * @param string $role
     * @param int    $userId
     */
    private function addRole($role, $userId)
    {
        if ($role) {
            $user = new \WP_User($userId);
            if (get_role($role)) {
                $user->add_role($role);
            }
        }
    }
}
