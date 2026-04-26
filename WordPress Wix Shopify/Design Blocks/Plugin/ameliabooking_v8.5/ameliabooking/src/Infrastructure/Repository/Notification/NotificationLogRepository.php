<?php

namespace AmeliaBooking\Infrastructure\Repository\Notification;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Notification\Notification;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Factory\Booking\Appointment\AppointmentFactory;
use AmeliaBooking\Domain\Factory\Booking\Event\EventFactory;
use AmeliaBooking\Domain\Factory\Notification\NotificationLogFactory;
use AmeliaBooking\Domain\Factory\User\UserFactory;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\ValueObjects\String\Status;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Connection;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\CustomerBookingsToEventsPeriodsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\CustomerBookingsToExtrasTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\EventsPeriodsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\EventsProvidersTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\EventsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Coupon\CouponsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Payment\PaymentsTable;

/**
 * Class NotificationLogRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Notification
 */
class NotificationLogRepository extends AbstractRepository
{
    public const FACTORY = NotificationLogFactory::class;

    /** @var string */
    protected $notificationsTable;

    /** @var string */
    protected $appointmentsTable;

    /** @var string */
    protected $bookingsTable;

    /** @var string */
    protected $usersTable;

    /**
     * NotificationLogRepository constructor.
     *
     * @param Connection $connection
     * @param string     $table
     * @param string     $notificationsTable
     * @param string     $appointmentsTable
     * @param string     $bookingsTable
     * @param string     $usersTable
     */
    public function __construct(
        Connection $connection,
        $table,
        $notificationsTable,
        $appointmentsTable,
        $bookingsTable,
        $usersTable
    ) {
        parent::__construct($connection, $table);
        $this->notificationsTable = $notificationsTable;
        $this->appointmentsTable  = $appointmentsTable;
        $this->bookingsTable      = $bookingsTable;
        $this->usersTable         = $usersTable;
    }

    /**
     * @param Notification $notification
     * @param int|null     $userId
     * @param int|null     $appointmentId
     * @param int|null     $eventId
     * @param int|null     $packageCustomerId
     * @param string|null  $data
     *
     * @return int
     *
     * @throws QueryExecutionException
     * @throws \Exception
     */
    public function add($notification, $userId, $appointmentId = null, $eventId = null, $packageCustomerId = null, $data = null)
    {
        $notificationData = $notification->toArray();

        $params = [
            ':notificationId'    => $notificationData['id'],
            ':userId'            => $userId,
            ':appointmentId'     => $appointmentId,
            ':packageCustomerId' => $packageCustomerId,
            ':eventId'           => $eventId,
            ':sentDateTime'      => DateTimeService::getNowDateTimeInUtc(),
            ':data'              => $data,
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO {$this->table} 
                (`notificationId`, `userId`, `appointmentId`, `eventId`, `packageCustomerId`, `sentDateTime`, `sent`, `data`)
                VALUES (:notificationId, :userId, :appointmentId, :eventId, :packageCustomerId, :sentDateTime, 0, :data)"
            );

            $res = $statement->execute($params);

            if (!$res) {
                throw new QueryExecutionException('Unable to add data in ' . __CLASS__);
            }

            return $this->connection->lastInsertId();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to add data in ' . __CLASS__, $e->getCode(), $e);
        }
    }

    /**
     * @param int    $entityId
     * @param string $entityType
     * @param int    $userId
     * @param array  $notificationsIds
     *
     * @return void
     * @throws QueryExecutionException
     */
    public function invalidateSentEmails($entityId, $entityType, $userId, $notificationsIds)
    {
        if (empty($notificationsIds)) {
            return;
        }

        $params = [
            ":$entityType" . 'Id' => $entityId,
        ];

        $userQuery = '';

        if ($userId) {
            $params[':userId'] = $userId;

            $userQuery = ' AND userId = :userId';
        }

        $queryNotificationsIds = [];

        foreach ($notificationsIds as $index => $value) {
            $param = ':notificationId' . $index;

            $queryNotificationsIds[] = $param;

            $params[$param] = $value;
        }

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table} SET 
                `sent` = -1
                WHERE
                {$entityType}Id = :{$entityType}Id
                AND notificationId IN (" . implode(', ', $queryNotificationsIds) . ')'
                . $userQuery
            );

            $res = $statement->execute($params);

            if (!$res) {
                throw new QueryExecutionException('Unable to save data in ' . __CLASS__);
            }
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to save data in ' . __CLASS__, $e->getCode(), $e);
        }
    }

    /**
     * Return a collection of tomorrow appointments where customer notification is not sent and should be.
     *
     * @param int   $notificationId
     * @param bool  $nextDay
     * @param array $statuses
     *
     * @return Collection
     *
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Exception
     */
    public function getCustomersNextDayAppointments($notificationId, $nextDay = true, $statuses = [])
    {
        $couponsTable = CouponsTable::getTableName();

        $customerBookingsExtrasTable = CustomerBookingsToExtrasTable::getTableName();

        $paymentsTable = PaymentsTable::getTableName();

        $startDate = DateTimeService::getCustomDateTimeObjectInUtc(
            DateTimeService::getNowDateTimeObject()->setTime(0, 0, 0)->format('Y-m-d H:i:s')
        );

        $endDate = DateTimeService::getCustomDateTimeObjectInUtc(
            DateTimeService::getNowDateTimeObject()->setTime(23, 59, 59)->format('Y-m-d H:i:s')
        );

        if ($nextDay) {
            $startDate = $startDate->modify('+1 day');
            $endDate   = $endDate->modify('+1 day');
        }

        $startCurrentDate = "STR_TO_DATE('" . $startDate->format('Y-m-d H:i:s') . "', '%Y-%m-%d %H:%i:%s')";

        $endCurrentDate = "STR_TO_DATE('" . $endDate->format('Y-m-d H:i:s') . "', '%Y-%m-%d %H:%i:%s')";

        $whereStatuses = [];

        foreach ($statuses as $key => $status) {
            $whereStatuses[] = "cb.status = '$status'";
        }

        $whereStatuses = $whereStatuses ? 'AND (' . implode(' OR ', $whereStatuses) . ')' : '';

        try {
            $statement = $this->connection->query(
                "SELECT
                    a.id AS appointment_id,
                    a.bookingStart AS appointment_bookingStart,
                    a.bookingEnd AS appointment_bookingEnd,
                    a.notifyParticipants AS appointment_notifyParticipants,
                    a.createPaymentLinks AS appointment_createPaymentLinks,
                    a.serviceId AS appointment_serviceId,
                    a.providerId AS appointment_providerId,
                    a.locationId AS appointment_locationId,
                    a.internalNotes AS appointment_internalNotes,
                    a.status AS appointment_status,
                    a.zoomMeeting AS appointment_zoom_meeting,
                    a.lessonSpace AS appointment_lesson_space,
                    a.googleMeetUrl AS appointment_google_meet_url,
                    a.microsoftTeamsUrl AS appointment_microsoft_teams_url,
                    
                    cb.id AS booking_id,
                    cb.customerId AS booking_customerId,
                    cb.status AS booking_status,
                    cb.price AS booking_price,
                    cb.customFields AS booking_customFields,
                    cb.info AS booking_info,
                    cb.utcOffset AS booking_utcOffset,
                    cb.aggregatedPrice AS booking_aggregatedPrice,
                    cb.persons AS booking_persons,
                    cb.duration AS booking_duration,
                    cb.created AS booking_created,
                    
                    p.id AS payment_id,
                    p.amount AS payment_amount,
                    p.dateTime AS payment_dateTime,
                    p.status AS payment_status,
                    p.gateway AS payment_gateway,
                    p.gatewayTitle AS payment_gatewayTitle,
                    p.data AS payment_data,
       
                    cbe.id AS bookingExtra_id,
                    cbe.extraId AS bookingExtra_extraId,
                    cbe.customerBookingId AS bookingExtra_customerBookingId,
                    cbe.quantity AS bookingExtra_quantity,
                    cbe.price AS bookingExtra_price,
                    cbe.aggregatedPrice AS bookingExtra_aggregatedPrice,
       
                    c.id AS coupon_id,
                    c.code AS coupon_code,
                    c.discount AS coupon_discount,
                    c.deduction AS coupon_deduction,
                    c.limit AS coupon_limit,
                    c.customerLimit AS coupon_customerLimit,
                    c.status AS coupon_status
                FROM {$this->appointmentsTable} a
                INNER JOIN {$this->bookingsTable} cb ON cb.appointmentId = a.id
                LEFT JOIN {$paymentsTable} p ON p.customerBookingId = cb.id
                LEFT JOIN {$customerBookingsExtrasTable} cbe ON cbe.customerBookingId = cb.id
                LEFT JOIN {$couponsTable} c ON c.id = cb.couponId
                WHERE a.bookingStart BETWEEN $startCurrentDate AND $endCurrentDate
                {$whereStatuses}
                AND a.notifyParticipants = 1 AND
                a.id NOT IN (
                    SELECT nl.appointmentId 
                    FROM {$this->table} nl 
                    INNER JOIN {$this->notificationsTable} n ON nl.notificationId = n.id 
                    WHERE n.id = {$notificationId} AND (nl.sent IS NULL OR nl.sent = 1) AND nl.appointmentId IS NOT NULL
                )"
            );

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find appointments in ' . __CLASS__, $e->getCode(), $e);
        }

        return AppointmentFactory::createCollection($rows);
    }

    /**
     * Return a collection of tomorrow events where customer notification is not sent and should be.
     *
     * @param $notificationId
     *
     * @return Collection
     *
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Exception
     */
    public function getCustomersNextDayEvents($notificationId, $nextDay = true)
    {
        $couponsTable  = CouponsTable::getTableName();
        $paymentsTable = PaymentsTable::getTableName();
        $eventsTable   = EventsTable::getTableName();

        $eventsPeriodsTable = EventsPeriodsTable::getTableName();

        $customerBookingsEventsPeriods = CustomerBookingsToEventsPeriodsTable::getTableName();

        $eventsProvidersTable = EventsProvidersTable::getTableName();

        $startDate = DateTimeService::getCustomDateTimeObjectInUtc(
            DateTimeService::getNowDateTimeObject()->setTime(0, 0, 0)->format('Y-m-d H:i:s')
        );
        $endDate   = DateTimeService::getCustomDateTimeObjectInUtc(
            DateTimeService::getNowDateTimeObject()->setTime(23, 59, 59)->format('Y-m-d H:i:s')
        );

        if ($nextDay) {
            $startDate = $startDate->modify('+1 day');
            $endDate   = $endDate->modify('+1 day');
        }

        $startCurrentDate = "STR_TO_DATE('" . $startDate->format('Y-m-d H:i:s') . "', '%Y-%m-%d %H:%i:%s')";
        $endCurrentDate   = "STR_TO_DATE('" . $endDate->format('Y-m-d H:i:s') . "', '%Y-%m-%d %H:%i:%s')";

        try {
            $statement = $this->connection->query(
                "SELECT
                    e.id AS event_id,
                    e.name AS event_name,
                    e.status AS event_status,
                    e.bookingOpens AS event_bookingOpens,
                    e.bookingCloses AS event_bookingCloses,
                    e.recurringCycle AS event_recurringCycle,
                    e.recurringOrder AS event_recurringOrder,
                    e.recurringUntil AS event_recurringUntil,
                    e.maxCapacity AS event_maxCapacity,
                    e.price AS event_price,
                    e.description AS event_description,
                    e.color AS event_color,
                    e.show AS event_show,
                    e.locationId AS event_locationId,
                    e.customLocation AS event_customLocation,
                    e.parentId AS event_parentId,
                    e.created AS event_created,
                    e.notifyParticipants AS event_notifyParticipants,
                    e.zoomUserId AS event_zoomUserId,
                    e.deposit AS event_deposit,
                    e.depositPayment AS event_depositPayment,
                    e.depositPerPerson AS event_depositPerPerson,
                    e.organizerId AS event_organizerId,
                     
                    ep.id AS event_periodId,
                    ep.periodStart AS event_periodStart,
                    ep.periodEnd AS event_periodEnd,
                    ep.zoomMeeting AS event_periodZoomMeeting,
                    ep.lessonSpace AS event_periodLessonSpace,
                    ep.googleMeetUrl AS event_googleMeetUrl,
                    
                    cb.id AS booking_id,
                    cb.customerId AS booking_customerId,
                    cb.status AS booking_status,
                    cb.price AS booking_price,
                    cb.customFields AS booking_customFields,
                    cb.info AS booking_info,
                    cb.utcOffset AS booking_utcOffset,
                    cb.aggregatedPrice AS booking_aggregatedPrice,
                    cb.persons AS booking_persons,
                    cb.created AS booking_created,
        
                    p.id AS payment_id,
                    p.amount AS payment_amount,
                    p.dateTime AS payment_dateTime,
                    p.status AS payment_status,
                    p.gateway AS payment_gateway,
                    p.gatewayTitle AS payment_gatewayTitle,
                    p.data AS payment_data,
       
                    pu.id AS provider_id,
                    pu.firstName AS provider_firstName,
                    pu.lastName AS provider_lastName,
                    pu.email AS provider_email,
                    pu.note AS provider_note,
                    pu.description AS provider_description,
                    pu.phone AS provider_phone,
                    pu.gender AS provider_gender,
                    pu.pictureFullPath AS provider_pictureFullPath,
                    pu.pictureThumbPath AS provider_pictureThumbPath,
                    pu.translations AS provider_translations,
                    
                    c.id AS coupon_id,
                    c.code AS coupon_code,
                    c.discount AS coupon_discount,
                    c.deduction AS coupon_deduction,
                    c.limit AS coupon_limit,
                    c.customerLimit AS coupon_customerLimit,
                    c.status AS coupon_status
                FROM {$eventsTable} e
                INNER JOIN {$eventsPeriodsTable} ep ON ep.eventId = e.id
                INNER JOIN {$customerBookingsEventsPeriods} cbe ON cbe.eventPeriodId = ep.id
                INNER JOIN {$this->bookingsTable} cb ON cb.id = cbe.customerBookingId
                LEFT JOIN {$paymentsTable} p ON p.customerBookingId = cb.id
                LEFT JOIN {$eventsProvidersTable} epr ON epr.eventId = e.id
                LEFT JOIN {$this->usersTable} pu ON pu.id = epr.userId
                LEFT JOIN {$couponsTable} c ON c.id = cb.couponId
                WHERE ep.periodStart BETWEEN {$startCurrentDate} AND {$endCurrentDate}
                AND cb.status = 'approved'
                AND e.status = 'approved'
                AND e.notifyParticipants = 1 AND
                e.id NOT IN (
                    SELECT nl.eventId 
                    FROM {$this->table} nl 
                    INNER JOIN {$this->notificationsTable} n ON nl.notificationId = n.id 
                    WHERE n.id = {$notificationId} AND (nl.sent IS NULL OR nl.sent = 1) AND nl.eventId IS NOT NULL
                )"
            );

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find appointments in ' . __CLASS__, $e->getCode(), $e);
        }

        return EventFactory::createCollection($rows);
    }

    /**
     * Return a collection of tomorrow appointments where provider notification is not sent and should be.
     *
     * @param int   $notificationId
     * @param bool  $nextDay
     * @param array $statuses
     *
     * @return Collection
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Exception
     */
    public function getProvidersNextDayAppointments($notificationId, $nextDay, $statuses)
    {
        $couponsTable = CouponsTable::getTableName();

        $customerBookingsExtrasTable = CustomerBookingsToExtrasTable::getTableName();

        $paymentsTable = PaymentsTable::getTableName();

        $startDate = DateTimeService::getCustomDateTimeObjectInUtc(
            DateTimeService::getNowDateTimeObject()->setTime(0, 0, 0)->format('Y-m-d H:i:s')
        );

        $endDate = DateTimeService::getCustomDateTimeObjectInUtc(
            DateTimeService::getNowDateTimeObject()->setTime(23, 59, 59)->format('Y-m-d H:i:s')
        );

        if ($nextDay) {
            $startDate = $startDate->modify('+1 day');
            $endDate   = $endDate->modify('+1 day');
        }

        $startCurrentDate = "STR_TO_DATE('" . $startDate->format('Y-m-d H:i:s') . "', '%Y-%m-%d %H:%i:%s')";

        $endCurrentDate = "STR_TO_DATE('" . $endDate->format('Y-m-d H:i:s') . "', '%Y-%m-%d %H:%i:%s')";

        $whereStatuses = [];

        foreach ($statuses as $key => $status) {
            $whereStatuses[] = "cb.status = '$status'";
        }

        $whereStatuses = $whereStatuses ? 'AND (' . implode(' OR ', $whereStatuses) . ')' : '';

        try {
            $statement = $this->connection->query(
                "SELECT
                    a.id AS appointment_id,
                    a.bookingStart AS appointment_bookingStart,
                    a.bookingEnd AS appointment_bookingEnd,
                    a.notifyParticipants AS appointment_notifyParticipants,
                    a.serviceId AS appointment_serviceId,
                    a.providerId AS appointment_providerId,
                    a.locationId AS appointment_locationId,
                    a.internalNotes AS appointment_internalNotes,
                    a.status AS appointment_status,
                    a.zoomMeeting AS appointment_zoom_meeting,
                    a.lessonSpace AS appointment_lesson_space,
                    a.googleMeetUrl AS appointment_google_meet_url,
                    a.microsoftTeamsUrl AS appointment_microsoft_teams_url,
       
                    cb.id AS booking_id,
                    cb.customerId AS booking_customerId,
                    cb.status AS booking_status,
                    cb.price AS booking_price,
                    cb.customFields AS booking_customFields,
                    cb.persons AS booking_persons,
                    cb.aggregatedPrice AS booking_aggregatedPrice,
                    cb.duration AS booking_duration,
                    cb.created AS booking_created,
        
                    p.id AS payment_id,
                    p.amount AS payment_amount,
                    p.dateTime AS payment_dateTime,
                    p.status AS payment_status,
                    p.gateway AS payment_gateway,
                    p.gatewayTitle AS payment_gatewayTitle,
                    p.data AS payment_data,
       
                    cbe.id AS bookingExtra_id,
                    cbe.extraId AS bookingExtra_extraId,
                    cbe.customerBookingId AS bookingExtra_customerBookingId,
                    cbe.quantity AS bookingExtra_quantity,
                    cbe.price AS bookingExtra_price,
                    cbe.aggregatedPrice AS bookingExtra_aggregatedPrice,
       
                    c.id AS coupon_id,
                    c.code AS coupon_code,
                    c.discount AS coupon_discount,
                    c.deduction AS coupon_deduction,
                    c.limit AS coupon_limit,
                    c.customerLimit AS coupon_customerLimit,
                    c.status AS coupon_status
                FROM {$this->appointmentsTable} a
                INNER JOIN {$this->bookingsTable} cb ON cb.appointmentId = a.id
                LEFT JOIN {$paymentsTable} p ON p.customerBookingId = cb.id
                LEFT JOIN {$customerBookingsExtrasTable} cbe ON cbe.customerBookingId = cb.id
                LEFT JOIN {$couponsTable} c ON c.id = cb.couponId
                WHERE a.bookingStart BETWEEN $startCurrentDate AND $endCurrentDate
                {$whereStatuses} 
                AND a.id NOT IN (
                    SELECT nl.appointmentId 
                    FROM {$this->table} nl 
                    INNER JOIN {$this->notificationsTable} n ON nl.notificationId = n.id 
                    WHERE n.id = {$notificationId} AND (nl.sent IS NULL OR nl.sent = 1) AND nl.appointmentId IS NOT NULL
                )"
            );

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find appointments in ' . __CLASS__, $e->getCode(), $e);
        }

        return AppointmentFactory::createCollection($rows);
    }

    /**
     * Return a collection of tomorrow events where provider notification is not sent and should be.
     *
     * @param $notificationId
     *
     * @return Collection
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Exception
     */
    public function getProvidersNextDayEvents($notificationId, $nextDay)
    {
        $couponsTable       = CouponsTable::getTableName();
        $eventsTable        = EventsTable::getTableName();
        $eventsPeriodsTable = EventsPeriodsTable::getTableName();
        $customerBookingsEventsPeriods = CustomerBookingsToEventsPeriodsTable::getTableName();
        $eventsProvidersTable          = EventsProvidersTable::getTableName();
        $paymentsTable = PaymentsTable::getTableName();

        $startDate = DateTimeService::getCustomDateTimeObjectInUtc(
            DateTimeService::getNowDateTimeObject()->setTime(0, 0, 0)->format('Y-m-d H:i:s')
        );
        $endDate   = DateTimeService::getCustomDateTimeObjectInUtc(
            DateTimeService::getNowDateTimeObject()->setTime(23, 59, 59)->format('Y-m-d H:i:s')
        );

        if ($nextDay) {
            $startDate = $startDate->modify('+1 day');
            $endDate   = $endDate->modify('+1 day');
        }

        $startCurrentDate = "STR_TO_DATE('" . $startDate->format('Y-m-d H:i:s') . "', '%Y-%m-%d %H:%i:%s')";
        $endCurrentDate   = "STR_TO_DATE('" . $endDate->format('Y-m-d H:i:s') . "', '%Y-%m-%d %H:%i:%s')";


        try {
            $statement = $this->connection->query(
                "SELECT
                    e.id AS event_id,
                    e.name AS event_name,
                    e.status AS event_status,
                    e.bookingOpens AS event_bookingOpens,
                    e.bookingCloses AS event_bookingCloses,
                    e.recurringCycle AS event_recurringCycle,
                    e.recurringOrder AS event_recurringOrder,
                    e.recurringUntil AS event_recurringUntil,
                    e.maxCapacity AS event_maxCapacity,
                    e.price AS event_price,
                    e.description AS event_description,
                    e.color AS event_color,
                    e.show AS event_show,
                    e.locationId AS event_locationId,
                    e.customLocation AS event_customLocation,
                    e.parentId AS event_parentId,
                    e.created AS event_created,
                    e.notifyParticipants AS event_notifyParticipants,
                    e.zoomUserId AS event_zoomUserId,
                    e.deposit AS event_deposit,
                    e.depositPayment AS event_depositPayment,
                    e.depositPerPerson AS event_depositPerPerson,
                    e.organizerId AS event_organizerId,
       
                    ep.id AS event_periodId,
                    ep.periodStart AS event_periodStart,
                    ep.periodEnd AS event_periodEnd,
                    ep.zoomMeeting AS event_periodZoomMeeting,
                    ep.lessonSpace AS event_periodLessonSpace,
                    ep.googleMeetUrl AS event_googleMeetUrl,
       
                    pu.id AS provider_id,
                    pu.firstName AS provider_firstName,
                    pu.lastName AS provider_lastName,
                    pu.email AS provider_email,
                    pu.note AS provider_note,
                    pu.description AS provider_description,
                    pu.phone AS provider_phone,
                    pu.gender AS provider_gender,
                    pu.pictureFullPath AS provider_pictureFullPath,
                    pu.pictureThumbPath AS provider_pictureThumbPath,
                    pu.timeZone AS provider_timeZone,
                    
                    cb.id AS booking_id,
                    cb.customerId AS booking_customerId,
                    cb.status AS booking_status,
                    cb.price AS booking_price,
                    cb.customFields AS booking_customFields,
                    cb.persons AS booking_persons,
                    cb.created AS booking_created,
                     
                    p.id AS payment_id,
                    p.amount AS payment_amount,
                    p.dateTime AS payment_dateTime,
                    p.status AS payment_status,
                    p.gateway AS payment_gateway,
                    p.gatewayTitle AS payment_gatewayTitle,
                    p.data AS payment_data,
       
                    c.id AS coupon_id,
                    c.code AS coupon_code,
                    c.discount AS coupon_discount,
                    c.deduction AS coupon_deduction,
                    c.limit AS coupon_limit,
                    c.customerLimit AS coupon_customerLimit,
                    c.status AS coupon_status
                FROM {$eventsTable} e
                INNER JOIN {$eventsPeriodsTable} ep ON ep.eventId = e.id
                INNER JOIN {$customerBookingsEventsPeriods} cbe ON cbe.eventPeriodId = ep.id
                INNER JOIN {$this->bookingsTable} cb ON cb.id = cbe.customerBookingId
                LEFT JOIN {$paymentsTable} p ON p.customerBookingId = cb.id
                LEFT JOIN {$couponsTable} c ON c.id = cb.couponId
                LEFT JOIN {$eventsProvidersTable} epr ON epr.eventId = e.id
                LEFT JOIN {$this->usersTable} pu ON pu.id = epr.userId
                WHERE ep.periodStart BETWEEN {$startCurrentDate} AND {$endCurrentDate}
                AND cb.status = 'approved' 
                AND e.status = 'approved' 
                AND e.id NOT IN (
                    SELECT nl.eventId 
                    FROM {$this->table} nl 
                    INNER JOIN {$this->notificationsTable} n ON nl.notificationId = n.id 
                    WHERE n.id = {$notificationId} AND (nl.sent IS NULL OR nl.sent = 1) AND nl.eventId IS NOT NULL
                )"
            );

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find events in ' . __CLASS__, $e->getCode(), $e);
        }

        return EventFactory::createCollection($rows);
    }

    /**
     * Return a collection of today's past appointments where follow up notification is not sent and should be.
     *
     * @param Notification $notification
     * @param array        $statuses
     *
     * @return Collection
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function getScheduledAppointments($notification, $statuses = [])
    {
        $couponsTable = CouponsTable::getTableName();
        $customerBookingsExtrasTable = CustomerBookingsToExtrasTable::getTableName();
        $paymentsTable = PaymentsTable::getTableName();

        try {
            $currentDateTime = "STR_TO_DATE('" . DateTimeService::getNowDateTimeInUtc() . "', '%Y-%m-%d %H:%i:%s')";

            $where = '';
            if ($notification->getTimeAfter()) {
                $timeAfter =
                    apply_filters(
                        'amelia_modify_scheduled_notification_time_after',
                        $notification->getTimeAfter()->getValue(),
                        $notification->toArray()
                    );
                $lastTime  = apply_filters('amelia_modify_scheduled_notification_last_time', $timeAfter + 259200, $notification->toArray());

                $where =
                    "{$currentDateTime} BETWEEN DATE_ADD(a.bookingEnd, INTERVAL {$timeAfter} SECOND) AND DATE_ADD(a.bookingEnd, INTERVAL {$lastTime} SECOND)";
            } elseif ($notification->getTimeBefore()) {
                $timeBefore =
                    apply_filters(
                        'amelia_modify_scheduled_notification_time_before',
                        $notification->getTimeBefore()->getValue(),
                        $notification->toArray()
                    );
                $where      =
                    "({$currentDateTime} BETWEEN
                     DATE_SUB(a.bookingStart, INTERVAL {$timeBefore} SECOND) AND a.bookingStart) AND
                      (a.bookingStart >= DATE_ADD(cb.created, INTERVAL {$timeBefore} SECOND))";
            }

            $whereStatuses = [];

            foreach ($statuses as $key => $status) {
                $whereStatuses[] = "cb.status = '$status'";
            }

            $whereStatuses = $whereStatuses ? ($where ? ' AND ' : '') . '(' . implode(' OR ', $whereStatuses) . ')' : '';


            $statement = $this->connection->query(
                "SELECT
                    a.id AS appointment_id,
                    a.bookingStart AS appointment_bookingStart,
                    a.bookingEnd AS appointment_bookingEnd,
                    a.notifyParticipants AS appointment_notifyParticipants,
                    a.createPaymentLinks AS appointment_createPaymentLinks,
                    a.serviceId AS appointment_serviceId,
                    a.providerId AS appointment_providerId,
                    a.locationId AS appointment_locationId,
                    a.internalNotes AS appointment_internalNotes,
                    a.status AS appointment_status,
                    a.googleMeetUrl AS appointment_google_meet_url,
                    a.lessonSpace AS appointment_lesson_space,
                    a.zoomMeeting AS appointment_zoom_meeting,
                    a.microsoftTeamsUrl AS appointment_microsoft_teams_url,
                    
                    cb.id AS booking_id,
                    cb.customerId AS booking_customerId,
                    cb.status AS booking_status,
                    cb.price AS booking_price,
                    cb.customFields AS booking_customFields,
                    cb.info AS booking_info,
                    cb.utcOffset AS booking_utcOffset,
                    cb.aggregatedPrice AS booking_aggregatedPrice,
                    cb.persons AS booking_persons,
                    cb.duration AS booking_duration,
                    cb.created AS booking_created,
                    
                    p.id AS payment_id,
                    p.amount AS payment_amount,
                    p.dateTime AS payment_dateTime,
                    p.status AS payment_status,
                    p.gateway AS payment_gateway,
                    p.gatewayTitle AS payment_gatewayTitle,
                    p.data AS payment_data,
       
                    cbe.id AS bookingExtra_id,
                    cbe.extraId AS bookingExtra_extraId,
                    cbe.customerBookingId AS bookingExtra_customerBookingId,
                    cbe.quantity AS bookingExtra_quantity,
                    cbe.price AS bookingExtra_price,
                    cbe.aggregatedPrice AS bookingExtra_aggregatedPrice,
       
                    c.id AS coupon_id,
                    c.code AS coupon_code,
                    c.discount AS coupon_discount,
                    c.deduction AS coupon_deduction,
                    c.limit AS coupon_limit,
                    c.customerLimit AS coupon_customerLimit,
                    c.status AS coupon_status
                FROM {$this->appointmentsTable} a
                INNER JOIN {$this->bookingsTable} cb ON cb.appointmentId = a.id
                LEFT JOIN {$paymentsTable} p ON p.customerBookingId = cb.id
                LEFT JOIN {$customerBookingsExtrasTable} cbe ON cbe.customerBookingId = cb.id
                LEFT JOIN {$couponsTable} c ON c.id = cb.couponId
                WHERE {$where} 
                AND a.notifyParticipants = 1 
                {$whereStatuses}
                AND a.id NOT IN (
                    SELECT nl.appointmentId 
                    FROM {$this->table} nl 
                    INNER JOIN {$this->notificationsTable} n ON nl.notificationId = n.id 
                    WHERE n.id = {$notification->getId()->getValue()} AND (nl.sent IS NULL OR nl.sent = 1) AND nl.appointmentId IS NOT NULL
                )"
            );

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find appointments in ' . __CLASS__, $e->getCode(), $e);
        }

        return AppointmentFactory::createCollection($rows);
    }

    /**
     * Return a collection of today's past appointments where follow-up notification is not sent and should be.
     *
     * @param Notification $notification
     *
     * @return Collection
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function getScheduledEvents($notification)
    {
        $eventsTable = EventsTable::getTableName();

        $eventsPeriodsTable = EventsPeriodsTable::getTableName();

        $customerBookingsEventsPeriods = CustomerBookingsToEventsPeriodsTable::getTableName();

        $paymentsTable = PaymentsTable::getTableName();

        $currentDateTime = "STR_TO_DATE('" . DateTimeService::getNowDateTimeInUtc() . "', '%Y-%m-%d %H:%i:%s')";

        $where = "WHERE e.notifyParticipants = 1 
                AND cb.status = 'approved' 
                AND e.status = 'approved' 
                AND e.id NOT IN (
                    SELECT nl.eventId 
                    FROM {$this->table} nl 
                    INNER JOIN {$this->notificationsTable} n ON nl.notificationId = n.id 
                    WHERE n.id = {$notification->getId()->getValue()} AND (nl.sent IS NULL OR nl.sent = 1) AND nl.eventId IS NOT NULL
                )";

        if ($notification->getTimeAfter()) {
            $timeAfter = $notification->getTimeAfter()->getValue();

            $lastTime = $timeAfter + 432000;

            $where .=
                " AND {$currentDateTime} BETWEEN DATE_ADD(ep.periodEnd, INTERVAL {$timeAfter} SECOND) 
                AND DATE_ADD(ep.periodEnd, INTERVAL {$lastTime} SECOND)";
        } elseif ($notification->getTimeBefore()) {
            $timeBefore = $notification->getTimeBefore()->getValue();

            $where .=
                " AND ({$currentDateTime} BETWEEN DATE_SUB(ep.periodStart, INTERVAL {$timeBefore} SECOND) AND ep.periodStart)
                 AND (ep.periodStart >= DATE_ADD(p.created, INTERVAL {$timeBefore} SECOND))";
        }

        try {
            $statement = $this->connection->query(
                "SELECT
                    e.id AS event_id,
                    e.name AS event_name,
                    e.status AS event_status,
                    e.bookingOpens AS event_bookingOpens,
                    e.bookingCloses AS event_bookingCloses,
                    e.recurringCycle AS event_recurringCycle,
                    e.recurringOrder AS event_recurringOrder,
                    e.recurringUntil AS event_recurringUntil,
                    e.recurringInterval AS event_recurringInterval,
                    e.bringingAnyone AS event_bringingAnyone,
                    e.bookMultipleTimes AS event_bookMultipleTimes,
                    e.maxCapacity AS event_maxCapacity,
                    e.price AS event_price,
                    e.description AS event_description,
                    e.color AS event_color,
                    e.show AS event_show,
                    e.locationId AS event_locationId,
                    e.customLocation AS event_customLocation,
                    e.parentId AS event_parentId,
                    e.created AS event_created,
                    e.notifyParticipants AS event_notifyParticipants,
                    e.zoomUserId AS event_zoomUserId,
                    e.deposit AS event_deposit,
                    e.depositPayment AS event_depositPayment,
                    e.depositPerPerson AS event_depositPerPerson,
                    e.organizerId AS event_organizerId,
                    
                    ep.id AS event_periodId,
                    ep.periodStart AS event_periodStart,
                    ep.periodEnd AS event_periodEnd,
                    ep.lessonSpace AS event_periodLessonSpace,
                    ep.zoomMeeting AS event_periodZoomMeeting,
                    ep.googleMeetUrl AS event_googleMeetUrl,                    
       
                    cb.id AS booking_id,
                    cb.customerId AS booking_customerId,
                    cb.status AS booking_status,
                    cb.price AS booking_price,
                    cb.customFields AS booking_customFields,
                    cb.info AS booking_info,
                    cb.utcOffset AS booking_utcOffset,
                    cb.aggregatedPrice AS booking_aggregatedPrice,
                    cb.persons AS booking_persons,
                    cb.duration AS booking_duration,
                    cb.created AS booking_created
                FROM {$eventsTable} e
                INNER JOIN {$eventsPeriodsTable} ep ON ep.eventId = e.id
                INNER JOIN {$customerBookingsEventsPeriods} cbe ON cbe.eventPeriodId = ep.id
                INNER JOIN {$this->bookingsTable} cb ON cb.id = cbe.customerBookingId
                LEFT JOIN {$paymentsTable} p ON p.customerBookingId = cb.id
                {$where}"
            );

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find events in ' . __CLASS__, $e->getCode(), $e);
        }

        return EventFactory::createCollection($rows);
    }

    /**
     * Returns a collection of customers that have birthday on today's date and where notification is not sent
     *
     * @param $notificationType
     *
     * @return Collection
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Exception
     */
    public function getBirthdayCustomers($notificationType)
    {
        $currentDate = "STR_TO_DATE('" . DateTimeService::getNowDateTimeInUtc() . "', '%Y-%m-%d')";

        $params = [
            ':type'          => AbstractUser::USER_ROLE_CUSTOMER,
            ':statusVisible' => Status::VISIBLE,
        ];

        try {
            $statement = $this->connection->prepare(
                "SELECT * FROM {$this->usersTable} as u 
                WHERE 
                u.type = :type AND
                u.status = :statusVisible AND
                MONTH(u.birthday) = MONTH({$currentDate}) AND
                DAY(u.birthday) = DAY({$currentDate}) AND 
                u.id NOT IN (
                  SELECT nl.userID 
                  FROM {$this->table} nl 
                  INNER JOIN {$this->notificationsTable} n ON nl.notificationId = n.id 
                  WHERE n.name = 'customer_birthday_greeting' AND n.type = '{$notificationType}' AND
                  YEAR(nl.sentDateTime) = YEAR({$currentDate}) AND (nl.sent IS NULL OR nl.sent = 1)
                )"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        $items = [];
        foreach ($rows as $row) {
            $items[] = call_user_func([UserFactory::class, 'create'], $row);
        }

        return new Collection($items);
    }

    /**
     * Returns a collection of undelivered notifications
     *
     * @param string $type
     *
     * @return Collection
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function getUndeliveredNotifications($type)
    {
        $params = [
            ':type' => $type,
        ];

        $currentDateTime = "STR_TO_DATE('" . DateTimeService::getNowDateTimeInUtc() . "', '%Y-%m-%d %H:%i:%s')";

        $pastDateTime =
            "STR_TO_DATE('" .
            DateTimeService::getNowDateTimeObjectInUtc()->modify('-1 day')->format('Y-m-d H:i:s') .
            "', '%Y-%m-%d %H:%i:%s')";

        try {
            $statement = $this->connection->prepare(
                "SELECT nl.* FROM {$this->table} nl
                INNER JOIN {$this->notificationsTable} n ON nl.notificationId = n.id
                WHERE 
                nl.sent = 0 AND
                {$currentDateTime} > DATE_ADD(nl.sentDateTime, INTERVAL 300 SECOND) AND
                {$pastDateTime} < nl.sentDateTime AND
                nl.data IS NOT NULL AND 
                n.type = :type"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        $items = [];

        foreach ($rows as $row) {
            $items[] = call_user_func([static::FACTORY, 'create'], $row);
        }

        return new Collection($items);
    }

    /**
     * @param int    $userId
     * @param string $type
     * @param string $entityType
     * @param int    $entityId
     *
     * @return Collection
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function getSentNotificationsByUserAndEntity($userId, $type, $entityType, $entityId)
    {
        $entityColumn = '';

        switch ($entityType) {
            case (Entities::APPOINTMENT):
                $entityColumn = 'nl.appointmentId';

                break;
            case (Entities::EVENT):
                $entityColumn = 'nl.eventId';

                break;
            case (Entities::PACKAGE):
                $entityColumn = 'nl.packageCustomerId';

                break;
        }

        $params = [
            ':entityId' => $entityId,
            ':userId'   => $userId,
            ':type'     => $type,
        ];

        try {
            $statement = $this->connection->prepare(
                "SELECT * FROM {$this->table} nl
                WHERE nl.userId = :userId 
                  AND {$entityColumn} = :entityId
                  AND nl.notificationId IN (SELECT id FROM {$this->notificationsTable} WHERE type = :type)
                ORDER BY nl.sentDateTime DESC"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        $items = [];

        foreach ($rows as $row) {
            $items[] = call_user_func([static::FACTORY, 'create'], $row);
        }

        return new Collection($items);
    }
}
