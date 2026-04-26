<?php

namespace AmeliaBooking\Application\Services\Tax;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Tax\Tax;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Common\Container;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class AbstractTaxApplicationService
 *
 * @package AmeliaBooking\Application\Services\Tax
 */
abstract class AbstractTaxApplicationService
{
    protected $container;

    /**
     * AbstractTaxApplicationService constructor.
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
     * @param Tax $tax
     *
     * @return boolean
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     */
    abstract public function add($tax);

    /**
     * @param Tax $tax
     *
     * @return boolean
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     */
    abstract public function update($tax);

    /**
     * @param Tax $tax
     *
     * @return boolean
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     */
    abstract public function delete($tax);

    /**
     * @return Collection
     */
    abstract public function getAll();
}
