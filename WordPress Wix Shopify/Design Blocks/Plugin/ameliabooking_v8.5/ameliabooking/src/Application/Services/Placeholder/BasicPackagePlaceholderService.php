<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Services\Placeholder;

use AmeliaBooking\Domain\Entity\User\AbstractUser;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class BasicPackagePlaceholderService
 *
 * @package AmeliaBooking\Application\Services\Placeholder
 */
class BasicPackagePlaceholderService extends AppointmentPlaceholderService
{
    /**
     * @return array
     *
     * @throws ContainerException
     */
    public function getEntityPlaceholdersDummyData($type)
    {
        return [];
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param array        $package
     * @param int          $bookingKey
     * @param string       $type
     * @param AbstractUser $customer
     * @param array        $allBookings
     * @param bool         $invoice
     * @param string       $notificationType
     *
     * @return array
     *
     * @throws ContainerValueNotFoundException
     * @throws ContainerException
     * @throws Exception
     */
    public function getPlaceholdersData(
        $package,
        $bookingKey = null,
        $type = null,
        $customer = null,
        $allBookings = null,
        $invoice = false,
        $notificationType = null
    ) {
        return [];
    }

    /**
     * @param array $entity
     *
     * @param string $subject
     * @param string $body
     * @param int    $userId
     * @return array
     */
    public function reParseContentForProvider($entity, $subject, $body, $userId)
    {
        return [
            'body'    => '',
            'subject' => '',
        ];
    }
}
