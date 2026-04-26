<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\WooCommerce;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Entities;

/**
 * Class Cache
 *
 * @package AmeliaBooking\Infrastructure\WP\Integrations\WooCommerce
 */
class Cache
{
    /** @var array */
    protected static $cache = [
        Entities::APPOINTMENT => [],
        Entities::EVENT       => [],
        Entities::PACKAGE     => [],
    ];

    /** @var Collection */
    protected static $taxes = null;

    /**
     * Add entities to cache.
     *
     * @param string $type
     * @param array  $data
     */
    public static function add($type, $data)
    {
        self::$cache[$type] = $data;
    }

    /**
     * Get entity from cache
     *
     * @param array $data
     *
     * @return mixed
     */
    public static function get($data)
    {
        switch ($data['type']) {
            case (Entities::APPOINTMENT):
                return array_key_exists($data['providerId'], self::$cache[$data['type']]) &&
                array_key_exists($data['serviceId'], self::$cache[$data['type']][$data['providerId']]) ?
                    self::$cache[$data['type']][$data['providerId']][$data['serviceId']] : null;

            case (Entities::EVENT):
                return array_key_exists($data['eventId'], self::$cache[$data['type']]) ?
                    self::$cache[$data['type']][$data['eventId']] : null;

            case (Entities::PACKAGE):
                return array_key_exists($data['packageId'], self::$cache[$data['type']]) ?
                    self::$cache[$data['type']][$data['packageId']] : null;
        }
    }

    /**
     * Get entity from cache
     *
     * @return mixed
     */
    public static function getAll()
    {
        return self::$cache;
    }

    /**
     * @param Collection $taxes
     *
     * @return void
     */
    public static function setTaxes($taxes)
    {
        self::$taxes = $taxes;
    }

    /**
     * @return Collection
     */
    public static function getTaxes()
    {
        return self::$taxes;
    }
}
