<?php

namespace AmeliaBooking\Application\Services\Resource;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Infrastructure\Common\Container;

/**
 * Class AbstractResourceApplicationService
 *
 * @package AmeliaBooking\Application\Services\Resource
 */
abstract class AbstractResourceApplicationService
{
    /** @var Container $container */
    protected $container;

    /**
     * AbstractResourceApplicationService constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param array $criteria
     *
     * @return Collection
     */
    abstract public function getAll($criteria);
}
