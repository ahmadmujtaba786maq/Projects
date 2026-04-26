<?php

namespace AmeliaBooking\Infrastructure\Repository\Location;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Location\Location;
use AmeliaBooking\Domain\Factory\Bookable\Service\ServiceFactory;
use AmeliaBooking\Domain\Repository\Location\LocationRepositoryInterface;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\ValueObjects\String\Status;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Connection;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;
use AmeliaBooking\Domain\Factory\Location\LocationFactory;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable\ServicesTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\AppointmentsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider\ProvidersLocationTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider\ProvidersServiceTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\UsersTable;

/**
 * Class LocationRepositoryInterface
 *
 * @package AmeliaBooking\Infrastructure\Repository
 */
class LocationRepository extends AbstractRepository implements LocationRepositoryInterface
{
    public const FACTORY         = LocationFactory::class;
    public const SERVICE_FACTORY = ServiceFactory::class;

    /** @var string */
    protected $providerServicesTable;

    /** @var string */
    protected $providerLocationTable;

    /** @var string */
    protected $servicesTable;

    /** @var string */
    protected $locationViewsTable;

    /**
     * @param Connection $connection
     * @param string     $table
     * @param string     $providerLocationTable
     * @param string     $providerServicesTable
     * @param string     $servicesTable
     * @param            $locationViewsTable
     */
    public function __construct(
        Connection $connection,
        $table,
        $providerLocationTable,
        $providerServicesTable,
        $servicesTable,
        $locationViewsTable
    ) {
        parent::__construct($connection, $table);

        $this->providerServicesTable = $providerServicesTable;
        $this->providerLocationTable = $providerLocationTable;
        $this->servicesTable         = $servicesTable;
        $this->locationViewsTable    = $locationViewsTable;
    }

    /**
     * @param Location $location
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function add($location)
    {
        $data = $location->toArray();

        $params = [
            ':status'           => $data['status'],
            ':name'             => $data['name'],
            ':description'      => $data['description'],
            ':address'          => $data['address'],
            ':phone'            => $data['phone'],
            ':latitude'         => $data['latitude'],
            ':longitude'        => $data['longitude'],
            ':pictureFullPath'  => $data['pictureFullPath'],
            ':pictureThumbPath' => $data['pictureThumbPath'],
            ':pin'              => $data['pin'],
            ':translations'     => $data['translations']
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO {$this->table}
                (
                `status`,
                `name`,
                `description`,
                `address`,
                `phone`,
                `latitude`,
                `longitude`,
                `pictureFullPath`,
                `pictureThumbPath`,
                `pin`,
                `translations`
                )
                 VALUES (
                 :status,
                 :name,
                 :description,
                 :address,
                 :phone,
                 :latitude,
                 :longitude,
                 :pictureFullPath,
                 :pictureThumbPath,
                 :pin,
                 :translations
                 )"
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
     * @param int      $id
     * @param Location $location
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function update($id, $location)
    {
        $data = $location->toArray();

        $params = [
            ':status'           => $data['status'],
            ':name'             => $data['name'],
            ':description'      => $data['description'],
            ':address'          => $data['address'],
            ':phone'            => $data['phone'],
            ':latitude'         => $data['latitude'],
            ':longitude'        => $data['longitude'],
            ':pictureFullPath'  => $data['pictureFullPath'],
            ':pictureThumbPath' => $data['pictureThumbPath'],
            ':pin'              => $data['pin'],
            ':translations'     => $data['translations'],
            ':id'               => $id
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET `status` = :status, `name` = :name, `description` = :description, `address` = :address,
                `phone` = :phone, `latitude` = :latitude, `longitude` = :longitude,
                `pictureFullPath` = :pictureFullPath, `pictureThumbPath` = :pictureThumbPath,
                `pin` = :pin, `translations` = :translations
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
     * @return Collection
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function getAllOrderedByName()
    {
        try {
            $statement = $this->connection->query(
                "SELECT * FROM {$this->table} ORDER BY name"
            );

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        $items = new Collection();
        foreach ($rows as $row) {
            $items->addItem(call_user_func([static::FACTORY, 'create'], $row), $row['id']);
        }

        return $items;
    }

    /**
     * @param array $criteria
     * @param int   $itemsPerPage
     *
     * @return Collection
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function getFiltered($criteria, $itemsPerPage)
    {
        $params = [];

        $order = '';
        if (!empty($criteria['sort'])) {
            $orderColumn    = $criteria['sort'][0] === '-' ? substr($criteria['sort'], 1) : $criteria['sort'];
            $orderDirection = $criteria['sort'][0] === '-' ? 'DESC' : 'ASC';
            $order          = "ORDER BY {$orderColumn} {$orderDirection}";
        }

        $search = '';
        if (!empty($criteria['search'])) {
            $params[':search1'] = $params[':search2'] = "%{$criteria['search']}%";

            $search = ' AND (l.name LIKE :search1 OR l.address LIKE :search2)';
        }

        $services = '';
        if (!empty($criteria['services'])) {
            foreach ((array)$criteria['services'] as $index => $value) {
                ++$index;
                $services .= ':service' . $index . ', ';
                $params[':service' . $index] = $value;
            }

            $services = ' AND s.id IN (' . rtrim($services, ', ') . ')';
        }

        $limit = $this->getLimit(
            !empty($criteria['page']) ? (int)$criteria['page'] : 0,
            (int)$itemsPerPage
        );

        try {
            $statement = $this->connection->prepare(
                "SELECT 
                  l.id,
                  l.status,
                  l.name,
                  l.description,
                  l.address,
                  l.phone,
                  l.latitude,
                  l.longitude,
                  l.pictureFullPath,
                  l.pictureThumbPath,
                  l.pin,
                  l.translations
                FROM {$this->table} l
                LEFT JOIN {$this->providerLocationTable} pl ON pl.locationId = l.id
                LEFT JOIN {$this->providerServicesTable}  ps ON ps.userId = pl.userId
                LEFT JOIN {$this->servicesTable} s ON s.id = ps.serviceId
                WHERE 1 = 1 $search $services
                GROUP BY l.id
                {$order}
                {$limit}"
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
     * @param $criteria
     *
     * @return array
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function getCount($criteria)
    {
        $providerLocationTable = ProvidersLocationTable::getTableName();
        $providerServicesTable = ProvidersServiceTable::getTableName();
        $servicesTable         = ServicesTable::getTableName();

        $params = [];

        $search = '';
        if (!empty($criteria['search'])) {
            $params[':search1'] = $params[':search2'] = "%{$criteria['search']}%";

            $search = ' AND (l.name LIKE :search1 OR l.address LIKE :search2)';
        }

        $services = '';
        if (!empty($criteria['services'])) {
            foreach ((array)$criteria['services'] as $index => $value) {
                ++$index;
                $services .= ':service' . $index . ', ';
                $params[':service' . $index] = $value;
            }

            $services = ' AND s.id IN (' . rtrim($services, ', ') . ')';
        }


        try {
            $statement = $this->connection->prepare(
                "SELECT COUNT(*) as count
                FROM (
                    SELECT l.id 
                    FROM {$this->table} l
                    LEFT JOIN {$providerLocationTable} pl ON pl.locationId = l.id
                    LEFT JOIN {$providerServicesTable}  ps ON ps.userId = pl.userId
                    LEFT JOIN {$servicesTable} s ON s.id = ps.serviceId
                    WHERE l.status IN (:visibleStatus, :hiddenStatus) $search $services
                    GROUP BY l.id
                ) as t"
            );

            $params[':visibleStatus'] = Status::VISIBLE;
            $params[':hiddenStatus']  = Status::HIDDEN;

            $statement->execute($params);

            $rows = $statement->fetch()['count'];
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        return $rows;
    }

    /**
     * @param $id
     *
     * @return Collection
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function getServicesById($id)
    {
        $params = [
            ':id' => $id
        ];

        try {
            $statement = $this->connection->prepare(
                "
              SELECT s.*
              FROM {$this->table} l
              INNER JOIN {$this->providerLocationTable} pl ON pl.locationId = l.id
              INNER JOIN {$this->providerServicesTable} ps ON ps.userId = pl.userId
              INNER JOIN {$this->servicesTable} s ON s.id = ps.serviceId
              WHERE l.id = :id
              GROUP BY s.id"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        $items = [];
        foreach ($rows as $row) {
            $items[] = call_user_func([static::SERVICE_FACTORY, 'create'], $row);
        }

        return new Collection($items);
    }

    /**
     * Return an array of locations with the number of appointments for the given date period.
     * Keys of the array are Locations IDs.
     *
     * @param $criteria
     *
     * @return array
     * @throws QueryExecutionException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     */
    public function getAllNumberOfAppointments($criteria)
    {
        $userTable        = UsersTable::getTableName();
        $appointmentTable = AppointmentsTable::getTableName();

        $params = [];
        $where  = [];

        if ($criteria['dates']) {
            $where[] = "(a.bookingStart BETWEEN :bookingFrom AND :bookingTo)";
            $params[':bookingFrom'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][0]);
            $params[':bookingTo']   = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][1]);
        }

        if (isset($criteria['status'])) {
            $where[]           = 'l.status = :status';
            $params[':status'] = $criteria['status'];
        }

        $where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        try {
            $statement = $this->connection->prepare(
                "SELECT
                l.id,
                l.name,
                COUNT(l.id) AS appointments
            FROM {$this->table} l
            INNER JOIN {$this->providerLocationTable} pl ON pl.locationId = l.id
            INNER JOIN {$userTable} u ON u.id = pl.userId
            INNER JOIN {$appointmentTable} a ON u.id = a.providerId
            $where
            GROUP BY l.id"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        $result = [];

        foreach ($rows as $row) {
            $result[$row['id']] = $row;
        }

        return $result;
    }

    /**
     * Return an array of locations with the number of views for the given date period.
     * Keys of the array are Locations IDs.
     *
     * @param $criteria
     *
     * @return array
     * @throws QueryExecutionException
     */
    public function getAllNumberOfViews($criteria)
    {
        $params = [];

        $where = [];

        if ($criteria['dates']) {
            $where[] = "(lv.date BETWEEN :bookingFrom AND :bookingTo)";

            $params[':bookingFrom'] = explode(' ', $criteria['dates'][0])[0];

            $params[':bookingTo'] = explode(' ', $criteria['dates'][1])[0];
        }

        if (isset($criteria['status'])) {
            $where[] = 'l.status = :status';

            $params[':status'] = $criteria['status'];
        }

        $where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        try {
            $statement = $this->connection->prepare(
                "SELECT
            l.id,
            l.name,
            SUM(lv.views) AS views
            FROM {$this->table} l
            INNER JOIN {$this->locationViewsTable} lv ON lv.locationId = l.id 
            $where
            GROUP BY l.id"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        $result = [];

        foreach ($rows as $row) {
            $result[$row['id']] = $row;
        }

        return $result;
    }

    /**
     * @param $locationId
     *
     * @return string
     * @throws QueryExecutionException
     */
    public function addViewStats($locationId)
    {
        $date = DateTimeService::getNowDate();

        $params = [
            ':locationId' => $locationId,
            ':date'       => $date,
            ':views'      => 1
        ];

        try {
            // Check if there is already data for this provider for this date
            $statement = $this->connection->prepare(
                "SELECT COUNT(*) AS count 
                FROM {$this->locationViewsTable} AS pv 
                WHERE pv.locationId = :locationId 
                AND pv.date = :date"
            );

            $statement->bindParam(':locationId', $locationId);
            $statement->bindParam(':date', $date);
            $statement->execute();
            $count = $statement->fetch()['count'];

            if (!$count) {
                $statement = $this->connection->prepare(
                    "INSERT INTO {$this->locationViewsTable}
                    (`locationId`, `date`, `views`)
                    VALUES 
                    (:locationId, :date, :views)"
                );
            } else {
                $statement = $this->connection->prepare(
                    "UPDATE {$this->locationViewsTable} pv SET pv.views = pv.views + :views
                    WHERE pv.locationId = :locationId
                    AND pv.date = :date"
                );
            }

            $response = $statement->execute($params);
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to add data in ' . __CLASS__, $e->getCode(), $e);
        }

        if (!$response) {
            throw new QueryExecutionException('Unable to add data in ' . __CLASS__);
        }

        return true;
    }

    /**
     * @param int $locationId
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function deleteViewStats($locationId)
    {
        $params = [
            ':locationId'  => $locationId,
        ];

        try {
            $statement = $this->connection->prepare(
                "DELETE FROM {$this->locationViewsTable} WHERE locationId = :locationId"
            );

            return $statement->execute($params);
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to delete data from ' . __CLASS__, $e->getCode(), $e);
        }
    }
}
