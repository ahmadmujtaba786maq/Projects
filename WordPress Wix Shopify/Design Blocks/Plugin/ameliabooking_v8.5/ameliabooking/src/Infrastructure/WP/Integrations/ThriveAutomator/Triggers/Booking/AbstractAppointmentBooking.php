<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\Triggers\Booking;

use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\Apps\AmeliaBooking;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Appointment\End as AppointmentEnd;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Appointment\Id as AppointmentId;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Appointment\Start as AppointmentStart;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Appointment\Status as AppointmentStatus;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\CancelUrl as BookingCancelUrl;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\CustomFields as BookingCustomFields;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\Duration as BookingDuration;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\Extras as BookingExtras;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\Id as BookingId;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\Locale as BookingLocale;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\Persons as BookingPersons;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\Price as BookingPrice;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\Status as BookingStatus;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\TimeZone as BookingTimeZone;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\UrlParams as BookingUrlParams;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\UtcOffset as BookingUtcOffset;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Coupon\Id as CouponId;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Customer\Birthday as CustomerBirthday;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Customer\Email as CustomerEmail;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Customer\ExternalId as CustomerExternalId;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Customer\FirstName as CustomerFirstName;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Customer\Gender as CustomerGender;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Customer\Id as CustomerId;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Customer\LastName as CustomerLastName;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Customer\PanelUrl as CustomerPanelUrl;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Customer\Phone as CustomerPhone;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Location\Address as LocationAddress;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Location\Id as LocationId;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Location\Name as LocationName;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Payment\Amount as PaymentAmount;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Payment\DateTime as PaymentDateTime;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Payment\Gateway as PaymentGateway;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Payment\GatewayTitle as PaymentGatewayTitle;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Payment\Id as PaymentId;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Payment\Status as PaymentStatus;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Payment\WcOrderId as PaymentWcOrderId;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Provider\Email as ProviderEmail;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Provider\ExternalId as ProviderExternalId;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Provider\FirstName as ProviderFirstName;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Provider\Id as ProviderId;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Provider\LastName as ProviderLastName;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Provider\Phone as ProviderPhone;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Service\Id as ServiceId;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Service\Name as ServiceName;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataObjects\AppointmentBookingData;
use Thrive\Automator\Items\Data_Object;
use Thrive\Automator\Items\Email_Data;
use Thrive\Automator\Items\Trigger;

abstract class AbstractAppointmentBooking extends Trigger
{
    public static function get_provided_data_objects()
    {
        return [
            AppointmentBookingData::get_id(),
            'email_data',
        ];
    }

    public static function get_app_id()
    {
        return AmeliaBooking::get_id();
    }

    public static function get_image()
    {
        return AMELIA_URL . 'public/img/amelia-logo-symbol.svg';
    }

    public function process_params($params = [])
    {
        $dataObjects = [];

        $appointmentData = !empty($params[0]) ? $params[0] : [];

        $bookingData = !empty($params[1][0]) ? $params[1][0] : $appointmentData['bookings'][0];

        if ($appointmentData && $bookingData) {
            $dataObjectClasses = Data_Object::get();

            $dataObjects[Email_Data::get_id()] = new $dataObjectClasses[Email_Data::get_id()](
                $bookingData['customer']['email']
            );

            $ameliaData = [
                AppointmentId::get_id()       => $appointmentData['id'],
                AppointmentStart::get_id()    => $appointmentData['bookingStart'],
                AppointmentEnd::get_id()      => $appointmentData['bookingEnd'],
                AppointmentStatus::get_id()   => $appointmentData['status'],

                CustomerId::get_id()          => $bookingData['customer']['id'],
                CustomerExternalId::get_id()  => $bookingData['customer']['externalId'],
                CustomerFirstName::get_id()   => $bookingData['customer']['firstName'],
                CustomerLastName::get_id()    => $bookingData['customer']['lastName'],
                CustomerEmail::get_id()       => $bookingData['customer']['email'],
                CustomerPhone::get_id()       => $bookingData['customer']['phone'],
                CustomerBirthday::get_id()    => !empty($bookingData['customer']['birthday']) ?
                    $bookingData['customer']['birthday']->format('Y-m-d') : null,
                CustomerGender::get_id()      => $bookingData['customer']['gender'],
                CustomerPanelUrl::get_id()    => $bookingData['customerPanelUrl'],

                LocationId::get_id()          => !empty($appointmentData['location']) ?
                    $appointmentData['location']['id'] : null,
                LocationAddress::get_id()     => !empty($appointmentData['location']) ?
                    $appointmentData['location']['address'] : null,
                LocationName::get_id()        => !empty($appointmentData['location']) ?
                    $appointmentData['location']['name'] : null,

                ProviderId::get_id()          => $appointmentData['provider']['id'],
                ProviderExternalId::get_id()  => $appointmentData['provider']['externalId'],
                ProviderFirstName::get_id()   => $appointmentData['provider']['firstName'],
                ProviderLastName::get_id()    => $appointmentData['provider']['lastName'],
                ProviderEmail::get_id()       => $appointmentData['provider']['email'],
                ProviderPhone::get_id()       => $appointmentData['provider']['phone'],

                ServiceId::get_id()           => $appointmentData['service']['id'],
                ServiceName::get_id()         => $appointmentData['service']['name'],

                PaymentId::get_id()           => $bookingData['payments'][0]['id'],
                PaymentAmount::get_id()       => $bookingData['payments'][0]['amount'],
                PaymentDateTime::get_id()     => $bookingData['payments'][0]['dateTime'],
                PaymentGateway::get_id()      => $bookingData['payments'][0]['gateway'],
                PaymentGatewayTitle::get_id() => $bookingData['payments'][0]['gatewayTitle'],
                PaymentStatus::get_id()       => $bookingData['payments'][0]['status'],
                PaymentWcOrderId::get_id()    => $bookingData['payments'][0]['wcOrderId'],

                CouponId::get_id()            => $bookingData['couponId'],

                BookingId::get_id()           => $bookingData['id'],
                BookingStatus::get_id()       => $bookingData['status'],
                BookingCancelUrl::get_id()    => $bookingData['cancelUrl'],
                BookingDuration::get_id()     => $bookingData['duration'],
                BookingPersons::get_id()      => $bookingData['persons'],
                BookingPrice::get_id()        => $bookingData['price'],
                BookingLocale::get_id()       => !empty($bookingData['infoArray']) ?
                    $bookingData['infoArray']['locale'] : null,
                BookingTimeZone::get_id()     => !empty($bookingData['infoArray']) ?
                    $bookingData['infoArray']['timeZone'] : null,
                BookingUrlParams::get_id()    => !empty($bookingData['infoArray']['urlParams']) ?
                    $bookingData['infoArray']['urlParams'] : null,
                BookingUtcOffset::get_id()    => !empty($bookingData['infoArray']['utcOffset']) ?
                    $bookingData['infoArray']['utcOffset'] : null,

                BookingCustomFields::get_id() => $bookingData['customFields'],
                BookingExtras::get_id()       => $bookingData['extras'],
            ];

            if ($bookingData['customFields']) {
                foreach ($bookingData['customFields'] as $id => $data) {
                    $ameliaData['ameliabooking/custom_field_' . $id] = $bookingData['customFields'][$id]['value'];
                }
            }

            foreach ($bookingData['extras'] as $extra) {
                $ameliaData['ameliabooking/extra_' . $extra['extraId']] = $extra['quantity'];
            }

            $dataObjects[AppointmentBookingData::get_id()] = new $dataObjectClasses[AppointmentBookingData::get_id()](
                $ameliaData,
                'ameliabooking'
            );
        }

        return $dataObjects;
    }
}
