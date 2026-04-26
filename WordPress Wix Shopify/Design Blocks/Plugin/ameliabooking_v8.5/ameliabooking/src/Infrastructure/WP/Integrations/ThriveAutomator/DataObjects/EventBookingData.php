<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataObjects;

use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\CancelUrl as BookingCancelUrl;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\CustomFields as BookingCustomFields;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\Tickets as BookingTickets;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\Item\Item;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\Id as BookingId;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\Locale as BookingLocale;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\Persons as BookingPersons;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\Price as BookingPrice;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\Status as BookingStatus;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\TimeZone as BookingTimeZone;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\UrlParams as BookingUrlParams;
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
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Payment\Amount as PaymentAmount;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Payment\DateTime as PaymentDateTime;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Payment\Gateway as PaymentGateway;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Payment\GatewayTitle as PaymentGatewayTitle;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Payment\Id as PaymentId;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Payment\Status as PaymentStatus;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Payment\WcOrderId as PaymentWcOrderId;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Event\Id as EventId;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Event\Name as EventName;
use Thrive\Automator\Items\Data_Object;

class EventBookingData extends Data_Object
{
    public static function get_id()
    {
        return 'ameliabooking/event-data';
    }

    public static function get_nice_name()
    {
        return 'Amelia Event Data';
    }

    public static function get_fields()
    {
        $fields = [
            EventId::get_id(),
            EventName::get_id(),

            CustomerId::get_id(),
            CustomerExternalId::get_id(),
            CustomerFirstName::get_id(),
            CustomerLastName::get_id(),
            CustomerEmail::get_id(),
            CustomerPhone::get_id(),
            CustomerBirthday::get_id(),
            CustomerGender::get_id(),
            CustomerPanelUrl::get_id(),

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
            BookingPersons::get_id(),
            BookingPrice::get_id(),
            BookingLocale::get_id(),
            BookingTimeZone::get_id(),
            BookingUrlParams::get_id(),
        ];

        $fields[] = BookingCustomFields::get_id();

        foreach (Item::$ameliaItemData as $type => $data) {
            if ($type === 'custom_field') {
                foreach ($data as $id => $label) {
                    $fields[] = 'ameliabooking/' . $type . '_' . $id;
                }
            }
        }

        $fields[] = BookingTickets::get_id();

        foreach (Item::$ameliaItemData as $type => $data) {
            if ($type === 'ticket') {
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
