<?php

namespace AmeliaBooking\Infrastructure\WP\EventListeners\User;

use AmeliaBooking\Application\Commands\CommandResult;

/**
 * Class UserDeletedEventHandler
 *
 * @package AmeliaBooking\Infrastructure\WP\EventListeners\User
 */
class UserDeletedEventHandler
{
    /**
     * @param CommandResult $commandResult
     */
    public static function handle($commandResult)
    {
        // Performing any actions only if the result is successful
        if ($commandResult->getResult() === CommandResult::RESULT_SUCCESS) {
            // Check if a WP user is linked
            $commandData = $commandResult->getData();
            if (!empty($commandData['user']['externalId'])) {
                $wpUser = new \WP_User((int)$commandData['user']['externalId']);
                // Remove the user role
                $wpUser->remove_role('wpamelia-' . $commandData['user']['type']);
                // Persist the changes
                wp_update_user($wpUser);
            }
        }
    }
}
