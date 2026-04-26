<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Services\Placeholder;

use AmeliaBooking\Application\Services\Helper\HelperService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Factory\User\UserFactory;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;
use Interop\Container\Exception\ContainerException;

/**
 * Class AppointmentsPlaceholderService
 *
 * @package AmeliaBooking\Application\Services\Placeholder
 */
class AppointmentsPlaceholderService extends AppointmentPlaceholderService
{
    /**
     *
     * @return array
     *
     * @throws ContainerException
     */
    public function getEntityPlaceholdersDummyData($type)
    {
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        /** @var HelperService $helperService */
        $helperService = $this->container->get('application.helper.service');

        $companySettings = $settingsService->getCategorySettings('company');

        $dateFormat = $settingsService->getSetting('wordpress', 'dateFormat');
        $timeFormat = $settingsService->getSetting('wordpress', 'timeFormat');

        $timestamp = date_create()->getTimestamp();

        $this->getPlaceholdersData(
            [
                'recurring' => [],
            ],
            null,
            $type,
            [
                'firstName' => 'John Doe',
                'lastName'  => 'John Doe',
                'email'     => 'johndoe@example.com',
                'phone'     => '(555) 555-1234',
            ],
            false
        );


        return [
            'appointment_id'          => '1',
            'appointment_date'        => date_i18n($dateFormat, $timestamp),
            'appointment_date_time'   => date_i18n($dateFormat . ' ' . $timeFormat, $timestamp),
            'appointment_start_time'  => date_i18n($timeFormat, $timestamp),
            'appointment_end_time'    => date_i18n($timeFormat, date_create('1 hour')->getTimestamp()),
            'appointment_notes'       => 'Appointment note',
            'appointment_price'       => $helperService->getFormattedPrice(100),
            'payment_due_amount'      => $helperService->getFormattedPrice(80),
            'appointment_cancel_url'  => 'http://cancel_url.com',
            'zoom_join_url'           => $type === 'email' ?
                '<a href="#">' . BackendStrings::getCommonStrings()['zoom_click_to_join'] . '</a>' : 'https://join_zoom_link.com',
            'zoom_host_url'           => $type === 'email' ?
                '<a href="#">' . BackendStrings::getCommonStrings()['zoom_click_to_start'] . '</a>' : 'https://start_zoom_link.com',
            'google_meet_url'         => $type === 'email' ?
                '<a href="#">' . BackendStrings::getCommonStrings()['google_meet_join'] . '</a>' : 'https://join_google_meet_link.com',
            'lesson_space_url'        => $type === 'email' ?
                '<a href="#">' . BackendStrings::getCommonStrings()['lesson_space_join'] . '</a>' : 'https://lessonspace.com/room-id',
            'microsoft_teams_url'     => $type === 'email' ?
                '<a href="#">' . BackendStrings::getCommonStrings()['microsoft_teams_join'] . '</a>' : 'https://join_microsoft_teams_link.com',
            'appointment_duration'    => $helperService->secondsToNiceDuration(1800),
            'appointment_deposit_payment'     => $helperService->getFormattedPrice(20),
            'appointment_status'      => BackendStrings::getCommonStrings()['approved'],
            'category_name'           => 'Category Name',
            'service_description'     => 'Service Description',
            'reservation_description' => 'Service Description',
            'service_duration'        => $helperService->secondsToNiceDuration(5400),
            'service_name'            => 'Service Name',
            'reservation_name'        => 'Service Name',
            'service_price'           => $helperService->getFormattedPrice(100),
            'service_extras'          => 'Extra1, Extra2, Extra3'
        ];
    }

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
     * @throws ContainerException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getPlaceholdersData(
        $data,
        $bookingKey = null,
        $type = null,
        $customer = null,
        $allBookings = null,
        $invoice = false,
        $notificationType = null
    ) {
        $providersData = [];

        foreach ($data['recurring'] as $item) {
            $providersData[$item['appointment']['providerId']][] = $item;
        }

        foreach ($providersData as $providerId => $providerAppointmentsData) {
            $providersData[$providerId] = $this->getRecurringAppointmentsData(
                array_merge($data, ['recurring' => $providerAppointmentsData]),
                $bookingKey,
                $type,
                'cart',
                0
            );
        }

        return array_merge(
            $this->getCompanyData($bookingKey !== null ? $data['bookings'][$bookingKey]['info'] : null),
            $this->getCustomersData(
                $data,
                $type,
                0,
                $customer ?: UserFactory::create($data['customer'])
            ),
            $this->getRecurringAppointmentsData($data, $bookingKey, $type, 'cart'),
            [
                'icsFiles'              => !empty($data['icsFiles']) ? $data['icsFiles'] : [],
                'providersAppointments' => $providersData,
            ]
        );
    }

    /**
     * @param array $entity
     *
     * @param string $subject
     * @param string $body
     * @param int    $userId
     * @return array
     *
     * @throws NotFoundException
     * @throws QueryExecutionException
     */
    public function reParseContentForProvider($entity, $subject, $body, $userId)
    {
        $employeeSubject = $subject;

        $employeeBody = $body;

        foreach ($entity['recurring'] as $recurringData) {
            if ($recurringData['appointment']['providerId'] === $userId) {
                $employeeData = $this->getEmployeeData($recurringData['appointment']);

                $employeeSubject = $this->applyPlaceholders(
                    $subject,
                    $employeeData
                );

                $employeeBody = $this->applyPlaceholders(
                    $body,
                    $employeeData
                );
            }
        }

        return [
            'body'    => $employeeBody,
            'subject' => $employeeSubject,
        ];
    }
}
