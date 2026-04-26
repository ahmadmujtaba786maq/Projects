<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Services\WebHook;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use Exception;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class AbstractWebHookApplicationService
 *
 * @package AmeliaBooking\Application\Services\WebHook
 */
abstract class AbstractWebHookApplicationService
{
    /** @var Container $container */
    protected $container;

    /**
     * WebHookApplicationService constructor.
     *
     * @param Container $container
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param string   $action
     * @param array    $reservation
     * @param array    $bookings
     *
     * @return void
     * @throws InvalidArgumentException
     * @throws ContainerValueNotFoundException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws Exception
     */
    abstract public function process($action, $reservation, $bookings);
}
