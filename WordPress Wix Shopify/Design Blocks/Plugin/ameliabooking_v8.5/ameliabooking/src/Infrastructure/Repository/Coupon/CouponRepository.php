<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Repository\Coupon;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Coupon\Coupon;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Factory\Coupon\CouponFactory;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\WholeNumber;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Connection;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;
use AmeliaBooking\Domain\Repository\Coupon\CouponRepositoryInterface;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;

/**
 * Class CouponRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Coupon
 */
class CouponRepository extends AbstractRepository implements CouponRepositoryInterface
{
    public const FACTORY = CouponFactory::class;

    /** @var string */
    protected $servicesTable;

    /** @var string */
    protected $couponToServicesTable;

    /** @var string */
    protected $eventsTable;

    /** @var string */
    protected $couponToEventsTable;

    /** @var string */
    protected $packagesTable;

    /** @var string */
    protected $couponToPackagesTable;

    /** @var string */
    protected $bookingsTable;

    /**
     * @param Connection $connection
     * @param string     $table
     * @param string     $servicesTable
     * @param string     $couponToServicesTable
     * @param string     $eventsTable
     * @param string     $couponToEventsTable
     * @param string     $packagesTable
     * @param string     $couponToPackagesTable
     * @param string     $bookingsTable
     */
    public function __construct(
        Connection $connection,
        $table,
        $servicesTable,
        $couponToServicesTable,
        $eventsTable,
        $couponToEventsTable,
        $packagesTable,
        $couponToPackagesTable,
        $bookingsTable
    ) {
        parent::__construct($connection, $table);

        $this->servicesTable         = $servicesTable;
        $this->couponToServicesTable = $couponToServicesTable;
        $this->eventsTable           = $eventsTable;
        $this->couponToEventsTable   = $couponToEventsTable;
        $this->packagesTable         = $packagesTable;
        $this->couponToPackagesTable = $couponToPackagesTable;
        $this->bookingsTable         = $bookingsTable;
    }

    /**
     * @param Coupon $entity
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function add($entity)
    {
        $data = $entity->toArray();

        $params = [
            ':code'                  => $data['code'],
            ':discount'              => $data['discount'],
            ':deduction'             => $data['deduction'],
            ':limit'                 => (int)$data['limit'],
            ':customerLimit'         => (int)$data['customerLimit'],
            ':status'                => $data['status'],
            ':notificationInterval'  => $data['notificationInterval'],
            ':notificationRecurring' => $data['notificationRecurring'] ? 1 : 0,
            ':expirationDate'        => $data['expirationDate'],
            ':allServices'           => $data['allServices'],
            ':allEvents'             => $data['allEvents'],
            ':allPackages'           => $data['allPackages']
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO
                {$this->table} 
                (
                 `code`,
                 `discount`,
                 `deduction`,
                 `limit`,
                 `customerLimit`,
                 `status`,
                 `notificationInterval`,
                 `notificationRecurring`,
                 `expirationDate`,
                 `allServices`,
                 `allEvents`,
                 `allPackages`  
                ) VALUES (
                  :code, 
                  :discount, 
                  :deduction,
                  :limit,
                  :customerLimit,
                  :status,
                  :notificationInterval,
                  :notificationRecurring,
                  :expirationDate,
                  :allServices,
                  :allEvents,
                  :allPackages
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
     * @param int    $id
     * @param Coupon $entity
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function update($id, $entity)
    {
        $data = $entity->toArray();

        $params = [
            ':code'                  => $data['code'],
            ':discount'              => $data['discount'],
            ':deduction'             => $data['deduction'],
            ':limit'                 => (int)$data['limit'],
            ':customerLimit'         => (int)$data['customerLimit'],
            ':status'                => $data['status'],
            ':notificationInterval'  => $data['notificationInterval'],
            ':notificationRecurring' => $data['notificationRecurring'] ? 1 : 0,
            ':id'                    => $id,
            ':expirationDate'        => $data['expirationDate'],
            ':allServices'           => $data['allServices'],
            ':allEvents'             => $data['allEvents'],
            ':allPackages'           => $data['allPackages']
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET
                `code`                  = :code,
                `discount`              = :discount,
                `deduction`             = :deduction,
                `limit`                 = :limit,
                `customerLimit`         = :customerLimit,
                `status`                = :status,
                `notificationInterval`  = :notificationInterval,
                `notificationRecurring` = :notificationRecurring,
                `expirationDate`        = :expirationDate,
                `allServices`           = :allServices,
                `allEvents`             = :allEvents,
                `allPackages`           = :allPackages
                WHERE
                id = :id"
            );

            $response = $statement->execute($params);
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to save data in ' . __CLASS__ . $e->getMessage());
        }

        if (!$response) {
            throw new QueryExecutionException('Unable to save data in ' . __CLASS__);
        }

        return $response;
    }

    /**
     * @param int $id
     *
     * @return Coupon
     * @throws QueryExecutionException
     * @throws NotFoundException
     */
    public function getById($id)
    {
        try {
            $statement = $this->connection->prepare(
                "SELECT
                    c.id AS coupon_id,
                    c.code AS coupon_code,
                    c.discount AS coupon_discount,
                    c.deduction AS coupon_deduction,
                    c.limit AS coupon_limit,
                    c.customerLimit AS coupon_customerLimit,
                    c.notificationInterval AS coupon_notificationInterval,
                    c.notificationRecurring AS coupon_notificationRecurring,
                    c.status AS coupon_status,
                    c.expirationDate AS coupon_expirationDate,
                    c.allServices AS coupon_allServices,
                    c.allEvents AS coupon_allEvents,
                    c.allPackages AS coupon_allPackages,
                    s.id AS service_id,
                    s.price AS service_price,
                    s.minCapacity AS service_minCapacity,
                    s.maxCapacity AS service_maxCapacity,
                    s.name AS service_name,
                    s.description AS service_description,
                    s.color AS service_color,
                    s.status AS service_status,
                    s.categoryId AS service_categoryId,
                    s.duration AS service_duration,
                    e.id AS event_id,
                    e.price AS event_price,
                    e.name AS event_name,
                    p.id AS package_id,
                    p.price AS package_price,
                    p.name AS package_name
                FROM {$this->table} c
                LEFT JOIN {$this->couponToServicesTable} cs ON cs.couponId = c.id
                LEFT JOIN {$this->couponToEventsTable} ce ON ce.couponId = c.id
                LEFT JOIN {$this->couponToPackagesTable} cp ON cp.couponId = c.id
                LEFT JOIN {$this->servicesTable} s ON cs.serviceId = s.id
                LEFT JOIN {$this->eventsTable} e ON ce.eventId = e.id
                LEFT JOIN {$this->packagesTable} p ON cp.packageId = p.id
                WHERE c.id = :couponId"
            );

            $statement->bindParam(':couponId', $id);

            $statement->execute();

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find by id in ' . __CLASS__, $e->getCode(), $e);
        }

        if (!$rows) {
            throw new NotFoundException('Data not found in ' . __CLASS__);
        }

        return call_user_func([static::FACTORY, 'createCollection'], $rows)->getItem($id);
    }

    /**
     * @param array $criteria
     * @param int   $itemsPerPage
     *
     * @return Collection
     * @throws QueryExecutionException
     */
    public function getFiltered($criteria, $itemsPerPage)
    {
        try {
            $params = [];

            $where = [];

            if (!empty($criteria['search'])) {
                $params[':search'] = "%{$criteria['search']}%";

                $where[] = 'UPPER(c.code) LIKE UPPER(:search)';
            }

            if (!empty($criteria['ids'])) {
                $queryIds = [];

                foreach ((array)$criteria['ids'] as $index => $value) {
                    $param          = ':id' . $index;
                    $queryIds[]     = $param;
                    $params[$param] = $value;
                }

                $where[] = "c.id IN (" . implode(', ', $queryIds) . ')';
            }

            if (!empty($criteria['services'])) {
                $queryServices = [];

                foreach ((array)$criteria['services'] as $index => $value) {
                    $param           = ':service' . $index;
                    $queryServices[] = $param;
                    $params[$param]  = $value;
                }

                $where[] = "c.id IN (
                    SELECT couponId FROM {$this->couponToServicesTable} 
                    WHERE serviceId IN (" . implode(', ', $queryServices) . ')
                )';
            }

            if (!empty($criteria['events'])) {
                $queryEvents = [];

                foreach ((array)$criteria['events'] as $index => $value) {
                    $param          = ':event' . $index;
                    $queryEvents[]  = $param;
                    $params[$param] = $value;
                }

                $where[] = "c.id IN (
                    SELECT couponId FROM {$this->couponToEventsTable} 
                    WHERE eventId IN (" . implode(', ', $queryEvents) . ')
                )';
            }

            if (!empty($criteria['packages'])) {
                $queryPackages = [];

                foreach ((array)$criteria['packages'] as $index => $value) {
                    $param           = ':package' . $index;
                    $queryPackages[] = $param;
                    $params[$param]  = $value;
                }

                $where[] = "c.id IN (
                    SELECT couponId FROM {$this->couponToPackagesTable} 
                    WHERE packageId IN (" . implode(', ', $queryPackages) . ')
                )';
            }


            $where = $where ? ' WHERE ' . implode(' AND ', $where) : '';

            $limit = $this->getLimit(
                !empty($criteria['page']) ? (int)$criteria['page'] : 0,
                (int)$itemsPerPage
            );

            $statement = $this->connection->prepare(
                "SELECT
                    c.id AS coupon_id,
                    c.code AS coupon_code,
                    c.discount AS coupon_discount,
                    c.deduction AS coupon_deduction,
                    c.limit AS coupon_limit,
                    c.customerLimit AS coupon_customerLimit,
                    c.notificationInterval AS coupon_notificationInterval,
                    c.notificationRecurring AS coupon_notificationRecurring,
                    c.status AS coupon_status,
                    c.expirationDate AS coupon_expirationDate,
                    c.allServices AS coupon_allServices,
                    c.allEvents AS coupon_allEvents,
                    c.allPackages AS coupon_allPackages
                FROM {$this->table} c
                {$where}
                {$limit}"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        return call_user_func([static::FACTORY, 'createCollection'], $rows);
    }

    /**
     * @param array $criteria
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function getCount($criteria)
    {
        try {
            $params = [];

            $where = [];

            if (!empty($criteria['search'])) {
                $params[':search'] = "%{$criteria['search']}%";

                $where[] = 'c.code LIKE :search';
            }

            if (!empty($criteria['services'])) {
                $queryServices = [];

                foreach ((array)$criteria['services'] as $index => $value) {
                    $param           = ':service' . $index;
                    $queryServices[] = $param;
                    $params[$param]  = $value;
                }

                $where[] = "c.id IN (SELECT couponId FROM {$this->couponToServicesTable}
                WHERE serviceId IN (" . implode(', ', $queryServices) . '))';
            }

            $where = $where ? ' WHERE ' . implode(' AND ', $where) : '';

            $statement = $this->connection->prepare(
                "SELECT COUNT(*) AS count
                FROM {$this->table} c
                $where"
            );

            $statement->execute($params);

            $row = $statement->fetch()['count'];
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        return $row;
    }

    /**
     * @param int    $id
     * @param string $status
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
     * @param array $criteria
     *
     * @return Collection
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws InvalidArgumentException
     */
    public function getAllByCriteria($criteria)
    {
        try {
            $params = [];

            $where = [];

            if (isset($criteria['code'])) {
                $where[] = $criteria['couponsCaseInsensitive'] ? 'LOWER(c.code) = LOWER(:code)' : 'c.code = :code';

                $params[':code'] = $criteria['code'];
            }

            if (!empty($criteria['notificationInterval'])) {
                $where[] = 'c.notificationInterval != 0';
            }

            if (!empty($criteria['notExpired'])) {
                $currentDateTime = "STR_TO_DATE('" . DateTimeService::getNowDateTimeInUtc() . "', '%Y-%m-%d %H:%i:%s')";

                $where[] = "(c.expirationDate IS NULL OR c.expirationDate >= {$currentDateTime})";
            }

            if (!empty($criteria['couponIds'])) {
                $couponIdsParams = [];

                foreach ((array)$criteria['couponIds'] as $key => $id) {
                    $couponIdsParams[":id$key"] = $id;
                }

                if ($couponIdsParams) {
                    $where[] = '(c.id IN ( ' . implode(', ', array_keys($couponIdsParams)) . '))';

                    $params = array_merge($params, $couponIdsParams);
                }
            }

            $where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

            $statement = $this->connection->prepare(
                "SELECT
                    c.id AS coupon_id,
                    c.code AS coupon_code,
                    c.discount AS coupon_discount,
                    c.deduction AS coupon_deduction,
                    c.limit AS coupon_limit,
                    c.customerLimit AS coupon_customerLimit,
                    c.notificationInterval AS coupon_notificationInterval,
                    c.notificationRecurring AS coupon_notificationRecurring,
                    c.status AS coupon_status,
                    c.expirationDate AS coupon_expirationDate,
                    c.allServices AS coupon_allServices,
                    c.allEvents AS coupon_allEvents,
                    c.allPackages AS coupon_allPackages
                FROM {$this->table} c
                {$where}"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find by id in ' . __CLASS__, $e->getCode(), $e);
        }

        /** @var Collection $coupons */
        $coupons = call_user_func([static::FACTORY, 'createCollection'], $rows);

        if (!$coupons->length()) {
            return $coupons;
        }

        $params = [];

        foreach ($coupons->keys() as $key => $id) {
            $params[":id$key"] = $id;
        }

        $where = 'WHERE cb.couponId IN ( ' . implode(', ', array_keys($params)) . ')';

        try {
            $statement = $this->connection->prepare(
                "SELECT
                    DISTINCT(cb.couponId) AS couponId,
                    COUNT(*) AS used
                FROM {$this->bookingsTable} cb
                {$where}
                GROUP BY cb.couponId"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find by id in ' . __CLASS__, $e->getCode(), $e);
        }

        foreach ($rows as $row) {
            if ($coupons->keyExists($row['couponId'])) {
                /** @var Coupon $coupon */
                $coupon = $coupons->getItem($row['couponId']);

                $coupon->setUsed(new WholeNumber($row['used']));
            }
        }

        return $coupons;
    }

    /**
     * @param array $couponIds
     * @param array $serviceIds
     *
     * @return array
     *
     * @throws QueryExecutionException
     */
    public function getCouponsServicesIds($couponIds, $serviceIds = [])
    {
        $couponsParams = [];

        $servicesParams = [];

        $where = [];

        if ($couponIds) {
            foreach ($couponIds as $key => $couponId) {
                $couponsParams[":id$key"] = $couponId;
            }

            $where[] = '(couponId IN (' . implode(', ', array_keys($couponsParams)) . '))';
        }

        if ($serviceIds) {
            foreach ($serviceIds as $key => $serviceId) {
                $servicesParams[":serviceId$key"] = $serviceId;
            }

            $where[] = '(serviceId IN (' . implode(', ', array_keys($servicesParams)) . '))';
        }

        $where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        try {
            $statement = $this->connection->prepare(
                "SELECT serviceId, couponId FROM {$this->couponToServicesTable} {$where} GROUP BY serviceId, couponId"
            );

            $statement->execute(array_merge($couponsParams, $servicesParams));

            return $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }
    }

    /**
     * @param array $couponIds
     * @param array $eventIds
     *
     * @return array
     *
     * @throws QueryExecutionException
     */
    public function getCouponsEventsIds($couponIds, $eventIds = [])
    {
        $couponsParams = [];

        $eventsParams = [];

        $where = [];

        if ($couponIds) {
            foreach ($couponIds as $key => $couponId) {
                $couponsParams[":id$key"] = $couponId;
            }

            $where[] = '(couponId IN (' . implode(', ', array_keys($couponsParams)) . '))';
        }

        if ($eventIds) {
            foreach ($eventIds as $key => $eventId) {
                $eventsParams[":eventId$key"] = $eventId;
            }

            $where[] = '(eventId IN (' . implode(', ', array_keys($eventsParams)) . '))';
        }

        $where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        try {
            $statement = $this->connection->prepare(
                "SELECT eventId, couponId FROM {$this->couponToEventsTable} {$where} GROUP BY eventId, couponId"
            );

            $statement->execute(array_merge($couponsParams, $eventsParams));

            return $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }
    }

    /**
     * @param array $couponIds
     * @param array $packageIds
     *
     * @return array
     *
     * @throws QueryExecutionException
     */
    public function getCouponsPackagesIds($couponIds, $packageIds = [])
    {
        $couponsParams = [];

        $packagesParams = [];

        $where = [];

        if ($couponIds) {
            foreach ($couponIds as $key => $couponId) {
                $couponsParams[":id$key"] = $couponId;
            }

            $where[] = '(couponId IN (' . implode(', ', array_keys($couponsParams)) . '))';
        }

        if ($packageIds) {
            foreach ($packageIds as $key => $packageId) {
                $packagesParams[":packageId$key"] = $packageId;
            }

            $where[] = '(packageId IN (' . implode(', ', array_keys($packagesParams)) . '))';
        }

        $where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        try {
            $statement = $this->connection->prepare(
                "SELECT packageId, couponId FROM {$this->couponToPackagesTable} {$where} GROUP BY packageId, couponId"
            );

            $statement->execute(array_merge($couponsParams, $packagesParams));

            return $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }
    }
}
