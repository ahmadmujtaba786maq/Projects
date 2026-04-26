<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Repository\Payment;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Payment\Payment;
use AmeliaBooking\Domain\Factory\Payment\PaymentFactory;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Connection;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable\PackagesCustomersServicesTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\CustomerBookingsToExtrasTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Coupon\CouponsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Location\LocationsTable;

/**
 * Class PaymentRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Payment
 */
class PaymentRepository extends AbstractRepository
{
    /** @var string */
    protected $appointmentsTable;

    /** @var string */
    protected $bookingsTable;

    /** @var string */
    protected $servicesTable;

    /** @var string */
    protected $usersTable;

    /** @var string */
    protected $eventsTable;

    /** @var string */
    protected $eventsProvidersTable;

    /** @var string */
    protected $eventsPeriodsTable;

    /** @var string */
    protected $customerBookingsToEventsPeriodsTable;

    /** @var string */
    protected $packagesTable;

    /** @var string */
    protected $packagesCustomersTable;

    /** @var string */
    protected $packagesCustomersServiceTable;


    /**
     * @param Connection $connection
     * @param string     $table
     * @param string     $appointmentsTable
     * @param string     $bookingsTable
     * @param string     $servicesTable
     * @param string     $usersTable
     * @param string     $eventsTable
     * @param string     $eventsProvidersTable
     * @param string     $eventsPeriodsTable
     * @param string     $customerBookingsToEventsPeriodsTable
     * @param string     $packagesTable
     * @param string     $packagesCustomersTable
     */
    public function __construct(
        Connection $connection,
        $table,
        $appointmentsTable,
        $bookingsTable,
        $servicesTable,
        $usersTable,
        $eventsTable,
        $eventsProvidersTable,
        $eventsPeriodsTable,
        $customerBookingsToEventsPeriodsTable,
        $packagesTable,
        $packagesCustomersTable
    ) {
        parent::__construct($connection, $table);

        $this->appointmentsTable    = $appointmentsTable;
        $this->bookingsTable        = $bookingsTable;
        $this->servicesTable        = $servicesTable;
        $this->usersTable           = $usersTable;
        $this->eventsTable          = $eventsTable;
        $this->eventsProvidersTable = $eventsProvidersTable;
        $this->eventsPeriodsTable   = $eventsPeriodsTable;
        $this->customerBookingsToEventsPeriodsTable = $customerBookingsToEventsPeriodsTable;
        $this->packagesTable          = $packagesTable;
        $this->packagesCustomersTable = $packagesCustomersTable;
        $this->packagesCustomersServiceTable = PackagesCustomersServicesTable::getTableName();
    }

    public const FACTORY = PaymentFactory::class;

    /**
     * @param Payment $entity
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function add($entity)
    {
        $data = $entity->toArray();

        $params = [
            ':customerBookingId' => $data['customerBookingId'] ? $data['customerBookingId'] : null,
            ':packageCustomerId' => $data['packageCustomerId'] ? $data['packageCustomerId'] : null,
            ':parentId'          => $data['parentId'] ? $data['parentId'] : null,
            ':amount'            => $data['amount'],
            ':dateTime'          => DateTimeService::getCustomDateTimeInUtc($data['dateTime']),
            ':status'            => $data['status'],
            ':gateway'           => $data['gateway'],
            ':gatewayTitle'      => $data['gatewayTitle'],
            ':data'              => $data['data'],
            ':entity'            => $data['entity'],
            ':created'           => DateTimeService::getNowDateTimeInUtc(),
            ':wcOrderId'         => !empty($data['wcOrderId']) ? $data['wcOrderId'] : null,
            ':transactionId'      => !empty($data['transactionId']) ? $data['transactionId'] : null,
            ':wcOrderItemId'     => !empty($data['wcOrderItemId']) ? $data['wcOrderItemId'] : null,
        ];

        if (!empty($data['invoiceNumber'])) {
            $params[':invoiceNumber'] = $data['invoiceNumber'];

            $invoiceNumberText = ":invoiceNumber";
        } else {
            $invoiceNumberText = "(SELECT MAX(CASE WHEN invoiceNumber IS NULL THEN 0 ELSE invoiceNumber END)+1 FROM {$this->table} p)";
        }

        if ($data['parentId']) {
            $params[':actionsCompleted'] = null;
        } else {
            $params[':actionsCompleted'] = !empty($data['actionsCompleted']) ? 1 : 0;
        }

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO
                {$this->table} 
                (
                 `customerBookingId`,
                 `packageCustomerId`,
                 `parentId`,
                 `amount`,
                 `dateTime`,
                 `status`,
                 `gateway`,
                 `gatewayTitle`,
                 `data`, `entity`,
                 `actionsCompleted`,
                 `created`,
                 `wcOrderId`,
                 `wcOrderItemId`,
                 `transactionId`,
                 `invoiceNumber`
                ) VALUES (
                  :customerBookingId,
                  :packageCustomerId,
                  :parentId,
                  :amount,
                  :dateTime,
                  :status,
                  :gateway,
                  :gatewayTitle,
                  :data,
                  :entity,
                  :actionsCompleted,
                  :created,
                  :wcOrderId,
                  :wcOrderItemId,
                  :transactionId,
                  {$invoiceNumberText}
                )"
            );

            $response = $statement->execute($params);
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to add data in ' . __CLASS__, $e->getCode(), $e);
        }

        if (!$response) {
            throw new QueryExecutionException('Unable to add data in ' . __CLASS__);
        }

        return $this->connection->lastInsertId();
    }

    /**
     * @param int     $id
     * @param Payment $entity
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function update($id, $entity)
    {
        $data = $entity->toArray();

        $params = [
            ':customerBookingId' => $data['customerBookingId'] ? $data['customerBookingId'] : null,
            ':packageCustomerId' => $data['packageCustomerId'] ? $data['packageCustomerId'] : null,
            ':parentId'          => $data['parentId'] ? $data['parentId'] : null,
            ':amount'            => $data['amount'],
            ':dateTime'          => DateTimeService::getCustomDateTimeInUtc($data['dateTime']),
            ':status'            => $data['status'],
            ':gateway'           => $data['gateway'],
            ':gatewayTitle'      => $data['gatewayTitle'],
            ':data'              => $data['data'],
            ':transactionId'     => $data['transactionId'],
            ':id'                => $id,
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET
                `customerBookingId` = :customerBookingId,
                `packageCustomerId` = :packageCustomerId,
                `parentId`          = :parentId,
                `amount`            = :amount,
                `dateTime`          = :dateTime,
                `status`            = :status,
                `gateway`           = :gateway,
                `gatewayTitle`      = :gatewayTitle,
                `data`              = :data,
                `transactionId`     = :transactionId
                WHERE
                id = :id"
            );

            $response = $statement->execute($params);
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to save data in ' . __CLASS__, $e->getCode(), $e);
        }

        if (!$response) {
            throw new QueryExecutionException('Unable to save data in ' . __CLASS__);
        }

        return $response;
    }

    /**
     * @param array $criteria
     *
     * @return Collection
     * @throws QueryExecutionException
     */
    public function getByCriteria($criteria)
    {
        $result = new Collection();

        $params = [];

        $where = [];

        if (!empty($criteria['bookingIds'])) {
            $queryBookings = [];

            foreach ($criteria['bookingIds'] as $index => $value) {
                $param = ':id' . $index;

                $queryBookings[] = $param;

                $params[$param] = $value;
            }

            $where[] = 'customerBookingId IN (' . implode(', ', $queryBookings) . ')';
        }


        if (!empty($criteria['packageCustomerId'])) {
            $params[':packageCustomerId'] = $criteria['packageCustomerId'];
            $where[] = 'packageCustomerId = :packageCustomerId';
        }

        if (!empty($criteria['ids'])) {
            $queryIds = [];

            foreach ($criteria['ids'] as $index => $value) {
                $param = ':id' . $index;

                $queryIds[] = $param;

                $params[$param] = $value;
            }

            $where[] = 'id IN (' . implode(', ', $queryIds) . ')';
        }

        $where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        try {
            $statement = $this->connection->prepare(
                "SELECT
                    id AS id,
                    customerBookingId AS customerBookingId,
                    packageCustomerId AS packageCustomerId,
                    parentId AS parentId,
                    invoiceNumber AS invoiceNumber,
                    amount AS amount,
                    dateTime AS dateTime,
                    status AS status,
                    gateway AS gateway,
                    gatewayTitle AS gatewayTitle,
                    data AS data
                FROM {$this->table}
                {$where}"
            );

            $statement->execute($params);

            while ($row = $statement->fetch()) {
                $result->addItem(call_user_func([static::FACTORY, 'create'], $row), $row['id']);
            }
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find by id in ' . __CLASS__, $e->getCode(), $e);
        }

        return $result;
    }

    /**
     * @param array $criteria
     * @param int   $itemsPerPage
     * @param boolean $invoice
     *
     * @return array
     * @throws QueryExecutionException
     */
    public function getFilteredIds($criteria, $itemsPerPage = null, $invoice = false)
    {
        $params = [];
        $appointmentParams1 = [];
        $appointmentParams2 = [];
        $eventParams        = [];
        $whereAppointment1  = [];
        $whereAppointment2  = [];
        $whereEvent         = [];

        $basedOnDate = $invoice ? 'created' : 'dateTime';

        if (!empty($criteria['dates'])) {
            $whereAppointment1[] = "(p.{$basedOnDate} BETWEEN :paymentAppointmentFrom1 AND :paymentAppointmentTo1)";
            $whereAppointment2[] = "(p.{$basedOnDate} BETWEEN :paymentAppointmentFrom2 AND :paymentAppointmentTo2)";
            $appointmentParams1[':paymentAppointmentFrom1'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][0]);
            $appointmentParams2[':paymentAppointmentFrom2'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][0]);
            $appointmentParams1[':paymentAppointmentTo1']   = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][1]);
            $appointmentParams2[':paymentAppointmentTo2']   = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][1]);

            $whereEvent[] = "(p.{$basedOnDate} BETWEEN :paymentEventFrom AND :paymentEventTo)";
            $eventParams[':paymentEventFrom'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][0]);
            $eventParams[':paymentEventTo']   = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][1]);
        }

        if (!empty($criteria['customerId'])) {
            $appointmentParams1[':customerAppointmentId1'] = $criteria['customerId'];
            $appointmentParams2[':customerAppointmentId2'] = $criteria['customerId'];
            $whereAppointment1[] = 'cb.customerId = :customerAppointmentId1';
            $whereAppointment2[] = 'pc.customerId = :customerAppointmentId2';

            $eventParams[':customerEventId'] = $criteria['customerId'];
            $whereEvent[] = 'cb.customerId = :customerEventId';
        }

        $eventsProvidersJoin = '';

        if (!empty($criteria['providerId'])) {
            $appointmentParams1[':providerAppointmentId1'] = $criteria['providerId'];
            $appointmentParams1[':providerAppointmentId2'] = $criteria['providerId'];
            $whereAppointment1[] = 'a.providerId = :providerAppointmentId1';
            $whereAppointment2[] = 'a.providerId = :providerAppointmentId2';

            $eventParams[':providerEventId'] = $criteria['providerId'];
            $whereEvent[] = 'epu.userId = :providerEventId';

            $eventsProvidersJoin = "
                INNER JOIN {$this->eventsPeriodsTable} ep ON ep.id = cbe.eventPeriodId
                INNER JOIN {$this->eventsProvidersTable} epu ON epu.eventId = ep.eventId
            ";
        }

        if (!empty($criteria['services'])) {
            $queryServices1 = [];
            $queryServices2 = [];

            foreach ((array)$criteria['services'] as $index => $value) {
                $param1           = ':service0' . $index;
                $param2           = ':service1' . $index;
                $queryServices1[] = $param1;
                $queryServices2[] = $param2;
                $appointmentParams1[$param1] = $value;
                $appointmentParams2[$param2] = $value;
            }

            $whereAppointment1[] = 'a.serviceId IN (' . implode(', ', $queryServices1) . ')';
            $whereAppointment2[] = 'a.serviceId IN (' . implode(', ', $queryServices2) . ')';
        }

        $appointments2ProvidersServicesJoin = '';

        if (!empty($criteria['providerId']) || !empty($criteria['services'])) {
            $appointments2ProvidersServicesJoin = "
                INNER JOIN {$this->packagesCustomersServiceTable} pcs ON pc.id = pcs.packageCustomerId
                INNER JOIN {$this->bookingsTable} cb ON cb.packageCustomerServiceId = pcs.id
                INNER JOIN {$this->appointmentsTable} a ON a.id = cb.appointmentId
            ";
        }

        if (!empty($criteria['status'])) {
            $appointmentParams1[':statusAppointment1'] = $criteria['status'];
            $appointmentParams2[':statusAppointment2'] = $criteria['status'];
            $whereAppointment1[] = 'p.status = :statusAppointment1';
            $whereAppointment2[] = 'p.status = :statusAppointment2';

            $eventParams[':statusEvent'] = $criteria['status'];
            $whereEvent[] = 'p.status = :statusEvent';
        }

        if (!empty($criteria['packages'])) {
            $queryPackages = [];

            foreach ((array)$criteria['packages'] as $index => $value) {
                $param           = ':package' . $index;
                $queryPackages[] = $param;
                $appointmentParams2[$param] = $value;
            }

            $whereAppointment2[] = "p.packageCustomerId IN (SELECT pc.id
              FROM {$this->packagesCustomersTable} pc
              WHERE pc.packageId IN (" . implode(', ', $queryPackages) . '))';
        }

        if (!empty($criteria['events'])) {
            $queryEvents = [];

            foreach ((array)$criteria['events'] as $index => $value) {
                $param         = ':event' . $index;
                $queryEvents[] = $param;
                $eventParams[$param] = $value;
            }

            $whereEvent[] = "p.customerBookingId IN (SELECT cbe.customerBookingId
              FROM {$this->eventsTable} e
              INNER JOIN {$this->eventsPeriodsTable} ep ON ep.eventId = e.id
              INNER JOIN {$this->customerBookingsToEventsPeriodsTable} cbe ON cbe.eventPeriodId = ep.id 
              WHERE e.id IN (" . implode(', ', $queryEvents) . '))';
        }

        $whereAppointment1 = $whereAppointment1 ? ' AND ' . implode(' AND ', $whereAppointment1) : '';
        $whereAppointment2 = $whereAppointment2 ? ' AND ' . implode(' AND ', $whereAppointment2) : '';
        $whereEvent        = $whereEvent ? ' AND ' . implode(' AND ', $whereEvent) : '';

        $groupBy = '';
        if ($invoice) {
            $groupBy = 'GROUP BY IFNULL(invoiceNumber, id)';
        }

        $appointmentQuery1 = "SELECT
                p.id AS id,
                p.dateTime AS dateTime,
                p.created AS created,
                p.invoiceNumber AS invoiceNumber,
                'appointment' AS type
            FROM {$this->table} p
            INNER JOIN {$this->bookingsTable} cb ON cb.id = p.customerBookingId
            INNER JOIN {$this->appointmentsTable} a ON a.id = cb.appointmentId
            WHERE 1=1 {$whereAppointment1} GROUP BY p.customerBookingId ORDER BY p.id ASC";

        $appointmentQuery2 = "SELECT
                p.id AS id,
                p.dateTime AS dateTime,
                p.created AS created,
                p.invoiceNumber AS invoiceNumber,
                'package' AS type
            FROM {$this->table} p
            INNER JOIN {$this->packagesCustomersTable} pc ON p.packageCustomerId = pc.id
            {$appointments2ProvidersServicesJoin}
            WHERE 1=1 {$whereAppointment2} GROUP BY p.packageCustomerId ORDER BY p.id ASC";

        $eventQuery = "SELECT
                p.id AS id,
                p.dateTime AS dateTime,
                p.created AS created,
                p.invoiceNumber AS invoiceNumber,
                'event' AS type
            FROM {$this->table} p
            INNER JOIN {$this->bookingsTable} cb ON cb.id = p.customerBookingId
            INNER JOIN {$this->customerBookingsToEventsPeriodsTable} cbe ON cbe.customerBookingId = cb.id
            {$eventsProvidersJoin}
            WHERE 1=1 {$whereEvent} GROUP BY p.customerBookingId ORDER BY p.id ASC";

        $result = [];

        if (isset($criteria['events'], $criteria['services'])) {
            return $result;
        } elseif (isset($criteria['services'])) {
            $paymentQuery = "({$appointmentQuery1}) UNION ALL ({$appointmentQuery2})";
            $params       = array_merge($params, $appointmentParams1, $appointmentParams2);
        } elseif (isset($criteria['events'])) {
            $paymentQuery = "({$eventQuery})";
            $params       = array_merge($params, $eventParams);
        } elseif (isset($criteria['packages'])) {
            $paymentQuery = "({$appointmentQuery2})";
            $params       = array_merge($params, $appointmentParams2);
        } else {
            $paymentQuery = "({$appointmentQuery1}) UNION ALL ({$appointmentQuery2}) UNION ALL ({$eventQuery})";
            $params       = array_merge($params, $appointmentParams1, $appointmentParams2, $eventParams);
        }

        $limit = $this->getLimit(
            !empty($criteria['page']) ? (int)$criteria['page'] : 0,
            (int)$itemsPerPage
        );

        try {
            $statement = $this->connection->prepare(
                "SELECT * FROM ({$paymentQuery}) payments
                {$groupBy}
                ORDER BY {$basedOnDate}, id
                {$limit}"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        foreach ($rows as $row) {
            $result[(int)$row['id']] = $row['type'];
        }

        return $result;
    }

    /**
     * @param array $criteria
     * @param boolean $invoice
     *
     * @return array
     * @throws QueryExecutionException
     */
    public function getFilteredIdsCount($criteria, $invoice = false)
    {
        $params = [];
        $appointmentParams1 = [];
        $appointmentParams2 = [];
        $eventParams        = [];
        $whereAppointment1  = [];
        $whereAppointment2  = [];
        $whereEvent         = [];

        $basedOnDate = $invoice ? 'created' : 'dateTime';

        if (!empty($criteria['dates'])) {
            $whereAppointment1[] = "(p.{$basedOnDate} BETWEEN :paymentAppointmentFrom1 AND :paymentAppointmentTo1)";
            $whereAppointment2[] = "(p.{$basedOnDate} BETWEEN :paymentAppointmentFrom2 AND :paymentAppointmentTo2)";
            $appointmentParams1[':paymentAppointmentFrom1'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][0]);
            $appointmentParams2[':paymentAppointmentFrom2'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][0]);
            $appointmentParams1[':paymentAppointmentTo1']   = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][1]);
            $appointmentParams2[':paymentAppointmentTo2']   = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][1]);

            $whereEvent[] = "(p.{$basedOnDate} BETWEEN :paymentEventFrom AND :paymentEventTo)";
            $eventParams[':paymentEventFrom'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][0]);
            $eventParams[':paymentEventTo']   = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][1]);
        }

        if (!empty($criteria['customerId'])) {
            $appointmentParams1[':customerAppointmentId1'] = $criteria['customerId'];
            $appointmentParams2[':customerAppointmentId2'] = $criteria['customerId'];
            $whereAppointment1[] = 'cb.customerId = :customerAppointmentId1';
            $whereAppointment2[] = 'pc.customerId = :customerAppointmentId2';

            $eventParams[':customerEventId'] = $criteria['customerId'];
            $whereEvent[] = 'cb.customerId = :customerEventId';
        }

        $eventsProvidersJoin = '';

        if (!empty($criteria['providerId'])) {
            $appointmentParams1[':providerAppointmentId1'] = $criteria['providerId'];
            $appointmentParams1[':providerAppointmentId2'] = $criteria['providerId'];
            $whereAppointment1[] = 'a.providerId = :providerAppointmentId1';
            $whereAppointment2[] = 'a.providerId = :providerAppointmentId2';

            $eventParams[':providerEventId'] = $criteria['providerId'];
            $whereEvent[] = 'epu.userId = :providerEventId';

            $eventsProvidersJoin = "
                INNER JOIN {$this->eventsPeriodsTable} ep ON ep.id = cbe.eventPeriodId
                INNER JOIN {$this->eventsProvidersTable} epu ON epu.eventId = ep.eventId
            ";
        }

        if (!empty($criteria['services'])) {
            $queryServices1 = [];
            $queryServices2 = [];

            foreach ((array)$criteria['services'] as $index => $value) {
                $param1           = ':service0' . $index;
                $param2           = ':service1' . $index;
                $queryServices1[] = $param1;
                $queryServices2[] = $param2;
                $appointmentParams1[$param1] = $value;
                $appointmentParams2[$param2] = $value;
            }

            $whereAppointment1[] = 'a.serviceId IN (' . implode(', ', $queryServices1) . ')';
            $whereAppointment2[] = 'a.serviceId IN (' . implode(', ', $queryServices2) . ')';
        }

        $appointments2ProvidersServicesJoin = '';

        if (!empty($criteria['providerId']) || !empty($criteria['services'])) {
            $appointments2ProvidersServicesJoin = "
                INNER JOIN {$this->packagesCustomersServiceTable} pcs ON pc.id = pcs.packageCustomerId
                INNER JOIN {$this->bookingsTable} cb ON cb.packageCustomerServiceId = pcs.id
                INNER JOIN {$this->appointmentsTable} a ON a.id = cb.appointmentId
            ";
        }

        if (!empty($criteria['status'])) {
            $appointmentParams1[':statusAppointment1'] = $criteria['status'];
            $appointmentParams2[':statusAppointment2'] = $criteria['status'];
            $whereAppointment1[] = 'p.status = :statusAppointment1';
            $whereAppointment2[] = 'p.status = :statusAppointment2';

            $eventParams[':statusEvent'] = $criteria['status'];
            $whereEvent[] = 'p.status = :statusEvent';
        }

        if (!empty($criteria['events'])) {
            $queryEvents = [];

            foreach ((array)$criteria['events'] as $index => $value) {
                $param         = ':event' . $index;
                $queryEvents[] = $param;
                $eventParams[$param] = $value;
            }

            $whereEvent[] = "p.customerBookingId IN (SELECT cbe.customerBookingId
              FROM {$this->eventsTable} e
              INNER JOIN {$this->eventsPeriodsTable} ep ON ep.eventId = e.id
              INNER JOIN {$this->customerBookingsToEventsPeriodsTable} cbe ON cbe.eventPeriodId = ep.id 
              WHERE e.id IN (" . implode(', ', $queryEvents) . '))';
        }

        if (!empty($criteria['packages'])) {
            $queryPackages = [];

            foreach ((array)$criteria['packages'] as $index => $value) {
                $param           = ':package' . $index;
                $queryPackages[] = $param;
                $appointmentParams2[$param] = $value;
            }

            $whereAppointment2[] = "p.packageCustomerId IN (SELECT pc.id
              FROM {$this->packagesCustomersTable} pc
              WHERE pc.packageId IN (" . implode(', ', $queryPackages) . '))';
        }

        $whereAppointment1 = $whereAppointment1 ? ' AND ' . implode(' AND ', $whereAppointment1) : '';
        $whereAppointment2 = $whereAppointment2 ? ' AND ' . implode(' AND ', $whereAppointment2) : '';
        $whereEvent        = $whereEvent ? ' AND ' . implode(' AND ', $whereEvent) : '';

        $groupBy = '';
        if ($invoice) {
            $groupBy = 'GROUP BY IFNULL(invoiceNumber, id)';
        }


        $appointmentQuery1 = "SELECT
                COUNT(DISTINCT(p.customerBookingId)) AS appointmentsCount1,
                0 AS appointmentsCount2,
                0 AS eventsCount,
                p.id AS id,
                p.invoiceNumber AS invoiceNumber,
                p.created AS created,
                p.dateTime AS dateTime
            FROM {$this->table} p
            INNER JOIN {$this->bookingsTable} cb ON cb.id = p.customerBookingId
            INNER JOIN {$this->appointmentsTable} a ON a.id = cb.appointmentId
            WHERE 1=1 {$whereAppointment1} GROUP BY p.customerBookingId ORDER BY p.id ASC";

        $appointmentQuery2 = "SELECT
                0 AS appointmentsCount1,
                COUNT(DISTINCT(p.packageCustomerId)) AS appointmentsCount2,
                0 AS eventsCount,
                p.id AS id,
                p.invoiceNumber AS invoiceNumber,
                p.created AS created,
                p.dateTime AS dateTime
            FROM {$this->table} p
            INNER JOIN {$this->packagesCustomersTable} pc ON p.packageCustomerId = pc.id
            {$appointments2ProvidersServicesJoin}
            WHERE 1=1 {$whereAppointment2} GROUP BY p.packageCustomerId ORDER BY p.id ASC";

        $eventQuery = "SELECT
                0 AS appointmentsCount1,
                0 AS appointmentsCount2,
                COUNT(DISTINCT(p.customerBookingId)) AS eventsCount,
                p.id AS id,
                p.invoiceNumber AS invoiceNumber,
                p.created AS created,
                p.dateTime AS dateTime
            FROM {$this->table} p
            INNER JOIN {$this->bookingsTable} cb ON cb.id = p.customerBookingId
            INNER JOIN {$this->customerBookingsToEventsPeriodsTable} cbe ON cbe.customerBookingId = cb.id
            {$eventsProvidersJoin}
            WHERE 1=1 {$whereEvent} GROUP BY p.customerBookingId ORDER BY p.id ASC";

        if (isset($criteria['events'], $criteria['services'])) {
            return [];
        } elseif (isset($criteria['services'])) {
            $paymentQuery = "({$appointmentQuery1}) UNION ALL ({$appointmentQuery2})";
            $params       = array_merge($params, $appointmentParams1, $appointmentParams2);
        } elseif (isset($criteria['events'])) {
            $paymentQuery = "({$eventQuery})";
            $params       = array_merge($params, $eventParams);
        } elseif (isset($criteria['packages'])) {
            $paymentQuery = "({$appointmentQuery2})";
            $params       = array_merge($params, $appointmentParams2);
        } else {
            $paymentQuery = "({$appointmentQuery1}) UNION ALL ({$appointmentQuery2}) UNION ALL ({$eventQuery})";
            $params       = array_merge($params, $appointmentParams1, $appointmentParams2, $eventParams);
        }

        try {
            $statement = $this->connection->prepare(
                "SELECT * FROM ({$paymentQuery}) payments
                {$groupBy}
                ORDER BY {$basedOnDate}, id"
            );

            $statement->execute($params);

            $statements = $statement->fetchAll();

            $appointmentsCount1 = 0;
            $appointmentsCount2 = 0;
            $eventsCount        = 0;

            foreach ($statements as $st) {
                $appointmentsCount1 += !empty($st['appointmentsCount1']) ? $st['appointmentsCount1'] : 0;
                $appointmentsCount2 += !empty($st['appointmentsCount2']) ? $st['appointmentsCount2'] : 0;
                $eventsCount        += !empty($st['eventsCount']) ? $st['eventsCount'] : 0;
            }
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        return $appointmentsCount1 + $appointmentsCount2 + $eventsCount;
    }

    /**
     * Returns a collection of customers that have birthday on today's date and where notification is not sent
     *
     * @return Collection
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Exception
     */
    public function getUncompletedActionsForPayments()
    {
        $params = [];

        $currentDateTime = "STR_TO_DATE('" . DateTimeService::getNowDateTimeInUtc() . "', '%Y-%m-%d %H:%i:%s')";

        $pastDateTime =
            "STR_TO_DATE('" .
            DateTimeService::getNowDateTimeObjectInUtc()->modify('-1 day')->format('Y-m-d H:i:s') .
            "', '%Y-%m-%d %H:%i:%s')";

        try {
            $statement = $this->connection->prepare(
                "SELECT * FROM {$this->table} 
                WHERE
                      actionsCompleted = 0 AND
                      {$currentDateTime} > DATE_ADD(created, INTERVAL 300 SECOND) AND
                      {$pastDateTime} < created AND
                      entity IS NOT NULL"
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
     * @param array $data
     * @param boolean $invoice
     *
     * @return array
     * @throws QueryExecutionException
     */
    public function getSecondaryPaymentIds($data, $invoice)
    {
        $params = [];

        $where = [];

        $parentIdParam1  = null;
        $parentIdParam2  = null;
        $paymentIdParam2 = null;

        foreach ($data as $index => $item) {
            $paymentIdParam1          = ':paymentId1' . $index;
            $params[$paymentIdParam1] = $item['paymentId'];

            if ($invoice) {
                if (!empty($item['parentId'])) {
                    $parentIdParam1          = ':parentId1' . $index;
                    $params[$parentIdParam1] = $item['parentId'];
                    $parentIdParam2          = ':parentId2' . $index;
                    $params[$parentIdParam2] = $item['parentId'];
                }
                $paymentIdParam2          = ':paymentId2' . $index;
                $params[$paymentIdParam2] = $item['paymentId'];
            }

            $relationParam = ':' . $item['columnName'] . $index;

            $params[$relationParam] = $item['columnId'];

            // change in the future to simply retrieve by invoiceNumber when on invoice page since all the related payments will have the same invoiceNumber
            // cannot be done immediately since invoiceNumber is NULL for existing payments
            if ($invoice) {
                $where[] =
                    "((id <> $paymentIdParam1 AND " .
                    $item['columnName'] . " = $relationParam) OR parentId = $paymentIdParam2" .
                    (!empty($item['parentId']) ? " OR id = $parentIdParam1 OR parentId = $parentIdParam2)" : ")");
            } else {
                $where[] = "(id <> $paymentIdParam1 AND " . $item['columnName'] . " = $relationParam)";
            }
        }

        $where = $where ? 'WHERE ' . implode(' OR ', $where) : '';

        try {
            $statement = $this->connection->prepare(
                "SELECT
                    p.id AS id,
                    p.entity AS entity
                FROM {$this->table} p
                {$where}"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find by id in ' . __CLASS__, $e->getCode(), $e);
        }

        $result = [];

        foreach ($rows as $row) {
            $result[(int)$row['id']] = $row['entity'];
        }

        return $result;
    }

    /**
     * @param int $paymentId
     * @param string $transactionId
     *
     * @throws QueryExecutionException
     */
    public function updateTransactionId($paymentId, $transactionId)
    {
        $params = [
            ':transactionId' => $transactionId,
            ':paymentId1'    => $paymentId,
            ':paymentId2'    => $paymentId
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table} SET `transactionId` = :transactionId WHERE id = :paymentId1 OR parentId = :paymentId2"
            );

            $response = $statement->execute($params);
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to update data in ' . __CLASS__, $e->getCode(), $e);
        }

        if (!$response) {
            throw new QueryExecutionException('Unable to update data in ' . __CLASS__);
        }

        return $response;
    }



    /**
     * @param array $data
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function setInvoiceNumber($data)
    {
        $params = [
            ':id1' => $data['id'],
            ':id2' => $data['id'],
            ":{$data['columnName']}" => $data['columnValue']
        ];

        $where = "WHERE id = :id1 OR parentId = :id2 OR {$data['columnName']} = :{$data['columnName']}";

        if (!empty($data['parentId'])) {
            $params[':parentId1'] = $params[':parentId2'] = $data['parentId'];
            $where = ' OR id = :parentId1 OR parentId = :parentId2';
        }

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table} 
                SET `invoiceNumber` = (SELECT MAX(CASE WHEN invoiceNumber IS NULL THEN 0 ELSE invoiceNumber END)+1 FROM (SELECT * FROM {$this->table}) AS p)
                {$where}"
            );

            $response = $statement->execute($params);
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to save invoice number in ' . __CLASS__, $e->getCode(), $e);
        }

        if (!$response) {
            throw new QueryExecutionException('Unable to save invoice number in ' . __CLASS__);
        }

        return $response;
    }

    /**
     * @param array $rows
     *
     * @return array
     */
    private function getEntitiesPaymentsResult($rows)
    {
        $result = [];

        foreach ($rows as &$row) {
            $customerInfo = $row['info'] ? json_decode($row['info'], true) : null;

            if (empty($result[(int)$row['id']])) {
                $result[(int)$row['id']] = [
                    'id' =>  (int)$row['id'],
                    'dateTime' =>  DateTimeService::getCustomDateTimeFromUtc($row['dateTime']),
                    'created'  =>  DateTimeService::getCustomDateTimeFromUtc($row['created']),
                    'bookingStart' =>  $row['bookingStart'] ?
                        DateTimeService::getCustomDateTimeFromUtc($row['bookingStart']) : null,
                    'status' =>  $row['status'],
                    'parentId' =>  $row['parentId'],
                    'wcOrderId' =>  $row['wcOrderId'],
                    'wcOrderItemId' =>  $row['wcOrderItemId'],
                    'gateway' =>  $row['gateway'],
                    'gatewayTitle' =>  $row['gatewayTitle'],
                    'transactionId' =>  $row['transactionId'],
                    'type' => $row['type'],
                    'name' => $row['bookableName'],
                    'customerBookingId' =>  (int)$row['customerBookingId'] ?: null,
                    'packageCustomerId' =>  (int)$row['packageCustomerId'] ?: null,
                    'amount' =>  (float)$row['amount'],
                    'invoiceNumber' => $row['invoiceNumber'],
                    'providers' =>  (int)$row['providerId'] ? [
                        [
                            'id' => (int)$row['providerId'],
                            'fullName' => $row['providerFirstName'] . ' ' . $row['providerLastName'],
                            'email' => $row['providerEmail'],
                        ]
                    ] : [],
                    'location' => (int)$row['locationId'] || $row['location_address'] ?
                        [
                            'id' => (int)$row['locationId'],
                            'location_name' => $row['location_name'],
                            'location_address' => $row['location_address'],
                        ]
                        : null,
                    'customerId' =>  (int)$row['customerId'],
                    'serviceId' =>  (int)$row['serviceId'] ?: null,
                    'appointmentId' =>  (int)$row['appointmentId'] ?: null,
                    'packageId' =>  (int)$row['packageId'] ?: null,
                    'bookedPrice' =>  (float)$row['bookedPrice'] ?: null,
                    'bookedTax' =>  $row['bookedTax'] ? : null,
                    'bookingId' => !empty($row['bookingId']) ? (int)$row['bookingId'] : null,
                    'bookableName' => $row['bookableName'],
                    'customerFirstName' => $customerInfo ? $customerInfo['firstName'] : $row['customerFirstName'],
                    'customerLastName' => $customerInfo ? $customerInfo['lastName'] : $row['customerLastName'],
                    'info' => $row['info'],
                    'customerEmail' => $row['customerEmail'],
                    'customerStatus' => $row['customerStatus'],
                    'coupon' => !empty($row['coupon_id']) ? [
                        'id' => (int)$row['coupon_id'],
                        'discount' => (float)$row['coupon_discount'],
                        'deduction' => (float)$row['coupon_deduction'],
                        'code' => $row['coupon_code']
                    ] : null,
                    'persons' => (int)$row['persons'],
                    'aggregatedPrice' => (bool)$row['aggregatedPrice'],
                    'extras' => [],
                ];
            }

            if ($result[(int)$row['id']] && $row['bookingExtra_id']) {
                $result[(int)$row['id']]['extras'][] = [
                    'id' => (int)$row['bookingExtra_id'],
                    'quantity' => (int)$row['bookingExtra_quantity'],
                    'price' => (float)$row['bookingExtra_price'],
                    'aggregatedPrice' => (bool)$row['bookingExtra_aggregatedPrice'],
                    'tax' => $row['bookingExtra_tax'] ?: null
                ];
            }
        }

        return $result;
    }

    /**
     * @param array $ids
     *
     * @return array
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getAppointmentsPaymentsByIds($ids)
    {
        $params = [];

        $where = [];

        if (!empty($ids)) {
            foreach ($ids as $index => $value) {
                $param = ':sId' . $index;

                $params[$param] = $value;
            }

            $where[] = 'p.id IN (' . implode(', ', array_keys($params)) . ')';
        }

        $where = $where ? ' AND ' . implode(' AND ', $where) : '';

        $customerBookingsExtrasTable = CustomerBookingsToExtrasTable::getTableName();

        $couponsTable = CouponsTable::getTableName();

        $locationsTable = LocationsTable::getTableName();

        try {
            $statement = $this->connection->prepare(
                "SELECT
                    p.id AS id,
                    p.customerBookingId AS customerBookingId,
                    NULL AS packageCustomerId,
                    p.amount AS amount,
                    p.invoiceNumber AS invoiceNumber,
                    p.dateTime AS dateTime,
                    p.created AS created,
                    p.status AS status,
                    p.wcOrderId AS wcOrderId,
                    p.wcOrderItemId AS wcOrderItemId,
                    p.gateway AS gateway,
                    p.gatewayTitle AS gatewayTitle,
                    p.transactionId AS transactionId,
                    p.parentId AS parentId,
                    p.entity AS type,
                    
                    NULL AS packageId,
                    cb.id AS bookingId,
                    cb.price AS bookedPrice,
                    cb.tax AS bookedTax,
                    a.providerId AS providerId,
                    cb.customerId AS customerId,
                    cb.persons AS persons,
                    cb.aggregatedPrice AS aggregatedPrice,
                    cb.info AS info,
    
                    cbe.id AS bookingExtra_id,
                    cbe.quantity AS bookingExtra_quantity,
                    cbe.price AS bookingExtra_price,
                    cbe.aggregatedPrice AS bookingExtra_aggregatedPrice,
                    cbe.tax AS bookingExtra_tax,
            
                    c.id AS coupon_id,
                    c.discount AS coupon_discount,
                    c.deduction AS coupon_deduction,
                    c.code AS coupon_code,
           
                    a.serviceId AS serviceId,
                    a.id AS appointmentId,
                    a.bookingStart AS bookingStart,
                    s.name AS bookableName,
                    cu.firstName AS customerFirstName,
                    cu.lastName AS customerLastName,
                    cu.email AS customerEmail,
                    cu.status AS customerStatus,
                    pu.firstName AS providerFirstName,
                    pu.lastName AS providerLastName,
                    pu.email AS providerEmail,
                    l.id AS locationId,
                    l.name AS location_name,
                    l.address AS location_address
                FROM {$this->table} p
                INNER JOIN {$this->bookingsTable} cb ON cb.id = p.customerBookingId
                LEFT JOIN {$customerBookingsExtrasTable} cbe ON cbe.customerBookingId = cb.id
                LEFT JOIN {$couponsTable} c ON c.id = cb.couponId
                INNER JOIN {$this->appointmentsTable} a ON a.id = cb.appointmentId
                INNER JOIN {$this->servicesTable} s ON s.id = a.serviceId
                INNER JOIN {$this->usersTable} cu ON cu.id = cb.customerId
                INNER JOIN {$this->usersTable} pu ON pu.id = a.providerId
                LEFT JOIN {$locationsTable} l ON l.id = a.locationId
                WHERE 1=1 {$where}"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        return $this->getEntitiesPaymentsResult($rows);
    }

    /**
     * @param array $ids
     *
     * @return array
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getEventsPaymentsByIds($ids)
    {
        $params = [];

        $where = [];

        if (!empty($ids)) {
            foreach ($ids as $index => $value) {
                $param = ':eId' . $index;

                $params[$param] = $value;
            }

            $where[] = 'p.id IN (' . implode(', ', array_keys($params)) . ')';
        }

        $where = $where ? ' AND ' . implode(' AND ', $where) : '';

        $couponsTable = CouponsTable::getTableName();

        $locationsTable = LocationsTable::getTableName();

        try {
            $statement = $this->connection->prepare(
                "SELECT
                    p.id AS id,
                    p.customerBookingId AS customerBookingId,
                    NULL AS packageCustomerId,
                    p.amount AS amount,
                    p.invoiceNumber AS invoiceNumber,
                    p.dateTime AS dateTime,
                    p.created AS created,
                    p.status AS status,
                    p.wcOrderId AS wcOrderId,
                    p.wcOrderItemId AS wcOrderItemId,
                    p.gateway AS gateway,
                    p.gatewayTitle AS gatewayTitle,
                    p.transactionId AS transactionId,
                    p.parentId AS parentId,
                    p.entity AS type,
                    
                    NULL AS packageId,
                    cb.id AS bookingId,
                    cb.price AS bookedPrice,
                    cb.tax AS bookedTax,
                    NULL AS providerId,
                    cb.customerId AS customerId,
                    cb.persons AS persons,
                    cb.aggregatedPrice AS aggregatedPrice,
                    cb.info AS info,
                    
                    NULL AS bookingExtra_id,
                    NULL AS bookingExtra_quantity,
                    NULL AS bookingExtra_price,
                    NULL AS bookingExtra_aggregatedPrice,
                    NULL AS bookingExtra_tax,
           
                    c.id AS coupon_id,
                    c.discount AS coupon_discount,
                    c.deduction AS coupon_deduction,
                    c.code AS coupon_code,
           
                    NULL AS serviceId,
                    NULL AS appointmentId,
                    NULL AS bookingStart,
                    NULL AS bookableName,
                    cu.firstName AS customerFirstName,
                    cu.lastName AS customerLastName,
                    cu.email AS customerEmail,
                    cu.status AS customerStatus,
                    NULL AS providerFirstName,
                    NULL AS providerLastName,
                    NULL AS providerEmail,
                    l.id AS locationId,
                    l.name AS location_name,
                    (CASE WHEN e.customlocation IS NOT NULL THEN e.customlocation ELSE l.address END) AS location_address
                FROM {$this->table} p
                INNER JOIN {$this->bookingsTable} cb ON cb.id = p.customerBookingId
                LEFT JOIN {$couponsTable} c ON c.id = cb.couponId
                INNER JOIN {$this->usersTable} cu ON cu.id = cb.customerId
                INNER JOIN {$this->customerBookingsToEventsPeriodsTable} cbe ON cbe.customerBookingId = cb.id
                INNER JOIN {$this->eventsPeriodsTable} ep ON ep.id = cbe.eventPeriodId
                INNER JOIN {$this->eventsTable} e ON e.id = ep.eventId
                LEFT JOIN {$this->eventsProvidersTable} epu ON epu.eventId = ep.eventId
                LEFT JOIN {$locationsTable} l ON l.id = e.locationId
                WHERE 1=1 {$where}"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        return $this->getEntitiesPaymentsResult($rows);
    }

    /**
     * @param array $ids
     *
     * @return array
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getPackagesPaymentsByIds($ids)
    {
        $params = [];

        $where = [];

        if (!empty($ids)) {
            foreach ($ids as $index => $value) {
                $param = ':pId' . $index;

                $params[$param] = $value;
            }

            $where[] = 'p.id IN (' . implode(', ', array_keys($params)) . ')';
        }

        $where = $where ? ' AND ' . implode(' AND ', $where) : '';

        $couponsTable = CouponsTable::getTableName();

        try {
            $statement = $this->connection->prepare(
                "SELECT
                    p.id AS id,
                    NULL AS customerBookingId,
                    p.packageCustomerId AS packageCustomerId,
                    p.amount AS amount,
                    p.invoiceNumber AS invoiceNumber,
                    p.dateTime AS dateTime,
                    p.created AS created,
                    p.status AS status,
                    p.wcOrderId AS wcOrderId,
                    p.wcOrderItemId AS wcOrderItemId,
                    p.gateway AS gateway,
                    p.gatewayTitle AS gatewayTitle,
                    p.transactionId AS transactionId,
                    p.parentId AS parentId,
                    p.entity AS type,
                    
                    pc.packageId AS packageId,
                    pc.id AS bookingId,
                    pc.price AS bookedPrice,
                    pc.tax AS bookedTax,
                    NULL AS providerId,
                    pc.customerId AS customerId,
                    NULL AS persons,
                    NULL AS aggregatedPrice,
                    cb.info AS info,  
                    
                    NULL AS bookingExtra_id,
                    NULL AS bookingExtra_quantity,
                    NULL AS bookingExtra_price,
                    NULL AS bookingExtra_aggregatedPrice,
                    NULL AS bookingExtra_tax,
           
                    c.id AS coupon_id,
                    c.discount AS coupon_discount,
                    c.deduction AS coupon_deduction,
                    c.code AS coupon_code,
           
                    NULL AS serviceId,
                    NULL AS appointmentId,
                    NULL AS bookingStart,
                    pa.name AS bookableName,
                    cu.firstName AS customerFirstName,
                    cu.lastName AS customerLastName,
                    cu.email AS customerEmail,
                    cu.status AS customerStatus,
                    '' AS providerFirstName,
                    '' AS providerLastName,
                    '' AS providerEmail,
                    '' AS locationId,
                    '' AS location_name,
                    '' AS location_address
                FROM {$this->table} p
                INNER JOIN {$this->packagesCustomersTable} pc ON p.packageCustomerId = pc.id
                INNER JOIN {$this->usersTable} cu ON cu.id = pc.customerId
                LEFT JOIN {$couponsTable} c ON c.id = pc.couponId
                INNER JOIN {$this->packagesTable} pa ON pa.id = pc.packageId
                INNER JOIN {$this->packagesCustomersServiceTable} pcs ON pc.id = pcs.packageCustomerId
                LEFT JOIN {$this->bookingsTable} cb ON cb.packageCustomerServiceId = pcs.id
                LEFT JOIN {$this->appointmentsTable} a ON a.id = cb.appointmentId
                WHERE 1=1 {$where} ORDER BY p.id ASC"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        return $this->getEntitiesPaymentsResult($rows);
    }
}
