<?php

namespace AmeliaBooking\Application\Commands\User\Customer;

use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\User\CustomerApplicationService;
use AmeliaBooking\Application\Services\User\UserApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Commands\CommandHandler;

/**
 * Class AddCustomerCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\User\Customer
 */
class AddCustomerCommandHandler extends CommandHandler
{
    public $mandatoryFields = [
        'firstName',
        'lastName',
        'email'
    ];

    /**
     * @param AddCustomerCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(AddCustomerCommand $command)
    {
        /** @var CommandResult $result */
        $result = new CommandResult();

        /** @var UserApplicationService $userAS */
        $userAS = $this->getContainer()->get('application.user.service');

        if (!$command->getPermissionService()->currentUserCanWrite(Entities::CUSTOMERS)) {
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
                throw new AccessDeniedException('You are not allowed to perform this action!');
            }
        }

        /** @var CustomerApplicationService $customerAS */
        $customerAS = $this->container->get('application.user.customer.service');

        $this->checkMandatoryFields($command);

        if ($command->getField('externalId') === -1) {
            $command->setField('externalId', null);
        }

        $userData = $command->getFields();

        $userData['type'] = 'customer';

        $userData = apply_filters('amelia_before_customer_added_filter', $userData);

        do_action('amelia_before_customer_added', $userData);

        $response = $customerAS->createCustomer($userData);

        do_action('amelia_after_customer_added', $response ? $response->getData() : null);

        return $response;
    }
}
