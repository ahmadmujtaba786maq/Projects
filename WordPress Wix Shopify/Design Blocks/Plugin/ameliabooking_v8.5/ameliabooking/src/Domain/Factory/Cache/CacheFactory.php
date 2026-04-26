<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Factory\Cache;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Cache\Cache;
use AmeliaBooking\Domain\ValueObjects\Json;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\String\Name;

/**
 * Class CacheFactory
 *
 * @package AmeliaBooking\Domain\Factory\Cache
 */
class CacheFactory
{
    /**
     * @param $data
     *
     * @return Cache
     * @throws InvalidArgumentException
     */
    public static function create($data)
    {
        $cache = new Cache(
            new Name($data['name'])
        );

        if (isset($data['id'])) {
            $cache->setId(new Id($data['id']));
        }

        if (!empty($data['paymentId'])) {
            $cache->setPaymentId(new Id($data['paymentId']));
        }

        if (!empty($data['data'])) {
            $cache->setData(new Json($data['data']));
        }

        return $cache;
    }
}
