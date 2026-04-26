<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Factory\Booking\Appointment;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Factory\Bookable\Service\ServiceFactory;
use AmeliaBooking\Domain\Factory\Location\LocationFactory;
use AmeliaBooking\Domain\Factory\User\UserFactory;
use AmeliaBooking\Domain\Factory\Zoom\ZoomFactory;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Domain\ValueObjects\String\Description;
use AmeliaBooking\Domain\ValueObjects\String\Label;
use AmeliaBooking\Domain\ValueObjects\String\Token;

/**
 * Class AppointmentFactory
 *
 * @package AmeliaBooking\Domain\Factory\Booking\Appointment
 */
class AppointmentFactory
{
    /**
     * @param $data
     *
     * @return Appointment
     * @throws InvalidArgumentException
     */
    public static function create($data)
    {
        $appointment = new Appointment(
            new DateTimeValue(DateTimeService::getCustomDateTimeObject($data['bookingStart'])),
            new DateTimeValue(DateTimeService::getCustomDateTimeObject($data['bookingEnd'])),
            $data['notifyParticipants'],
            new Id($data['serviceId']),
            new Id($data['providerId'])
        );

        if (isset($data['createPaymentLinks'])) {
            $appointment->setCreatePaymentLinks($data['createPaymentLinks']);
        }

        if (!empty($data['id'])) {
            $appointment->setId(new Id($data['id']));
        }

        if (!empty($data['parentId'])) {
            $appointment->setParentId(new Id($data['parentId']));
        }

        if (!empty($data['locationId'])) {
            $appointment->setLocationId(new Id($data['locationId']));
        }

        if (!empty($data['location'])) {
            $appointment->setLocation(LocationFactory::create($data['location']));
        }

        if (isset($data['internalNotes'])) {
            $appointment->setInternalNotes(new Description($data['internalNotes']));
        }

        if (isset($data['status'])) {
            $appointment->setStatus(new BookingStatus($data['status']));
        }

        if (isset($data['provider'])) {
            $appointment->setProvider(UserFactory::create($data['provider']));
        }

        if (isset($data['service'])) {
            $appointment->setService(ServiceFactory::create($data['service']));
        }

        if (!empty($data['googleCalendarEventId'])) {
            $appointment->setGoogleCalendarEventId(new Token($data['googleCalendarEventId']));
        }

        if (!empty($data['googleMeetUrl'])) {
            $appointment->setGoogleMeetUrl($data['googleMeetUrl']);
        }

        if (!empty($data['outlookCalendarEventId'])) {
            $appointment->setOutlookCalendarEventId(new Label($data['outlookCalendarEventId']));
        }

        if (!empty($data['microsoftTeamsUrl'])) {
            $appointment->setMicrosoftTeamsUrl($data['microsoftTeamsUrl']);
        }

        if (!empty($data['appleCalendarEventId'])) {
            $appointment->setAppleCalendarEventId(new Label($data['appleCalendarEventId']));
        }

        if (!empty($data['zoomMeeting']['id'])) {
            $zoomMeeting = ZoomFactory::create(
                $data['zoomMeeting']
            );

            $appointment->setZoomMeeting($zoomMeeting);
        }

        if (isset($data['lessonSpace']) && !empty($data['lessonSpace'])) {
            $appointment->setLessonSpace($data['lessonSpace']);
        }

        if (isset($data['isRescheduled'])) {
            $appointment->setRescheduled(new BooleanValueObject($data['isRescheduled']));
        }

        if (array_key_exists('isChangedStatus', $data)) {
            $appointment->setChangedStatus(new BooleanValueObject($data['isChangedStatus']));
        }

        if (!empty($data['initialAppointmentDateTime']['bookingStart'])) {
            $appointment->setInitialBookingStart(
                new DateTimeValue(
                    DateTimeService::getCustomDateTimeObject($data['initialAppointmentDateTime']['bookingStart'])
                )
            );
        }

        if (!empty($data['initialAppointmentDateTime']['bookingEnd'])) {
            $appointment->setInitialBookingEnd(
                new DateTimeValue(
                    DateTimeService::getCustomDateTimeObject($data['initialAppointmentDateTime']['bookingEnd'])
                )
            );
        }

        $bookings = new Collection();

        if (isset($data['bookings'])) {
            foreach ((array)$data['bookings'] as $key => $value) {
                $bookings->addItem(
                    CustomerBookingFactory::create($value),
                    $key
                );
            }
        }

        $appointment->setBookings($bookings);

        return $appointment;
    }

    /**
     * @param array $rows
     *
     * @return Collection
     * @throws InvalidArgumentException
     */
    public static function createCollection($rows)
    {
        $appointments = [];

        foreach ($rows as $row) {
            $appointmentId  = $row['appointment_id'];
            $bookingId      = isset($row['booking_id']) ? $row['booking_id'] : null;
            $bookingExtraId = isset($row['bookingExtra_id']) ? $row['bookingExtra_id'] : null;
            $paymentId      = isset($row['payment_id']) ? $row['payment_id'] : null;
            $couponId       = isset($row['coupon_id']) ? $row['coupon_id'] : null;
            $customerId     = isset($row['customer_id']) ? $row['customer_id'] : null;
            $providerId     = isset($row['provider_id']) ? $row['provider_id'] : null;
            $locationId     = isset($row['location_id']) ? $row['location_id'] : null;
            $serviceId      = isset($row['service_id']) ? $row['service_id'] : null;

            if (!array_key_exists($appointmentId, $appointments)) {
                $zoomMeetingJson = !empty($row['appointment_zoom_meeting']) ?
                    json_decode($row['appointment_zoom_meeting'], true) : null;

                $appointments[$appointmentId] = [
                    'id'                     => $appointmentId,
                    'parentId'               => isset($row['appointment_parentId']) ?
                        $row['appointment_parentId'] : null,
                    'bookingStart'           => DateTimeService::getCustomDateTimeFromUtc(
                        $row['appointment_bookingStart']
                    ),
                    'bookingEnd'             => DateTimeService::getCustomDateTimeFromUtc(
                        $row['appointment_bookingEnd']
                    ),
                    'notifyParticipants'     => isset($row['appointment_notifyParticipants']) ?
                        $row['appointment_notifyParticipants'] : null,
                    'createPaymentLinks'     => isset($row['appointment_createPaymentLinks']) ?
                        $row['appointment_createPaymentLinks'] : null,
                    'serviceId'              => $row['appointment_serviceId'],
                    'providerId'             => $row['appointment_providerId'],
                    'locationId'             => isset($row['appointment_locationId']) ?
                        $row['appointment_locationId'] : null,
                    'internalNotes'          => isset($row['appointment_internalNotes']) ?
                        $row['appointment_internalNotes'] : null,
                    'status'                 => $row['appointment_status'],
                    'googleCalendarEventId'  => isset($row['appointment_google_calendar_event_id']) ?
                        $row['appointment_google_calendar_event_id'] : null,
                    'googleMeetUrl'          => isset($row['appointment_google_meet_url']) ?
                        $row['appointment_google_meet_url'] : null,
                    'outlookCalendarEventId' => isset($row['appointment_outlook_calendar_event_id']) ?
                        $row['appointment_outlook_calendar_event_id'] : null,
                    'microsoftTeamsUrl'      => isset($row['appointment_microsoft_teams_url']) ?
                        $row['appointment_microsoft_teams_url'] : null,
                    'appleCalendarEventId'   => isset($row['appointment_apple_calendar_event_id']) ?
                        $row['appointment_apple_calendar_event_id'] : null,
                    'zoomMeeting'            => [
                        'id'       => $zoomMeetingJson ? $zoomMeetingJson['id'] : null,
                        'startUrl' => $zoomMeetingJson ? $zoomMeetingJson['startUrl'] : null,
                        'joinUrl'  => $zoomMeetingJson ? $zoomMeetingJson['joinUrl'] : null,
                    ],
                    'lessonSpace'            => !empty($row['appointment_lesson_space']) ? $row['appointment_lesson_space'] : null,
                ];
            }

            if ($bookingId && !isset($appointments[$appointmentId]['bookings'][$bookingId])) {
                $appointments[$appointmentId]['bookings'][$bookingId] = [
                    'id'              => $bookingId,
                    'appointmentId'   => $appointmentId,
                    'customerId'      => $row['booking_customerId'],
                    'status'          => $row['booking_status'],
                    'couponId'        => $couponId,
                    'price'           => $row['booking_price'],
                    'persons'         => $row['booking_persons'],
                    'customFields'    => isset($row['booking_customFields']) ? $row['booking_customFields'] : null,
                    'info'            => isset($row['booking_info']) ? $row['booking_info'] : null,
                    'utcOffset'       => isset($row['booking_utcOffset']) ? $row['booking_utcOffset'] : null,
                    'aggregatedPrice' => isset($row['booking_aggregatedPrice']) ?
                        $row['booking_aggregatedPrice'] : null,
                    'packageCustomerService' => !empty($row['booking_packageCustomerServiceId']) ? [
                        'id'              => $row['booking_packageCustomerServiceId'],
                        'serviceId'       => !empty($row['package_customer_service_serviceId']) ?
                            $row['package_customer_service_serviceId'] : null,
                        'bookingsCount'   => !empty($row['package_customer_service_bookingsCount']) ?
                            $row['package_customer_service_bookingsCount'] : null,
                        'packageCustomer' => [
                            'id' => !empty($row['package_customer_id']) ?
                                $row['package_customer_id'] : null,
                            'packageId' => !empty($row['package_customer_packageId']) ?
                                $row['package_customer_packageId'] : null,
                            'price'     => !empty($row['package_customer_price']) ?
                                $row['package_customer_price'] : null,
                            'couponId'  => !empty($row['package_customer_couponId']) ?
                                $row['package_customer_couponId'] : null,
                            'tax'       => !empty($row['package_customer_tax']) ?
                                $row['package_customer_tax'] : null,
                        ]
                    ] : null,
                    'duration'       => isset($row['booking_duration']) ? $row['booking_duration'] : null,
                    'created'        => !empty($row['booking_created']) ? DateTimeService::getCustomDateTimeFromUtc($row['booking_created']) : null,
                    'tax'            => isset($row['booking_tax']) ? $row['booking_tax'] : null,
                ];
            }

            if ($bookingId && $bookingExtraId) {
                $appointments[$appointmentId]['bookings'][$bookingId]['extras'][$bookingExtraId] =
                    [
                        'id'                => $bookingExtraId,
                        'customerBookingId' => $bookingId,
                        'extraId'           => $row['bookingExtra_extraId'],
                        'quantity'          => $row['bookingExtra_quantity'],
                        'price'             => $row['bookingExtra_price'],
                        'aggregatedPrice'   => $row['bookingExtra_aggregatedPrice'],
                        'tax'               => isset($row['bookingExtra_tax']) ? $row['bookingExtra_tax'] : null,
                    ];
            }

            if ($bookingId && $paymentId) {
                $appointments[$appointmentId]['bookings'][$bookingId]['payments'][$paymentId] =
                    [
                        'id'                => $paymentId,
                        'customerBookingId' => $bookingId,
                        'packageCustomerId' => !empty($row['payment_packageCustomerId']) ? $row['payment_packageCustomerId'] : null,
                        'status'            => $row['payment_status'],
                        'dateTime'          => DateTimeService::getCustomDateTimeFromUtc($row['payment_dateTime']),
                        'gateway'           => $row['payment_gateway'],
                        'gatewayTitle'      => $row['payment_gatewayTitle'],
                        'transactionId'     => !empty($row['payment_transactionId']) ? $row['payment_transactionId'] : null,
                        'parentId'          => !empty($row['payment_parentId']) ? $row['payment_parentId'] : null,
                        'amount'            => $row['payment_amount'],
                        'data'              => $row['payment_data'],
                        'invoiceNumber'     => !empty($row['payment_invoiceNumber']) ? $row['payment_invoiceNumber'] : null,
                        'wcOrderId'         => !empty($row['payment_wcOrderId']) ? $row['payment_wcOrderId'] : null,
                        'wcOrderItemId'     => !empty($row['payment_wcOrderItemId']) ?
                            $row['payment_wcOrderItemId'] : null,
                        'created'           => !empty($row['payment_created']) ? $row['payment_created'] : null,
                    ];
            }

            if ($bookingId && $couponId) {
                $appointments[$appointmentId]['bookings'][$bookingId]['coupon']['id']            = $couponId;
                $appointments[$appointmentId]['bookings'][$bookingId]['coupon']['code']          = $row['coupon_code'];
                $appointments[$appointmentId]['bookings'][$bookingId]['coupon']['discount']      = $row['coupon_discount'];
                $appointments[$appointmentId]['bookings'][$bookingId]['coupon']['deduction']     = $row['coupon_deduction'];
                $appointments[$appointmentId]['bookings'][$bookingId]['coupon']['limit']         = $row['coupon_limit'];
                $appointments[$appointmentId]['bookings'][$bookingId]['coupon']['customerLimit'] = $row['coupon_customerLimit'];
                $appointments[$appointmentId]['bookings'][$bookingId]['coupon']['status']        = $row['coupon_status'];
                $appointments[$appointmentId]['bookings'][$bookingId]['coupon']['expirationDate'] = $row['coupon_expirationDate'];
            }

            if ($bookingId && $customerId) {
                $appointments[$appointmentId]['bookings'][$bookingId]['customer'] =
                    [
                        'id'        => $customerId,
                        'firstName' => $row['customer_firstName'],
                        'lastName'  => $row['customer_lastName'],
                        'email'     => $row['customer_email'],
                        'note'      => $row['customer_note'],
                        'phone'     => $row['customer_phone'],
                        'gender'    => $row['customer_gender'],
                        'status'    => $row['customer_status'],
                        'type'      => 'customer',
                    ];
            }

            if ($bookingId && $locationId) {
                $appointments[$appointmentId]['location'] =
                    [
                        'id' => $locationId,
                        'name' => !empty($row['location_name']) ? $row['location_name'] : '',
                        'address' => !empty($row['location_address']) ? $row['location_address'] : '',
                        'description' => !empty($row['location_description']) ? $row['location_description'] : null,
                        'status' => !empty($row['location_status']) ? $row['location_status'] : null,
                        'phone' => !empty($row['location_phone']) ? $row['location_phone'] : null,
                        'latitude' => !empty($row['location_latitude']) ? $row['location_latitude'] : null,
                        'longitude' => !empty($row['location_longitude']) ? $row['location_longitude'] : null,
                        'pictureFullPath' => !empty($row['location_pictureFullPath']) ? $row['location_pictureFullPath'] : null,
                        'pictureThumbPath' => !empty($row['location_pictureThumbPath']) ? $row['location_pictureThumbPath'] : null,
                        'pin' => !empty($row['location_pin']) ? $row['location_pin'] : null,
                        'translations' => !empty($row['location_translations']) ? $row['location_translations'] : null
                    ];
            }

            if ($bookingId && $providerId) {
                $appointments[$appointmentId]['provider'] =
                    [
                        'id'        => $providerId,
                        'firstName' => $row['provider_firstName'],
                        'lastName'  => $row['provider_lastName'],
                        'email'     => $row['provider_email'],
                        'note'      => $row['provider_note'],
                        'description' => $row['provider_description'],
                        'phone'     => $row['provider_phone'],
                        'gender'    => $row['provider_gender'],
                        'timeZone'  => !empty($row['provider_timeZone']) ? $row['provider_timeZone'] : null,
                        'type'      => 'provider',
                    ];
            }

            if ($serviceId) {
                $appointments[$appointmentId]['service']['id']          = $row['service_id'];
                $appointments[$appointmentId]['service']['name']        = $row['service_name'];
                $appointments[$appointmentId]['service']['description'] = $row['service_description'];
                $appointments[$appointmentId]['service']['color']       = $row['service_color'];
                $appointments[$appointmentId]['service']['price']       = $row['service_price'];
                $appointments[$appointmentId]['service']['status']      = $row['service_status'];
                $appointments[$appointmentId]['service']['categoryId']  = $row['service_categoryId'];
                $appointments[$appointmentId]['service']['minCapacity'] = $row['service_minCapacity'];
                $appointments[$appointmentId]['service']['maxCapacity'] = $row['service_maxCapacity'];
                $appointments[$appointmentId]['service']['duration']    = $row['service_duration'];
                $appointments[$appointmentId]['service']['timeBefore']  = isset($row['service_timeBefore'])
                    ? $row['service_timeBefore'] : null;
                $appointments[$appointmentId]['service']['timeAfter']   = isset($row['service_timeAfter'])
                    ? $row['service_timeAfter'] : null;
                $appointments[$appointmentId]['service']['aggregatedPrice'] = isset($row['service_aggregatedPrice'])
                    ? $row['service_aggregatedPrice'] : null;
                $appointments[$appointmentId]['service']['settings']        = isset($row['service_settings'])
                    ? $row['service_settings'] : null;
            }
        }

        $collection = new Collection();

        foreach ($appointments as $key => $value) {
            $collection->addItem(
                self::create($value),
                $key
            );
        }

        return $collection;
    }
}
