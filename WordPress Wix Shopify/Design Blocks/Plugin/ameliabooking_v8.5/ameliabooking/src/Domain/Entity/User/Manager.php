<?php

namespace AmeliaBooking\Domain\Entity\User;

/**
 * Class Manager
 *
 * @package AmeliaBooking\Domain\Entity\User
 */
class Manager extends AbstractUser
{
    /**
     * Get the user type in a string form
     */
    public function getType()
    {
        return self::USER_ROLE_MANAGER;
    }
}
