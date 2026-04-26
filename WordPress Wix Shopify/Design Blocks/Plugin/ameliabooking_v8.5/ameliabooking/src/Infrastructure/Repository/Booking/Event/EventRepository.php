<?php

namespace AmeliaBooking\Infrastructure\Repository\Booking\Event;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Factory\Booking\Appointment\CustomerBookingFactory;
use AmeliaBooking\Domain\Factory\Booking\Event\EventFactory;
use AmeliaBooking\Domain\Repository\Booking\Event\EventRepositoryInterface;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Licence;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\CustomerBookingsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\CustomerBookingsToEventsPeriodsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\CustomerBookingToEventsTicketsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\EventsPeriodsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\EventsProvidersTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\EventsTagsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\EventsTicketsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Coupon\CouponsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Gallery\GalleriesTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Payment\PaymentsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\UsersTable;

/**
 * Class EventRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Booking\Event
 */
class EventRepository extends AbstractRepository implements EventRepositoryInterface
{
    public const FACTORY = EventFactory::class;

    /**
     * @param Event $entity
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function add($entity)
    {
        $data = $entity->toArray();

        $params = [
            ':bookingOpens'         => $data['bookingOpens'] ? DateTimeService::getCustomDateTimeInUtc($data['bookingOpens']) : null,
            ':bookingCloses'        => $data['bookingCloses'] ? DateTimeService::getCustomDateTimeInUtc($data['bookingCloses']) : null,
            ':bookingOpensRec'      => $data['bookingOpensRec'],
            ':bookingClosesRec'     => $data['bookingClosesRec'],
            ':status'               => $data['status'],
            ':name'                 => $data['name'],
            ':description'          => $data['description'],
            ':color'                => $data['color'],
            ':price'                => $data['price'],
            ':bringingAnyone'       => $data['bringingAnyone'] ? 1 : 0,
            ':bookMultipleTimes'    => $data['bookMultipleTimes'] ? 1 : 0,
            ':maxCapacity'          => $data['maxCapacity'],
            ':maxCustomCapacity'    => $data['maxCustomCapacity'],
            ':maxExtraPeople'       => $data['maxExtraPeople'],
            ':show'                 => $data['show'] ? 1 : 0,
            ':notifyParticipants'   => $data['notifyParticipants'],
            ':customLocation'       => $data['customLocation'],
            ':parentId'             => $data['parentId'],
            ':created'              => $data['created'],
            ':closeAfterMin'        => $data['closeAfterMin'],
            ':closeAfterMinBookings'  => $data['closeAfterMinBookings'] ? 1 : 0,
            ':aggregatedPrice'      => $data['aggregatedPrice'] ? 1 : 0,
            ':error'                => '',
        ];

        $additionalData = Licence\DataModifier::getEventRepositoryData($data);

        $params = array_merge($params, $additionalData['values'], $additionalData['addValues']);

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO {$this->table} 
                (
                {$additionalData['columns']}
                `bookingOpens`,
                `bookingCloses`,
                `bookingOpensRec`,
                `bookingClosesRec`,
                `status`,
                `name`,
                `description`,
                `color`,
                `price`,
                `bringingAnyone`,
                `bookMultipleTimes`,
                `maxCapacity`,
                `maxCustomCapacity`,
                `maxExtraPeople`,
                `show`,
                `notifyParticipants`,
                `customLocation`,
                `parentId`,
                `created`,
                `closeAfterMin`,
                `closeAfterMinBookings`,
                `aggregatedPrice`,
                `error`
                 )
                VALUES (
                {$additionalData['placeholders']}
                :bookingOpens,
                :bookingCloses,
                :bookingOpensRec,
                :bookingClosesRec,
                :status,
                :name,
                :description,
                :color,
                :price,
                :bringingAnyone,
                :bookMultipleTimes,
                :maxCapacity,
                :maxCustomCapacity,
                :maxExtraPeople,
                :show,
                :notifyParticipants,
                :customLocation,
                :parentId,
                :created,
                :closeAfterMin,
                :closeAfterMinBookings,
                :aggregatedPrice,
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
     * @param Event $entity
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function update($id, $entity)
    {
        $data = $entity->toArray();

        $params = [
            ':id'                   => $id,
            ':bookingOpens'         => $data['bookingOpens'] ? DateTimeService::getCustomDateTimeInUtc($data['bookingOpens']) : null,
            ':bookingCloses'        => $data['bookingCloses'] ? DateTimeService::getCustomDateTimeInUtc($data['bookingCloses']) : null,
            ':bookingOpensRec'      => $data['bookingOpensRec'],
            ':bookingClosesRec'     => $data['bookingClosesRec'],
            ':status'               => $data['status'],
            ':name'                 => $data['name'],
            ':description'          => $data['description'],
            ':color'                => $data['color'],
            ':price'                => $data['price'],
            ':bringingAnyone'       => $data['bringingAnyone'] ? 1 : 0,
            ':bookMultipleTimes'    => $data['bookMultipleTimes'] ? 1 : 0,
            ':maxCapacity'          => $data['maxCapacity'],
            ':maxCustomCapacity'    => $data['maxCustomCapacity'],
            ':maxExtraPeople'       => $data['maxExtraPeople'],
            ':show'                 => $data['show'] ? 1 : 0,
            ':notifyParticipants'   => $data['notifyParticipants'] ? 1 : 0,
            ':customLocation'       => $data['customLocation'],
            ':parentId'             => $data['parentId'],
            ':closeAfterMin'        => $data['closeAfterMin'],
            ':closeAfterMinBookings'  => $data['closeAfterMinBookings'] ? 1 : 0,
            ':aggregatedPrice'      => $data['aggregatedPrice'] ? 1 : 0
        ];

        $additionalData = Licence\DataModifier::getEventRepositoryData($data);

        $params = array_merge($params, $additionalData['values']);

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET
                {$additionalData['columnsPlaceholders']}
                `bookingOpens` = :bookingOpens,
                `bookingCloses` = :bookingCloses, 
                `bookingOpensRec` = :bookingOpensRec,
                `bookingClosesRec` = :bookingClosesRec, 
                `status` = :status,
                `name` = :name,
                `description` = :description,
                `color` = :color,
                `price` = :price,
                `bringingAnyone` = :bringingAnyone,
                `bookMultipleTimes` = :bookMultipleTimes,
                `maxCapacity` = :maxCapacity,
                `maxCustomCapacity` = :maxCustomCapacity, 
                `maxExtraPeople` = :maxExtraPeople,    
                `show` = :show,
                `notifyParticipants` = :notifyParticipants,
                `customLocation` = :customLocation,
                `parentId` = :parentId,
                `closeAfterMin` = :closeAfterMin,
                `closeAfterMinBookings` = :closeAfterMinBookings,
                `aggregatedPrice` = :aggregatedPrice
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
     * @param int      $id
     * @param int|null $parentId
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function updateParentId($id, $parentId)
    {
        $params = [
            ':id'             => $id,
            ':parentId'       => $parentId,
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET
                `parentId` = :parentId
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
     * @param array $criteria
     *
     * @return Collection
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getProvidersEvents($criteria)
    {
        $eventsPeriodsTable   = EventsPeriodsTable::getTableName();
        $eventsProvidersTable = EventsProvidersTable::getTableName();
        $usersTable           = UsersTable::getTableName();

        $params = [];
        $where  = [];

        if (!empty($criteria['dates'])) {
            if (isset($criteria['dates'][0], $criteria['dates'][1])) {
                $whereStart           = "(ep.periodStart BETWEEN :eventFrom AND :eventTo)";
                $params[':eventFrom'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][0]);
                $params[':eventTo']   = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][1]);

                $whereEnd = "(ep.periodEnd BETWEEN :bookingFrom2 AND :bookingTo2)";
                $params[':bookingFrom2'] = $params[':eventFrom'];
                $params[':bookingTo2']   = $params[':eventTo'];

                $where[] = "({$whereStart} OR {$whereEnd})";
            } elseif (isset($criteria['dates'][0])) {
                $where[] = "(ep.periodStart >= :eventFrom)";
                $params[':eventFrom'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][0]);
            } elseif (isset($criteria['dates'][1])) {
                $where[]            = "(ep.periodStart <= :eventTo)";
                $params[':eventTo'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][1]);
            } else {
                $where[] = "(ep.periodStart > :eventFrom)";
                $params[':eventFrom'] = DateTimeService::getNowDateTimeInUtc();
            }
        }

        if (!empty($criteria['providers'])) {
            $queryProviders = [];

            foreach ((array)$criteria['providers'] as $index => $value) {
                $param            = ':provider' . $index;
                $queryProviders[] = $param;
                $params[$param]   = $value;
            }

            $where[] = 'epr.userId IN (' . implode(', ', $queryProviders) . ')';
        }

        if (!empty($criteria['status'])) {
            $params[':status'] = $criteria['status'];

            $where[] = 'e.status = :status';
        }

        $where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        try {
            $statement = $this->connection->prepare(
                "SELECT
                    e.id AS event_id,
                    e.name AS event_name,
                    e.status AS event_status,
                    e.bookingOpens AS event_bookingOpens,
                    e.bookingCloses AS event_bookingCloses,
                    e.recurringCycle AS event_recurringCycle,
                    e.recurringOrder AS event_recurringOrder,
                    e.recurringInterval AS event_recurringInterval,
                    e.recurringUntil AS event_recurringUntil,
                    e.recurringMonthly AS event_recurringMonthly, 
                    e.monthlyDate AS event_monthlyDate,
                    e.monthlyOnRepeat AS event_monthlyOnRepeat,
                    e.monthlyOnDay AS event_monthlyOnDay,
                    e.bringingAnyone AS event_bringingAnyone,
                    e.bookMultipleTimes AS event_bookMultipleTimes,
                    e.maxCapacity AS event_maxCapacity,
                    e.maxCustomCapacity AS event_maxCustomCapacity,
                    e.maxExtraPeople AS event_maxExtraPeople,
                    e.price AS event_price,
                    e.description AS event_description,
                    e.color AS event_color,
                    e.show AS event_show,
                    e.locationId AS event_locationId,
                    e.customLocation AS event_customLocation,
                    e.parentId AS event_parentId,
                    e.created AS event_created,
                    e.notifyParticipants AS event_notifyParticipants,
                    e.translations AS event_translations,
                    e.deposit AS event_deposit,
                    e.depositPayment AS event_depositPayment,
                    e.depositPerPerson AS event_depositPerPerson,
                    e.fullPayment AS event_fullPayment,
                    e.customPricing AS event_customPricing,
                    e.aggregatedPrice AS event_aggregatedPrice,
                    
                    ep.id AS event_periodId,
                    ep.periodStart AS event_periodStart,
                    ep.periodEnd AS event_periodEnd,
                    
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
                    pu.translations AS provider_translations
                FROM {$this->table} e
                INNER JOIN {$eventsPeriodsTable} ep ON ep.eventId = e.id
                INNER JOIN {$eventsProvidersTable} epr ON epr.eventId = e.id
                INNER JOIN {$usersTable} pu ON pu.id = epr.userId
                {$where}
                ORDER BY ep.periodStart"
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
     * @param int   $itemsPerPage
     *
     * @return array
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getFilteredIds($criteria, $itemsPerPage)
    {
        $eventsPeriodsTable    = EventsPeriodsTable::getTableName();
        $eventsTagsTable       = EventsTagsTable::getTableName();
        $customerBookingsTable = CustomerBookingsTable::getTableName();
        $customerBookingsEventsPeriods = CustomerBookingsToEventsPeriodsTable::getTableName();
        $eventsProvidersTable          = EventsProvidersTable::getTableName();
        $usersTable = UsersTable::getTableName();

        $params = [];

        $where = [];

        if (isset($criteria['parentId'])) {
            $params[':parentId'] = $criteria['parentId'];

            $params[':originParentId'] = $criteria['parentId'];

            $where[] = 'e.parentId = :parentId OR e.id = :originParentId';
        }

        if (!empty($criteria['search'])) {
            $where[] = "(e.name LIKE '%" . $criteria['search'] . "%' 
            OR e.translations LIKE '{\"name\":{%" . $criteria['search'] . "%\"description\":{%'
            OR e.translations LIKE '{\"description\":{%\"name\":{%" . $criteria['search'] . "%'
            OR (e.translations LIKE '{\"name\":{%" . $criteria['search'] . "%' AND e.translations NOT LIKE '%\"description\":{%'))";
        }


        if (isset($criteria['show'])) {
            $where[] = 'e.show = 1';
        }

        if (!empty($criteria['dates'])) {
            if (isset($criteria['dates'][0], $criteria['dates'][1])) {
                $where[] = "((ep.periodStart BETWEEN :eventFrom1 AND :eventTo1)
                OR (ep.periodEnd BETWEEN :eventFrom2 AND :eventTo2)
                OR (:eventFrom3 BETWEEN ep.periodStart AND ep.periodEnd)
                OR (:eventTo3  BETWEEN ep.periodStart AND ep.periodEnd))";

                $params[':eventFrom1'] = $params[':eventFrom2'] = $params[':eventFrom3'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][0]);
                $params[':eventTo1']   = $params[':eventTo2']   = $params[':eventTo3']   = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][1]);
            } elseif (isset($criteria['dates'][0])) {
                $where[] = "(ep.periodStart >= :eventFrom OR (ep.periodEnd >= :eventTo))";
                $params[':eventFrom'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][0]);
                $params[':eventTo']   = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][0]);
            } elseif (isset($criteria['dates'][1])) {
                $where[] = "(ep.periodStart <= :eventTo)";

                $params[':eventTo'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][1]);
            } else {
                $where[] = "(ep.periodStart > :eventFrom)";
                $params[':eventFrom'] = DateTimeService::getNowDateTimeInUtc();
            }
        }

        $tagJoin = '';

        if (isset($criteria['tag'])) {
            $queryTags = [];

            $tags = $criteria['tag'];
            foreach ((array)$tags as $index => $value) {
                $param = ':tag' . $index;

                $queryTags[] = $param;

                $params[$param] = $value;
            }

            $where[] = 'et.name IN (' . implode(', ', $queryTags) . ')';

            $tagJoin = "INNER JOIN {$eventsTagsTable} et ON et.eventId = e.id";
        }

        if (!empty($criteria['id'])) {
            if (!empty($criteria['recurring'])) {
                $whereOr = [];
                foreach ((array)$criteria['id'] as $index => $value) {
                    $param = 'id' . $index;

                    $params[':rec1' . $param] = (int)$value;
                    $params[':rec2' . $param] = (int)$value;
                    $params[':rec3' . $param] = (int)$value;
                    $params[':rec4' . $param] = (int)$value;

                    $whereOr[] = "((e.id = :rec1id" . $index . " AND e.parentId IS NULL) OR 
                    (e.parentId IN (SELECT parentId FROM {$this->table} WHERE parentId IS NOT NULL AND parentId = :rec2id" . $index . ")) OR
                    (e.id >= :rec3id" . $index . "  AND e.parentId IN (SELECT parentId FROM {$this->table} WHERE id = :rec4id" . $index . ")))";
                }
                $where[] = '(' . implode(' OR ', $whereOr) . ')';
            } else {
                $queryIds = [];

                foreach ((array)$criteria['id'] as $index => $value) {
                    $param = ':id' . $index;

                    $queryIds[] = $param;

                    $params[$param] = (int)$value;
                }

                $where[] = 'e.id IN (' . implode(', ', $queryIds) . ')';
            }
        }

        $customerJoin = '';

        if (!empty($criteria['customerId']) || !empty($criteria['customerBookingsIds'])) {
            $customerJoin = "
            LEFT JOIN {$customerBookingsEventsPeriods} cbe ON cbe.eventPeriodId = ep.id
            LEFT JOIN {$customerBookingsTable} cb ON cb.id = cbe.customerBookingId";

            if (!empty($criteria['customerId'])) {
                $params[':customerId'] = $criteria['customerId'];

                $where[] = 'cb.customerId = :customerId';
            }

            if (!empty($criteria['customerBookingsIds'])) {
                $queryBookingsIds = [];

                foreach ($criteria['customerBookingsIds'] as $index => $value) {
                    $param = ':customerBookingId' . $index;

                    $queryBookingsIds[] = $param;

                    $params[$param] = $value;
                }

                $where[] = 'cb.id IN (' . implode(', ', $queryBookingsIds) . ')';
            }

            if (!empty($criteria['customerBookingStatus'])) {
                $params[':customerBookingStatus'] = $criteria['customerBookingStatus'];

                $where[] = 'cb.status = :customerBookingStatus';
            }

            if (!empty($criteria['customerBookingCouponId'])) {
                $params[':customerBookingCouponId'] = $criteria['customerBookingCouponId'];

                $where[] = 'cb.couponId = :customerBookingCouponId';
            }
        }

        if (!empty($criteria['locationId'])) {
            $params[':locationId'] = $criteria['locationId'];

            $where[] = 'e.locationId = :locationId';
        }

        if (!empty($criteria['locations'])) {
            $queryLocations = [];

            foreach ((array)$criteria['locations'] as $index => $value) {
                $param            = ':location' . $index;
                $queryLocations[] = $param;
                $params[$param]   = $value;
            }

            $where3 = 'e.locationId IN (' . implode(', ', $queryLocations) . ')';

            $where[] = '(' . $where3 . ')';
        }

        $providerJoin = '';

        if (!empty($criteria['providers'])) {
            $providerJoin   = "
            LEFT JOIN {$eventsProvidersTable} epr ON epr.eventId = e.id
            INNER JOIN {$usersTable} pu ON pu.id = epr.userId OR pu.id = e.organizerId";
            $queryProviders = [];

            foreach ((array)$criteria['providers'] as $index => $value) {
                $param            = ':provider' . $index;
                $queryProviders[] = $param;
                $params[$param]   = $value;
            }

            $where1 = 'epr.userId IN (' . implode(', ', $queryProviders) . ')';

            $queryProviders = [];
            foreach ((array)$criteria['providers'] as $index => $value) {
                $param            = ':organizer' . $index;
                $queryProviders[] = $param;
                $params[$param]   = $value;
            }

            $where2 = 'e.organizerId IN (' . implode(', ', $queryProviders) . ')';

            $where[] = '(' . $where1 . ' OR ' . $where2 . ')';
        }

        $where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $limit = $this->getLimit(
            !empty($criteria['page']) ? (int)$criteria['page'] : 0,
            (int)$itemsPerPage
        );

        try {
            $statement = $this->connection->prepare(
                "SELECT
                     e.id
                FROM {$this->table} e
                INNER JOIN {$eventsPeriodsTable} ep ON ep.eventId = e.id
                {$tagJoin}
                {$providerJoin}
                {$customerJoin}
                {$where}
                GROUP BY e.id
                ORDER BY ep.periodStart, e.id
                {$limit}"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find by id in ' . __CLASS__, $e->getCode(), $e);
        }

        return array_column($rows, 'id');
    }

    /**
     * @param array $criteria
     *
     * @return int
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getFilteredIdsCount($criteria)
    {
        $eventsPeriodsTable   = EventsPeriodsTable::getTableName();
        $eventsTagsTable      = EventsTagsTable::getTableName();
        $eventsProvidersTable = EventsProvidersTable::getTableName();
        $usersTable           = UsersTable::getTableName();


        $params = [];
        $where  = [];

        if (isset($criteria['parentId'])) {
            $params[':parentId'] = $criteria['parentId'];

            $params[':originParentId'] = $criteria['parentId'];

            $where[] = 'e.parentId = :parentId OR e.id = :originParentId';
        }

        if (!empty($criteria['search'])) {
            $where[] = "(e.name LIKE '%" . $criteria['search'] . "%' 
            OR e.translations LIKE '{\"name\":{%" . $criteria['search'] . "%\"description\":{%'
            OR e.translations LIKE '{\"description\":{%\"name\":{%" . $criteria['search'] . "%'
            OR (e.translations LIKE '{\"name\":{%" . $criteria['search'] . "%' AND e.translations NOT LIKE '%\"description\":{%'))";
        }

        if (isset($criteria['show'])) {
            $where[] = 'e.show = 1';
        }

        if (!empty($criteria['dates'])) {
            if (isset($criteria['dates'][0], $criteria['dates'][1])) {
                $where[] = "((ep.periodStart BETWEEN :eventFrom1 AND :eventTo1)
                OR (ep.periodEnd BETWEEN :eventFrom2 AND :eventTo2)
                OR (:eventFrom3 BETWEEN ep.periodStart AND ep.periodEnd)
                OR (:eventTo3  BETWEEN ep.periodStart AND ep.periodEnd))";

                $params[':eventFrom1'] = $params[':eventFrom2'] = $params[':eventFrom3'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][0]);
                $params[':eventTo1']   = $params[':eventTo2']   = $params[':eventTo3']   = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][1]);
            } elseif (isset($criteria['dates'][0])) {
                $where[] = "(ep.periodStart >= :eventFrom OR (ep.periodEnd >= :eventTo))";

                $params[':eventFrom'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][0]);

                $params[':eventTo'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][0]);
            } elseif (isset($criteria['dates'][1])) {
                $where[] = "(ep.periodStart <= :eventTo)";

                $params[':eventTo'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][1]);
            } else {
                $where[] = "(ep.periodStart > :eventFrom)";

                $params[':eventFrom'] = DateTimeService::getNowDateTimeInUtc();
            }
        }

        if (!empty($criteria['locationId'])) {
            $params[':locationId'] = $criteria['locationId'];

            $where[] = 'e.locationId = :locationId';
        }

        if (!empty($criteria['locations'])) {
            $queryLocations = [];

            foreach ((array)$criteria['locations'] as $index => $value) {
                $param            = ':location' . $index;
                $queryLocations[] = $param;
                $params[$param]   = $value;
            }

            $where3 = 'e.locationId IN (' . implode(', ', $queryLocations) . ')';

            $where[] = '(' . $where3 . ')';
        }


        $tagJoin = '';

        if (isset($criteria['tag'])) {
            $queryTags = [];

            $tags = $criteria['tag'];//explode(',', $criteria['tag']);
            foreach ((array)$tags as $index => $value) {
                $param = ':tag' . $index;

                $queryTags[] = $param;

                $params[$param] = $value;//trim($value, '{}');
            }

            $where[] = 'et.name IN (' . implode(', ', $queryTags) . ')';

            $tagJoin = "INNER JOIN {$eventsTagsTable} et ON et.eventId = e.id";
        }

        if (!empty($criteria['id'])) {
            if (!empty($criteria['recurring'])) {
                $whereOr = [];
                foreach ((array)$criteria['id'] as $index => $value) {
                    $param = 'id' . $index;

                    $params[':rec1' . $param] = (int)$value;
                    $params[':rec2' . $param] = (int)$value;
                    $params[':rec3' . $param] = (int)$value;
                    $params[':rec4' . $param] = (int)$value;

                    $whereOr[] = "((e.id = :rec1id" . $index . " AND e.parentId IS NULL) OR 
                    (e.parentId IN (SELECT parentId FROM {$this->table} WHERE parentId IS NOT NULL AND parentId = :rec2id" . $index . ")) OR
                    (e.id >= :rec3id" . $index . "  AND e.parentId IN (SELECT parentId FROM {$this->table} WHERE id = :rec4id" . $index . ")))";
                }
                $where[] = '(' . implode(' OR ', $whereOr) . ')';
            } else {
                $queryIds = [];

                foreach ((array)$criteria['id'] as $index => $value) {
                    $param = ':id' . $index;

                    $queryIds[] = $param;

                    $params[$param] = (int)$value;
                }

                $where[] = 'e.id IN (' . implode(', ', $queryIds) . ')';
            }
        }

        $providerJoin = '';

        if (!empty($criteria['providers'])) {
            $providerJoin = "
            LEFT JOIN {$eventsProvidersTable} epr ON epr.eventId = e.id
            INNER JOIN {$usersTable} pu ON pu.id = epr.userId OR pu.id = e.organizerId";

            $queryProviders = [];

            foreach ((array)$criteria['providers'] as $index => $value) {
                $param            = ':provider' . $index;
                $queryProviders[] = $param;
                $params[$param]   = $value;
            }
            $where1 = 'epr.userId IN (' . implode(', ', $queryProviders) . ')';

            $queryProviders = [];
            foreach ((array)$criteria['providers'] as $index => $value) {
                $param            = ':organizer' . $index;
                $queryProviders[] = $param;
                $params[$param]   = $value;
            }
            $where2 = 'e.organizerId IN (' . implode(', ', $queryProviders) . ')';

            $where[] = '(' . $where1 . ' OR ' . $where2 . ')';
        }

        $customerJoin = '';

        $customerBookingsTable         = CustomerBookingsTable::getTableName();
        $customerBookingsEventsPeriods = CustomerBookingsToEventsPeriodsTable::getTableName();

        if (!empty($criteria['customerId']) || !empty($criteria['customerBookingsIds'])) {
            $customerJoin = "
            LEFT JOIN {$customerBookingsEventsPeriods} cbe ON cbe.eventPeriodId = ep.id
            LEFT JOIN {$customerBookingsTable} cb ON cb.id = cbe.customerBookingId";

            if (!empty($criteria['customerId'])) {
                $params[':customerId'] = $criteria['customerId'];

                $where[] = 'cb.customerId = :customerId';
            }

            if (!empty($criteria['customerBookingsIds'])) {
                $queryBookingsIds = [];

                foreach ($criteria['customerBookingsIds'] as $index => $value) {
                    $param = ':customerBookingId' . $index;

                    $queryBookingsIds[] = $param;

                    $params[$param] = $value;
                }

                $where[] = 'cb.id IN (' . implode(', ', $queryBookingsIds) . ')';
            }

            if (!empty($criteria['customerBookingStatus'])) {
                $params[':customerBookingStatus'] = $criteria['customerBookingStatus'];

                $where[] = 'cb.status = :customerBookingStatus';
            }

            if (!empty($criteria['customerBookingCouponId'])) {
                $params[':customerBookingCouponId'] = $criteria['customerBookingCouponId'];

                $where[] = 'cb.couponId = :customerBookingCouponId';
            }
        }

        $where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        try {
            $statement = $this->connection->prepare(
                "SELECT e.id
                FROM {$this->table} e
                INNER JOIN {$eventsPeriodsTable} ep ON ep.eventId = e.id
                {$tagJoin}
                {$providerJoin}
                {$customerJoin}
                {$where}
                GROUP BY e.id
                ORDER BY ep.periodStart"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find by id in ' . __CLASS__, $e->getCode(), $e);
        }

        return sizeOf($rows);
    }

    /**
     * @param int $id
     *
     * @return Event
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getById($id, $criteria = [])
    {
        $eventsPeriodsTable = EventsPeriodsTable::getTableName();
        $eventsTagsTable    = EventsTagsTable::getTableName();
        $eventsTicketTable  = EventsTicketsTable::getTableName();

        $customerBookingsTable = CustomerBookingsTable::getTableName();
        $paymentsTable         = PaymentsTable::getTableName();
        $usersTable            = UsersTable::getTableName();
        $customerBookingsEventsPeriods = CustomerBookingsToEventsPeriodsTable::getTableName();
        $galleriesTable       = GalleriesTable::getTableName();
        $eventsProvidersTable = EventsProvidersTable::getTableName();
        $couponsTable         = CouponsTable::getTableName();

        $fields = '';

        $joins = "INNER JOIN {$eventsPeriodsTable} ep ON ep.eventId = e.id
                LEFT JOIN {$eventsTagsTable} et ON et.eventId = e.id
                LEFT JOIN {$customerBookingsEventsPeriods} cbe ON cbe.eventPeriodId = ep.id
                LEFT JOIN {$customerBookingsTable} cb ON cb.id = cbe.customerBookingId
                LEFT JOIN {$usersTable} cu ON cu.id = cb.customerId
                LEFT JOIN {$eventsProvidersTable} epr ON epr.eventId = e.id
                LEFT JOIN {$usersTable} pu ON pu.id = epr.userId
                LEFT JOIN {$paymentsTable} p ON p.customerBookingId = cb.id
                LEFT JOIN {$galleriesTable} g ON g.entityId = e.id AND g.entityType = 'event'
                LEFT JOIN {$couponsTable} c ON c.id = cb.couponId
                LEFT JOIN {$eventsTicketTable} t ON t.eventId = e.id";

        if (!empty($criteria['fetchBookingsTickets'])) {
            $bookingsTicketsTable = CustomerBookingToEventsTicketsTable::getTableName();

            $fields .= '
                cbt.id AS booking_ticket_id,
                cbt.eventTicketId AS booking_ticket_eventTicketId,
                cbt.price AS booking_ticket_price,
                cbt.persons AS booking_ticket_persons,
            ';

            $joins .= "
                LEFT JOIN {$bookingsTicketsTable} cbt ON cbt.customerBookingId = cb.id
            ";
        }

        $fields .= 'e.id AS event_id,
                    e.name AS event_name,
                    e.status AS event_status,
                    e.bookingOpens AS event_bookingOpens,
                    e.bookingCloses AS event_bookingCloses, 
                    e.bookingOpensRec AS event_bookingOpensRec,
                    e.bookingClosesRec AS event_bookingClosesRec,
                    e.ticketRangeRec AS event_ticketRangeRec,
                    e.recurringCycle AS event_recurringCycle,
                    e.recurringOrder AS event_recurringOrder,
                    e.recurringInterval AS event_recurringInterval,
                    e.recurringMonthly AS event_recurringMonthly, 
                    e.monthlyDate AS event_monthlyDate,
                    e.monthlyOnRepeat AS event_monthlyOnRepeat,
                    e.monthlyOnDay AS event_monthlyOnDay,
                    e.recurringUntil AS event_recurringUntil,
                    e.bringingAnyone AS event_bringingAnyone,
                    e.bookMultipleTimes AS event_bookMultipleTimes,
                    e.maxCapacity AS event_maxCapacity,
                    e.maxCustomCapacity AS event_maxCustomCapacity,
                    e.maxExtraPeople AS event_maxExtraPeople,
                    e.price AS event_price,
                    e.description AS event_description,
                    e.color AS event_color,
                    e.show AS event_show,
                    e.notifyParticipants AS event_notifyParticipants,
                    e.locationId AS event_locationId,
                    e.customLocation AS event_customLocation,
                    e.parentId AS event_parentId,
                    e.created AS event_created,
                    e.settings AS event_settings,
                    e.zoomUserId AS event_zoomUserId,
                    e.organizerId AS event_organizerId,
                    e.translations AS event_translations,
                    e.deposit AS event_deposit,
                    e.depositPayment AS event_depositPayment,
                    e.depositPerPerson AS event_depositPerPerson,
                    e.fullPayment AS event_fullPayment,
                    e.customPricing AS event_customPricing,
                    e.aggregatedPrice AS event_aggregatedPrice,
                    
                    ep.id AS event_periodId,
                    ep.periodStart AS event_periodStart,
                    ep.periodEnd AS event_periodEnd,
                    ep.zoomMeeting AS event_periodZoomMeeting,
                    ep.lessonSpace AS event_periodLessonSpace,
                    ep.googleCalendarEventId AS event_googleCalendarEventId,
                    ep.googleMeetUrl AS event_googleMeetUrl,
                    ep.outlookCalendarEventId AS event_outlookCalendarEventId,
                    ep.microsoftTeamsUrl AS event_microsoftTeamsUrl,
                    ep.appleCalendarEventId AS event_appleCalendarEventId,
                    
                    et.id AS event_tagId,
                    et.name AS event_tagName,
                                        
                    cb.id AS booking_id,
                    cb.customerId AS booking_customerId,
                    cb.status AS booking_status,
                    cb.price AS booking_price,
                    cb.persons AS booking_persons,
                    cb.customFields AS booking_customFields,
                    cb.info AS booking_info,
                    cb.aggregatedPrice AS booking_aggregatedPrice,
                    cb.token AS booking_token,
                    cb.utcOffset AS booking_utcOffset,
                    cb.couponId AS booking_couponId,
                    
                    cu.id AS customer_id,
                    cu.firstName AS customer_firstName,
                    cu.lastName AS customer_lastName,
                    cu.email AS customer_email,
                    cu.note AS customer_note,
                    cu.phone AS customer_phone,
                    cu.gender AS customer_gender,
                    cu.birthday AS customer_birthday,
                    
                    p.id AS payment_id,
                    p.amount AS payment_amount,
                    p.dateTime AS payment_dateTime,
                    p.status AS payment_status,
                    p.gateway AS payment_gateway,
                    p.gatewayTitle AS payment_gatewayTitle,
                    p.transactionId AS payment_transactionId,
                    p.data AS payment_data,
                    p.wcOrderId AS payment_wcOrderId,
                    p.wcOrderItemId AS payment_wcOrderItemId,
                    p.invoiceNumber AS payment_invoiceNumber,
                    
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
       
                    g.id AS gallery_id,
                    g.pictureFullPath AS gallery_picture_full,
                    g.pictureThumbPath AS gallery_picture_thumb,
                    g.position AS gallery_position,
                    
                    c.id AS coupon_id,
                    c.code AS coupon_code,
                    c.discount AS coupon_discount,
                    c.deduction AS coupon_deduction,
                    c.limit AS coupon_limit,
                    c.customerLimit AS coupon_customerLimit,
                    c.status AS coupon_status,
       
                    t.id AS ticket_id,
                    t.name AS ticket_name,
                    t.enabled AS ticket_enabled,
                    t.price AS ticket_price,
                    t.spots AS ticket_spots,
                    t.waitingListSpots AS ticket_waiting_list_spots,
                    t.dateRanges AS ticket_dateRanges,
                    t.translations AS ticket_translations';

        try {
            $statement = $this->connection->prepare(
                "SELECT
                    {$fields}
                FROM {$this->table} e
                {$joins}
                
                WHERE e.id = :eventId"
            );

            $statement->bindParam(':eventId', $id);

            $statement->execute();

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find event by id in ' . __CLASS__, $e->getCode(), $e);
        }

        return call_user_func([static::FACTORY, 'createCollection'], $rows)->getItem($id);
    }


    /**
     * @param int $id
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function isRecurring($id)
    {
        try {
            $statement = $this->connection->prepare(
                "SELECT
                  e.recurringOrder AS event_recurringOrder, 
                  e.parentId AS event_parentId 
                FROM {$this->table} e 
                WHERE e.id = :eventId"
            );

            $statement->bindParam(':eventId', $id);

            $statement->execute();

            return $statement->fetch();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find event by id in ' . __CLASS__, $e->getCode(), $e);
        }
    }


    /**
     * @param int $id
     * @param int $parentId
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function getRecurringIds($id, $parentId)
    {
        $whereParent = empty($parentId) ? '' : ' OR e.parentId = :parentId';
        try {
            $statement = $this->connection->prepare(
                "SELECT
                  e.id AS eventId 
                FROM {$this->table} e 
                WHERE e.parentId = :eventId" . $whereParent
            );

            $statement->bindParam(':eventId', $id);
            if ($parentId) {
                $statement->bindParam(':parentId', $parentId);
            }

            $statement->execute();

            $events = $statement->fetchAll();

            return array_column($events, 'eventId');
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find event by id in ' . __CLASS__, $e->getCode(), $e);
        }
    }

    /**
     * @param int   $bookingId
     * @param array $criteria
     *
     * @return Event
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getByBookingId($bookingId, $criteria = [])
    {
        $eventsPeriodsTable = EventsPeriodsTable::getTableName();

        $customerBookingsEventsPeriods = CustomerBookingsToEventsPeriodsTable::getTableName();

        $fields = '';

        $joins = '';

        if (!empty($criteria['fetchEventsCoupons'])) {
            $couponsTable = CouponsTable::getTableName();

            $fields .= '
                ec.id AS coupon_id,
                ec.code AS coupon_code,
                ec.discount AS coupon_discount,
                ec.deduction AS coupon_deduction,
                ec.limit AS coupon_limit,
                ec.customerLimit AS coupon_customerLimit,
                ec.status AS coupon_status,
            ';

            $joins .= "
                LEFT JOIN {$couponsTable} ec ON ec.id = cb.couponId
            ";
        }

        if (!empty($criteria['fetchEventsTickets'])) {
            $ticketsTable = EventsTicketsTable::getTableName();

            $fields .= '
                eti.id AS ticket_id,
                eti.name AS ticket_name,
                eti.enabled AS ticket_enabled,
                eti.price AS ticket_price,
                eti.spots AS ticket_spots,
                eti.waitingListSpots AS ticket_waiting_list_spots,
                eti.dateRanges AS ticket_dateRanges,
                eti.translations AS ticket_translations,
            ';

            $joins .= "
                LEFT JOIN {$ticketsTable} eti ON eti.eventId = e.id
            ";
        }

        if (!empty($criteria['fetchEventsTags'])) {
            $tagsTable = EventsTagsTable::getTableName();

            $fields .= '
                eta.id AS event_tagId,
                eta.name AS event_tagName,
            ';

            $joins .= "
                LEFT JOIN {$tagsTable} eta ON eta.eventId = e.id
            ";
        }

        if (!empty($criteria['fetchEventsImages'])) {
            $galleriesTable = GalleriesTable::getTableName();

            $fields .= '
                eg.id AS gallery_id,
                eg.pictureFullPath AS gallery_picture_full,
                eg.pictureThumbPath AS gallery_picture_thumb,
                eg.position AS gallery_position,
            ';

            $joins .= "
                LEFT JOIN {$galleriesTable} eg ON eg.entityId = e.id AND eg.entityType = 'event'
            ";
        }

        if (!empty($criteria['fetchEventsProviders'])) {
            $eventsProvidersTable = EventsProvidersTable::getTableName();

            $usersTable = UsersTable::getTableName();

            $joins .= "
                LEFT JOIN {$eventsProvidersTable} epr ON epr.eventId = e.id
                LEFT JOIN {$usersTable} pu ON pu.id = epr.userId
            ";

            $fields .= '
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
                pu.timeZone AS provider_timeZone,
            ';
        }

        $fields .= "
            e.id AS event_id,
            e.name AS event_name,
            e.status AS event_status,
            e.bookingOpens AS event_bookingOpens,
            e.bookingCloses AS event_bookingCloses,
            e.recurringCycle AS event_recurringCycle,
            e.recurringOrder AS event_recurringOrder,
            e.recurringInterval AS event_recurringInterval,
            e.recurringUntil AS event_recurringUntil,
            e.bringingAnyone AS event_bringingAnyone,
            e.bookMultipleTimes AS event_bookMultipleTimes,
            e.maxCapacity AS event_maxCapacity,
            e.maxCustomCapacity AS event_maxCustomCapacity,
            e.maxExtraPeople AS event_maxExtraPeople,
            e.price AS event_price,
            e.description AS event_description,
            e.color AS event_color,
            e.show AS event_show,
            e.notifyParticipants AS event_notifyParticipants,
            e.locationId AS event_locationId,
            e.customLocation AS event_customLocation,
            e.customPricing AS event_customPricing,
            e.parentId AS event_parentId,
            e.created AS event_created,
            e.settings AS event_settings,
            e.zoomUserId AS event_zoomUserId,
            e.translations AS event_translations,
            e.deposit AS event_deposit,
            e.depositPayment AS event_depositPayment,
            e.depositPerPerson AS event_depositPerPerson,
            e.fullPayment AS event_fullPayment,
            e.organizerId AS event_organizerId,
            e.aggregatedPrice AS event_aggregatedPrice,
            
            ep.id AS event_periodId,
            ep.periodStart AS event_periodStart,
            ep.periodEnd AS event_periodEnd,
            ep.zoomMeeting AS event_periodZoomMeeting,
            ep.lessonSpace AS event_periodLessonSpace,
            ep.googleCalendarEventId AS event_googleCalendarEventId,
            ep.googleMeetUrl AS event_googleMeetUrl,
            ep.outlookCalendarEventId AS event_outlookCalendarEventId,
            ep.microsoftTeamsUrl AS event_microsoftTeamsUrl,
            ep.appleCalendarEventId AS event_appleCalendarEventId
        ";

        $params = [
            ':customerBookingId' => $bookingId,
        ];

        try {
            $statement = $this->connection->prepare(
                "SELECT
                {$fields}
                FROM {$customerBookingsEventsPeriods} cbe
                INNER JOIN {$eventsPeriodsTable} ep ON ep.id = cbe.eventPeriodId
                INNER JOIN {$this->table} e ON e.id = ep.eventId
                {$joins}
                WHERE cbe.customerBookingId = :customerBookingId"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find event by booking id in ' . __CLASS__, $e->getCode(), $e);
        }

        /** @var Collection $events */
        $events = call_user_func([static::FACTORY, 'createCollection'], $rows);

        return $events->length() ? $events->getItem($events->keys()[0]) : null;
    }

    /**
     * @param array $ids
     * @param array $criteria
     *
     * @return Collection
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getByIdsWithEntities($ids, $criteria = [])
    {
        $params = [];

        $where = [];

        $fields = '';

        $joins = '';

        $orderBy = '';

        if (!empty($criteria['fetchEventsPeriods'])) {
            $eventsPeriodsTable = EventsPeriodsTable::getTableName();

            $fields .= '
                ep.id AS event_periodId,
                ep.periodStart AS event_periodStart,
                ep.periodEnd AS event_periodEnd,
                ep.zoomMeeting AS event_periodZoomMeeting,
                ep.lessonSpace AS event_periodLessonSpace,
                ep.googleCalendarEventId AS event_googleCalendarEventId,
                ep.googleMeetUrl AS event_googleMeetUrl,
                ep.outlookCalendarEventId AS event_outlookCalendarEventId,
                ep.microsoftTeamsUrl AS event_microsoftTeamsUrl,
                ep.appleCalendarEventId AS event_appleCalendarEventId,
            ';

            $joins .= "
                INNER JOIN {$eventsPeriodsTable} ep ON ep.eventId = e.id
            ";

            $orderBy = !empty($criteria['ordered']) ? 'ORDER BY e.id, ep.periodStart' : 'ORDER BY ep.periodStart';
        }

        if (!empty($criteria['fetchEventsCoupons'])) {
            $couponsTable = CouponsTable::getTableName();

            $fields .= '
                ec.id AS coupon_id,
                ec.code AS coupon_code,
                ec.discount AS coupon_discount,
                ec.deduction AS coupon_deduction,
                ec.limit AS coupon_limit,
                ec.customerLimit AS coupon_customerLimit,
                ec.status AS coupon_status,
            ';

            $joins .= "
                LEFT JOIN {$couponsTable} ec ON ec.id = cb.couponId
            ";
        }

        if (!empty($criteria['fetchEventsTickets'])) {
            $ticketsTable = EventsTicketsTable::getTableName();

            $fields .= '
                eti.id AS ticket_id,
                eti.name AS ticket_name,
                eti.enabled AS ticket_enabled,
                eti.price AS ticket_price,
                eti.spots AS ticket_spots,
                eti.waitingListSpots AS ticket_waiting_list_spots,
                eti.dateRanges AS ticket_dateRanges,
                eti.translations AS ticket_translations,
            ';

            $joins .= "
                LEFT JOIN {$ticketsTable} eti ON eti.eventId = e.id
            ";
        }

        if (!empty($criteria['fetchEventsTags'])) {
            $tagsTable = EventsTagsTable::getTableName();

            $fields .= '
                eta.id AS event_tagId,
                eta.name AS event_tagName,
            ';

            $joins .= "
                LEFT JOIN {$tagsTable} eta ON eta.eventId = e.id
            ";
        }

        if (!empty($criteria['fetchEventsImages'])) {
            $galleriesTable = GalleriesTable::getTableName();

            $fields .= '
                eg.id AS gallery_id,
                eg.pictureFullPath AS gallery_picture_full,
                eg.pictureThumbPath AS gallery_picture_thumb,
                eg.position AS gallery_position,
            ';

            $joins .= "
                LEFT JOIN {$galleriesTable} eg ON eg.entityId = e.id AND eg.entityType = 'event'
            ";
        }

        if (!empty($criteria['fetchEventsProviders'])) {
            $eventsProvidersTable = EventsProvidersTable::getTableName();

            $usersTable = UsersTable::getTableName();

            $joins .= "
                LEFT JOIN {$eventsProvidersTable} epr ON epr.eventId = e.id
                LEFT JOIN {$usersTable} pu ON pu.id = epr.userId
            ";

            $fields .= '
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
                pu.timeZone AS provider_timeZone,
            ';
        }

        $fields .= "
            e.id AS event_id,
            e.name AS event_name,
            e.status AS event_status,
            e.bookingOpens AS event_bookingOpens,
            e.bookingCloses AS event_bookingCloses, 
            e.bookingOpensRec AS event_bookingOpensRec,
            e.bookingClosesRec AS event_bookingClosesRec,
            e.ticketRangeRec AS event_ticketRangeRec,
            e.recurringCycle AS event_recurringCycle,
            e.recurringOrder AS event_recurringOrder,
            e.recurringInterval AS event_recurringInterval,
            e.recurringMonthly AS event_recurringMonthly, 
            e.monthlyDate AS event_monthlyDate,
            e.monthlyOnRepeat AS event_monthlyOnRepeat,
            e.monthlyOnDay AS event_monthlyOnDay,
            e.recurringUntil AS event_recurringUntil,
            e.bringingAnyone AS event_bringingAnyone,
            e.bookMultipleTimes AS event_bookMultipleTimes,
            e.maxCapacity AS event_maxCapacity,
            e.maxCustomCapacity AS event_maxCustomCapacity,
            e.maxExtraPeople AS event_maxExtraPeople,
            e.price AS event_price,
            e.description AS event_description,
            e.color AS event_color,
            e.show AS event_show,
            e.notifyParticipants AS event_notifyParticipants,
            e.locationId AS event_locationId,
            e.customLocation AS event_customLocation,
            e.parentId AS event_parentId,
            e.created AS event_created,
            e.settings AS event_settings,
            e.zoomUserId AS event_zoomUserId,
            e.organizerId AS event_organizerId,
            e.translations AS event_translations,
            e.deposit AS event_deposit,
            e.depositPayment AS event_depositPayment,
            e.depositPerPerson AS event_depositPerPerson,
            e.fullPayment AS event_fullPayment,
            e.customPricing AS event_customPricing,
            e.closeAfterMin AS event_closeAfterMin,
            e.closeAfterMinBookings AS event_closeAfterMinBookings,
            e.aggregatedPrice AS event_aggregatedPrice
        ";

        if (!empty($ids)) {
            $queryIds = [];

            foreach ($ids as $index => $value) {
                $param = ':id' . $index;

                $queryIds[] = $param;

                $params[$param] = $value;
            }

            $where[] = 'e.id IN (' . implode(', ', $queryIds) . ')';
        }

        $where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        try {
            $statement = $this->connection->prepare(
                "SELECT
                {$fields}
                FROM {$this->table} e
                {$joins}
                {$where}
                {$orderBy}"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find event by id in ' . __CLASS__, $e->getCode(), $e);
        }

        return call_user_func([static::FACTORY, 'createCollection'], $rows);
    }

    /**
     * @param array $criteria
     *
     * @return Collection
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getBookingsByCriteria($criteria = [])
    {
        $params = [];

        $where = [];

        $fields = '';

        $joins = '';

        $eventsPeriodsTable = EventsPeriodsTable::getTableName();

        $customerBookingsEventsPeriods = CustomerBookingsToEventsPeriodsTable::getTableName();

        $customerBookingsTable = CustomerBookingsTable::getTableName();

        if (!empty($criteria['fetchApprovedBookings'])) {
            $where[] = "cb.status = 'approved'";
        }

        if (!empty($criteria['customerId'])) {
            $params[':customerId'] = $criteria['customerId'];

            $where[] = 'cb.customerId = :customerId';
        }

        if (!empty($criteria['customerBookingStatus'])) {
            $params[':customerBookingStatus'] = $criteria['customerBookingStatus'];

            $where[] = 'cb.status = :customerBookingStatus';
        }

        if (!empty($criteria['customerBookingId'])) {
            $params[':customerBookingId'] = $criteria['customerBookingId'];

            $where[] = 'cb.id = :customerBookingId';
        }

        if (!empty($criteria['fetchBookingsPayments'])) {
            $paymentsTable = PaymentsTable::getTableName();

            $fields .= '
                p.id AS payment_id,
                p.amount AS payment_amount,
                p.dateTime AS payment_dateTime,
                p.created AS payment_created,
                p.status AS payment_status,
                p.gateway AS payment_gateway,
                p.gatewayTitle AS payment_gatewayTitle,
                p.transactionId AS payment_transactionId,
                p.data AS payment_data,
                p.wcOrderId AS payment_wcOrderId,
                p.wcOrderItemId AS payment_wcOrderItemId,
                p.invoiceNumber AS payment_invoiceNumber,
            ';

            $joins .= "
                LEFT JOIN {$paymentsTable} p ON p.customerBookingId = cb.id
            ";
        }

        if (!empty($criteria['fetchBookingsCoupons'])) {
            $couponsTable = CouponsTable::getTableName();

            $fields .= '
                c.id AS coupon_id,
                c.code AS coupon_code,
                c.discount AS coupon_discount,
                c.deduction AS coupon_deduction,
                c.limit AS coupon_limit,
                c.customerLimit AS coupon_customerLimit,
                c.status AS coupon_status,
            ';

            $joins .= "
                LEFT JOIN {$couponsTable} c ON c.id = cb.couponId
            ";
        }

        if (!empty($criteria['fetchBookingsUsers'])) {
            $usersTable = UsersTable::getTableName();

            $fields .= '
                cu.id AS customer_id,
                cu.type AS customer_type,
                cu.firstName AS customer_firstName,
                cu.lastName AS customer_lastName,
                cu.email AS customer_email,
                cu.note AS customer_note,
                cu.phone AS customer_phone,
                cu.gender AS customer_gender,
                cu.birthday AS customer_birthday,
            ';

            $joins .= "
                INNER JOIN {$usersTable} cu ON cu.id = cb.customerId
            ";
        }

        if (!empty($criteria['fetchBookingsTickets'])) {
            $bookingsTicketsTable = CustomerBookingToEventsTicketsTable::getTableName();

            $fields .= '
                cbt.id AS booking_ticket_id,
                cbt.eventTicketId AS booking_ticket_eventTicketId,
                cbt.price AS booking_ticket_price,
                cbt.persons AS booking_ticket_persons,
            ';

            $joins .= "
                LEFT JOIN {$bookingsTicketsTable} cbt ON cbt.customerBookingId = cb.id
            ";
        }

        $fields .= '
            ep.eventId AS eventId,
            cb.id AS booking_id,
            cb.appointmentId AS booking_appointmentId,
            cb.customerId AS booking_customerId,
            cb.status AS booking_status,
            cb.price AS booking_price,
            cb.tax AS booking_tax,
            cb.persons AS booking_persons,
            cb.couponId AS booking_couponId,
            cb.customFields AS booking_customFields,
            cb.info AS booking_info,
            cb.utcOffset AS booking_utcOffset,
            cb.token AS booking_token,
            cb.aggregatedPrice AS booking_aggregatedPrice,
            cb.tax AS booking_tax
        ';

        if (!empty($criteria['ids'])) {
            $queryIds = [];

            foreach ($criteria['ids'] as $index => $value) {
                $param = ':id' . $index;

                $queryIds[] = $param;

                $params[$param] = $value;
            }

            $where[] = 'ep.eventId IN (' . implode(', ', $queryIds) . ')';
        }

        $where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        try {
            $statement = $this->connection->prepare(
                "SELECT
                {$fields}
                FROM {$eventsPeriodsTable} ep
                INNER JOIN {$customerBookingsEventsPeriods} cbe ON cbe.eventPeriodId = ep.id
                INNER JOIN {$customerBookingsTable} cb ON cb.id = cbe.customerBookingId
                {$joins}
                {$where}
                ORDER BY cb.id"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find event by id in ' . __CLASS__, $e->getCode(), $e);
        }

        $reformattedData = [];

        foreach ($rows as $row) {
            if (empty($reformattedData[$row['eventId']])) {
                $reformattedData[$row['eventId']] = [];
            }

            $reformattedData[$row['eventId']][] = $row;
        }

        $result = new Collection();

        foreach ($reformattedData as $eventId => $bookingsData) {
            $reformattedBookingsData = CustomerBookingFactory::reformat($bookingsData);

            $eventBookings = new Collection();

            foreach ($reformattedBookingsData as $bookingId => $data) {
                $eventBookings->addItem(CustomerBookingFactory::create($data), $bookingId);
            }

            $result->addItem($eventBookings, $eventId);
        }

        return $result;
    }


    /**
     * @param Event $event
     * @param array $booking
     * @param array $limitPerCustomer
     * @return int
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getRelevantBookingsCount($event, $booking, $limitPerCustomer)
    {
        $eventsPeriodsTable = EventsPeriodsTable::getTableName();

        $customerBookingsEventsPeriods = CustomerBookingsToEventsPeriodsTable::getTableName();

        $customerBookingsTable = CustomerBookingsTable::getTableName();

        $params = [
            ':customerId' => $booking['customerId']
        ];

        $paymentTableJoin = '';
        $compareToDate    = 'ep.periodStart';

        if ($limitPerCustomer['from'] === 'bookingDate') {
            $eventStartDate =
                (clone $event->getPeriods()->getItems()[0]->getPeriodStart()->getValue())->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i');
        } else {
            $paymentTableJoin = 'INNER JOIN ' . PaymentsTable::getTableName() . ' p ON p.customerBookingId = cb.id';
            $eventStartDate   = DateTimeService::getNowDateTimeObject()->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i');
            $compareToDate    = 'p.created';
        }

        $intervalString = "interval " . $limitPerCustomer['period'] . " " . $limitPerCustomer['timeFrame'];

        $where = "(STR_TO_DATE('" . $eventStartDate . "', '%Y-%m-%d %H:%i:%s') BETWEEN " .
            "(" . $compareToDate . " - " . $intervalString . " + interval 1 second)" .
            " AND (" .
            $compareToDate . " + " . $intervalString . " - interval 1 second))";

        try {
            $statement = $this->connection->prepare(
                "SELECT COUNT(DISTINCT cb.id) AS count FROM 
                    {$this->table} e 
                    INNER JOIN {$eventsPeriodsTable} ep ON ep.eventId = e.id 
                    INNER JOIN {$customerBookingsEventsPeriods} cbep ON cbep.eventPeriodId = ep.id 
                    INNER JOIN {$customerBookingsTable} cb ON cb.id = cbep.customerBookingId  
                    {$paymentTableJoin}
                    WHERE cb.customerId = :customerId AND {$where} AND e.status = 'approved' AND cb.status = 'approved'
                "
            );

            $statement->execute($params);

            $rows = $statement->fetch()['count'];
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find by id in ' . __CLASS__, $e->getCode(), $e);
        }

        return $rows;
    }
}
