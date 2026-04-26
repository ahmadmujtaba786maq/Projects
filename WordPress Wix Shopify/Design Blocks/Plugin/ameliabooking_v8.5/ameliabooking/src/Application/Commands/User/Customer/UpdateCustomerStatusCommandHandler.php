<?php

namespace AmeliaBooking\Application\Commands\User\Customer;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\User\ProviderRepository;

/**
 * Class UpdateCustomerStatusCommandHandler
 *
 * @package AmeliaBooking\Application\Common
 */
class UpdateCustomerStatusCommandHandler extends CommandHandler
{
    /**
     * @var array
     */
    public $mandatoryFields = [
        'status',
    ];

    /**
     * @param UpdateCustomerStatusCommand $command
     *
     * @return CommandResult
     * @throws \InvalidArgumentException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function handle(UpdateCustomerStatusCommand $command)
    {
        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var ProviderRepository $providerRepository */
        $providerRepository = $this->container->get('domain.users.providers.repository');

        $status = $command->getField('status');

        $providerRepository->updateFieldById($command->getArg('id'), $status, 'status');

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully updated user');
        $result->setData(true);

        return $result;
    }
}
