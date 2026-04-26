<?php

namespace AmeliaBooking\Application\Commands\Location;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Location\Location;
use AmeliaBooking\Domain\Repository\Location\LocationRepositoryInterface;

/**
 * Class GetLocationCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Location
 */
class GetLocationCommandHandler extends CommandHandler
{
    /**
     * @param GetLocationCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(GetLocationCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanRead(Entities::LOCATIONS)) {
            throw new AccessDeniedException('You are not allowed to read location');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var LocationRepositoryInterface $locationRepository */
        $locationRepository = $this->getContainer()->get('domain.locations.repository');

        $location = $locationRepository->getById($command->getArg('id'));

        if (!$location instanceof Location) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not retrieve location');

            return $result;
        }

        $locationArray = $location->toArray();

        $locationArray = apply_filters('amelia_get_location_filter', $locationArray);

        do_action('amelia_get_location', $locationArray);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved location.');
        $result->setData(
            [
            Entities::LOCATION => $locationArray
            ]
        );

        return $result;
    }
}
