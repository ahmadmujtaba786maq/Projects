<?php

namespace AmeliaBooking\Application\Services\Tax;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Tax\Tax;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class StarterTaxApplicationService
 *
 * @package AmeliaBooking\Application\Services\Tax
 */
class StarterTaxApplicationService extends AbstractTaxApplicationService
{
    /**
     * @param Tax $tax
     *
     * @return boolean
     *
     * @throws ContainerValueNotFoundException
     */
    public function add($tax)
    {
        return true;
    }

    /**
     * @param Tax $tax
     *
     * @return boolean
     *
     * @throws ContainerValueNotFoundException
     */
    public function update($tax)
    {
        return true;
    }

    /**
     * @param Tax $tax
     *
     * @return boolean
     *
     * @throws ContainerValueNotFoundException
     */
    public function delete($tax)
    {
        return true;
    }

    /**
     * @return Collection
     */
    public function getAll()
    {
        return new Collection();
    }
}
