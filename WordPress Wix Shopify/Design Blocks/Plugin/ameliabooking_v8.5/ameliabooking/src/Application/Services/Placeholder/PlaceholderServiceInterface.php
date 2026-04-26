<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Services\Placeholder;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Interface PlaceholderServiceInterface
 *
 * @package AmeliaBooking\Application\Services\Placeholder
 */
interface PlaceholderServiceInterface
{
    /**
     *
     * @return array
     *
     * @throws ContainerException
     */
    public function getEntityPlaceholdersDummyData($type);

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param array        $data
     * @param int          $bookingKey
     * @param string       $type
     * @param AbstractUser $customer
     * @param array        $allBookings
     * @param bool         $invoice
     * @param string       $notificationType
     *
     * @return array
     *
     * @throws InvalidArgumentException
     * @throws ContainerValueNotFoundException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws ContainerException
     * @throws Exception
     */
    public function getPlaceholdersData(
        $data,
        $bookingKey = null,
        $type = null,
        $customer = null,
        $allBookings = null,
        $invoice = false,
        $notificationType = null
    );

    /**
     * @param array $bookingArray
     * @param array $entity
     * @param boolean $invoice
     *
     * @return array
     */
    public function getAmountData(&$bookingArray, $entity, $invoice = false);


    /**
     * @param array $reservationData
     *
     * @return array
     *
     * @throws InvalidArgumentException
     * @throws ContainerValueNotFoundException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws ContainerException
     * @throws Exception
     */
    public function getInvoicePlaceholdersData($reservationData);
}
