<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Services\WebHook;

use AmeliaBooking\Application\Services\Booking\BookingApplicationService;
use AmeliaBooking\Application\Services\Helper\HelperService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use Exception;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class WebHookApplicationService
 *
 * @package AmeliaBooking\Application\Services\WebHook
 */
class WebHookApplicationService extends AbstractWebHookApplicationService
{
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
    public function process($action, $reservation, $bookings)
    {
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');
        /** @var BookingApplicationService $bookingApplicationService */
        $bookingApplicationService = $this->container->get('application.booking.booking.service');

        do_action('Amelia' . ucwords($action), $reservation, $bookings, $this->container);

        /** @var HelperService $helperService */
        $helperService = $this->container->get('application.helper.service');

        $reservationEntity = !empty($bookings) ? $bookingApplicationService->getReservationEntity($reservation) : null;

        $affectedBookingEntitiesArray = [];

        foreach ((array)$bookings as $booking) {
            /** @var CustomerBooking $bookingEntity */
            $bookingEntity = $bookingApplicationService->getBookingEntity($booking);

            $bookingEntityArray = $bookingEntity->toArray();

            if (isset($booking['isRecurringBooking'])) {
                $bookingEntityArray['isRecurringBooking'] = $booking['isRecurringBooking'];

                $bookingEntityArray['isPackageBooking'] = $booking['isPackageBooking'];

                $bookingEntityArray['isPackageAppointment'] = isset($booking['isPackageAppointment']) ? $booking['isPackageAppointment'] : null;
            }

            $affectedBookingEntitiesArray[] = $bookingEntityArray;
        }

        $reservationEntityArray = !empty($reservationEntity) ? $reservationEntity->toArray() : $reservation;

        if (!empty($reservation['initialAppointmentDateTime'])) {
            $reservationEntityArray['initialAppointmentDateTime'] = $reservation['initialAppointmentDateTime'];
        }

        switch ($reservation['type']) {
            case Entities::APPOINTMENT:
                if (isset($reservationEntityArray['provider']['googleCalendar']['token'])) {
                    unset($reservationEntityArray['provider']['googleCalendar']['token']);
                }

                if (isset($reservationEntityArray['provider']['outlookCalendar']['token'])) {
                    unset($reservationEntityArray['provider']['outlookCalendar']['token']);
                }

                break;

            case Entities::EVENT:
                break;
        }

        foreach ($affectedBookingEntitiesArray as $key => $booking) {
            if ($booking['customFields'] && json_decode($booking['customFields'], true) !== null) {
                $affectedBookingEntitiesArray[$key]['customFields'] = json_decode($booking['customFields'], true);
            }

            $affectedBookingEntitiesArray[$key]['cancelUrl'] = !empty($booking['token']) ?
                AMELIA_ACTION_URL .
                '/bookings/cancel/' . $booking['id'] .
                '&token=' . $booking['token'] .
                "&type={$reservation['type']}" : '';

            $info = !empty($booking['info']) ?
                json_decode($booking['info'], true) : null;

            $affectedBookingEntitiesArray[$key]['customerPanelUrl'] = $helperService->getCustomerCabinetUrl(
                $booking['customer']['email'],
                'email',
                null,
                null,
                isset($info['locale']) ? $info['locale'] : ''
            );

            $affectedBookingEntitiesArray[$key]['infoArray'] = $info;
        }

        foreach (!empty($reservationEntityArray['bookings']) ? $reservationEntityArray['bookings'] : [] as $key => $booking) {
            if ($booking['customFields'] && json_decode($booking['customFields'], true) !== null) {
                $reservationEntityArray['bookings'][$key]['customFields'] = json_decode(
                    $booking['customFields'],
                    true
                );
            }

            $reservationEntityArray['bookings'][$key]['cancelUrl'] = !empty($booking['token']) ?
                AMELIA_ACTION_URL .
                '/bookings/cancel/' . $booking['id'] .
                '&token=' . $booking['token'] .
                "&type={$reservation['type']}" : '';

            $info = !empty($booking['info']) ?
                json_decode($booking['info'], true) : null;

            $reservationEntityArray['bookings'][$key]['customerPanelUrl'] = $helperService->getCustomerCabinetUrl(
                $booking['customer']['email'],
                'email',
                null,
                null,
                isset($info['locale']) ? $info['locale'] : ''
            );

            $reservationEntityArray['bookings'][$key]['infoArray'] = $info;
        }

        $data = apply_filters(
            'Amelia' . ucwords($reservationEntityArray['type']) . ucwords($action) . 'Filter',
            [
                $reservation['type'] => $reservationEntityArray,
                Entities::BOOKINGS   => $affectedBookingEntitiesArray,
            ]
        );

        do_action(
            'Amelia' . ucwords($reservationEntityArray['type']) . ucwords($action),
            $data[$reservation['type']],
            $data[Entities::BOOKINGS],
            $this->container
        );

        $webHooks = apply_filters(
            'amelia_modify_web_hooks',
            $settingsService->getCategorySettings('webHooks'),
            $data
        );

        foreach ($webHooks as $webHook) {
            if ($webHook['action'] === $action && $webHook['type'] === $reservation['type']) {
                $ch = curl_init($webHook['url']);

                $headers = apply_filters(
                    'Amelia' . ucwords($reservationEntityArray['type']) . ucwords($action) . 'FilterHeader',
                    ['Content-Type:application/json'],
                    $data
                );

                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                curl_setopt(
                    $ch,
                    CURLOPT_POSTFIELDS,
                    json_encode(
                        $data,
                        JSON_FORCE_OBJECT
                    )
                );

                curl_exec($ch);

                curl_close($ch);
            }
        }
    }
}
