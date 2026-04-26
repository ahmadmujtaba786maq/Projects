<?php

namespace AmeliaBooking\Application\Commands\Location;

use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Factory\Location\ProviderLocationFactory;
use AmeliaBooking\Domain\ValueObjects\String\Status;
use AmeliaBooking\Infrastructure\Repository\Location\LocationRepository;
use AmeliaBooking\Infrastructure\Repository\Location\ProviderLocationRepository;
use AmeliaBooking\Infrastructure\Repository\User\ProviderRepository;

/**
 * Class UpdateLocationStatusCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Location
 */
class UpdateLocationStatusCommandHandler extends CommandHandler
{
    /**
     * @var array
     */
    public $mandatoryFields = [
        'status',
    ];

    /**
     * @param UpdateLocationStatusCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(UpdateLocationStatusCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanWrite(Entities::LOCATIONS)) {
            throw new AccessDeniedException('You are not allowed to update location!');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var LocationRepository $locationRepository */
        $locationRepository = $this->getContainer()->get('domain.locations.repository');

        $locationRepository->beginTransaction();

        $status = $command->getField('status');

        do_action('amelia_before_location_status_updated', $status, $command->getArg('id'));

        $locationRepository->updateStatusById(
            $command->getArg('id'),
            $status
        );

        if ($command->getField('status') === Status::VISIBLE) {
            /** @var ProviderRepository $providerRepository */
            $providerRepository = $this->container->get('domain.users.providers.repository');

            /** @var ProviderLocationRepository $providerLocationRepo */
            $providerLocationRepo = $this->container->get('domain.bookable.service.providerLocation.repository');

            /** @var Provider $provider */
            foreach ($providerRepository->getAll()->getItems() as $provider) {
                if (!$provider->getLocationId()) {
                    $providerLocation = ProviderLocationFactory::create(
                        [
                        'userId'     => $provider->getId()->getValue(),
                        'locationId' => $command->getArg('id')
                        ]
                    );

                    $providerLocationRepo->add($providerLocation);
                }
            }
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully updated location');
        $result->setData(true);

        $locationRepository->commit();

        do_action('amelia_after_location_status_updated', $status, $command->getArg('id'));

        return $result;
    }
}
