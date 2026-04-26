<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Services\Notification;

use AmeliaBooking\Domain\Entity\User\Customer;
use AmeliaBooking\Domain\Entity\User\Provider;
use Exception;
use AmeliaBooking\Domain\Entity\Notification\Notification;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Interface AbstractWhatsAppNotificationService
 *
 * @package AmeliaBooking\Application\Services\Notification
 */
abstract class AbstractWhatsAppNotificationService extends AbstractNotificationService
{
    /**
     * @param array $appointmentArray
     * @param Notification $notification
     * @param bool $logNotification
     * @param null $bookingKey
     *
     * @return mixed
     */
    public function sendNotification($appointmentArray, $notification, $logNotification, $bookingKey = null, $allBookings = null, $invoice = [])
    {
        return null;
    }

    /**
     * @throws ContainerException
     * @throws Exception
     */
    public function sendBirthdayGreetingNotifications()
    {
        return null;
    }

    public function checkRequiredFields()
    {
        return false;
    }

    public function getTemplates()
    {
        return [];
    }

    /**
     * @param $sendTo
     * @param Notification $notification
     * @param $dummyData
     * @return mixed
     */
    public function sendTestNotification($sendTo, $notification, $dummyData)
    {
        return null;
    }

    /**
     * @param Customer $customer
     * @param string   $locale
     *
     * @return void
     *
     * @throws ContainerValueNotFoundException
     * @throws \Slim\Exception\ContainerException
     * @throws Exception
     */
    public function sendRecoveryWhatsApp($customer, $locale, $cabinetType)
    {
    }

    /**
     * @param Provider $provider
     *
     * @param $plainPassword
     * @return void
     *
     */
    public function sendEmployeePanelAccess($provider, $plainPassword)
    {
    }

    /**
     * @param string $to
     */
    public function sendMessage($to)
    {
    }

    public function sendUndeliveredNotifications()
    {
    }
}
