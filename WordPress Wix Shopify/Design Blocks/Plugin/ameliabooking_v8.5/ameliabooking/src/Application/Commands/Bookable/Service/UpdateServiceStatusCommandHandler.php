<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Bookable\Service;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;

/**
 * Class UpdateServiceStatusCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Bookable\Service
 */
class UpdateServiceStatusCommandHandler extends CommandHandler
{
    /**
     * @var array
     */
    public $mandatoryFields = [
        'status',
    ];

    /**
     * @param UpdateServiceStatusCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws QueryExecutionException
     */
    public function handle(UpdateServiceStatusCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanWrite(Entities::SERVICES)) {
            throw new AccessDeniedException('You are not allowed to update service.');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');

        do_action('amelia_before_service_status_updated', $command->getArg('id'), $command->getField('status'));

        $serviceRepository->updateStatusById(
            $command->getArg('id'),
            $command->getField('status')
        );

        do_action('amelia_after_service_status_updated', $command->getArg('id'), $command->getField('status'));

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully updated service');
        $result->setData(true);

        return $result;
    }
}
