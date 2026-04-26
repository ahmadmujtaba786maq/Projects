<?php

namespace AmeliaBooking\Domain\Entity\User;

/**
 * Class Admin
 *
 * @package AmeliaBooking\Domain\Entity\User
 */
class Admin extends AbstractUser
{
    /**
     * Get the user type in a string form
     */
    public function getType()
    {
        return self::USER_ROLE_ADMIN;
    }
}
