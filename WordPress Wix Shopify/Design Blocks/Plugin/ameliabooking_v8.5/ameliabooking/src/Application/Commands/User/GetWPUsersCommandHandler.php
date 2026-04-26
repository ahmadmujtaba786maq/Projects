<?php

namespace AmeliaBooking\Application\Commands\User;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Infrastructure\Repository\User\WPUserRepository;
use AmeliaBooking\Infrastructure\WP\UserService\UserService;

/**
 * Class GetWPUsersCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\User
 */
class GetWPUsersCommandHandler extends CommandHandler
{
    /**
     * @param GetWPUsersCommand $command
     *
     * @return CommandResult
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(GetWPUsersCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanRead(Entities::EMPLOYEES)) {
            throw new AccessDeniedException('You are not allowed to read employees.');
        }

        if (!$command->getPermissionService()->currentUserCanRead(Entities::CUSTOMERS)) {
            throw new AccessDeniedException('You are not allowed to read customers.');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var UserService $userService */
        $userService = $this->container->get('users.service');

        $adminIds = $userService->getWpUserIdsByRoles(['administrator']);

        /** @var WPUserRepository $wpUserRepository */
        $wpUserRepository = $this->getContainer()->get('domain.wpUsers.repository');

        $wpUsers = $wpUserRepository->getAllNonRelatedWPUsers($command->getFields(), $adminIds);

        $wpUsers = apply_filters('amelia_get_wp_users_filter', $wpUsers);

        do_action('amelia_get_wp_users', $wpUsers);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved users.');

        $result->setData(
            [
            Entities::USER . 's' => $wpUsers
            ]
        );

        return $result;
    }
}
