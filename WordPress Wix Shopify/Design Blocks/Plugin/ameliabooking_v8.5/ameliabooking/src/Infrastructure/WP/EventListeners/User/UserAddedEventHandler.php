<?php

namespace AmeliaBooking\Infrastructure\WP\EventListeners\User;

use AmeliaBooking\Application\Commands\CommandResult;

/**
 * Class UserAddedEventHandler
 *
 * @package AmeliaBooking\Infrastructure\WP\EventListeners\User
 */
class UserAddedEventHandler
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
                // Set the user role
                $wpUser->set_role('wpamelia-' . $commandData['user']['type']);
                // Persist the changes
                wp_update_user($wpUser);
            }
        }
    }
}
