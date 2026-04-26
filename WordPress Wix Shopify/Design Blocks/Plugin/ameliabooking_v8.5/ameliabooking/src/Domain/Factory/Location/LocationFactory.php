<?php

namespace AmeliaBooking\Domain\Factory\Location;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Location\Location;
use AmeliaBooking\Domain\ValueObjects\Json;
use AmeliaBooking\Domain\ValueObjects\String\Status;
use AmeliaBooking\Domain\ValueObjects\Picture;
use AmeliaBooking\Domain\ValueObjects\String\Address;
use AmeliaBooking\Domain\ValueObjects\String\Description;
use AmeliaBooking\Domain\ValueObjects\GeoTag;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\String\Name;
use AmeliaBooking\Domain\ValueObjects\String\Phone;
use AmeliaBooking\Domain\ValueObjects\String\Url;

/**
 * Class LocationFactory
 *
 * @package AmeliaBooking\Domain\Factory\Location
 */
class LocationFactory
{
    /**
     * @param $data
     *
     * @return Location
     * @throws InvalidArgumentException
     */
    public static function create($data)
    {
        $location = new Location();

        if (isset($data['id'])) {
            $location->setId(new Id($data['id']));
        }

        if (isset($data['name'])) {
            $location->setName(new Name($data['name']));
        }

        if (isset($data['address'])) {
            $location->setAddress(new Address($data['address']));
        }

        if (isset($data['phone'])) {
            $location->setPhone(new Phone($data['phone']));
        }

        if (isset($data['latitude'], $data['longitude'])) {
            $location->setCoordinates(new GeoTag($data['latitude'], $data['longitude']));
        }

        if (isset($data['description'])) {
            $location->setDescription(new Description($data['description']));
        }

        if (isset($data['status'])) {
            $location->setStatus(new Status($data['status']));
        }

        if (!empty($data['pictureFullPath']) && !empty($data['pictureThumbPath'])) {
            $location->setPicture(new Picture($data['pictureFullPath'], $data['pictureThumbPath']));
        }

        if (isset($data['pin'])) {
            $location->setPin(new Url($data['pin']));
        }

        if (!empty($data['translations'])) {
            $location->setTranslations(new Json($data['translations']));
        }

        return $location;
    }
}
