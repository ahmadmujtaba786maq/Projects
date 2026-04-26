<?php

namespace AmeliaBooking\Application\Commands\User;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Infrastructure\WP\UserService\UserService;

/**
 * Class LogoutCabinetCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\User
 */
class LogoutCabinetCommandHandler extends CommandHandler
{
    /**
     * @param LogoutCabinetCommand $command
     *
     * @return CommandResult
     */
    public function handle(LogoutCabinetCommand $command)
    {
        $result = new CommandResult();

        /** @var UserService $userService */
        $userService = $this->container->get('users.service');

        do_action('amelia_before_logout_user');

        $userService->logoutWordPressUser();

        do_action('amelia_after_logout_user');

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setData([]);

        return $result;
    }
}
