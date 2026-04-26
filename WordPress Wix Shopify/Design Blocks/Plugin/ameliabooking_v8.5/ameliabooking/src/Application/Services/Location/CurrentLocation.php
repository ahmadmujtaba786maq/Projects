<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Services\Location;

/**
 * Class CurrentLocation
 *
 * @package AmeliaBooking\Application\Services\Location
 */
class CurrentLocation extends AbstractCurrentLocation
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
    public function getCurrentLocationCountryIso($ipLocateApyKey)
    {
        try {
            $curlHandle = curl_init();

            curl_setopt(
                $curlHandle,
                CURLOPT_URL,
                'https://www.iplocate.io/api/lookup/' . $_SERVER['REMOTE_ADDR'] . ($ipLocateApyKey ? ('?apikey=' . $ipLocateApyKey) : '')
            );

            curl_setopt($curlHandle, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curlHandle, CURLOPT_USERAGENT, 'Amelia');
            $result = json_decode(curl_exec($curlHandle));
            curl_close($curlHandle);

            return !isset($result->country_code) ? '' : strtolower($result->country_code);
        } catch (\Exception $e) {
            return '';
        }
    }
}
