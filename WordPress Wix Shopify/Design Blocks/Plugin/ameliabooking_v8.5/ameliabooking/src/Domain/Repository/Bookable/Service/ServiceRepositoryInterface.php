<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Repository\Bookable\Service;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Repository\BaseRepositoryInterface;

/**
 * Interface ServiceRepositoryInterface
 *
 * @package AmeliaBooking\Domain\Repository\Bookable\Service
 */
interface ServiceRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @param $serviceId
     * @param $userId
     *
     * @return Collection
     */
    public function getProviderServicesWithExtras($serviceId, $userId);

    /**
     * @param $serviceId
     *
     * @return mixed
     */
    public function getByIdWithExtras($serviceId);
}
