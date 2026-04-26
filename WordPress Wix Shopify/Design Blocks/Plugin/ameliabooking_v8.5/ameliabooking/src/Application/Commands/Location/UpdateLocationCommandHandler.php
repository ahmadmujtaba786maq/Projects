<?php

namespace AmeliaBooking\Application\Commands\Location;

use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Location\Location;
use AmeliaBooking\Domain\Factory\Location\LocationFactory;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Location\LocationRepository;

/**
 * Class UpdateLocationCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Location
 */
class UpdateLocationCommandHandler extends CommandHandler
{
    /**
     * @var array
     */
    public $mandatoryFields = [
        'name',
        'address',
        'phone',
        'latitude',
        'longitude'
    ];

    /**
     * @param UpdateLocationCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(UpdateLocationCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanWrite(Entities::LOCATIONS)) {
            throw new AccessDeniedException('You are not allowed to update location!');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        $locationArray = $command->getFields();

        $locationArray = apply_filters('amelia_before_location_updated_filter', $locationArray);

        do_action('amelia_before_location_updated', $locationArray);

        $location = LocationFactory::create($locationArray);
        if (!$location instanceof Location) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not update location.');

            return $result;
        }

        /** @var LocationRepository $locationRepository */
        $locationRepository = $this->container->get('domain.locations.repository');

        if ($locationRepository->update($command->getArg('id'), $location)) {
            $location->setId(new Id($command->getArg('id')));
            do_action('amelia_after_location_updated', $location->toArray());

            $result->setResult(CommandResult::RESULT_SUCCESS);
            $result->setMessage('Successfully updated location.');
            $result->setData(
                [
                Entities::LOCATION => $location->toArray()
                ]
            );
        }

        return $result;
    }
}
