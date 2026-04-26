<?php

namespace AmeliaBooking\Application\Services\Extra;

use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Infrastructure\Common\Container;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class AbstractExtraApplicationService
 *
 * @package AmeliaBooking\Application\Services\Extra
 */
abstract class AbstractExtraApplicationService
{
    protected $container;

    /**
     * AbstractExtraApplicationService constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param Service $service
     *
     * @throws ContainerValueNotFoundException
     */
    abstract public function manageExtrasForServiceAdd($service);

    /**
     * @param Service $service
     *
     * @return void
     *
     * @throws ContainerValueNotFoundException
     */
    abstract public function manageExtrasForServiceUpdate($service);
}
