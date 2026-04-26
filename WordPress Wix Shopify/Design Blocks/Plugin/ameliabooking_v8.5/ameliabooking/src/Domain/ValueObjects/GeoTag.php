<?php

namespace AmeliaBooking\Domain\ValueObjects;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;

/**
 * Class GeoTag
 *
 * @package AmeliaBooking\Domain\ValueObjects
 */
final class GeoTag
{
    /**
     * @var float
     */
    private $latitude;

    /**
     * @var float
     */
    private $longitude;

    /**
     * GeoTag constructor.
     *
     * @param $latitude
     * @param $longitude
     *
     * @throws InvalidArgumentException
     */
    public function __construct($latitude, $longitude)
    {
        if (empty($latitude)) {
            throw new InvalidArgumentException("Latitude can't be empty");
        }
        $this->latitude = (float)$latitude;

        if (empty($longitude)) {
            throw new InvalidArgumentException("Longitude can't be empty");
        }
        $this->longitude = (float)$longitude;
    }

    /**
     * Return the latitude from the value object
     *
     * @return float
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Return the longitude from the value object
     *
     * @return float
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Return array with longitude and latitude from value object
     *
     * @return array
     */
    public function getValue()
    {
        return [
            'latitude'  => $this->getLatitude(),
            'longitude' => $this->getLongitude()
        ];
    }
}
