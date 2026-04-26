<?php

namespace AmeliaBooking\Domain\Services\Location;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Location\Location;
use AmeliaBooking\Domain\ValueObjects\String\Status;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class LocationService
 *
 * @package AmeliaBooking\Domain\Services\Location
 */
class LocationService
{
    /**
     * @param Collection $locations
     *
     * @return boolean
     *
     * @throws ContainerValueNotFoundException
     */
    public function hasVisibleLocations($locations)
    {
        /** @var Location $location */
        foreach ($locations->getItems() as $location) {
            if ($location->getStatus()->getValue() === Status::VISIBLE) {
                return true;
            }
        }

        return false;
    }
}
