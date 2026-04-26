<?php

namespace AmeliaBooking\Domain\Factory\Location;

use AmeliaBooking\Domain\Entity\Location\ProviderLocation;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;

/**
 * Class ProviderLocationFactory
 *
 * @package AmeliaBooking\Domain\Factory\Location
 */
class ProviderLocationFactory
{
    /**
     * @param $data
     *
     * @return ProviderLocation
     */
    public static function create($data)
    {
        $providerLocation = new ProviderLocation(
            new Id($data['userId']),
            new Id($data['locationId'])
        );

        if (isset($data['id'])) {
            $providerLocation->setId(new Id($data['id']));
        }

        return $providerLocation;
    }
}
