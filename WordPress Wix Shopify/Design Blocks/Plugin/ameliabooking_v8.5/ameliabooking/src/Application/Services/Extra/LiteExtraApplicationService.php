<?php

namespace AmeliaBooking\Application\Services\Extra;

use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class LiteExtraApplicationService
 *
 * @package AmeliaBooking\Application\Services\Extra
 */
class LiteExtraApplicationService extends AbstractExtraApplicationService
{
    /**
     * @param Service $service
     *
     * @return void
     *
     * @throws ContainerValueNotFoundException
     */
    public function manageExtrasForServiceAdd($service)
    {
    }

    /**
     * @param Service $service
     *
     * @return void
     *
     * @throws ContainerValueNotFoundException
     */
    public function manageExtrasForServiceUpdate($service)
    {
    }
}
