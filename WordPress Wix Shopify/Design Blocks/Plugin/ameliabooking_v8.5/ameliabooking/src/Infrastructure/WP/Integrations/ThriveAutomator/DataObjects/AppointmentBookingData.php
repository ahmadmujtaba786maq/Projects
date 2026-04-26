<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataObjects;

use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Appointment\End as AppointmentEnd;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Appointment\Start as AppointmentStart;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Appointment\Status as AppointmentStatus;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Appointment\Id as AppointmentId;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\CancelUrl as BookingCancelUrl;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\CustomFields as BookingCustomFields;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\Duration as BookingDuration;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\Extras as BookingExtras;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\Item\Item;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\Id as BookingId;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\Locale as BookingLocale;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\Persons as BookingPersons;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\Price as BookingPrice;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\Status as BookingStatus;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\TimeZone as BookingTimeZone;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\UrlParams as BookingUrlParams;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\UtcOffset as BookingUtcOffset;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Coupon\Id as CouponId;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Customer\Id as CustomerId;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Customer\Email as CustomerEmail;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Customer\ExternalId as CustomerExternalId;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Customer\FirstName as CustomerFirstName;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Customer\LastName as CustomerLastName;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Customer\PanelUrl as CustomerPanelUrl;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Customer\Phone as CustomerPhone;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Customer\Birthday as CustomerBirthday;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Customer\Gender as CustomerGender;
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
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Provider\Id as ProviderId;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Provider\Email as ProviderEmail;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Provider\ExternalId as ProviderExternalId;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Provider\FirstName as ProviderFirstName;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Provider\LastName as ProviderLastName;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Provider\Phone as ProviderPhone;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Service\Id as ServiceId;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Service\Name as ServiceName;
use Thrive\Automator\Items\Data_Object;

class AppointmentBookingData extends Data_Object
{
    public static function get_id()
    {
        return 'ameliabooking/appointment-data';
    }

    public static function get_nice_name()
    {
        return 'Amelia Appointment Data';
    }

    public static function get_fields()
    {
        $fields = [
            AppointmentId::get_id(),
            AppointmentStart::get_id(),
            AppointmentEnd::get_id(),
            AppointmentStatus::get_id(),

            CustomerId::get_id(),
            CustomerExternalId::get_id(),
            CustomerFirstName::get_id(),
            CustomerLastName::get_id(),
            CustomerEmail::get_id(),
            CustomerPhone::get_id(),
            CustomerBirthday::get_id(),
            CustomerGender::get_id(),
            CustomerPanelUrl::get_id(),

            LocationId::get_id(),
            LocationAddress::get_id(),
            LocationName::get_id(),

            ProviderId::get_id(),
            ProviderEmail::get_id(),
            ProviderExternalId::get_id(),
            ProviderFirstName::get_id(),
            ProviderLastName::get_id(),
            ProviderPhone::get_id(),

            ServiceId::get_id(),
            ServiceName::get_id(),

            PaymentId::get_id(),
            PaymentAmount::get_id(),
            PaymentDateTime::get_id(),
            PaymentGateway::get_id(),
            PaymentGatewayTitle::get_id(),
            PaymentStatus::get_id(),
            PaymentWcOrderId::get_id(),

            CouponId::get_id(),

            BookingId::get_id(),
            BookingStatus::get_id(),
            BookingCancelUrl::get_id(),
            BookingDuration::get_id(),
            BookingPersons::get_id(),
            BookingPrice::get_id(),
            BookingLocale::get_id(),
            BookingTimeZone::get_id(),
            BookingUrlParams::get_id(),
            BookingUtcOffset::get_id(),
        ];

        $fields[] = BookingCustomFields::get_id();

        foreach (Item::$ameliaItemData as $type => $data) {
            if ($type === 'custom_field') {
                foreach ($data as $id => $label) {
                    $fields[] = 'ameliabooking/' . $type . '_' . $id;
                }
            }
        }

        $fields[] = BookingExtras::get_id();

        foreach (Item::$ameliaItemData as $type => $data) {
            if ($type === 'extra') {
                foreach ($data as $id => $label) {
                    $fields[] = 'ameliabooking/' . $type . '_' . $id;
                }
            }
        }

        return $fields;
    }

    public static function create_object($param)
    {
        $result = [];

        foreach (self::get_fields() as $field) {
            $result[$field] = $param[$field];
        }

        return $result;
    }
}
