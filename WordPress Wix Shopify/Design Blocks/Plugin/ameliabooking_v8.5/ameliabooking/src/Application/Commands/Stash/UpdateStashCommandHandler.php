<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Stash;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Stash\StashApplicationService;
use AmeliaBooking\Application\Services\User\UserApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\AuthorizationException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class UpdateStashCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Coupon
 */
class UpdateStashCommandHandler extends CommandHandler
{
    /**
     * @param UpdateStashCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(UpdateStashCommand $command)
    {
        /** @var StashApplicationService $stashApplicationService */
        $stashApplicationService = $this->container->get('application.stash.service');

        /** @var UserApplicationService $userAS */
        $userAS = $this->container->get('application.user.service');

        try {
            /** @var AbstractUser $currentUser */
            $currentUser = $userAS->authorization(
                $command->getToken() ?: null,
                Entities::PROVIDER
            );
        } catch (AuthorizationException $e) {
            $currentUser =  null;
        }

        if (
            $currentUser && (
                $currentUser->getType() === AbstractUser::USER_ROLE_ADMIN ||
                $currentUser->getType() === AbstractUser::USER_ROLE_PROVIDER ||
                $currentUser->getType() === AbstractUser::USER_ROLE_MANAGER
            )
        ) {
            $stashApplicationService->setStash();
        }

        $result = new CommandResult();

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully updated stash');
        $result->setData(true);

        return $result;
    }
}
