<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Factory\Booking\Event;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Gallery\GalleryImage;
use AmeliaBooking\Domain\Factory\Booking\Appointment\CustomerBookingFactory;
use AmeliaBooking\Domain\Factory\Coupon\CouponFactory;
use AmeliaBooking\Domain\Factory\User\ProviderFactory;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Domain\ValueObjects\Json;
use AmeliaBooking\Domain\ValueObjects\Number\Float\Price;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\IntegerValue;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\PositiveInteger;
use AmeliaBooking\Domain\ValueObjects\Picture;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\ValueObjects\String\Color;
use AmeliaBooking\Domain\ValueObjects\String\DepositType;
use AmeliaBooking\Domain\ValueObjects\String\Description;
use AmeliaBooking\Domain\ValueObjects\String\EntityType;
use AmeliaBooking\Domain\ValueObjects\String\Name;
use AmeliaBooking\Infrastructure\Licence;

/**
 * Class EventFactory
 *
 * @package AmeliaBooking\Domain\Factory\Booking\Event
 */
class EventFactory
{
    /**
     * @param array $data
     *
     * @return Event
     * @throws InvalidArgumentException
     */
    public static function create($data)
    {
        Licence\DataModifier::eventFactory($data);

        $event = new Event();

        if (isset($data['id'])) {
            $event->setId(new Id($data['id']));
        }

        if (isset($data['name'])) {
            $event->setName(new Name($data['name']));
        }

        if (isset($data['price'])) {
            $event->setPrice(new Price($data['price']));
        }

        if (isset($data['parentId'])) {
            $event->setParentId(new Id($data['parentId']));
        }

        if (!empty($data['bookingOpens'])) {
            $event->setBookingOpens(new DateTimeValue(DateTimeService::getCustomDateTimeObject($data['bookingOpens'])));
        }

        if (!empty($data['bookingCloses'])) {
            $event->setBookingCloses(new DateTimeValue(DateTimeService::getCustomDateTimeObject($data['bookingCloses'])));
        }

        if (!empty($data['bookingOpensRec'])) {
            $event->setBookingOpensRec($data['bookingOpensRec']);
        }

        if (!empty($data['bookingClosesRec'])) {
            $event->setBookingClosesRec($data['bookingClosesRec']);
        }

        if (!empty($data['ticketRangeRec'])) {
            $event->setTicketRangeRec($data['ticketRangeRec']);
        }

        if (isset($data['notifyParticipants'])) {
            $event->setNotifyParticipants($data['notifyParticipants']);
        }

        if (isset($data['status'])) {
            $event->setStatus(new BookingStatus($data['status']));
        }

        if (isset($data['recurring']['cycle'], $data['recurring']['until'])) {
            $event->setRecurring(RecurringFactory::create($data['recurring']));
        }

        if (isset($data['bringingAnyone'])) {
            $event->setBringingAnyone(new BooleanValueObject($data['bringingAnyone']));
        }

        if (isset($data['bookMultipleTimes'])) {
            $event->setBookMultipleTimes(new BooleanValueObject($data['bookMultipleTimes']));
        }

        if (isset($data['maxCapacity'])) {
            $event->setMaxCapacity(new IntegerValue($data['maxCapacity']));
        }

        if (isset($data['maxCustomCapacity'])) {
            $event->setMaxCustomCapacity(new IntegerValue($data['maxCustomCapacity']));
        }

        if (isset($data['description'])) {
            $event->setDescription(new Description($data['description']));
        }

        if (!empty($data['locationId'])) {
            $event->setLocationId(new Id($data['locationId']));
        }

        if (!empty($data['customLocation'])) {
            $event->setCustomLocation(new Name($data['customLocation']));
        }

        if (isset($data['color'])) {
            $event->setColor(new Color($data['color']));
        }

        if (isset($data['show'])) {
            $event->setShow(new BooleanValueObject($data['show']));
        }

        if (isset($data['created'])) {
            $event->setCreated(new DateTimeValue(DateTimeService::getCustomDateTimeObject($data['created'])));
        }

        if (!empty($data['settings'])) {
            $event->setSettings(new Json($data['settings']));
        }

        if (isset($data['deposit'])) {
            $event->setDeposit(new Price($data['deposit']));
        }

        if (isset($data['depositPayment'])) {
            $event->setDepositPayment(new DepositType($data['depositPayment']));
        }

        if (isset($data['fullPayment'])) {
            $event->setFullPayment(new BooleanValueObject($data['fullPayment']));
        }

        if (isset($data['customPricing'])) {
            $event->setCustomPricing(new BooleanValueObject($data['customPricing']));
        }

        if (isset($data['depositPerPerson'])) {
            $event->setDepositPerPerson(new BooleanValueObject($data['depositPerPerson']));
        }

        if (isset($data['closeAfterMin'])) {
            $event->setCloseAfterMin(new IntegerValue($data['closeAfterMin']));
        }

        if (isset($data['closeAfterMinBookings'])) {
            $event->setCloseAfterMinBookings(new BooleanValueObject($data['closeAfterMinBookings']));
        }

        if (isset($data['aggregatedPrice'])) {
            $event->setAggregatedPrice(new BooleanValueObject($data['aggregatedPrice']));
        }


        if (isset($data['maxExtraPeople'])) {
            $event->setMaxExtraPeople(new IntegerValue($data['maxExtraPeople']));
        }

        $tickets = new Collection();

        if (isset($data['customTickets'])) {
            foreach ($data['customTickets'] as $key => $value) {
                $tickets->addItem(
                    EventTicketFactory::create($value),
                    $key
                );
            }
        }

        $event->setCustomTickets($tickets);

        $tags = new Collection();

        if (isset($data['tags'])) {
            foreach ((array)$data['tags'] as $key => $value) {
                $tags->addItem(
                    EventTagFactory::create($value),
                    $key
                );
            }
        }

        $event->setTags($tags);

        $bookings = new Collection();

        if (isset($data['bookings'])) {
            foreach ((array)$data['bookings'] as $key => $value) {
                $bookings->addItem(
                    CustomerBookingFactory::create($value),
                    $key
                );
            }
        }

        $event->setBookings($bookings);

        $periods = new Collection();

        if (isset($data['periods'])) {
            foreach ((array)$data['periods'] as $key => $value) {
                $periods->addItem(EventPeriodFactory::create($value));
            }
        }

        $event->setPeriods($periods);

        $gallery = new Collection();

        if (!empty($data['gallery'])) {
            foreach ((array)$data['gallery'] as $image) {
                $galleryImage = new GalleryImage(
                    new EntityType(Entities::EVENT),
                    new Picture($image['pictureFullPath'], $image['pictureThumbPath']),
                    new PositiveInteger($image['position'])
                );

                if (!empty($image['id'])) {
                    $galleryImage->setId(new Id($image['id']));
                }

                if ($event->getId()) {
                    $galleryImage->setEntityId($event->getId());
                }

                $gallery->addItem($galleryImage);
            }
        }

        $event->setGallery($gallery);

        $coupons = new Collection();

        if (!empty($data['coupons'])) {
            /** @var array $couponsList */
            $couponsList = $data['coupons'];

            foreach ($couponsList as $couponKey => $coupon) {
                $coupons->addItem(CouponFactory::create($coupon), $couponKey);
            }
        }

        $event->setCoupons($coupons);

        $providers = new Collection();

        if (!empty($data['providers'])) {
            /** @var array $providerList */
            $providerList = $data['providers'];

            foreach ($providerList as $providerKey => $provider) {
                $providers->addItem(ProviderFactory::create($provider), $providerKey);
            }
        }

        if (!empty($data['organizerId'])) {
            $event->setOrganizerId(new Id($data['organizerId']));
        }

        $event->setProviders($providers);

        if (!empty($data['zoomUserId'])) {
            $event->setZoomUserId(new Name($data['zoomUserId']));
        }

        if (!empty($data['translations'])) {
            $event->setTranslations(new Json($data['translations']));
        }

        return $event;
    }

    /**
     * @param array $rows
     *
     * @return Collection
     * @throws InvalidArgumentException
     */
    public static function createCollection($rows)
    {
        $events = [];

        foreach ($rows as $row) {
            $eventId         = $row['event_id'];
            $eventPeriodId   = isset($row['event_periodId']) ? $row['event_periodId'] : null;
            $galleryId       = isset($row['gallery_id']) ? $row['gallery_id'] : null;
            $customerId      = isset($row['customer_id']) ? $row['customer_id'] : null;
            $bookingId       = isset($row['booking_id']) ? $row['booking_id'] : null;
            $bookingTicketId = isset($row['booking_ticket_id']) ? $row['booking_ticket_id'] : null;
            $paymentId       = isset($row['payment_id']) ? $row['payment_id'] : null;
            $tagId           = isset($row['event_tagId']) ? $row['event_tagId'] : null;
            $ticketId        = isset($row['ticket_id']) ? $row['ticket_id'] : null;
            $providerId      = isset($row['provider_id']) ? $row['provider_id'] : null;
            $couponId        = isset($row['coupon_id']) ? $row['coupon_id'] : null;

            if (!array_key_exists($eventId, $events)) {
                $events[$eventId] = [
                    'id'                    => $eventId,
                    'name'                  => $row['event_name'],
                    'status'                => $row['event_status'],
                    'bookingOpens'          => $row['event_bookingOpens'] && $row['event_bookingOpens'] !== '0000-00-00 00:00:00' ?
                        DateTimeService::getCustomDateTimeFromUtc($row['event_bookingOpens']) : null,
                    'bookingCloses'         => $row['event_bookingCloses'] && $row['event_bookingCloses'] !== '0000-00-00 00:00:00' ?
                        DateTimeService::getCustomDateTimeFromUtc($row['event_bookingCloses']) : null,
                    'bookingOpensRec'       => isset($row['event_bookingOpensRec']) ?
                        $row['event_bookingOpensRec'] : null,
                    'bookingClosesRec'      => isset($row['event_bookingClosesRec']) ?
                        $row['event_bookingClosesRec'] : null,
                    'ticketRangeRec'        => isset($row['event_ticketRangeRec']) ?
                        $row['event_ticketRangeRec'] : null,
                    'recurring'             => [
                        'cycle'            => $row['event_recurringCycle'],
                        'order'            => $row['event_recurringOrder'],
                        'cycleInterval'    => $row['event_recurringInterval'],
                        'monthlyRepeat'    => isset($row['event_recurringMonthly']) ? $row['event_recurringMonthly'] : null,
                        'monthDate'        => !empty($row['event_monthlyDate']) && $row['event_monthlyDate'] !== '0000-00-00 00:00:00' ?
                            DateTimeService::getCustomDateTimeFromUtc($row['event_monthlyDate']) : null,
                        'monthlyOnRepeat'  => isset($row['event_monthlyOnRepeat']) ? $row['event_monthlyOnRepeat'] : null,
                        'monthlyOnDay'     => isset($row['event_monthlyOnDay']) ? $row['event_monthlyOnDay'] : null,
                        'until'            => !empty($row['event_recurringUntil']) && $row['event_recurringUntil'] !== '0000-00-00 00:00:00' ?
                            DateTimeService::getCustomDateTimeFromUtc($row['event_recurringUntil']) : null,
                    ],
                    'bringingAnyone'        => $row['event_bringingAnyone'],
                    'bookMultipleTimes'     => $row['event_bookMultipleTimes'],
                    'maxCapacity'           => !empty($row['event_maxCapacity']) ? $row['event_maxCapacity'] : null,
                    'maxCustomCapacity'     => !empty($row['event_maxCustomCapacity']) ? $row['event_maxCustomCapacity'] : null,
                    'maxExtraPeople'        => !empty($row['event_maxExtraPeople']) ? $row['event_maxExtraPeople'] : null,
                    'price'                 => $row['event_price'],
                    'description'           => $row['event_description'],
                    'color'                 => $row['event_color'],
                    'show'                  => $row['event_show'],
                    'notifyParticipants'    => $row['event_notifyParticipants'],
                    'locationId'            => $row['event_locationId'],
                    'customLocation'        => $row['event_customLocation'],
                    'parentId'              => $row['event_parentId'],
                    'created'               => $row['event_created'],
                    'settings'              => isset($row['event_settings']) ? $row['event_settings'] : null,
                    'zoomUserId'            => isset($row['event_zoomUserId']) ? $row['event_zoomUserId'] : null,
                    'organizerId'           => isset($row['event_organizerId']) ? $row['event_organizerId'] : null,
                    'translations'          => isset($row['event_translations']) ? $row['event_translations'] : null,
                    'deposit'               => isset($row['event_deposit']) ? $row['event_deposit'] : null,
                    'depositPayment'        => isset($row['event_depositPayment']) ?
                        $row['event_depositPayment'] : null,
                    'depositPerPerson'      => isset($row['event_depositPerPerson']) ?
                        $row['event_depositPerPerson'] : null,
                    'fullPayment'           => isset($row['event_fullPayment']) ?
                        $row['event_fullPayment'] : null,
                    'customPricing'         => isset($row['event_customPricing']) ?
                        $row['event_customPricing'] : null,
                    'closeAfterMin'         => isset($row['event_closeAfterMin']) ? $row['event_closeAfterMin'] : null,
                    'closeAfterMinBookings' => isset($row['event_closeAfterMinBookings']) ? $row['event_closeAfterMinBookings'] : null,
                    'aggregatedPrice'       => isset($row['event_aggregatedPrice']) ? $row['event_aggregatedPrice'] : null,
                ];
            }

            if ($galleryId) {
                $events[$eventId]['gallery'][$galleryId]['id'] = $row['gallery_id'];
                $events[$eventId]['gallery'][$galleryId]['pictureFullPath']  = $row['gallery_picture_full'];
                $events[$eventId]['gallery'][$galleryId]['pictureThumbPath'] = $row['gallery_picture_thumb'];
                $events[$eventId]['gallery'][$galleryId]['position']         = $row['gallery_position'];
            }

            if ($providerId) {
                $events[$eventId]['providers'][$providerId] =
                    [
                        'id'               => $providerId,
                        'firstName'        => $row['provider_firstName'],
                        'lastName'         => $row['provider_lastName'],
                        'email'            => $row['provider_email'],
                        'note'             => $row['provider_note'],
                        'description'      => $row['provider_description'],
                        'phone'            => $row['provider_phone'],
                        'pictureFullPath'  =>
                            isset($row['provider_pictureFullPath']) ? $row['provider_pictureFullPath'] : null,
                        'pictureThumbPath' =>
                            isset($row['provider_pictureFullPath']) ? $row['provider_pictureThumbPath'] : null,
                        'type'             => 'provider',
                        'googleCalendar' => [
                            'id'         =>  isset($row['google_calendar_id']) ? $row['google_calendar_id'] : null,
                            'token'      =>  isset($row['google_calendar_token']) ? $row['google_calendar_token'] : null,
                            'calendarId' =>  isset($row['google_calendar_calendar_id']) ? $row['google_calendar_calendar_id'] : null
                        ],
                        'translations'     => $row['provider_translations'],
                        'timeZone'         => isset($row['provider_timeZone']) ? $row['provider_timeZone'] : null,
                        'outlookCalendar'  => [
                            'id'         =>  isset($row['outlook_calendar_id']) ? $row['outlook_calendar_id'] : null,
                            'token'      =>  isset($row['outlook_calendar_token']) ? $row['outlook_calendar_token'] : null,
                            'calendarId' =>  isset($row['outlook_calendar_calendar_id']) ? $row['outlook_calendar_calendar_id'] : null
                        ],
                    ];
            }


            if ($eventPeriodId && !isset($events[$eventId]['periods'][$eventPeriodId])) {
                $zoomMeetingJson = !empty($row['event_periodZoomMeeting']) ?
                    json_decode($row['event_periodZoomMeeting'], true) : null;

                $events[$eventId]['periods'][$eventPeriodId] = [
                    'id'             => $eventPeriodId,
                    'eventId'        => $eventId,
                    'periodStart'    => DateTimeService::getCustomDateTimeFromUtc($row['event_periodStart']),
                    'periodEnd'      => DateTimeService::getCustomDateTimeFromUtc($row['event_periodEnd']),
                    'zoomMeeting'    => [
                        'id'       => $zoomMeetingJson ? $zoomMeetingJson['id'] : null,
                        'startUrl' => $zoomMeetingJson ? $zoomMeetingJson['startUrl'] : null,
                        'joinUrl'  => $zoomMeetingJson ? $zoomMeetingJson['joinUrl'] : null,
                    ],
                    'lessonSpace'            => !empty($row['event_periodLessonSpace']) ?
                        $row['event_periodLessonSpace'] : null,
                    'bookings'               => [],
                    'googleCalendarEventId'  => !empty($row['event_googleCalendarEventId']) ?
                        $row['event_googleCalendarEventId'] : null,
                    'googleMeetUrl'          => !empty($row['event_googleMeetUrl']) ?
                        $row['event_googleMeetUrl'] : null,
                    'outlookCalendarEventId' => !empty($row['event_outlookCalendarEventId']) ?
                        $row['event_outlookCalendarEventId'] : null,
                    'microsoftTeamsUrl'     => !empty($row['event_microsoftTeamsUrl']) ?
                        $row['event_microsoftTeamsUrl'] : null,
                    'appleCalendarEventId'   => !empty($row['event_appleCalendarEventId']) ?
                        $row['event_appleCalendarEventId'] : null
                ];
            }

            if ($tagId && !isset($events[$eventId]['tags'][$tagId])) {
                $events[$eventId]['tags'][$tagId] = [
                    'id'             => $tagId,
                    'eventId'        => $eventId,
                    'name'           => $row['event_tagName']
                ];
            }

            if ($bookingId && !isset($events[$eventId]['bookings'][$bookingId])) {
                $events[$eventId]['bookings'][$bookingId] = [
                    'id'            => $bookingId,
                    'appointmentId' => null,
                    'customerId'    => $row['booking_customerId'],
                    'status'        => $row['booking_status'],
                    'price'         => $row['booking_price'],
                    'persons'       => $row['booking_persons'],
                    'customFields'  => !empty($row['booking_customFields']) ? $row['booking_customFields'] : null,
                    'info'          => !empty($row['booking_info']) ? $row['booking_info'] : null,
                    'utcOffset'     => isset($row['booking_utcOffset']) ? $row['booking_utcOffset'] : null,
                    'aggregatedPrice' => isset($row['booking_aggregatedPrice']) ?
                        $row['booking_aggregatedPrice'] : null,
                    'token'         => isset($row['booking_token']) ? $row['booking_token'] : null,
                    'created'       => !empty($row['booking_created']) ? DateTimeService::getCustomDateTimeFromUtc($row['booking_created']) : null,
                    'tax'           => isset($row['booking_tax']) ? $row['booking_tax'] : null,
                ];
            }

            if ($bookingTicketId && !isset($events[$eventId]['bookings'][$bookingId]['ticketsData'][$bookingTicketId])) {
                $events[$eventId]['bookings'][$bookingId]['ticketsData'][$bookingTicketId] = [
                    'id'                => $bookingTicketId,
                    'eventTicketId'     => $row['booking_ticket_eventTicketId'],
                    'customerBookingId' => $bookingId,
                    'persons'           => $row['booking_ticket_persons'],
                    'price'             => $row['booking_ticket_price'],
                ];
            }

            if ($ticketId && !isset($events[$eventId]['customTickets'][$ticketId])) {
                $events[$eventId]['customTickets'][$ticketId] = [
                    'id'               => $ticketId,
                    'eventId'          => $eventId,
                    'name'             => $row['ticket_name'],
                    'enabled'          => $row['ticket_enabled'],
                    'spots'            => $row['ticket_spots'],
                    'waitingListSpots' => !empty($row['ticket_waiting_list_spots']) ? $row['ticket_waiting_list_spots'] : null,
                    'price'            => $row['ticket_price'],
                    'dateRanges'       => $row['ticket_dateRanges'],
                    'translations'     => $row['ticket_translations'],
                ];
            }

            if ($bookingId && !isset($events[$eventId]['periods'][$eventPeriodId]['bookings'][$bookingId])) {
                $events[$eventId]['periods'][$eventPeriodId]['bookings'][$bookingId] = [
                    'id'            => $bookingId,
                    'appointmentId' => null,
                    'customerId'    => $row['booking_customerId'],
                    'status'        => $row['booking_status'],
                    'price'         => $row['booking_price'],
                    'persons'       => $row['booking_persons'],
                    'customFields'  => !empty($row['booking_customFields']) ? $row['booking_customFields'] : null,
                    'info'          => !empty($row['booking_info']) ? $row['booking_info'] : null,
                    'utcOffset'     => isset($row['booking_utcOffset']) ? $row['booking_utcOffset'] : null
                ];
            }

            if ($bookingId && $paymentId) {
                $events[$eventId]['bookings'][$bookingId]['payments'][$paymentId] =
                    [
                        'id'                => $paymentId,
                        'customerBookingId' => $bookingId,
                        'status'            => $row['payment_status'],
                        'dateTime'          => DateTimeService::getCustomDateTimeFromUtc($row['payment_dateTime']),
                        'gateway'           => $row['payment_gateway'],
                        'gatewayTitle'      => $row['payment_gatewayTitle'],
                        'transactionId'     => !empty($row['payment_transactionId']) ? $row['payment_transactionId'] : null,
                        'parentId'          => !empty($row['payment_parentId']) ? $row['payment_parentId'] : null,
                        'amount'            => $row['payment_amount'],
                        'data'              => $row['payment_data'],
                        'wcOrderId'         => !empty($row['payment_wcOrderId']) ? $row['payment_wcOrderId'] : null,
                        'wcOrderItemId'     => !empty($row['payment_wcOrderItemId']) ?
                            $row['payment_wcOrderItemId'] : null,
                        'invoiceNumber'     => !empty($row['payment_invoiceNumber']) ? $row['payment_invoiceNumber'] : null,
                    ];
            }

            if ($bookingId && $customerId) {
                $events[$eventId]['bookings'][$bookingId]['customer'] =
                    [
                        'id'        => $customerId,
                        'firstName' => $row['customer_firstName'],
                        'lastName'  => $row['customer_lastName'],
                        'email'     => $row['customer_email'],
                        'note'      => $row['customer_note'],
                        'phone'     => $row['customer_phone'],
                        'gender'    => $row['customer_gender'],
                        'birthday'  => !empty($row['customer_birthday']) ? $row['customer_birthday'] : null,
                        'type'      => 'customer',
                    ];
            }

            if ($bookingId && $couponId) {
                $events[$eventId]['bookings'][$bookingId]['coupon']['id']            = $couponId;
                $events[$eventId]['bookings'][$bookingId]['coupon']['code']          = $row['coupon_code'];
                $events[$eventId]['bookings'][$bookingId]['coupon']['discount']      = $row['coupon_discount'];
                $events[$eventId]['bookings'][$bookingId]['coupon']['deduction']     = $row['coupon_deduction'];
                $events[$eventId]['bookings'][$bookingId]['coupon']['limit']         = $row['coupon_limit'];
                $events[$eventId]['bookings'][$bookingId]['coupon']['customerLimit'] = $row['coupon_customerLimit'];
                $events[$eventId]['bookings'][$bookingId]['coupon']['status']        = $row['coupon_status'];
            }

            if ($couponId) {
                $events[$eventId]['coupons'][$couponId]['id']            = $couponId;
                $events[$eventId]['coupons'][$couponId]['code']          = $row['coupon_code'];
                $events[$eventId]['coupons'][$couponId]['discount']      = $row['coupon_discount'];
                $events[$eventId]['coupons'][$couponId]['deduction']     = $row['coupon_deduction'];
                $events[$eventId]['coupons'][$couponId]['limit']         = $row['coupon_limit'];
                $events[$eventId]['coupons'][$couponId]['customerLimit'] = $row['coupon_customerLimit'];
                $events[$eventId]['coupons'][$couponId]['status']        = $row['coupon_status'];
            }
        }

        $collection = new Collection();

        foreach ($events as $key => $value) {
            $collection->addItem(
                self::create($value),
                $key
            );
        }

        return $collection;
    }
}
