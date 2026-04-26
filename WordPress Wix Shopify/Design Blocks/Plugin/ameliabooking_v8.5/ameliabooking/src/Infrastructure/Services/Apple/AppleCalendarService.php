<?php

namespace AmeliaBooking\Infrastructure\Services\Apple;

use AmeliaBooking\Application\Services\CustomField\AbstractCustomFieldApplicationService;
use AmeliaBooking\Application\Services\Placeholder\PlaceholderService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Booking\Event\EventPeriod;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Location\Location;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Factory\Booking\Appointment\AppointmentFactory;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\String\Label;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventPeriodsRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventRepository;
use AmeliaBooking\Infrastructure\Repository\Location\LocationRepository;
use AmeliaBooking\Infrastructure\Repository\User\CustomerRepository;
use AmeliaBooking\Infrastructure\Repository\User\ProviderRepository;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\AppointmentAddedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\AppointmentDeletedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\AppointmentEditedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\AppointmentStatusUpdatedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\AppointmentTimeUpdatedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\BookingAddedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\BookingApprovedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\BookingCanceledEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\BookingRejectedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Event\EventAddedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Event\EventEditedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Event\EventStatusUpdatedEventHandler;
use DateInterval;
use DateTime;
use DateTimeZone;
use Exception;
use Interop\Container\Exception\ContainerException;
use AmeliaSabre\VObject\Component\VCalendar;
use AmeliaSabre\VObject\FreeBusyGenerator;
use AmeliaSabre\VObject\Reader;
use AmeliaSabre\VObject\UUIDUtil;
use WP_Error;

class AppleCalendarService extends AbstractAppleCalendarService
{
    public const ICLOUD_URL = 'https://caldav.icloud.com';

    /**
     * @var SettingsService
     */
    private $settings;

    /**
     * AppleCalendarService constructor.
     *
     * @param Container $container
     */
    public function __construct(
        Container $container
    ) {
        $this->container = $container;
        $this->settings  = $this->container->get('domain.settings.service')->getCategorySettings('appleCalendar');
        ;
    }

    /**
     * Check if the Apple ID and app-specific password are correct
     *
     * @param $appleId
     * @param $appSpecificPassword
     *
     * @return string|bool
     */
    public function handleAppleCredentials($appleId, $appSpecificPassword)
    {
        $args = [
            'method' => 'PROPFIND',
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($appleId . ':' . $appSpecificPassword),
                'Depth' => '0',
                'Content-Type' => "text/xml; charset='UTF-8'",
            ],
            'body' => '<A:propfind xmlns:A="DAV:"><A:prop><A:current-user-principal/></A:prop></A:propfind>',
        ];

        $response = wp_remote_request(self::ICLOUD_URL, $args);

        if ($response instanceof WP_Error) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);

        if (!$body) {
            return false;
        }

        $result = simplexml_load_string($body);

        if (!$result) {
            return false;
        }

        return (string) $result->response[0]->propstat[0]->prop[0]->{'current-user-principal'}->href;
    }

    /**
     * @param string $appleId
     * @param string $password
     *
     * @return bool|string
     */
    public function getCalendarsUrl($appleId, $password)
    {
        $principalUrl = $this->handleAppleCredentials($appleId, $password);
        $url          = self::ICLOUD_URL . $principalUrl;

        $args = [
            'method' => 'PROPFIND',
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($appleId . ':' . $password),
                'Depth' => '0',
                'Content-Type' => "text/xml; charset='UTF-8'",
            ],
            'body' => '<d:propfind xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav">
                      <d:prop>
                         <c:calendar-home-set />
                      </d:prop>
                    </d:propfind>',
        ];

        $response = wp_remote_request($url, $args);

        $body = wp_remote_retrieve_body($response);

        $result = simplexml_load_string($body);

        if (!$result) {
            return false;
        }

        $calendarsUrl = $result->response[0]->propstat[0]->prop[0]->{'calendar-home-set'}->href;

        return $calendarsUrl;
    }

    /**
     * Returns apple calendar list
     *
     * @param string $appleId
     * @param string $appSpecificPassword
     *
     * @return array
     */
    public function getCalendars($appleId, $appSpecificPassword)
    {
        $calendarsUrl = $this->getCalendarsUrl($appleId, $appSpecificPassword);

        $url = $calendarsUrl;

        $args = [
            'method' => 'PROPFIND',
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode(
                    $appleId . ':' . $appSpecificPassword
                ),
                'Depth' => '1',
                'Content-Type' => "text/calendar; charset='UTF-8'",
            ],
            'body' => '<d:propfind xmlns:d="DAV:" 
                        xmlns:cs="http://calendarserver.org/ns/" 
                        xmlns:c="urn:ietf:params:xml:ns:caldav">
                          <d:prop>
                             <d:resourcetype>
                             <d:shared />
                             </d:resourcetype>
                             <d:displayname />
                             <cs:getctag />
                             <c:supported-calendar-component-set />
                             <d:current-user-privilege-set>
                               <d:privilege><d:read/><d:write/></d:privilege>
                             </d:current-user-privilege-set>         
                          </d:prop>
                        </d:propfind>',
        ];

        $response = wp_remote_request($url, $args);

        $body = wp_remote_retrieve_body($response);

        $result = simplexml_load_string($body);

        if (!$result) {
            return [];
        }

        $calendars = [];
        foreach ($result->response as $cal) {
            $calendarId = explode('/', $cal->href->__toString());
            $privileges = $cal->propstat[0]->prop[0]->{'current-user-privilege-set'}->privilege;
            $entry      = ['privilege' => 'write'];

            if ($cal->propstat[0]->prop[0]->resourcetype[0]->shared) {
                foreach ($privileges as $privilege) {
                    if (!$privilege[0]->write) {
                        $entry['privilege'] = 'read-only';
                    }
                }
            }

            $entry['id']   = $calendarId[3];
            $entry['name'] = isset($cal->propstat[0]->prop[0]->{'displayname'})
                ? $cal->propstat[0]->prop[0]->{'displayname'}->__toString()
                : '';

            if ($entry['id'] && $entry['name'] && strpos($entry['name'], 'Reminders') === false) {
                $calendars[] = $entry;
            }
        }

        return $calendars;
    }

    /**
     * Create fake appointments in provider's list so that these slots will not be available for booking
     *
     * @param Collection $providers
     * @param int        $excludeAppointmentId
     * @param DateTime   $startDateTime
     * @param DateTime   $endDateTime
     *
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws Exception
     * @throws ContainerException
     */
    public function removeSlotsFromAppleCalendar(
        $providers,
        $excludeAppointmentId,
        $startDateTime,
        $endDateTime
    ) {
        if ($this->settings['removeAppleCalendarBusySlots'] === true) {
            foreach ($providers->keys() as $providerKey) {
                /** @var Provider $provider */
                $provider = $providers->getItem($providerKey);
                if ($provider && $provider->getAppleCalendarId()) {
                    if (!array_key_exists($provider->getId()->getValue(), self::$providersAppleEvents)) {
                        $startDateTimeCopy = clone $startDateTime;

                        $startDateTimeCopy->modify('-1 days');

                        $endDateTimeCopy = clone $endDateTime;

                        $endDateTimeCopy->modify('+1 days');

                        $calendarEvents = ['calendarId' => [], 'events' => []];

                        $events = $this->getEmployeeEvents(
                            $provider,
                            $excludeAppointmentId,
                            $startDateTimeCopy,
                            $endDateTimeCopy,
                            $calendarEvents
                        );

                        self::$providersAppleEvents[$provider->getId()->getValue()] = $events;
                    } else {
                        $events = self::$providersAppleEvents[$provider->getId()->getValue()];
                    }

                    foreach ($events as $event) {
                        $data          = Reader::read($event);
                        $startDateTime = $data->VFREEBUSY->DTSTART->getDateTime();
                        $endDateTime   = $data->VFREEBUSY->DTEND->getDateTime();

                        $eventStartString = DateTimeService::getCustomDateTimeFromUtc(
                            $startDateTime->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s')
                        );

                        $eventEndString = DateTimeService::getCustomDateTimeFromUtc(
                            $endDateTime->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s')
                        );

                        /** @var Appointment $appointment */
                        $appointment = AppointmentFactory::create(
                            [
                                'bookingStart'       => $eventStartString,
                                'bookingEnd'         => $eventEndString,
                                'notifyParticipants' => false,
                                'serviceId'          => 0,
                                'providerId'         => $provider->getId()->getValue(),
                            ]
                        );

                        $provider->getAppointmentList()->addItem($appointment);
                    }
                }
            }
        }
    }

    /**
     * Get providers events within date range
     *
     * @param Provider $provider
     * @param $excludeAppointmentId
     * @param $startDateTime
     * @param $endDateTime
     * @param array $calendarEvents
     *
     * @return array
     */
    private function getEmployeeEvents(
        Provider $provider,
        $excludeAppointmentId,
        $startDateTime,
        $endDateTime,
        array &$calendarEvents
    ) {
        $allEvents = [];

        $appleCalendarId = $provider->getAppleCalendarId()->getValue();

        if ($appleCalendarId) {
            $employeeAppleCalendar = $provider && $provider->getEmployeeAppleCalendar() ? $provider->getEmployeeAppleCalendar()->toArray() : null;

            $appleId = $employeeAppleCalendar && $employeeAppleCalendar['iCloudId'] ? $employeeAppleCalendar['iCloudId'] : $this->settings['clientID'];
            $appSpecificPassword =
                $employeeAppleCalendar &&
                $employeeAppleCalendar['appSpecificPassword'] ?
                    $employeeAppleCalendar['appSpecificPassword'] :
                    $this->settings['clientSecret'];

            $events = $this->getCalendarEvents(
                $appleId,
                $appSpecificPassword,
                $excludeAppointmentId,
                $appleCalendarId,
                $startDateTime,
                $endDateTime,
                $provider
            );

            $allEvents = array_merge($allEvents, $events);
        }

        $calendarEvents['events'] = $allEvents;

        return $allEvents;
    }

    /**
     * Get providers calendar events within date range
     *
     * @param $appleId
     * @param $appSpecificPassword
     * @param $excludeAppointmentId
     * @param $calendarId
     * @param $startDateTime
     * @param $endDateTime
     * @param $provider
     *
     * @return array
     */
    public function getCalendarEvents(
        $appleId,
        $appSpecificPassword,
        $excludeAppointmentId,
        $calendarId,
        $startDateTime,
        $endDateTime,
        $provider
    ) {
        $calendarsUrl        = $this->getCalendarsUrl(
            $appleId,
            $appSpecificPassword
        );
        $startDateTimeString = $startDateTime->format('Ymd\THis\Z');

        $endDateTimeString = $endDateTime->format('Ymd\THis\Z');

        $url = $calendarsUrl . $calendarId;

        $event = '
        <C:calendar-query xmlns:C="urn:ietf:params:xml:ns:caldav" xmlns:D="DAV:">
          <D:prop>
             <C:calendar-data>
               <C:expand start=' . '"' . $startDateTimeString . '"' . ' end=' . '"' . $endDateTimeString . '"' . '/>
             </C:calendar-data>
          </D:prop>
          <C:filter>
            <C:comp-filter name="VCALENDAR">
              <C:comp-filter name="VEVENT">
                <C:time-range start=' . '"' . $startDateTimeString . '"' . ' end=' . '"' . $endDateTimeString . '"' . '/>
                <C:prop-filter name="ATTENDEE"/>
              </C:comp-filter>
            </C:comp-filter>
          </C:filter>
        </C:calendar-query>';

        $args = [
            'method' => 'REPORT',
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode(
                    $appleId . ':' . $appSpecificPassword
                ),
                'Depth' => '1',
                'Content-Type' => 'application/xml; charset=utf-8',
            ],
            'body' => $event,
        ];

        $response = wp_remote_request($url, $args);
        $body     = wp_remote_retrieve_body($response);
        $result   = simplexml_load_string($body, null, LIBXML_NOCDATA);

        if (!$result) {
            return [];
        }

        $contents = [];
        $entry    = [];

        foreach ($result as $item) {
            $entry['calendarData'] = $item->propstat->prop->{'calendar-data'};
            $calendar = Reader::read($entry['calendarData'][0]->__toString());

            foreach ($calendar->getComponents() as $component) {
                if ($component->name === 'VEVENT') {
                    $icsFileName = explode('/', $item->href->__toString());
                    $organizer   = $component->ORGANIZER ? $component->ORGANIZER->getValue() : null;

                    if (
                        strpos($icsFileName[4], 'ameliaAppointmentEvent_') !== false &&
                        $excludeAppointmentId !== null &&
                        $organizer !== null &&
                        $excludeAppointmentId !== 0 &&
                        $organizer === 'mailto:' . $provider->getEmail()->getValue()
                    ) {
                        continue;
                    }
                    $freeBusyGenerator = new FreeBusyGenerator(
                        $component->DTSTART->getDateTime(),
                        $component->DTEND->getDateTime(),
                        $item->propstat->prop->{'calendar-data'}->__toString(),
                        $startDateTime->getTimezone()
                    );

                    $freeBusy = $freeBusyGenerator->getResult();
                    $freeBusy->add('SUMMARY', $component->SUMMARY);
                    $freeBusy->add('UID', $component->UID);

                    $serialize = $freeBusy->serialize();

                    $contents[] = $serialize;
                }
            }
        }

        return $contents;
    }

    /**
     * Handle Apple Calendar Event's.
     *
     * @param Appointment|Event $appointment
     * @param string           $commandSlug
     *
     * @return void
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    public function handleEvent($appointment, $commandSlug)
    {
        try {
            $this->handleEventAction($appointment, $commandSlug);
        } catch (Exception $e) {
            /** @var AppointmentRepository $appointmentRepository */
            $appointmentRepository = $this->container->get('domain.booking.appointment.repository');

            $appointmentRepository->updateErrorColumn($appointment->getId()->getValue(), $e->getMessage());
        }
    }

    /**
     * @param $appointment
     * @param $commandSlug
     * @param null $oldStatus
     *
     * @return void
     * @throws ContainerException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     */
    private function handleEventAction($appointment, $commandSlug, $oldStatus = null)
    {
        /** @var ProviderRepository $providerRepository */
        $providerRepository = $this->container->get('domain.users.providers.repository');

        $appointmentStatus = $appointment->getStatus()->getValue();

        $provider = $providerRepository->getById($appointment->getProviderId()->getValue());

        if ($provider && $provider->getAppleCalendarId() && $provider->getAppleCalendarId()->getValue()) {
            switch ($commandSlug) {
                case AppointmentAddedEventHandler::APPOINTMENT_ADDED:
                case BookingAddedEventHandler::BOOKING_ADDED:
                    if ($appointmentStatus === 'pending' && $this->settings['insertPendingAppointments'] === false) {
                        break;
                    }

                    if (!$appointment->getAppleCalendarEventId()) {
                        $this->insertEvent($appointment, $provider);
                    } else {
                        $this->deleteEvent($appointment, $provider);
                        $this->insertEvent($appointment, $provider);
                    }
                    break;

                case AppointmentEditedEventHandler::APPOINTMENT_EDITED:
                case AppointmentTimeUpdatedEventHandler::TIME_UPDATED:
                case AppointmentStatusUpdatedEventHandler::APPOINTMENT_STATUS_UPDATED:
                case BookingCanceledEventHandler::BOOKING_CANCELED:
                case BookingApprovedEventHandler::BOOKING_APPROVED:
                case BookingRejectedEventHandler::BOOKING_REJECTED:
                    if (
                        $appointmentStatus === 'canceled' || $appointmentStatus === 'rejected' ||
                        ($appointmentStatus === 'pending' && $this->settings['insertPendingAppointments'] === false)
                    ) {
                        $this->deleteEvent($appointment, $provider);
                        break;
                    }

                    if (
                        $appointmentStatus === 'approved' && $oldStatus && $oldStatus !== 'approved' &&
                        $this->settings['insertPendingAppointments'] === false
                    ) {
                        $this->insertEvent($appointment, $provider);
                        break;
                    }

                    if (!$appointment->getAppleCalendarEventId()) {
                        $this->insertEvent($appointment, $provider);
                        break;
                    }

                    $this->deleteEvent($appointment, $provider);
                    $this->insertEvent($appointment, $provider);
                    break;

                case AppointmentDeletedEventHandler::APPOINTMENT_DELETED:
                    $this->deleteEvent($appointment, $provider);
                    break;
            }
        }
    }

    /**
     * Insert an Event in Apple Calendar.
     *
     * @param $appointment
     * @param $provider
     * @param $period
     *
     * @return void
     * @throws ContainerException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     */
    private function insertEvent($appointment, $provider, $period = null)
    {
        // Set random event_id
        $eventId = is_a($appointment, Appointment::class) ?
            'ameliaAppointmentEvent_' . UUIDUtil::getUUID() :
            'ameliaEventEvent_'  . UUIDUtil::getUUID();

        $employeeAppleCalendar = $provider && $provider->getEmployeeAppleCalendar() ? $provider->getEmployeeAppleCalendar()->toArray() : null;

        $appleId = $employeeAppleCalendar && $employeeAppleCalendar['iCloudId'] ? $employeeAppleCalendar['iCloudId'] : $this->settings['clientID'];
        $appSpecificPassword =
            $employeeAppleCalendar &&
            $employeeAppleCalendar['appSpecificPassword'] ?
                $employeeAppleCalendar['appSpecificPassword'] :
                $this->settings['clientSecret'];

        $eventUrl = $this->getAddEventUrl($eventId, $appleId, $appSpecificPassword, $provider);

        $event = $this->createEvent($eventId, $appointment, $provider, $period);

        $this->createRequest($eventUrl, $appleId, $appSpecificPassword, $event);

        $event = apply_filters('amelia_before_apple_calendar_event_added_filter', $event, $appointment->toArray(), $provider->toArray());

        do_action('amelia_before_apple_calendar_event_added', $event, $appointment->toArray(), $provider->toArray());

        if ($period) {
            /** @var EventPeriodsRepository $eventPeriodsRepository */
            $eventPeriodsRepository = $this->container->get('domain.booking.event.period.repository');
            $period->setAppleCalendarEventId(new Label($eventId));
            $eventPeriodsRepository->updateFieldById($period->getId()->getValue(), $period->getAppleCalendarEventId()->getValue(), 'appleCalendarEventId');
        } else {
            /** @var AppointmentRepository $appointmentRepository */
            $appointmentRepository = $this->container->get('domain.booking.appointment.repository');
            $appointment->setAppleCalendarEventId(new Label($eventId));
            $appointmentRepository->update($appointment->getId()->getValue(), $appointment);
        }
    }

    /**
     * @param $url
     * @param $appleId
     * @param $appSpecificPassword
     * @param $event
     *
     * @return void
     */
    private function createRequest($url, $appleId, $appSpecificPassword, $event)
    {
        $args = [
            'method' => 'PUT',
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($appleId . ':' . $appSpecificPassword),
                'Content-Type' => 'text/calendar; charset=utf-8',
            ],
            'body' => $event,
        ];

        wp_remote_request($url, $args);
    }

    /**
     * Returns a URL for adding events to Apple Calendar
     *
     * @param string $eventId
     * @param string $appleId
     * @param string $appSpecificPassword
     * @param Provider $provider
     *
     * @return string
     */
    public function getAddEventUrl(
        $eventId,
        $appleId,
        $appSpecificPassword,
        Provider $provider
    ) {
        $calendarsUrl = $this->getCalendarsUrl(
            $appleId,
            $appSpecificPassword
        );

        $calendarId = $provider->getAppleCalendarId()->getValue();

        $url = $calendarsUrl . $calendarId . '/' . $eventId . '.ics';
        return $url;
    }

    /**
     * Creating an Apple Calendar event
     *
     * @param $eventId
     * @param $appointment
     * @param $provider
     * @param null $period
     *
     * @throws ContainerException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws Exception
     */
    private function createEvent($eventId, $appointment, $provider, $period = null)
    {
        /** @var LocationRepository $locationRepository */
        $locationRepository = $this->container->get('domain.locations.repository');

        /** @var AbstractCustomFieldApplicationService $customFieldService */
        $customFieldService = $this->container->get('application.customField.service');

        $type = $period ? Entities::EVENT : Entities::APPOINTMENT;
        /** @var PlaceholderService $placeholderService */
        $placeholderService = $this->container->get("application.placeholder.{$type}.service");

        $appointmentLocationId = $appointment && $appointment->getLocationId() ? $appointment->getLocationId()->getValue() : null;
        $providerLocationId    = $provider->getLocationId() ? $provider->getLocationId()->getValue() : null;

        $locationId = $appointmentLocationId ?: $providerLocationId;

        /** @var Location $location */
        $location = $locationId ? $locationRepository->getById($locationId) : null;

        $address = $customFieldService->getCalendarEventLocation($appointment);

        $appointmentArray           = $appointment->toArray();
        $appointmentArray['sendCF'] = true;

        $placeholderData = $placeholderService->getPlaceholdersData($appointmentArray);

        $start    = $period ?  clone $period->getPeriodStart()->getValue() : clone $appointment->getBookingStart()->getValue();
        $timezone = $start->getTimezone()->getName();
        if ($period) {
            $time = (int)$period->getPeriodEnd()->getValue()->format('H') * 60 + (int)$period->getPeriodEnd()->getValue()->format('i');
            $end  = DateTimeService::getCustomDateTimeObject(
                $start->format('Y-m-d')
            )->add(new DateInterval('PT' . $time . 'M'));
        } else {
            $end = clone $appointment->getBookingEnd()->getValue();
        }

        if ($this->settings['includeBufferTimeAppleCalendar'] === true && $type === Entities::APPOINTMENT) {
            $timeBefore = $appointment->getService()->getTimeBefore() ?
                $appointment->getService()->getTimeBefore()->getValue() : 0;
            $timeAfter  = $appointment->getService()->getTimeAfter() ?
                $appointment->getService()->getTimeAfter()->getValue() : 0;
            $start->modify('-' . $timeBefore . ' second');
            $end->modify('+' . $timeAfter . ' second');
        }

        $attendees = $this->getAttendees($appointment);

        $description = $placeholderService->applyPlaceholders(
            $period ? $this->settings['description']['event'] : $this->settings['description']['appointment'],
            $placeholderData
        );

        $subject = $placeholderService->applyPlaceholders(
            $period ? $this->settings['title']['event'] : $this->settings['title']['appointment'],
            $placeholderData
        );

        $providerEmail = $provider->getEmail()->getValue();

        $startDateTime = $start->setTimezone(new DateTimeZone($timezone));
        $endDateTime   = $end->setTimezone(new DateTimeZone($timezone));

        $calendar = new VCalendar();

        $event = $calendar->add(
            'VEVENT',
            [
            'UID' => $eventId,
            'SUMMARY' => $subject,
            'DESCRIPTION' => $description,
            ]
        );

        if ($period && $period->getPeriodStart()->getValue()->diff($period->getPeriodEnd()->getValue())->format('%a') !== '0') {
            $periodStart = $period->getPeriodStart()->getValue();
            $periodEnd   = $period->getPeriodEnd()->getValue();
            $event->add('DTSTART', $periodStart);
            $event->add('DTEND', $endDateTime);
            $event->add('RRULE', 'FREQ=DAILY;INTERVAL=1;UNTIL=' . $periodEnd->format('Ymd\THis'));
        } else {
            $event->add('DTSTART', $startDateTime);
            $event->add('DTEND', $endDateTime);
        }

        if ($location) {
            $event->add(
                'LOCATION',
                $address
                ? $address
                : ($location->getAddress() && $location->getAddress()->getValue()
                    ? $location->getAddress()->getValue()
                : $location->getName()->getValue())
            );
        }

        $calendar->PRODID = '-//AMELIA//EN';
        $event->add('ORGANIZER', 'mailto:' . $providerEmail);
        foreach ($attendees as $attendee) {
            $event->add('ATTENDEE', 'mailto:' . $attendee['emailAddress']);
        }

        $calendar->validate();

        return $calendar->serialize();
    }

    // Returns the customer's email address
    private function getAttendees($appointment)
    {
        $attendees = [];

        if ($this->settings['addAttendees'] === true) {

            /** @var CustomerRepository $customerRepository */
            $customerRepository = $this->container->get('domain.users.customers.repository');

            $bookings = $appointment->getBookings()->getItems();

            /** @var CustomerBooking $booking */
            foreach ($bookings as $booking) {
                $bookingStatus = $booking->getStatus()->getValue();

                if (
                    $bookingStatus === 'approved' ||
                    ($bookingStatus === 'pending' && $this->settings['insertPendingAppointments'] === true)
                ) {
                    $customer = $customerRepository->getById($booking->getCustomerId()->getValue());

                    if ($customer->getEmail()->getValue()) {
                        $attendees[] = [
                            'emailAddress' => $customer->getEmail()->getValue(),
                        ];
                    }
                }
            }
        }

        return $attendees;
    }

    // Deletes an event from an Apple Calendar
    private function deleteEvent($appointment, $provider)
    {
        if ($appointment->getAppleCalendarEventId()) {
            do_action('amelia_before_apple_calendar_event_deleted', $appointment->toArray(), $provider->toArray());

            $employeeAppleCalendar = $provider && $provider->getEmployeeAppleCalendar() ? $provider->getEmployeeAppleCalendar()->toArray() : null;

            $appleCalendarId = $provider && $provider->getAppleCalendarId() ? $provider->getAppleCalendarId()->getValue() : null;

            /** @var AbstractAppleCalendarService $appleCalendarService */
            $appleCalendarService = $this->container->get('infrastructure.apple.calendar.service');

            $appleId = $employeeAppleCalendar && $employeeAppleCalendar['iCloudId'] ? $employeeAppleCalendar['iCloudId'] : $this->settings['clientID'];
            $appSpecificPassword =
                $employeeAppleCalendar &&
                $employeeAppleCalendar['appSpecificPassword'] ?
                    $employeeAppleCalendar['appSpecificPassword'] :
                    $this->settings['clientSecret'];

            $eventId    = $appointment->getAppleCalendarEventId();
            $calendarId = $provider->getAppleCalendarId()->getValue();

            $calendarsUrl = $this->getCalendarsUrl(
                $appleId,
                $appSpecificPassword
            );

            $url = $calendarsUrl . $calendarId . '/' . $eventId->getValue() . '.ics';

            $args = [
                'method' => 'DELETE',
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode(
                        $appleId . ':' . $appSpecificPassword
                    ),
                    'Content-Type' => 'text/calendar; charset=utf-8',
                ],
            ];

            wp_remote_request($url, $args);
        }
    }

    /**
     * @throws ContainerException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     */
    private function updateEvent($appointment, $period, $provider)
    {
        $entity  = $period ?: $appointment;
        $eventId = $entity->getAppleCalendarEventId();
        if ($entity->getAppleCalendarEventId()) {
            $event = $this->createEvent($eventId->getValue(), $appointment, $provider, $period);

            $event = apply_filters('amelia_before_apple_calendar_event_updated_filter', $event, $period->toArray(), $provider->toArray());

            do_action('amelia_before_apple_calendar_event_updated', $event, $period->toArray(), $provider->toArray());

            $employeeAppleCalendar = $provider && $provider->getEmployeeAppleCalendar() ? $provider->getEmployeeAppleCalendar()->toArray() : null;

            $calendarId = $provider && $provider->getAppleCalendarId() ? $provider->getAppleCalendarId()->getValue() : null;

            /** @var AbstractAppleCalendarService $appleCalendarService */
            $appleCalendarService = $this->container->get('infrastructure.apple.calendar.service');

            $appleId = $employeeAppleCalendar && $employeeAppleCalendar['iCloudId'] ? $employeeAppleCalendar['iCloudId'] : $this->settings['clientID'];
            $appSpecificPassword =
                $employeeAppleCalendar &&
                $employeeAppleCalendar['appSpecificPassword'] ?
                    $employeeAppleCalendar['appSpecificPassword'] :
                    $this->settings['clientSecret'];

            $calendarsUrl = $this->getCalendarsUrl(
                $appleId,
                $appSpecificPassword
            );

            $url  = $calendarsUrl . $calendarId . '/' . $eventId->getValue() . '.ics';
            $args = [
                'method' => 'PUT',
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($appleId . ':' . $appSpecificPassword),
                    'Content-Type' => 'text/calendar; charset=utf-8',
                ],
                'body' => $event,
            ];

            wp_remote_request($url, $args);
        }
    }

    public function handleEventPeriod(
        $event,
        $commandSlug,
        $periods,
        $newProviders = null,
        $removeProviders = null
    ) {
        try {
            $this->handleEventPeriodAction($event, $commandSlug, $periods);
        } catch (Exception $e) {
            /** @var EventRepository $eventRepository */
            $eventRepository = $this->container->get('domain.booking.event.repository');

            $eventRepository->updateErrorColumn($event->getId()->getValue(), $e->getMessage());
        }
    }

    /**
     * @throws ContainerException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     */
    private function handleEventPeriodAction(
        Event $event,
        $commandSlug,
        Collection $periods
    ) {
        /** @var ProviderRepository $providerRepository */
        $providerRepository = $this->container->get('domain.users.providers.repository');

        if ($event->getOrganizerId()) {
            $provider = $providerRepository->getById($event->getOrganizerId()->getValue());

            if ($provider && $provider->getAppleCalendarId() && $provider->getAppleCalendarId()->getValue()) {
                /** @var EventPeriod $period */
                foreach ($periods->getItems() as $period) {
                    switch ($commandSlug) {
                        case EventAddedEventHandler::EVENT_ADDED:
                        case EventEditedEventHandler::TIME_UPDATED:
                        case EventEditedEventHandler::PROVIDER_CHANGED:
                            if (!$period->getAppleCalendarEventId()) {
                                $this->insertEvent($event, $provider, $period);
                                break;
                            } else {
                                $this->deleteEvent($period, $provider);
                                $this->insertEvent($event, $provider, $period);
                            }
                            break;

                        case EventEditedEventHandler::EVENT_PERIOD_DELETED:
                            $this->deleteEvent($period, $provider);
                            break;
                        case BookingAddedEventHandler::BOOKING_ADDED:
                        case BookingCanceledEventHandler::BOOKING_CANCELED:
                            $this->updateEvent($event, $period, $provider);
                            break;
                        case EventStatusUpdatedEventHandler::EVENT_STATUS_UPDATED:
                            if ($event->getStatus()->getValue() === 'rejected') {
                                $this->deleteEvent($period, $provider);
                            } elseif ($event->getStatus()->getValue() === 'approved') {
                                $this->insertEvent($event, $provider, $period);
                            }
                            break;
                        case EventEditedEventHandler::EVENT_PERIOD_ADDED:
                            $this->insertEvent($event, $provider, $period);
                            break;
                    }
                }
            }
        }
    }
}
