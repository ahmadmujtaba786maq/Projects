<?php

namespace AmeliaBooking\Application\Commands\User\Provider;

use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\User\ProviderRepository;

/**
 * Class UpdateProviderStatusCommandHandler
 *
 * @package AmeliaBooking\Application\Common
 */
class UpdateProviderStatusCommandHandler extends CommandHandler
{
    /**
     * @var array
     */
    public $mandatoryFields = [
        'status',
    ];

    /**
     * @param UpdateProviderStatusCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerException
     * @throws \InvalidArgumentException
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(UpdateProviderStatusCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanWrite(Entities::EMPLOYEES)) {
            throw new AccessDeniedException('You are not allowed to update employee.');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var ProviderRepository $providerRepository */
        $providerRepository = $this->container->get('domain.users.providers.repository');

        $status = $command->getField('status');

        do_action('amelia_before_provider_status_updated', $status, $command->getArg('id'));

        $providerRepository->updateFieldById($command->getArg('id'), $status, 'status');

        do_action('amelia_after_provider_status_updated', $status, $command->getArg('id'));

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully updated user');
        $result->setData(true);

        return $result;
    }
}
