<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Services\Location;

/**
 * Class AbstractCurrentLocation
 *
 * @package AmeliaBooking\Application\Services\Location
 */
abstract class AbstractCurrentLocation
{
    /**
     * Get country ISO code by public IP address
     *
     * @param string $ipLocateApyKey
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    abstract public function getCurrentLocationCountryIso($ipLocateApyKey);
}
