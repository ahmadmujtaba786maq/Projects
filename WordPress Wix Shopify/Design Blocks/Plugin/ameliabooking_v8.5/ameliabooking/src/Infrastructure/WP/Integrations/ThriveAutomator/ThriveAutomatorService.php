<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Extra;
use AmeliaBooking\Domain\Entity\Booking\Event\EventTicket;
use AmeliaBooking\Domain\Entity\CustomField\CustomField;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ExtraRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventTicketRepository;
use AmeliaBooking\Infrastructure\Repository\CustomField\CustomFieldRepository;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\Apps\AmeliaBooking;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Appointment\End as AppointmentEnd;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Appointment\Start as AppointmentStart;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Appointment\Status as AppointmentStatus;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Appointment\Id as AppointmentId;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\CancelUrl as BookingCancelUrl;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\CustomFields as BookingCustomFields;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\Tickets as BookingTickets;
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
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Customer\Phone as CustomerPhone;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Customer\Birthday as CustomerBirthday;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Customer\Gender as CustomerGender;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Customer\PanelUrl as CustomerPanelUrl;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Event\Id as EventId;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Event\Name as EventName;
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
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Provider\TimeZone as ProviderTimeZone;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Service\Id as ServiceId;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Service\Name as ServiceName;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataObjects\AppointmentBookingData;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataObjects\EventBookingData;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\Triggers\Booking\AppointmentBookingAdded;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\Triggers\Booking\AppointmentBookingCanceled;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\Triggers\Booking\AppointmentBookingRescheduled;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\Triggers\Booking\AppointmentBookingStatusUpdated;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\Triggers\Booking\EventBookingAdded;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\Triggers\Booking\EventBookingCanceled;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\Triggers\Booking\EventBookingRescheduled;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\Triggers\Booking\EventBookingStatusUpdated;
use AmeliaBooking\Infrastructure\WP\SettingsService\SettingsStorage;

class ThriveAutomatorService
{
    /**
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public static function init()
    {
        thrive_automator_register_app(AmeliaBooking::class);

        thrive_automator_register_trigger(AppointmentBookingAdded::class);
        thrive_automator_register_trigger(AppointmentBookingCanceled::class);
        thrive_automator_register_trigger(AppointmentBookingRescheduled::class);
        thrive_automator_register_trigger(AppointmentBookingStatusUpdated::class);

        thrive_automator_register_trigger(EventBookingAdded::class);
        thrive_automator_register_trigger(EventBookingCanceled::class);
        thrive_automator_register_trigger(EventBookingRescheduled::class);
        thrive_automator_register_trigger(EventBookingStatusUpdated::class);

        thrive_automator_register_data_field(AppointmentId::class);
        thrive_automator_register_data_field(AppointmentStatus::class);
        thrive_automator_register_data_field(AppointmentStart::class);
        thrive_automator_register_data_field(AppointmentEnd::class);

        thrive_automator_register_data_field(CustomerId::class);
        thrive_automator_register_data_field(CustomerEmail::class);
        thrive_automator_register_data_field(CustomerExternalId::class);
        thrive_automator_register_data_field(CustomerFirstName::class);
        thrive_automator_register_data_field(CustomerLastName::class);
        thrive_automator_register_data_field(CustomerPhone::class);
        thrive_automator_register_data_field(CustomerBirthday::class);
        thrive_automator_register_data_field(CustomerGender::class);
        thrive_automator_register_data_field(CustomerPanelUrl::class);

        thrive_automator_register_data_field(LocationId::class);
        thrive_automator_register_data_field(LocationAddress::class);
        thrive_automator_register_data_field(LocationName::class);

        thrive_automator_register_data_field(ProviderId::class);
        thrive_automator_register_data_field(ProviderEmail::class);
        thrive_automator_register_data_field(ProviderExternalId::class);
        thrive_automator_register_data_field(ProviderFirstName::class);
        thrive_automator_register_data_field(ProviderLastName::class);
        thrive_automator_register_data_field(ProviderPhone::class);
        thrive_automator_register_data_field(ProviderTimeZone::class);

        thrive_automator_register_data_field(ServiceId::class);
        thrive_automator_register_data_field(ServiceName::class);

        thrive_automator_register_data_field(EventId::class);
        thrive_automator_register_data_field(EventName::class);

        thrive_automator_register_data_field(PaymentId::class);
        thrive_automator_register_data_field(PaymentAmount::class);
        thrive_automator_register_data_field(PaymentDateTime::class);
        thrive_automator_register_data_field(PaymentGateway::class);
        thrive_automator_register_data_field(PaymentGatewayTitle::class);
        thrive_automator_register_data_field(PaymentStatus::class);
        thrive_automator_register_data_field(PaymentWcOrderId::class);

        thrive_automator_register_data_field(BookingId::class);
        thrive_automator_register_data_field(BookingStatus::class);
        thrive_automator_register_data_field(BookingCancelUrl::class);
        thrive_automator_register_data_field(BookingDuration::class);
        thrive_automator_register_data_field(BookingLocale::class);
        thrive_automator_register_data_field(BookingPersons::class);
        thrive_automator_register_data_field(BookingPrice::class);
        thrive_automator_register_data_field(BookingTimeZone::class);
        thrive_automator_register_data_field(BookingUrlParams::class);
        thrive_automator_register_data_field(BookingUtcOffset::class);

        thrive_automator_register_data_field(CouponId::class);
        thrive_automator_register_data_field(BookingCustomFields::class);
        thrive_automator_register_data_field(BookingExtras::class);
        thrive_automator_register_data_field(BookingTickets::class);

        if (
            isset($_SERVER['REQUEST_URI']) &&
            strpos($_SERVER['REQUEST_URI'], '/tap/') !== false &&
            (
                strpos($_SERVER['REQUEST_URI'], '/automation') !== false ||
                strpos($_SERVER['REQUEST_URI'], '/automations') !== false ||
                strpos($_SERVER['REQUEST_URI'], '/data_objects') !== false ||
                strpos($_SERVER['REQUEST_URI'], '/triggers') !== false
            )
        ) {
            self::initItems();
        }

        thrive_automator_register_data_object(AppointmentBookingData::class);
        thrive_automator_register_data_object(EventBookingData::class);
    }

    /**
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public static function initItems()
    {
        if (class_exists('Thrive\Automator\Items\Data_Field')) {
            /** @var SettingsService $settingsService */
            $settingsService = new SettingsService(new SettingsStorage());

            if ($settingsService->getSetting('activation', 'enableThriveItems')) {
                /** @var Container $container */
                $container = require AMELIA_PATH . '/src/Infrastructure/ContainerConfig/container.php';

                $itemClassName = Item::class;

                $itemId = 'ameliaItem';

                $index = 1;

                $maxIndex = 50;

                /** @var ExtraRepository $extraRepository */
                $extraRepository = $container->get('domain.bookable.extra.repository');

                /** @var Collection $extras */
                $extras = $extraRepository->getAllIndexedById();

                /** @var Extra $extra */
                foreach ($extras->getItems() as $extra) {
                    if ($index <= $maxIndex) {
                        $newItemClassName = $itemClassName . $index;

                        $newItemClassName::$$itemId = [
                            'type' => 'extra',
                            'id'   => $extra->getId()->getValue(),
                        ];

                        Item::$ameliaItemData['extra'][$extra->getId()->getValue()] =
                            $extra->getName()->getValue();

                        thrive_automator_register_data_field($newItemClassName);

                        $index++;
                    }
                }

                /** @var CustomFieldRepository $customFieldRepository */
                $customFieldRepository = $container->get('domain.customField.repository');

                /** @var Collection $customFields */
                $customFields = $customFieldRepository->getAllIndexedById();

                /** @var CustomField $customField */
                foreach ($customFields->getItems() as $customField) {
                    if ($index <= $maxIndex) {
                        $newItemClassName = $itemClassName . $index;

                        $newItemClassName::$$itemId = [
                            'type' => 'custom_field',
                            'id'   => $customField->getId()->getValue(),
                        ];

                        Item::$ameliaItemData['custom_field'][$customField->getId()->getValue()] =
                            $customField->getLabel()->getValue();

                        thrive_automator_register_data_field($newItemClassName);

                        $index++;
                    }
                }

                /** @var EventTicketRepository $eventTicketRepository */
                $eventTicketRepository = $container->get('domain.booking.event.ticket.repository');

                /** @var Collection $eventsTickets */
                $eventsTickets = $eventTicketRepository->getAllIndexedById();

                /** @var EventTicket $eventTicket */
                foreach ($eventsTickets->getItems() as $eventTicket) {
                    if ($index <= $maxIndex) {
                        $newItemClassName = $itemClassName . $index;

                        $newItemClassName::$$itemId = [
                            'type' => 'ticket',
                            'id'   => $eventTicket->getId()->getValue(),
                        ];

                        Item::$ameliaItemData['ticket'][$eventTicket->getId()->getValue()] =
                            $eventTicket->getName()->getValue();

                        thrive_automator_register_data_field($newItemClassName);

                        $index++;
                    }
                }
            }
        }
    }
}
