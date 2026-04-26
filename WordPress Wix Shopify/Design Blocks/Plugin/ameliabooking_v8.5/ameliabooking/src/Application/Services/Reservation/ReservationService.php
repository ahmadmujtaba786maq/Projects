<?php

namespace AmeliaBooking\Application\Services\Reservation;

use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Services\Reservation\ReservationServiceInterface;
use AmeliaBooking\Infrastructure\Common\Container;
use InvalidArgumentException;

/**
 * Class ReservationService
 *
 * @package AmeliaBooking\Application\Services\Reservation
 */
class ReservationService
{
    /** @var Container $container */
    protected $container;

    /**
     * ReservationService constructor.
     *
     * @param Container $container
     *
     * @throws InvalidArgumentException
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $type
     * @return ReservationServiceInterface
     */
    public function get($type)
    {
        $entityType = $type === Entities::CART ? Entities::APPOINTMENT : $type;

        return $this->container->get("application.reservation.{$entityType}.service");
    }
}
