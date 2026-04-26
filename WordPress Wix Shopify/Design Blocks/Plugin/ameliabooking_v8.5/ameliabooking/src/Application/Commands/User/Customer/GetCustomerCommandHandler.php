<?php

namespace AmeliaBooking\Application\Commands\User\Customer;

use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\User\UserApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Domain\Repository\User\UserRepositoryInterface;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;

/**
 * Class GetCustomerCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\User\Customer
 */
class GetCustomerCommandHandler extends CommandHandler
{
    /**
     * @param GetCustomerCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws QueryExecutionException
     */
    public function handle(GetCustomerCommand $command)
    {
        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var UserApplicationService $userAS */
        $userAS = $this->getContainer()->get('application.user.service');

        if (!$command->getPermissionService()->currentUserCanRead(Entities::CUSTOMERS)) {
            if ($command->getToken()) {
                if ($userAS->getAuthenticatedUser($command->getToken(), false, 'providerCabinet') === null) {
                    $result->setResult(CommandResult::RESULT_ERROR);
                    $result->setMessage('Could not retrieve user');
                    $result->setData(
                        [
                            'reauthorize' => true
                        ]
                    );

                    return $result;
                }
            } else {
                throw new AccessDeniedException('You are not allowed to read user');
            }
        }

        /** @var UserRepositoryInterface $userRepository */
        $userRepository = $this->getContainer()->get('domain.users.repository');

        /** @var AbstractUser $user */
        $user = $userRepository->getById((int)$command->getField('id'));

        $userArray = $user->toArray();

        $userArray = apply_filters('amelia_get_customer_filter', $userArray);

        do_action('amelia_get_customer', $userArray);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved user');
        $result->setData(
            [
                Entities::USER => $userArray
            ]
        );

        return $result;
    }
}
