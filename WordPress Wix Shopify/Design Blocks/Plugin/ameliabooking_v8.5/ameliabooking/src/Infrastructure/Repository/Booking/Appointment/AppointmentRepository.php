<?php

namespace AmeliaBooking\Infrastructure\Repository\Booking\Appointment;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Factory\Booking\Appointment\AppointmentFactory;
use AmeliaBooking\Domain\Factory\Booking\Appointment\CustomerBookingFactory;
use AmeliaBooking\Domain\Repository\Booking\Appointment\AppointmentRepositoryInterface;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Connection;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Location\LocationsTable;

/**
 * Class AppointmentRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Booking\Appointment
 */
class AppointmentRepository extends AbstractRepository implements AppointmentRepositoryInterface
{
    public const FACTORY = AppointmentFactory::class;

    /** @var string */
    protected $servicesTable;

    /** @var string */
    protected $bookingsTable;

    /** @var string */
    protected $customerBookingsExtrasTable;

    /** @var string */
    protected $extrasTable;

    /** @var string */
    protected $usersTable;

    /** @var string */
    protected $paymentsTable;

    /** @var string */
    protected $couponsTable;

    /** @var string */
    protected $providersLocationTable;

    /** @var string */
    protected $providerServicesTable;

    /** @var string */
    protected $packagesCustomersTable;

    /** @var string */
    protected $packagesCustomersServicesTable;

    /**
     * @param Connection $connection
     * @param string     $table
     * @param string     $servicesTable
     * @param string     $bookingsTable
     * @param string     $customerBookingsExtrasTable
     * @param string     $extrasTable
     * @param string     $usersTable
     * @param string     $paymentsTable
     * @param string     $couponsTable
     * @param string     $providersLocationTable
     * @param string     $providerServicesTable
     * @param string     $packagesCustomersTable
     * @param string     $packagesCustomersServicesTable
     */
    public function __construct(
        Connection $connection,
        $table,
        $servicesTable,
        $bookingsTable,
        $customerBookingsExtrasTable,
        $extrasTable,
        $usersTable,
        $paymentsTable,
        $couponsTable,
        $providersLocationTable,
        $providerServicesTable,
        $packagesCustomersTable,
        $packagesCustomersServicesTable
    ) {
        parent::__construct($connection, $table);

        $this->servicesTable = $servicesTable;
        $this->bookingsTable = $bookingsTable;
        $this->customerBookingsExtrasTable = $customerBookingsExtrasTable;
        $this->extrasTable   = $extrasTable;
        $this->usersTable    = $usersTable;
        $this->paymentsTable = $paymentsTable;
        $this->couponsTable  = $couponsTable;
        $this->providersLocationTable         = $providersLocationTable;
        $this->providerServicesTable          = $providerServicesTable;
        $this->packagesCustomersTable         = $packagesCustomersTable;
        $this->packagesCustomersServicesTable = $packagesCustomersServicesTable;
    }

    /**
     * @param int $id
     *
     * @return Appointment
     * @throws QueryExecutionException
     */
    public function getById($id)
    {
        try {
            $statement = $this->connection->prepare(
                "SELECT
                    a.id AS appointment_id,
                    a.bookingStart AS appointment_bookingStart,
                    a.bookingEnd AS appointment_bookingEnd,
                    a.notifyParticipants AS appointment_notifyParticipants,
                    a.createPaymentLinks AS appointment_createPaymentLinks,
                    a.internalNotes AS appointment_internalNotes,
                    a.status AS appointment_status,
                    a.serviceId AS appointment_serviceId,
                    a.providerId AS appointment_providerId,
                    a.locationId AS appointment_locationId,
                    a.googleCalendarEventId AS appointment_google_calendar_event_id,
                    a.googleMeetUrl AS appointment_google_meet_url,
                    a.outlookCalendarEventId AS appointment_outlook_calendar_event_id,
                    a.microsoftTeamsUrl AS appointment_microsoft_teams_url,
                    a.appleCalendarEventId AS appointment_apple_calendar_event_id,
                    a.zoomMeeting AS appointment_zoom_meeting,
                    a.lessonSpace AS appointment_lesson_space,
                    a.parentId AS appointment_parentId,
                    
                    cb.id AS booking_id,
                    cb.customerId AS booking_customerId,
                    cb.status AS booking_status,
                    cb.price AS booking_price,
                    cb.persons AS booking_persons,
                    cb.customFields AS booking_customFields,
                    cb.info AS booking_info,
                    cb.aggregatedPrice AS booking_aggregatedPrice,
                    cb.utcOffset AS booking_utcOffset,
                    cb.packageCustomerServiceId AS booking_packageCustomerServiceId,
                    cb.duration AS booking_duration,
                    cb.created AS booking_created,
                    cb.tax AS booking_tax,
                    
                    cbe.id AS bookingExtra_id,
                    cbe.extraId AS bookingExtra_extraId,
                    cbe.customerBookingId AS bookingExtra_customerBookingId,
                    cbe.quantity AS bookingExtra_quantity,
                    cbe.price AS bookingExtra_price,
                    cbe.aggregatedPrice AS bookingExtra_aggregatedPrice,
                    cbe.tax AS bookingExtra_tax,
                    
                    p.id AS payment_id,
                    p.packageCustomerId AS payment_packageCustomerId,
                    p.amount AS payment_amount,
                    p.created AS payment_created,
                    p.invoiceNumber AS payment_invoiceNumber,
                    p.dateTime AS payment_dateTime,
                    p.status AS payment_status,
                    p.parentId AS payment_parentId,
                    p.gateway AS payment_gateway,
                    p.gatewayTitle AS payment_gatewayTitle,
                    p.transactionId AS payment_transactionId,
                    p.data AS payment_data,
                    p.wcOrderId AS payment_wcOrderId,
                    p.wcOrderItemId AS payment_wcOrderItemId,
                    
                    c.id AS coupon_id,
                    c.code AS coupon_code,
                    c.discount AS coupon_discount,
                    c.deduction AS coupon_deduction,
                    c.expirationDate AS coupon_expirationDate,
                    c.limit AS coupon_limit,
                    c.customerLimit AS coupon_customerLimit,
                    c.status AS coupon_status,

                    pc.id AS package_customer_id,
                    pc.packageId AS package_customer_packageId,
                    pc.tax AS package_customer_tax,
                    pc.price AS package_customer_price,
                    pc.couponId AS package_customer_couponId
                FROM {$this->table} a
                INNER JOIN {$this->bookingsTable} cb ON cb.appointmentId = a.id
                LEFT JOIN {$this->packagesCustomersServicesTable} pcs ON pcs.id = cb.packageCustomerServiceId
                LEFT JOIN {$this->packagesCustomersTable} pc ON pcs.packageCustomerId = pc.id
                LEFT JOIN {$this->paymentsTable} p ON (
                    (p.customerBookingId = cb.id AND cb.packageCustomerServiceId IS NULL) OR 
                    (p.packageCustomerId = pc.id AND cb.packageCustomerServiceId IS NOT NULL AND cb.packageCustomerServiceId = pcs.id)
                    )
                LEFT JOIN {$this->customerBookingsExtrasTable} cbe ON cbe.customerBookingId = cb.id
                LEFT JOIN {$this->couponsTable} c ON (pc.couponId IS NOT NULL AND c.id = pc.couponId) OR (c.id = cb.couponId)
                WHERE a.id = :appointmentId
                ORDER BY cb.id, p.id"
            );

            $statement->bindParam(':appointmentId', $id);

            $statement->execute();

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find appointment by id in ' . __CLASS__, $e->getCode(), $e);
        }

        return call_user_func([static::FACTORY, 'createCollection'], $rows)->getItem($id);
    }

    /**
     * @param int $id
     *
     * @return Appointment
     * @throws QueryExecutionException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     */
    public function getByBookingId($id)
    {
        try {
            $statement = $this->connection->prepare(
                "SELECT
                    a.id AS appointment_id,
                    a.bookingStart AS appointment_bookingStart,
                    a.bookingEnd AS appointment_bookingEnd,
                    a.notifyParticipants AS appointment_notifyParticipants,
                    a.internalNotes AS appointment_internalNotes,
                    a.status AS appointment_status,
                    a.serviceId AS appointment_serviceId,
                    a.providerId AS appointment_providerId,
                    a.locationId AS appointment_locationId,
                    a.googleCalendarEventId AS appointment_google_calendar_event_id,
                    a.googleMeetUrl AS appointment_google_meet_url,
                    a.outlookCalendarEventId AS appointment_outlook_calendar_event_id,
                    a.microsoftTeamsUrl AS appointment_microsoft_teams_url,
                    a.appleCalendarEventId AS appointment_apple_calendar_event_id,
                    a.zoomMeeting AS appointment_zoom_meeting,
                    a.lessonSpace AS appointment_lesson_space,
                    
                    cb.id AS booking_id,
                    cb.customerId AS booking_customerId,
                    cb.status AS booking_status,
                    cb.price AS booking_price,
                    cb.persons AS booking_persons,
                    cb.customFields AS booking_customFields,
                    cb.info AS booking_info,
                    cb.utcOffset AS booking_utcOffset,
                    cb.aggregatedPrice AS booking_aggregatedPrice,
                    cb.couponId AS booking_couponId,
                    cb.duration AS booking_duration,
                    cb.created AS booking_created,
                    cb.tax AS booking_tax,
                    
                    cbe.id AS bookingExtra_id,
                    cbe.extraId AS bookingExtra_extraId,
                    cbe.customerBookingId AS bookingExtra_customerBookingId,
                    cbe.quantity AS bookingExtra_quantity,
                    cbe.price AS bookingExtra_price,
                    cbe.aggregatedPrice AS bookingExtra_aggregatedPrice,
                    cbe.tax AS bookingExtra_tax,
                    
                    p.id AS payment_id,
                    p.packageCustomerId AS payment_packageCustomerId,
                    p.amount AS payment_amount,
                    p.dateTime AS payment_dateTime,
                    p.status AS payment_status,
                    p.gateway AS payment_gateway,
                    p.parentId AS payment_parentId,
                    p.gatewayTitle AS payment_gatewayTitle, 
                    p.transactionId AS payment_transactionId,
                    p.data AS payment_data,
                    p.wcOrderId AS payment_wcOrderId,
                    p.wcOrderItemId AS payment_wcOrderItemId,
                    
                    c.id AS coupon_id,
                    c.code AS coupon_code,
                    c.discount AS coupon_discount,
                    c.deduction AS coupon_deduction,
                    c.expirationDate AS coupon_expirationDate,
                    c.limit AS coupon_limit,
                    c.customerLimit AS coupon_customerLimit,
                    c.status AS coupon_status        
                FROM {$this->table} a
                INNER JOIN {$this->bookingsTable} cb ON cb.appointmentId = a.id
                LEFT JOIN {$this->packagesCustomersTable} pc ON pc.customerId = cb.customerId
                LEFT JOIN {$this->packagesCustomersServicesTable} pcs ON pcs.id = cb.packageCustomerServiceId
                LEFT JOIN {$this->paymentsTable} p ON (
                    (p.customerBookingId = cb.id AND cb.packageCustomerServiceId IS NULL) OR
                    (p.packageCustomerId = pc.id AND cb.packageCustomerServiceId IS NOT NULL AND cb.packageCustomerServiceId = pcs.id)
                    )
                LEFT JOIN {$this->customerBookingsExtrasTable} cbe ON cbe.customerBookingId = cb.id
                LEFT JOIN {$this->couponsTable} c ON c.id = cb.couponId
                WHERE a.id = (
                  SELECT cb2.appointmentId FROM {$this->bookingsTable} cb2 WHERE cb2.id = :customerBookingId
                )
                ORDER BY a.bookingStart, cb.id"
            );

            $statement->bindParam(':customerBookingId', $id);

            $statement->execute();

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find appointment by id in ' . __CLASS__, $e->getCode(), $e);
        }

        /** @var Collection $appointments */
        $appointments = call_user_func([static::FACTORY, 'createCollection'], $rows);

        return $appointments->length() ? $appointments->getItem($appointments->keys()[0]) : null;
    }

    /**
     * @param int $id
     *
     * @return Appointment
     * @throws QueryExecutionException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     */
    public function getByPaymentId($id)
    {
        try {
            $statement = $this->connection->prepare(
                "SELECT
                    a.id AS appointment_id,
                    a.bookingStart AS appointment_bookingStart,
                    a.bookingEnd AS appointment_bookingEnd,
                    a.notifyParticipants AS appointment_notifyParticipants,
                    a.internalNotes AS appointment_internalNotes,
                    a.status AS appointment_status,
                    a.serviceId AS appointment_serviceId,
                    a.providerId AS appointment_providerId,
                    a.locationId AS appointment_locationId,
                    a.googleCalendarEventId AS appointment_google_calendar_event_id,
                    a.googleMeetUrl AS appointment_google_meet_url,
                    a.outlookCalendarEventId AS appointment_outlook_calendar_event_id,
                    a.microsoftTeamsUrl AS appointment_microsoft_teams_url,
                    a.appleCalendarEventId AS appointment_apple_calendar_event_id,
                    a.zoomMeeting AS appointment_zoom_meeting,
                    a.lessonSpace AS appointment_lesson_space,
                    
                    cb.id AS booking_id,
                    cb.customerId AS booking_customerId,
                    cb.status AS booking_status,
                    cb.price AS booking_price,
                    cb.persons AS booking_persons,
                    cb.customFields AS booking_customFields,
                    cb.info AS booking_info,
                    cb.utcOffset AS booking_utcOffset,
                    cb.aggregatedPrice AS booking_aggregatedPrice,
                    cb.couponId AS booking_couponId,
                    cb.duration AS booking_duration,
                    cb.created AS booking_created,
                    cb.tax AS booking_tax,
                    
                    cbe.id AS bookingExtra_id,
                    cbe.extraId AS bookingExtra_extraId,
                    cbe.customerBookingId AS bookingExtra_customerBookingId,
                    cbe.quantity AS bookingExtra_quantity,
                    cbe.price AS bookingExtra_price,
                    cbe.aggregatedPrice AS bookingExtra_aggregatedPrice,
                    cbe.tax AS bookingExtra_tax,
                    
                    p.id AS payment_id,
                    p.packageCustomerId AS payment_packageCustomerId,
                    p.amount AS payment_amount,
                    p.dateTime AS payment_dateTime,
                    p.status AS payment_status,
                    p.parentId AS payment_parentId,
                    p.gateway AS payment_gateway,
                    p.gatewayTitle AS payment_gatewayTitle,
                    p.transactionId AS payment_transactionId,
                    p.data AS payment_data,
                    p.invoiceNumber AS payment_invoiceNumber,
                    p.created AS payment_created,
                    
                    c.id AS coupon_id,
                    c.code AS coupon_code,
                    c.discount AS coupon_discount,
                    c.deduction AS coupon_deduction,
                    c.expirationDate AS coupon_expirationDate,
                    c.limit AS coupon_limit,
                    c.customerLimit AS coupon_customerLimit,
                    c.status AS coupon_status
                FROM {$this->table} a
                INNER JOIN {$this->bookingsTable} cb ON cb.appointmentId = a.id
                LEFT JOIN {$this->packagesCustomersTable} pc ON pc.customerId = cb.customerId
                LEFT JOIN {$this->packagesCustomersServicesTable} pcs ON pcs.id = cb.packageCustomerServiceId
                LEFT JOIN {$this->paymentsTable} p ON (
                    (p.customerBookingId = cb.id AND cb.packageCustomerServiceId IS NULL) OR
                    (p.packageCustomerId = pc.id AND cb.packageCustomerServiceId IS NOT NULL AND cb.packageCustomerServiceId = pcs.id)
                    )
                LEFT JOIN {$this->customerBookingsExtrasTable} cbe ON cbe.customerBookingId = cb.id
                LEFT JOIN {$this->couponsTable} c ON c.id = cb.couponId
                WHERE a.id IN (
                  SELECT cb2.appointmentId
                  FROM {$this->paymentsTable} p2
                  INNER JOIN {$this->bookingsTable} cb2 ON cb2.id = p2.customerBookingId
                  WHERE p2.id = :paymentId
                )
                ORDER BY a.bookingStart"
            );

            $statement->bindParam(':paymentId', $id);

            $statement->execute();

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find appointment by id in ' . __CLASS__, $e->getCode(), $e);
        }

        /** @var Collection $appointments */
        $appointments = call_user_func([static::FACTORY, 'createCollection'], $rows);

        return $appointments->length() ? $appointments->getItem($appointments->keys()[0]) : null;
    }

    /**
     * @param Appointment $entity
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function add($entity)
    {
        $data = $entity->toArray();

        $params = [
            ':bookingStart'       => DateTimeService::getCustomDateTimeInUtc($data['bookingStart']),
            ':bookingEnd'         => DateTimeService::getCustomDateTimeInUtc($data['bookingEnd']),
            ':notifyParticipants' => $data['notifyParticipants'],
            ':createPaymentLinks' => $data['createPaymentLinks'],
            ':internalNotes'      => $data['internalNotes'] ?: '',
            ':status'             => $data['status'],
            ':serviceId'          => $data['serviceId'],
            ':providerId'         => $data['providerId'],
            ':locationId'         => $data['locationId'],
            ':parentId'           => $data['parentId'],
            ':lessonSpace'        => !empty($data['lessonSpace']) ? $data['lessonSpace'] : null,
            ':error'              => '',
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO {$this->table} 
                (
                `bookingStart`,
                `bookingEnd`,
                `notifyParticipants`,
                `createPaymentLinks`,
                `internalNotes`,
                `status`,
                `locationId`,
                `serviceId`,
                `providerId`,
                `parentId`,
                `lessonSpace`,
                `error`
                )
                VALUES (
                :bookingStart,
                :bookingEnd,
                :notifyParticipants,
                :createPaymentLinks,
                :internalNotes,
                :status,
                :locationId,
                :serviceId,
                :providerId,
                :parentId,
                :lessonSpace,
                :error
                )"
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
     * @param int         $id
     * @param Appointment $entity
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function update($id, $entity)
    {
        $data = $entity->toArray();

        $params = [
            ':id'                     => $id,
            ':bookingStart'           => DateTimeService::getCustomDateTimeInUtc($data['bookingStart']),
            ':bookingEnd'             => DateTimeService::getCustomDateTimeInUtc($data['bookingEnd']),
            ':notifyParticipants'     => $data['notifyParticipants'],
            ':createPaymentLinks'     => $data['createPaymentLinks'],
            ':internalNotes'          => $data['internalNotes'],
            ':status'                 => $data['status'],
            ':locationId'             => $data['locationId'],
            ':serviceId'              => $data['serviceId'],
            ':providerId'             => $data['providerId'],
            ':googleCalendarEventId'  => $data['googleCalendarEventId'],
            ':googleMeetUrl'          => $data['googleMeetUrl'],
            ':outlookCalendarEventId' => $data['outlookCalendarEventId'],
            ':microsoftTeamsUrl'      => $data['microsoftTeamsUrl'],
            ':appleCalendarEventId'   => $data['appleCalendarEventId'],
            ':lessonSpace'            => $data['lessonSpace'],
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET
                `bookingStart` = :bookingStart,
                `bookingEnd` = :bookingEnd, 
                `notifyParticipants` = :notifyParticipants,
                `createPaymentLinks` = :createPaymentLinks,
                `internalNotes` = :internalNotes,
                `status` = :status,
                `locationId` = :locationId,
                `serviceId` = :serviceId,
                `providerId` = :providerId,
                `googleCalendarEventId` = :googleCalendarEventId,                    
                `googleMeetUrl` = :googleMeetUrl,
                `outlookCalendarEventId` = :outlookCalendarEventId,
                `microsoftTeamsUrl` = :microsoftTeamsUrl,
                `appleCalendarEventId` = :appleCalendarEventId,
                `lessonSpace` = :lessonSpace
                WHERE id = :id"
            );

            $res = $statement->execute($params);

            if (!$res) {
                throw new QueryExecutionException('Unable to save data in ' . __CLASS__);
            }

            return $res;
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to save data in ' . __CLASS__, $e->getCode(), $e);
        }
    }

    /**
     * @param int $id
     * @param int $status
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function updateStatusById($id, $status)
    {
        $params = [
            ':id'     => $id,
            ':status' => $status
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET
                `status` = :status
                WHERE id = :id"
            );

            $res = $statement->execute($params);

            if (!$res) {
                throw new QueryExecutionException('Unable to save data in ' . __CLASS__);
            }

            return $res;
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to save data in ' . __CLASS__, $e->getCode(), $e);
        }
    }

    /**
     * Returns array of current appointments where keys are Provider ID's
     * and array values are Appointments Data (modified by service padding time)
     *
     * @return array
     * @throws QueryExecutionException
     */
    public function getCurrentAppointments()
    {
        try {
            $currentDateTime = "STR_TO_DATE('" . DateTimeService::getNowDateTimeInUtc() . "', '%Y-%m-%d %H:%i:%s')";

            $statement = $this->connection->query(
                "SELECT
                a.bookingStart AS bookingStart,
                a.bookingEnd AS bookingEnd,
                a.providerId AS providerId,
                a.serviceId AS serviceId,
                s.timeBefore AS timeBefore,
                s.timeAfter AS timeAfter
                FROM {$this->table} a
                INNER JOIN {$this->servicesTable} s ON s.id = a.serviceId
                WHERE {$currentDateTime} >= a.bookingStart
                AND {$currentDateTime} <= a.bookingEnd
                ORDER BY a.bookingStart"
            );

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find appointments in ' . __CLASS__, $e->getCode(), $e);
        }

        $result = [];

        foreach ($rows as $row) {
            $row['bookingStart'] = DateTimeService::getCustomDateTimeObjectFromUtc($row['bookingStart'])
                ->modify('-' . ($row['timeBefore'] ?: '0') . ' seconds')
                ->format('Y-m-d H:i:s');

            $row['bookingEnd'] = DateTimeService::getCustomDateTimeObjectFromUtc($row['bookingEnd'])
                ->modify('+' . ($row['timeAfter'] ?: '0') . ' seconds')
                ->format('Y-m-d H:i:s');

            $result[$row['providerId']] = $row;
        }

        return $result;
    }

    /**
     * @param Collection $collection
     * @param array      $providerIds
     * @param string     $startDateTime
     * @param string     $endDateTime
     * @return void
     * @throws QueryExecutionException
     */
    public function getFutureAppointments($collection, $providerIds, $startDateTime, $endDateTime)
    {
        $params = [];

        $where = [
            "a.status IN ('approved', 'pending')",
            "cb.status IN ('approved', 'pending')",
            "a.bookingStart >= STR_TO_DATE('{$startDateTime}', '%Y-%m-%d %H:%i:%s')",
        ];

        if ($endDateTime) {
            $where[] = "a.bookingStart <= STR_TO_DATE('{$endDateTime}', '%Y-%m-%d %H:%i:%s')";
        }

        if (!empty($providerIds)) {
            $queryProviders = [];

            foreach ($providerIds as $index => $value) {
                $param = ':provider' . $index;

                $queryProviders[] = $param;

                $params[$param] = $value;
            }

            $where[] = 'a.providerId IN (' . implode(', ', $queryProviders) . ')';
        }

        $where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        try {
            $statement = $this->connection->prepare(
                "SELECT
                a.id AS id,
                a.bookingStart AS bookingStart,
                a.bookingEnd AS bookingEnd,
                a.providerId AS providerId,
                a.serviceId AS serviceId,
                a.locationId AS locationId,
                a.status AS status,
                                
                cb.id AS bookingId,
                cb.customerId AS customerId,
                cb.status AS bookingStatus,
                cb.persons AS persons
                
                FROM {$this->table} a
                INNER JOIN {$this->bookingsTable} cb ON cb.appointmentId = a.id
                {$where}
                ORDER BY a.bookingStart
                "
            );

            $statement->execute($params);

            while ($row = $statement->fetch()) {
                $id = (int)$row['id'];

                $bookingId = (int)$row['bookingId'];

                if (!$collection->keyExists($id)) {
                    $collection->addItem(
                        AppointmentFactory::create(
                            [
                                'id'                 => $id,
                                'bookingStart'       => DateTimeService::getCustomDateTimeFromUtc(
                                    $row['bookingStart']
                                ),
                                'bookingEnd'         => DateTimeService::getCustomDateTimeFromUtc(
                                    $row['bookingEnd']
                                ),
                                'providerId'         => $row['providerId'],
                                'serviceId'          => $row['serviceId'],
                                'locationId'         => $row['locationId'],
                                'status'             => $row['status'],
                                'bookings'           => [],
                                'notifyParticipants' => false
                            ]
                        ),
                        $id
                    );
                }

                if (!$collection->getItem($id)->getBookings()->keyExists($bookingId)) {
                    $collection->getItem($id)->getBookings()->addItem(
                        CustomerBookingFactory::create(
                            [
                                'id'         => $bookingId,
                                'customerId' => $row['customerId'],
                                'status'     => $row['bookingStatus'],
                                'persons'    => $row['persons'],
                            ]
                        ),
                        $bookingId
                    );
                }
            }
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find appointments in ' . __CLASS__, $e->getCode(), $e);
        }
    }

    /**
     * @param array  $providerIds
     * @param string $startDateTime
     * @param string $endDateTime
     * @return array
     * @throws QueryExecutionException
     */
    public function getFutureAppointmentsServicesIds($providerIds, $startDateTime, $endDateTime)
    {
        $params = [];

        $where = [];

        if ($startDateTime) {
            $where = ["bookingStart >= STR_TO_DATE('{$startDateTime}', '%Y-%m-%d %H:%i:%s')"];
        }

        if ($endDateTime) {
            $where = ["bookingStart <= STR_TO_DATE('{$endDateTime}', '%Y-%m-%d %H:%i:%s')"];
        }

        if (!empty($providerIds)) {
            $queryProviders = [];

            foreach ($providerIds as $index => $value) {
                $param = ':provider' . $index;

                $queryProviders[] = $param;

                $params[$param] = $value;
            }

            $where[] = 'providerId IN (' . implode(', ', $queryProviders) . ')';
        }

        $where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        try {
            $statement = $this->connection->prepare("SELECT DISTINCT(serviceId) FROM {$this->table} {$where}");

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find appointments in ' . __CLASS__, $e->getCode(), $e);
        }

        return $rows ? array_column($rows, 'serviceId') : [];
    }

    /**
     * @param array  $serviceIds
     * @param string $startDateTime
     * @param string $endDateTime
     * @return array
     * @throws QueryExecutionException
     */
    public function getFutureAppointmentsProvidersIds($serviceIds, $startDateTime, $endDateTime)
    {
        $params = [];

        $where = [];

        if ($startDateTime) {
            $where = ["bookingStart >= STR_TO_DATE('{$startDateTime}', '%Y-%m-%d %H:%i:%s')"];
        }

        if ($endDateTime) {
            $where = ["bookingStart <= STR_TO_DATE('{$endDateTime}', '%Y-%m-%d %H:%i:%s')"];
        }

        if (!empty($serviceIds)) {
            $queryServices = [];

            foreach ($serviceIds as $index => $value) {
                $param = ':service' . $index;

                $queryServices[] = $param;

                $params[$param] = $value;
            }

            $where[] = 'serviceId IN (' . implode(', ', $queryServices) . ')';
        }

        $where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        try {
            $statement = $this->connection->prepare("SELECT DISTINCT(providerId) FROM {$this->table} {$where}");

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find appointments in ' . __CLASS__, $e->getCode(), $e);
        }

        return $rows ? array_column($rows, 'providerId') : [];
    }

    /**
     * @param array $criteria
     *
     * @return Collection
     * @throws QueryExecutionException
     */
    public function getFiltered($criteria)
    {
        try {
            $params = [];

            $where = [];

            if (!empty($criteria['dates'])) {
                if (isset($criteria['dates'][0], $criteria['dates'][1])) {
                    $whereStart = "(a.bookingStart BETWEEN :bookingFrom AND :bookingTo)";

                    $params[':bookingFrom'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][0]);

                    $params[':bookingTo'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][1]);

                    $whereEnd = '';
                    if (!empty($criteria['endsInDateRange'])) {
                        $whereEnd = "OR (a.bookingEnd BETWEEN :bookingFrom2 AND :bookingTo2)";
                        $params[':bookingFrom2'] = $params[':bookingFrom'];
                        $params[':bookingTo2']   = $params[':bookingTo'];
                    }

                    $where[] = "({$whereStart} {$whereEnd})";
                } elseif (isset($criteria['dates'][0])) {
                    $where[] = "(a.bookingStart >= :bookingFrom)";

                    $params[':bookingFrom'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][0]);
                } elseif (isset($criteria['dates'][1])) {
                    $where[] = "(a.bookingStart <= :bookingTo)";

                    $params[':bookingTo'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][1]);
                } else {
                    $where[] = "(a.bookingStart > :bookingFrom)";

                    $params[':bookingFrom'] = DateTimeService::getNowDateTimeInUtc();
                }
            }

            if (!empty($criteria['ids'])) {
                $queryAppointments = [];

                foreach ((array)$criteria['ids'] as $index => $value) {
                    $param = ':id' . $index;

                    $queryAppointments[] = $param;

                    $params[$param] = $value;
                }

                $where[] = 'a.id IN (' . implode(', ', $queryAppointments) . ')';
            }

            if (!empty($criteria['services'])) {
                $queryServices = [];

                foreach ((array)$criteria['services'] as $index => $value) {
                    $param = ':service' . $index;

                    $queryServices[] = $param;

                    $params[$param] = $value;
                }

                $where[] = 'a.serviceId IN (' . implode(', ', $queryServices) . ')';
            }

            if (!empty($criteria['providers'])) {
                $queryProviders = [];

                foreach ((array)$criteria['providers'] as $index => $value) {
                    $param = ':provider' . $index;

                    $queryProviders[] = $param;

                    $params[$param] = $value;
                }

                $where[] = 'a.providerId IN (' . implode(', ', $queryProviders) . ')';
            }

            if (!empty($criteria['customers'])) {
                $queryCustomers = [];

                foreach ((array)$criteria['customers'] as $index => $value) {
                    $param = ':customer' . $index;

                    $queryCustomers[] = $param;

                    $params[$param] = $value;
                }

                $where[] = 'cb.customerId IN (' . implode(', ', $queryCustomers) . ')';
            }

            if (isset($criteria['customerId'])) {
                $where[] = 'cb.customerId = :customerId';
                $params[':customerId'] = $criteria['customerId'];
            }


            if (isset($criteria['providerId'])) {
                $where[] = 'a.providerId = :providerId';
                $params[':providerId'] = $criteria['providerId'];
            }

            if (array_key_exists('status', $criteria)) {
                $where[] = 'a.status = :status';

                $params[':status'] = $criteria['status'];
            }

            if (!empty($criteria['statuses'])) {
                $queryStatuses = [];

                foreach ($criteria['statuses'] as $index => $value) {
                    $param = ':statuses' . $index;

                    $queryStatuses[] = $param;

                    $params[$param] = $value;
                }

                $where[] = 'a.status IN (' . implode(', ', $queryStatuses) . ')';
            }

            if (array_key_exists('bookingStatus', $criteria)) {
                $where[] = 'cb.status = :bookingStatus';
                $params[':bookingStatus'] = $criteria['bookingStatus'];
            }

            if (array_key_exists('bookingStatuses', $criteria)) {
                $queryStatuses = [];

                foreach ($criteria['bookingStatuses'] as $index => $value) {
                    $param = ':bookingStatuses' . $index;

                    $queryStatuses[] = $param;

                    $params[$param] = $value;
                }

                $where[] = 'cb.status IN (' . implode(', ', $queryStatuses) . ')';
            }

            if (!empty($criteria['locations'])) {
                $queryLocations = [];

                foreach ((array)$criteria['locations'] as $index => $value) {
                    $param = ':location' . $index;

                    $queryLocations[] = $param;

                    $params[$param] = $value;
                }

                $where[] = 'a.locationId IN (' . implode(', ', $queryLocations) . ')';
            }

            if (isset($criteria['bookingId'])) {
                $where[] = 'cb.id = :bookingId';
                $params[':bookingId'] = $criteria['bookingId'];
            }

            if (isset($criteria['bookingIds'])) {
                $queryBookings = [];

                foreach ((array)$criteria['bookingIds'] as $index => $value) {
                    $param = ':bookingId' . $index;

                    $queryBookings[] = $param;

                    $params[$param] = $value;
                }

                $where[] = 'cb.id IN (' . implode(', ', $queryBookings) . ')';
            }

            if (isset($criteria['bookingCouponId'])) {
                $where[] = 'cb.couponId = :bookingCouponId';
                $params[':bookingCouponId'] = $criteria['bookingCouponId'];
            }

            if (isset($criteria['parentId'])) {
                $where[] = 'a.parentId = :parentId';
                $params[':parentId'] = $criteria['parentId'];
            }

            if (!empty($criteria['packageCustomerServices'])) {
                $queryLocations = [];

                foreach ($criteria['packageCustomerServices'] as $index => $value) {
                    $param = ':packageCustomerServices' . $index;

                    $queryLocations[] = $param;

                    $params[$param] = $value;
                }

                $where[] = 'cb.packageCustomerServiceId IN (' . implode(', ', $queryLocations) . ')';
            }

            $packagesJoin = '';
            if (isset($criteria['packageId'])) {
                $where[] = 'pc.packageId = :packageId';
                $params[':packageId'] = $criteria['packageId'];

                $packagesJoin = "LEFT JOIN {$this->packagesCustomersServicesTable} pcs ON pcs.id = cb.packageCustomerServiceId
                                 LEFT JOIN {$this->packagesCustomersTable} pc ON pcs.packageCustomerId = pc.id";
            } elseif (!empty($criteria['packageCustomerId'])) {
                $where[] = 'pc.id = :packageCustomerId';
                $params[':packageCustomerId'] = $criteria['packageCustomerId'];

                $packagesJoin = "LEFT JOIN {$this->packagesCustomersServicesTable} pcs ON pcs.id = cb.packageCustomerServiceId
                                 LEFT JOIN {$this->packagesCustomersTable} pc ON pcs.packageCustomerId = pc.id";
            }


            $servicesFields = '
                s.id AS service_id,
                s.name AS service_name,
                s.description AS service_description,
                s.color AS service_color,
                s.price AS service_price,
                s.status AS service_status,
                s.categoryId AS service_categoryId,
                s.minCapacity AS service_minCapacity,
                s.maxCapacity AS service_maxCapacity,
                s.timeAfter AS service_timeAfter,
                s.timeBefore AS service_timeBefore,
                s.duration AS service_duration,
                s.settings AS service_settings,
            ';

            $servicesJoin = "INNER JOIN {$this->servicesTable} s ON s.id = a.serviceId";

            if (!empty($criteria['skipServices'])) {
                $servicesFields = '';

                $servicesJoin = '';
            }

            $providersFields = '
                pu.id AS provider_id,
                pu.firstName AS provider_firstName,
                pu.lastName AS provider_lastName,
                pu.email AS provider_email,
                pu.note AS provider_note,
                pu.description AS provider_description,
                pu.phone AS provider_phone,
                pu.gender AS provider_gender,
                pu.translations AS provider_translations,
                pu.timeZone AS provider_timeZone,
            ';

            $providersJoin = "INNER JOIN {$this->usersTable} pu ON pu.id = a.providerId";

            if (!empty($criteria['skipProviders'])) {
                $providersFields = '';

                $providersJoin = '';
            }

            $locationsTable = LocationsTable::getTableName();

            $locationsFields = '';

            $locationsJoin = '';

            if (!empty($criteria['withLocations'])) {
                $locationsFields = '
                    l.id AS location_id,
                    l.name AS location_name,
                    l.address AS location_address,
                ';

                $locationsJoin = "LEFT JOIN {$locationsTable} l ON l.id = a.locationId";
            }

            $customersFields = '
                cu.id AS customer_id,
                cu.firstName AS customer_firstName,
                cu.lastName AS customer_lastName,
                cu.email AS customer_email,
                cu.note AS customer_note,
                cu.phone AS customer_phone,
                cu.gender AS customer_gender,
                cu.status AS customer_status,
            ';

            $customersJoin = "INNER JOIN {$this->usersTable} cu ON cu.id = cb.customerId";

            if (!empty($criteria['skipCustomers'])) {
                $customersFields = '';

                $customersJoin = '';
            }

            $paymentsFields = '
                p.id AS payment_id,
                p.packageCustomerId AS payment_packageCustomerId,
                p.amount AS payment_amount,
                p.dateTime AS payment_dateTime,
                p.status AS payment_status,
                p.gateway AS payment_gateway,
                p.gatewayTitle AS payment_gatewayTitle,
                p.transactionId AS payment_transactionId,
                p.data AS payment_data,
                p.parentId AS payment_parentId,
                p.wcOrderId AS payment_wcOrderId,
                p.wcOrderItemId AS payment_wcOrderItemId,
                p.created AS payment_created,
            ';

            $paymentsJoin = "LEFT JOIN {$this->paymentsTable} p ON p.customerBookingId = cb.id";

            if (!empty($criteria['skipPayments'])) {
                $paymentsFields = '';

                $paymentsJoin = '';
            }

            $bookingExtrasFields = '
                cbe.id AS bookingExtra_id,
                cbe.extraId AS bookingExtra_extraId,
                cbe.customerBookingId AS bookingExtra_customerBookingId,
                cbe.quantity AS bookingExtra_quantity,
                cbe.price AS bookingExtra_price,
                cbe.tax AS bookingExtra_tax,
                cbe.aggregatedPrice AS bookingExtra_aggregatedPrice,
            ';

            $bookingExtrasJoin = "LEFT JOIN {$this->customerBookingsExtrasTable} cbe ON cbe.customerBookingId = cb.id";

            if (!empty($criteria['skipExtras'])) {
                $bookingExtrasFields = '';

                $bookingExtrasJoin = '';
            }

            $couponsFields = '
                c.id AS coupon_id,
                c.code AS coupon_code,
                c.discount AS coupon_discount,
                c.deduction AS coupon_deduction,
                c.expirationDate AS coupon_expirationDate,
                c.limit AS coupon_limit,
                c.customerLimit AS coupon_customerLimit,
                c.status AS coupon_status,
            ';

            $couponsJoin = "LEFT JOIN {$this->couponsTable} c ON c.id = cb.couponId";

            if (!empty($criteria['skipCoupons'])) {
                $couponsFields = '';

                $couponsJoin = '';
            }

            $bookingsFields = '
                cb.id AS booking_id,
                cb.customerId AS booking_customerId,
                cb.status AS booking_status,
                cb.price AS booking_price,
                cb.tax AS booking_tax,
                cb.persons AS booking_persons,
                cb.customFields AS booking_customFields,
                cb.info AS booking_info,
                cb.aggregatedPrice AS booking_aggregatedPrice,
                cb.packageCustomerServiceId AS booking_packageCustomerServiceId,
                cb.duration AS booking_duration,
                cb.created AS booking_created,
                cb.tax AS booking_tax,
            ';

            $bookingsJoin = "INNER JOIN {$this->bookingsTable} cb ON cb.appointmentId = a.id";

            if (!empty($criteria['skipBookings'])) {
                $bookingsFields = '';

                $bookingsJoin = '';
            }

            $where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

            $statement = $this->connection->prepare(
                "SELECT
                    {$customersFields}
                    {$bookingExtrasFields}
                    {$providersFields}
                    {$locationsFields}
                    {$servicesFields}
                    {$paymentsFields}
                    {$couponsFields}
                    {$bookingsFields}
                    a.id AS appointment_id,
                    a.bookingStart AS appointment_bookingStart,
                    a.bookingEnd AS appointment_bookingEnd,
                    a.notifyParticipants AS appointment_notifyParticipants,
                    a.internalNotes AS appointment_internalNotes,
                    a.status AS appointment_status,
                    a.serviceId AS appointment_serviceId,
                    a.providerId AS appointment_providerId,
                    a.locationId AS appointment_locationId,
                    a.googleCalendarEventId AS appointment_google_calendar_event_id,
                    a.googleMeetUrl AS appointment_google_meet_url,
                    a.outlookCalendarEventId AS appointment_outlook_calendar_event_id,
                    a.microsoftTeamsUrl AS appointment_microsoft_teams_url,
                    a.appleCalendarEventId AS appointment_apple_calendar_event_id,
                    a.zoomMeeting AS appointment_zoom_meeting,
                    a.lessonSpace AS appointment_lesson_space,
                    a.parentId AS appointment_parentId
                FROM {$this->table} a
                {$bookingsJoin}
                {$packagesJoin}
                {$customersJoin}
                {$providersJoin}
                {$locationsJoin}
                {$servicesJoin}
                {$paymentsJoin}
                {$bookingExtrasJoin}
                {$couponsJoin}
                {$where}
                ORDER BY a.bookingStart, a.id"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find by id in ' . __CLASS__, $e->getCode(), $e);
        }

        return call_user_func([static::FACTORY, 'createCollection'], $rows);
    }

    /**
     * @return Collection $criteria
     * @throws QueryExecutionException
     */
    public function getAppointmentsWithoutBookings()
    {
        try {
            $statement = $this->connection->query(
                "SELECT
                a.id AS appointment_id,
                a.bookingStart AS appointment_bookingStart,
                a.bookingEnd AS appointment_bookingEnd,
                a.providerId AS appointment_providerId,
                a.serviceId AS appointment_serviceId,
                a.status AS appointment_status,
                a.googleCalendarEventId as appointment_google_calendar_event_id,
                a.googleMeetUrl AS appointment_google_meet_url,
                a.outlookCalendarEventId AS appointment_outlook_calendar_event_id,
                a.microsoftTeamsUrl AS appointment_microsoft_teams_url,
                a.appleCalendarEventId AS appointment_apple_calendar_event_id,
                a.notifyParticipants AS appointment_notifyParticipants
            FROM {$this->table} a WHERE (
                  SELECT COUNT(*) FROM {$this->bookingsTable} cb WHERE a.id = cb.appointmentId
                ) = 0"
            );

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find data from ' . __CLASS__, $e->getCode(), $e);
        }

        return call_user_func([static::FACTORY, 'createCollection'], $rows);
    }

    /**
     * @param array $criteria
     * @param null $itemsPerPage
     * @return Collection
     * @throws QueryExecutionException
     */
    public function getPeriodAppointments($criteria, $itemsPerPage = null)
    {
        $params = [];

        $where = [];

        if (!empty($criteria['dates'])) {
            if (isset($criteria['dates'][0], $criteria['dates'][1])) {
                $whereStart = "(a.bookingStart BETWEEN :bookingFrom AND :bookingTo)";

                $params[':bookingFrom'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][0]);

                $params[':bookingTo'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][1]);

                $whereEnd = '';
                if (!empty($criteria['endsInDateRange'])) {
                    $whereEnd = "OR (a.bookingEnd BETWEEN :bookingFrom2 AND :bookingTo2)";
                    $params[':bookingFrom2'] = $params[':bookingFrom'];
                    $params[':bookingTo2']   = $params[':bookingTo'];
                }

                $where[] = "({$whereStart} {$whereEnd})";
            } elseif (isset($criteria['dates'][0])) {
                $where[] = "(a.bookingStart >= :bookingFrom)";

                $params[':bookingFrom'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][0]);
            } elseif (isset($criteria['dates'][1])) {
                $where[] = "(a.bookingStart <= :bookingTo)";

                $params[':bookingTo'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][1]);
            } else {
                $where[] = "(a.bookingStart > :bookingFrom)";

                $params[':bookingFrom'] = DateTimeService::getNowDateTimeInUtc();
            }
        }

        $whereOr = [];
        if (!empty($criteria['services'])) {
            $queryServices = [];

            foreach ((array)$criteria['services'] as $index => $value) {
                $param = ':service' . $index;

                $queryServices[] = $param;

                $params[$param] = $value;
            }

            $where[] = 'a.serviceId IN (' . implode(', ', $queryServices) . ')';
        }

        if (!empty($criteria['providers'])) {
            $queryProviders = [];

            foreach ((array)$criteria['providers'] as $index => $value) {
                $param = ':provider' . $index;

                $queryProviders[] = $param;

                $params[$param] = $value;
            }

            $where[] = 'a.providerId IN (' . implode(', ', $queryProviders) . ')';
        }

        $bookingsJoin = "INNER JOIN {$this->bookingsTable} cb ON cb.appointmentId = a.id";

        if (!empty($criteria['skipBookings'])) {
            $bookingsJoin = '';
        }

        if (empty($criteria['skipBookings']) && !empty($criteria['customers'])) {
            $queryCustomers = [];

            foreach ((array)$criteria['customers'] as $index => $value) {
                $param = ':customer' . $index;

                $queryCustomers[] = $param;

                $params[$param] = $value;
            }

            $whereOr[] = 'cb.customerId IN (' . implode(', ', $queryCustomers) . ')';
        }

        if (empty($criteria['skipBookings']) && isset($criteria['customerId'])) {
            $where[] = 'cb.customerId = :customerId';
            $params[':customerId'] = $criteria['customerId'];
        }

        if (isset($criteria['providerId'])) {
            $where[] = 'a.providerId = :providerId';
            $params[':providerId'] = $criteria['providerId'];
        }

        if (array_key_exists('status', $criteria)) {
            $where[] = 'a.status = :status';

            $params[':status'] = $criteria['status'];
        }

        if (!empty($criteria['locations'])) {
            $queryLocations = [];

            foreach ((array)$criteria['locations'] as $index => $value) {
                $param = ':location' . $index;

                $queryLocations[] = $param;

                $params[$param] = $value;
            }

            $where[] = 'a.locationId IN (' . implode(', ', $queryLocations) . ')';
        }

        $limit = $this->getLimit(
            !empty($criteria['page']) ? (int)$criteria['page'] : 0,
            (int)$itemsPerPage
        );

        if (!empty($whereOr)) {
            $where[] = '(' . implode(' OR ', $whereOr) . ')';
        }

        $where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        try {
            $statement = $this->connection->prepare(
                "SELECT
                    a.id AS appointment_id,
                    a.bookingStart AS appointment_bookingStart,
                    a.bookingEnd AS appointment_bookingEnd,
                    a.notifyParticipants AS appointment_notifyParticipants,
                    a.internalNotes AS appointment_internalNotes,
                    a.status AS appointment_status,
                    a.serviceId AS appointment_serviceId,
                    a.providerId AS appointment_providerId,
                    a.locationId AS appointment_locationId,
                    a.googleCalendarEventId AS appointment_google_calendar_event_id,
                    a.googleMeetUrl AS appointment_google_meet_url,
                    a.outlookCalendarEventId AS appointment_outlook_calendar_event_id,
                    a.microsoftTeamsUrl AS appointment_microsoft_teams_url,
                    a.appleCalendarEventId AS appointment_apple_calendar_event_id,
                    a.zoomMeeting AS appointment_zoom_meeting,
                    a.lessonSpace AS appointment_lesson_space,
                    a.parentId AS appointment_parentId
                FROM {$this->table} a
                {$bookingsJoin}
                {$where}
                GROUP BY a.id
                ORDER BY a.bookingStart, a.id
                {$limit}
                "
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find by id in ' . __CLASS__, $e->getCode(), $e);
        }

        return call_user_func([static::FACTORY, 'createCollection'], $rows);
    }

    /**
     * @param array $criteria
     * @return int
     * @throws QueryExecutionException
     */
    public function getPeriodAppointmentsCount($criteria)
    {
        $params = [];

        $where = [];

        if (!empty($criteria['dates'])) {
            if (isset($criteria['dates'][0], $criteria['dates'][1])) {
                $where[] = "(a.bookingStart BETWEEN :bookingFrom AND :bookingTo)";

                $params[':bookingFrom'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][0]);

                $params[':bookingTo'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][1]);
            } elseif (isset($criteria['dates'][0])) {
                $where[] = "(a.bookingStart >= :bookingFrom)";

                $params[':bookingFrom'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][0]);
            } elseif (isset($criteria['dates'][1])) {
                $where[] = "(a.bookingStart <= :bookingTo)";

                $params[':bookingTo'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][1]);
            } else {
                $where[] = "(a.bookingStart > :bookingFrom)";

                $params[':bookingFrom'] = DateTimeService::getNowDateTimeInUtc();
            }
        }

        $whereOr = [];
        if (!empty($criteria['services'])) {
            $queryServices = [];

            foreach ((array)$criteria['services'] as $index => $value) {
                $param = ':service' . $index;

                $queryServices[] = $param;

                $params[$param] = $value;
            }

            $where[] = 'a.serviceId IN (' . implode(', ', $queryServices) . ')';
        }

        if (!empty($criteria['providers'])) {
            $queryProviders = [];

            foreach ((array)$criteria['providers'] as $index => $value) {
                $param = ':provider' . $index;

                $queryProviders[] = $param;

                $params[$param] = $value;
            }

            $where[] = 'a.providerId IN (' . implode(', ', $queryProviders) . ')';
        }

        if (!empty($criteria['customers'])) {
            $queryCustomers = [];

            foreach ((array)$criteria['customers'] as $index => $value) {
                $param = ':customer' . $index;

                $queryCustomers[] = $param;

                $params[$param] = $value;
            }

            $whereOr[] = 'cb.customerId IN (' . implode(', ', $queryCustomers) . ')';
        }

        if (isset($criteria['customerId'])) {
            $where[] = 'cb.customerId = :customerId';
            $params[':customerId'] = $criteria['customerId'];
        }

        if (isset($criteria['providerId'])) {
            $where[] = 'a.providerId = :providerId';
            $params[':providerId'] = $criteria['providerId'];
        }

        if (array_key_exists('status', $criteria)) {
            $where[] = 'a.status = :status';

            $params[':status'] = $criteria['status'];
        }

        $customerBookingJoin = !empty($criteria['customers']) || isset($criteria['customerId']) ?
            "INNER JOIN {$this->bookingsTable} cb ON cb.appointmentId = a.id" : '';

        if (!empty($whereOr)) {
            $where[] = '(' . implode(' OR ', $whereOr) . ')';
        }

        $where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        try {
            $statement = $this->connection->prepare(
                "SELECT
                    COUNT(*) AS count
                FROM {$this->table} a
                {$customerBookingJoin}
                {$where}
                ORDER BY a.bookingStart
                "
            );

            $statement->execute($params);

            $rows = $statement->fetch()['count'];
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find by id in ' . __CLASS__, $e->getCode(), $e);
        }

        return $rows;
    }

    /**
     * @param Service $service
     * @param int $customerId
     * @param \DateTime $appointmentStart
     * @param int $bookingId
     * @return Collection
     * @throws QueryExecutionException
     */
    public function getRelevantAppointmentsCount($service, $customerId, $appointmentStart, $limitPerCustomer, $serviceSpecific, $bookingId = null)
    {
        $params = [
            ':customerId' => $customerId
        ];

        $paymentTableJoin = '';
        $compareToDate    = 'a.bookingStart';

        if ($limitPerCustomer['from'] === 'bookingDate') {
            $appointmentStart = DateTimeService::getCustomDateTimeObject(
                $appointmentStart->format('Y-m-d H:i')
            )->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i');
        } else {
            $paymentTableJoin = 'INNER JOIN ' . $this->paymentsTable . ' p ON p.customerBookingId = cb.id';
            $appointmentStart = DateTimeService::getNowDateTimeObject()->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i');
            $compareToDate    = 'p.created';
        }

        $intervalString = "interval " . $limitPerCustomer['period'] . " " . $limitPerCustomer['timeFrame'];

        $where = "(STR_TO_DATE('" . $appointmentStart . "', '%Y-%m-%d %H:%i:%s') BETWEEN " .
            "(" . $compareToDate . " - " . $intervalString . " + interval 1 second)"
            . " AND (" .
            $compareToDate . " + " . $intervalString . " - interval 1 second))";  //+ interval 2 day

        if ($serviceSpecific) {
            $where .= " AND a.serviceId = :serviceId";
            $params[':serviceId'] = $service->getId()->getValue();
        }

        if ($bookingId) {
            $where .= " AND cb.id <> :bookingId";
            $params[':bookingId'] = $bookingId;
        }

        try {
            $statement = $this->connection->prepare(
                "SELECT COUNT(DISTINCT a.id) AS count
                    FROM {$this->table} a 
                    INNER JOIN {$this->bookingsTable} cb 
                    ON cb.appointmentId = a.id 
                    {$paymentTableJoin}
                    WHERE 
                        cb.customerId = :customerId 
                        AND {$where} 
                        AND (a.status = 'approved' OR a.status = 'pending') 
                        AND (cb.status = 'approved' OR cb.status = 'pending')
                "
            );

            $statement->execute($params);

            $rows = $statement->fetch()['count'];
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find by id in ' . __CLASS__, $e->getCode(), $e);
        }

        return $rows;
    }

    /**
     * @param $providerIds
     *
     * @return array
     * @throws QueryExecutionException
     */
    public function getLastBookedEmployee($providerIds)
    {
        try {
            $params = [];

            $queryProviders = [];

            $where = '';

            if (!empty($providerIds)) {
                foreach ($providerIds as $index => $value) {
                    $param = ':provider' . $index;

                    $queryProviders[] = $param;

                    $params[$param] = $value;
                }

                $where = ' AND a.providerId IN (' . implode(', ', $queryProviders) . ')';
            }

            $statement = $this->connection->prepare(
                "SELECT a.providerId
                    FROM {$this->table} a 
                    JOIN {$this->bookingsTable} cb ON cb.appointmentId = a.id
                     WHERE (a.status = 'approved' OR a.status = 'pending') AND (cb.status = 'approved' OR cb.status = 'pending')
                     {$where}
                    ORDER BY cb.created DESC, a.id DESC LIMIT 1;
                "
            );

            $statement->execute($params);

            $rows = $statement->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find by id in ' . __CLASS__, $e->getCode(), $e);
        }

        return !empty($rows) ? $rows[0] : $providerIds[0];
    }
}
