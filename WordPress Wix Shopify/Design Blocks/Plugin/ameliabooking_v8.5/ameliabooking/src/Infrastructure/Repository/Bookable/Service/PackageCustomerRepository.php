<?php

namespace AmeliaBooking\Infrastructure\Repository\Bookable\Service;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Bookable\Service\Package;
use AmeliaBooking\Domain\Entity\Bookable\Service\PackageCustomer;
use AmeliaBooking\Domain\Factory\Bookable\Service\PackageCustomerFactory;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable\PackagesCustomersServicesTable;
use AmeliaBooking\Infrastructure\Connection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\CustomerBookingsTable;

/**
 * Class PackageCustomerRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Bookable\Service
 */
class PackageCustomerRepository extends AbstractRepository
{
    public const FACTORY = PackageCustomerFactory::class;

    /** @var string */
    protected $packagesCustomersServicesTable;

    /**
     * @param Connection $connection
     * @param string     $table
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        Connection $connection,
        $table
    ) {
        parent::__construct($connection, $table);

        $this->packagesCustomersServicesTable = PackagesCustomersServicesTable::getTableName();
    }

    /**
     * @param PackageCustomer $entity
     *
     * @return int
     * @throws QueryExecutionException
     */
    public function add($entity)
    {
        $data = $entity->toArray();

        $params = [
            ':packageId'        => $data['packageId'],
            ':customerId'       => $data['customerId'],
            ':price'            => $data['price'],
            ':tax'              => !empty($data['tax']) ? json_encode($data['tax']) : null,
            ':start'            => $data['start'],
            ':end'              => $data['end'],
            ':purchased'        => $data['purchased'],
            ':bookingsCount'    => $data['bookingsCount'],
            ':couponId'         => $data['couponId'],
            ':token'            => $data['token'] ?: null,
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO {$this->table}
                (`packageId`, `customerId`, `price`, `tax`, `start`, `end`, `purchased`, `status`, `bookingsCount`, `couponId`, `token`)
                VALUES
                (:packageId, :customerId, :price, :tax, :start, :end, :purchased, 'approved', :bookingsCount, :couponId, :token)"
            );

            $res = $statement->execute($params);

            if (!$res) {
                throw new QueryExecutionException('Unable to add data in ' . __CLASS__);
            }
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to add data in ' . __CLASS__, $e->getCode(), $e);
        }

        return $this->connection->lastInsertId();
    }


    /**
     * @param Package $package
     * @param int $customerId
     * @param array $limitPerCustomer
     * @param boolean $packageSpecific
     * @return int
     * @throws QueryExecutionException
     */
    public function getUserPackageCount($package, $customerId, $limitPerCustomer, $packageSpecific)
    {
        $params = [
            ':customerId' => $customerId
        ];

        $startDate = DateTimeService::getNowDateTimeObject()->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i');

        $intervalString = "interval " . $limitPerCustomer['period'] . " " . $limitPerCustomer['timeFrame'];

        $where = "(STR_TO_DATE('" . $startDate . "', '%Y-%m-%d %H:%i:%s') BETWEEN " .
            "(pc.purchased - " . $intervalString . " + interval 1 second) AND " .
            "(pc.purchased + " . $intervalString . " - interval 1 second))";  //+ interval 2 day

        if ($packageSpecific) {
            $where .= " AND pc.packageId = :packageId";
            $params[':packageId'] = $package->getId()->getValue();
        }

        try {
            $statement = $this->connection->prepare(
                "SELECT COUNT(DISTINCT pc.id) AS count
                    FROM {$this->table} pc
                    WHERE pc.customerId = :customerId AND {$where} AND pc.status = 'approved'
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
     * @param array $criteria
     *
     * @return Collection
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getFiltered($criteria)
    {
        $params = [];

        $where = [];

        if (!empty($criteria['customerId'])) {
            $params[':customerId'] = $criteria['customerId'];

            $where[] = 'pc.customerId = :customerId';
        }

        if (array_key_exists('bookingStatus', $criteria)) {
            $where[] = 'pc.status = :bookingStatus';
            $params[':bookingStatus'] = $criteria['bookingStatus'];
        }

        if (!empty($criteria['packages'])) {
            $queryPackages = [];

            foreach ($criteria['packages'] as $index => $value) {
                $param = ':package' . $index;

                $queryPackages[] = $param;

                $params[$param] = $value;
            }

            $where[] = 'pc.packageId IN (' . implode(', ', $queryPackages) . ')';
        }

        if (isset($criteria['couponId'])) {
            $where[] = "pc.couponId = {$criteria['couponId']}";
        }

        $where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        try {
            $statement = $this->connection->prepare(
                "SELECT 
                    pc.id AS id
                FROM {$this->table} pc
                {$where}"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find by id in ' . __CLASS__, $e->getCode(), $e);
        }

        $entities = new Collection();

        foreach ($rows as $row) {
            $entities->addItem(
                call_user_func([static::FACTORY, 'create'], $row),
                $row['id']
            );
        }

        return $entities;
    }

    /**
     * @return array
     * @throws QueryExecutionException
     */
    public function getIds($criteria = [])
    {
        $bookingsTable = CustomerBookingsTable::getTableName();

        $where = [];

        $params = [];

        if (!empty($criteria['purchased'])) {
            $where[] = "(pc.purchased BETWEEN :purchasedFrom AND :purchasedTo)";

            $params[':purchasedFrom'] = DateTimeService::getCustomDateTimeInUtc($criteria['purchased'][0]);

            $params[':purchasedTo'] = DateTimeService::getCustomDateTimeInUtc($criteria['purchased'][1]);
        }

        if (!empty($criteria['packages'])) {
            $queryServices = [];

            foreach ($criteria['packages'] as $index => $value) {
                $param = ':package' . $index;

                $queryServices[] = $param;

                $params[$param] = $value;
            }

            $where[] = 'pc.packageId IN (' . implode(', ', $queryServices) . ')';
        }

        if (!empty($criteria['packageStatus'])) {
            switch ($criteria['packageStatus']) {
                case 'expired':
                    $where[] = "(pc.end IS NOT NULL && pc.end < NOW())";
                    break;
                case 'approved':
                    $where[] = "(pc.end > NOW() OR pc.end IS NULL)";
                    $where[] = "(pc.status = :packageStatus)";
                    $params[':packageStatus'] = $criteria['packageStatus'];
                    break;
                case 'canceled':
                    $where[] = "(pc.status = :packageStatus)";
                    $params[':packageStatus'] = $criteria['packageStatus'];
                    break;
                default:
                    break;
            }
        }

        if (!empty($criteria['customerId'])) {
            $params[':customerId'] = $criteria['customerId'];

            $where[] = 'pc.customerId = :customerId';
        }

        $limit = $this->getLimit(
            !empty($criteria['page']) ? (int)$criteria['page'] : 0,
            !empty($criteria['itemsPerPage']) ? (int)$criteria['itemsPerPage'] : 0
        );

        $where = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        try {
            $statement = $this->connection->prepare(
                "SELECT 
                    pc.id AS id,
                    pc.packageId AS package_customer_packageId,
                    pc.purchased AS package_customer_purchased,
                    pc.end AS package_customer_end,
                    pc.status AS package_customer_status,
                    pc.customerId AS package_customer_customerId,
                    pc.bookingsCount AS package_customer_bookingsCount,
                    
                    pcs.id AS package_customer_service_id,
                    pcs.packageCustomerId AS package_customer_customerId,
                    pcs.bookingsCount AS service_bookingsCount
                FROM {$this->table} pc
                INNER JOIN {$this->packagesCustomersServicesTable} AS pcs ON pc.id = pcs.packageCustomerId
                LEFT JOIN $bookingsTable cb ON pcs.id = cb.packageCustomerServiceId
                {$where}
                GROUP BY pc.id
                {$limit}"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        return array_map('intval', array_column($rows, 'id'));
    }

    /**
     * @param array $criteria
     * @return int
     * @throws QueryExecutionException
     */
    public function getPackagePurchasedCount($criteria = [])
    {
        $params = [];

        $where = [];

        if (!empty($criteria['purchased'])) {
            $where[] = "(pc.purchased BETWEEN :purchasedFrom AND :purchasedTo)";

            $params[':purchasedFrom'] = DateTimeService::getCustomDateTimeInUtc($criteria['purchased'][0]);

            $params[':purchasedTo'] = DateTimeService::getCustomDateTimeInUtc($criteria['purchased'][1]);
        }

        if (!empty($criteria['customerId'])) {
            $params[':customerId'] = $criteria['customerId'];

            $where[] = 'pc.customerId = :customerId';
        }

        if (!empty($criteria['packages'])) {
            $queryServices = [];

            foreach ($criteria['packages'] as $index => $value) {
                $param = ':package' . $index;

                $queryServices[] = $param;

                $params[$param] = $value;
            }

            $where[] = 'pc.packageId IN (' . implode(', ', $queryServices) . ')';
        }

        if (!empty($criteria['packageStatus'])) {
            switch ($criteria['packageStatus']) {
                case 'expired':
                    $where[] = "(pc.end IS NOT NULL && pc.end < NOW())";
                    break;
                case 'approved':
                    $where[] = "(pc.end > NOW() OR pc.end IS NULL)";
                    $where[] = "(pc.status = :packageStatus)";
                    $params[':packageStatus'] = $criteria['packageStatus'];
                    break;
                case 'canceled':
                    $where[] = "(pc.status = :packageStatus)";
                    $params[':packageStatus'] = $criteria['packageStatus'];
                    break;
                default:
                    break;
            }
        }

        $where = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        try {
            $statement = $this->connection->prepare(
                "SELECT
                    pc.id AS id,
                    pc.packageId AS package_customer_packageId,
                    pc.purchased AS package_customer_purchased,
                    pc.end AS package_customer_end,
                    pc.status AS package_customer_status,
                    pc.customerId AS package_customer_customerId,
                    COUNT(DISTINCT pc.id) AS count,
                    
                    pcs.id AS package_customer_service_id,
                    pcs.packageCustomerId AS package_customer_customerId
                FROM {$this->table} pc
                INNER JOIN {$this->packagesCustomersServicesTable} AS pcs ON pc.id = pcs.packageCustomerId
                {$where}"
            );

            $statement->execute($params);

            $rows = $statement->fetch()['count'];
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find by id in ' . __CLASS__, $e->getCode(), $e);
        }

        return $rows;
    }
}
