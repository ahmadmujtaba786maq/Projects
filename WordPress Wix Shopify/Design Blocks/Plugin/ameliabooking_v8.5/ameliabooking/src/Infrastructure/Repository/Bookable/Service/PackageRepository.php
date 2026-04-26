<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Repository\Bookable\Service;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Package;
use AmeliaBooking\Domain\Factory\Bookable\Service\PackageFactory;
use AmeliaBooking\Infrastructure\Connection;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable\PackagesServicesLocationsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable\PackagesServicesProvidersTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable\PackagesServicesTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable\ServicesTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Gallery\GalleriesTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Location\LocationsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\UsersTable;

/**
 * Class PackageRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Service
 */
class PackageRepository extends AbstractRepository
{
    public const FACTORY = PackageFactory::class;

    /**
     * @param Connection $connection
     * @param string     $table
     */
    public function __construct(
        Connection $connection,
        $table
    ) {
        parent::__construct($connection, $table);
    }

    /**
     * @param Package $entity
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function add($entity)
    {
        $data = $entity->toArray();

        $params = [
            ':name'             => $data['name'],
            ':description'      => $data['description'],
            ':color'            => $data['color'],
            ':price'            => $data['price'],
            ':status'           => $data['status'],
            ':pictureFullPath'  => $data['pictureFullPath'],
            ':pictureThumbPath' => $data['pictureThumbPath'],
            ':position'         => $data['position'],
            ':calculatedPrice'  => $data['calculatedPrice'] ? 1 : 0,
            ':discount'         => $data['discount'],
            ':settings'         => $data['settings'],
            ':endDate'          => $data['endDate'],
            ':durationCount'    => $data['durationCount'],
            ':durationType'     => $data['durationType'],
            ':translations'     => $data['translations'],
            ':deposit'          => $data['deposit'],
            ':depositPayment'   => $data['depositPayment'],
            ':fullPayment'      => $data['fullPayment'] ? 1 : 0,
            ':sharedCapacity'   => $data['sharedCapacity'] ? 1 : 0,
            ':quantity'         => $data['quantity'],
            ':limitPerCustomer' => $data['limitPerCustomer']
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO 
                {$this->table} 
                (
                `name`, 
                `description`, 
                `color`, 
                `price`, 
                `status`, 
                `pictureFullPath`,
                `pictureThumbPath`,
                `calculatedPrice`,
                `discount`,
                `position`,
                `settings`,
                `endDate`,
                `durationCount`,
                `durationType`,
                `translations`,
                `deposit`,
                `depositPayment`,
                `fullPayment`,
                `sharedCapacity`,
                `quantity`,
                `limitPerCustomer`
                ) VALUES (
                :name,
                :description,
                :color,
                :price,
                :status,
                :pictureFullPath,
                :pictureThumbPath,
                :calculatedPrice,
                :discount,
                :position,
                :settings,
                :endDate,
                :durationCount,
                :durationType,
                :translations,
                :deposit,
                :depositPayment,
                :fullPayment,
                :sharedCapacity,
                :quantity,
                :limitPerCustomer
                )"
            );

            $result = $statement->execute($params);

            if (!$result) {
                throw new QueryExecutionException('Unable to add data in ' . __CLASS__);
            }

            return $this->connection->lastInsertId();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to add data in ' . __CLASS__, $e->getCode(), $e);
        }
    }

    /**
     * @param int     $packageId
     * @param Package $entity
     *
     * @throws QueryExecutionException
     */
    public function update($packageId, $entity)
    {
        $data = $entity->toArray();

        $params = [
            ':name'             => $data['name'],
            ':description'      => $data['description'],
            ':color'            => $data['color'],
            ':price'            => $data['price'],
            ':status'           => $data['status'],
            ':pictureFullPath'  => $data['pictureFullPath'],
            ':pictureThumbPath' => $data['pictureThumbPath'],
            ':position'         => $data['position'],
            ':calculatedPrice'  => $data['calculatedPrice'] ? 1 : 0,
            ':discount'         => $data['discount'],
            ':settings'         => $data['settings'],
            ':endDate'          => $data['endDate'],
            ':durationCount'    => $data['durationCount'],
            ':durationType'     => $data['durationType'],
            ':translations'     => $data['translations'],
            ':deposit'          => $data['deposit'],
            ':depositPayment'   => $data['depositPayment'],
            ':fullPayment'      => $data['fullPayment'] ? 1 : 0,
            ':sharedCapacity'   => $data['sharedCapacity'] ? 1 : 0,
            ':quantity'         => $data['quantity'],
            ':limitPerCustomer' => $data['limitPerCustomer'],
            ':id'               => $packageId
        ];


        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET
                `name`              = :name,
                `description`       = :description,
                `color`             = :color,
                `price`             = :price,
                `status`            = :status,
                `pictureFullPath`   = :pictureFullPath,
                `pictureThumbPath`  = :pictureThumbPath,
                `position`          = :position,
                `calculatedPrice`   = :calculatedPrice,
                `discount`          = :discount,
                `settings`          = :settings,
                `endDate`           = :endDate,
                `durationCount`     = :durationCount,
                `durationType`      = :durationType,
                `translations`      = :translations,
                `deposit`           = :deposit,
                `depositPayment`    = :depositPayment,
                `fullPayment`       = :fullPayment,
                `sharedCapacity`    = :sharedCapacity,
                `quantity`          = :quantity, 
                `limitPerCustomer`  = :limitPerCustomer    
                WHERE
                id = :id"
            );

            $result = $statement->execute($params);

            if (!$result) {
                throw new QueryExecutionException('Unable to save data in ' . __CLASS__);
            }
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to save data in ' . __CLASS__, $e->getCode(), $e);
        }
    }

    /**
     * @param $criteria
     *
     * @return Collection
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getByCriteria($criteria)
    {
        $params = [];

        $where = [];

        $order = 'ORDER BY p.name, ps.position ASC, ps.id ASC';

        if (isset($criteria['sort'])) {
            if ($criteria['sort'] === '') {
                $order = 'ORDER BY p.position';
            } else {
                $orderColumn = strpos($criteria['sort'], 'name') !== false ? 'p.name' : 'p.price';

                $orderDirection = $criteria['sort'][0] === '-' ? 'DESC' : 'ASC';

                $order = "ORDER BY {$orderColumn} {$orderDirection}";
            }
        }

        if (!empty($criteria['search'])) {
            $params[':search'] = "%{$criteria['search']}%";

            $where[] = 'p.name LIKE :search';
        }

        if (!empty($criteria['services'])) {
            $queryServices = [];

            foreach ((array)$criteria['services'] as $index => $value) {
                $param = ':service' . $index;

                $queryServices[] = $param;

                $params[$param] = $value;
            }

            $where[] = 's.id IN (' . implode(', ', $queryServices) . ')';
        }

        if (!empty($criteria['packages'])) {
            $queryPackages = [];

            foreach ((array)$criteria['packages'] as $index => $value) {
                $param           = ':package' . $index;
                $queryPackages[] = $param;
                $params[$param]  = $value;
            }

            $where[] = 'p.id IN (' . implode(', ', $queryPackages) . ')';
        }

        if (!empty($criteria['status'])) {
            $params[':status'] = $criteria['status'];

            $where[] = 's.status = :status';
        }

        $where = $where ? ' AND ' . implode(' AND ', $where) : '';

        $servicesTable = ServicesTable::getTableName();

        $usersTable = UsersTable::getTableName();

        $locationsTable = LocationsTable::getTableName();

        $packageServicesTable = PackagesServicesTable::getTableName();

        $packageServicesProvidersTable = PackagesServicesProvidersTable::getTableName();
        $packageServicesLocationsTable = PackagesServicesLocationsTable::getTableName();
        $galleriesTable = GalleriesTable::getTableName();

        try {
            $statement = $this->connection->prepare(
                "SELECT
                p.id AS package_id,
                p.name AS package_name,
                p.description AS package_description,
                p.color AS package_color,
                p.price AS package_price,
                p.status AS package_status,
                p.pictureFullPath AS package_picture_full,
                p.pictureThumbPath AS package_picture_thumb,
                p.calculatedPrice AS package_calculated_price,
                p.discount AS package_discount,
                p.position AS package_position,
                p.settings AS package_settings,
                p.endDate AS package_endDate,
                p.durationCount AS package_durationCount,
                p.durationType AS package_durationType,
                p.translations AS package_translations,
                p.deposit AS package_deposit,
                p.depositPayment AS package_depositPayment,
                p.fullPayment AS package_fullPayment,
                p.sharedCapacity AS package_sharedCapacity,
                p.quantity AS package_quantity,
                p.limitPerCustomer AS package_limitPerCustomer,
                
                ps.id AS package_service_id,
                ps.quantity AS package_service_quantity,
                ps.minimumScheduled AS package_service_minimumScheduled,
                ps.maximumScheduled AS package_service_maximumScheduled,
                ps.allowProviderSelection AS package_service_allowProviderSelection,
                ps.position AS package_service_position,
                                
                s.id AS service_id,
                s.price AS service_price,
                s.minCapacity AS service_minCapacity,
                s.maxCapacity AS service_maxCapacity,
                s.name AS service_name,
                s.description AS service_description,
                s.status AS service_status,
                s.categoryId AS service_categoryId,
                s.duration AS service_duration,
                s.timeBefore AS service_timeBefore,
                s.timeAfter AS service_timeAfter,
                s.pictureFullPath AS service_picture_full,
                s.pictureThumbPath AS service_picture_thumb,
                s.translations AS service_translations,
                s.show AS service_show,
                
                l.id AS location_id,
                l.name AS location_name,
                l.address AS location_address,
                l.phone AS location_phone,
                l.latitude AS location_latitude,
                l.longitude AS location_longitude,

                pu.id AS provider_id,
                pu.firstName AS provider_firstName,
                pu.lastName AS provider_lastName,
                pu.email AS provider_email,
                pu.status AS provider_status,
                pu.translations AS provider_translations,

                g.id AS gallery_id,
                g.pictureFullPath AS gallery_picture_full,
                g.pictureThumbPath AS gallery_picture_thumb,
                g.position AS gallery_position
                
                FROM {$this->table} p
                LEFT JOIN {$packageServicesTable} ps ON ps.packageId = p.id
                LEFT JOIN {$servicesTable} s ON ps.serviceId = s.id
                LEFT JOIN {$packageServicesProvidersTable} psp ON psp.packageServiceId = ps.id
                LEFT JOIN {$packageServicesLocationsTable} psl ON psl.packageServiceId = ps.id
                LEFT JOIN {$usersTable} pu ON pu.id = psp.userId
                LEFT JOIN {$locationsTable} l ON l.id = psl.locationId
                LEFT JOIN {$galleriesTable} g ON g.entityId = p.id AND g.entityType = 'package'
                WHERE 1 = 1 {$where}
                {$order}"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find by id in ' . __CLASS__, $e->getCode(), $e);
        }

        return call_user_func([static::FACTORY, 'createCollection'], $rows);
    }

    /**
     * @param $id
     *
     * @return Collection
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getById($id)
    {
        $params[':id'] = $id;

        $servicesTable = ServicesTable::getTableName();

        $usersTable = UsersTable::getTableName();

        $locationsTable = LocationsTable::getTableName();

        $packageServicesTable = PackagesServicesTable::getTableName();

        $packageServicesProvidersTable = PackagesServicesProvidersTable::getTableName();

        $packageServicesLocationsTable = PackagesServicesLocationsTable::getTableName();

        $galleriesTable = GalleriesTable::getTableName();

        try {
            $statement = $this->connection->prepare(
                "SELECT
                p.id AS package_id,
                p.name AS package_name,
                p.description AS package_description,
                p.color AS package_color,
                p.price AS package_price,
                p.status AS package_status,
                p.pictureFullPath AS package_picture_full,
                p.pictureThumbPath AS package_picture_thumb,
                p.calculatedPrice AS package_calculated_price,
                p.discount AS package_discount,
                p.position AS package_position,
                p.settings AS package_settings,
                p.endDate AS package_endDate,
                p.durationCount AS package_durationCount,
                p.durationType AS package_durationType,
                p.translations AS package_translations,
                p.deposit AS package_deposit,
                p.depositPayment AS package_depositPayment,
                p.fullPayment AS package_fullPayment,
                p.sharedCapacity AS package_sharedCapacity,
                p.quantity AS package_quantity,
                p.limitPerCustomer AS package_limitPerCustomer,
                
                ps.id AS package_service_id,
                ps.quantity AS package_service_quantity,
                ps.minimumScheduled AS package_service_minimumScheduled,
                ps.maximumScheduled AS package_service_maximumScheduled,
                ps.allowProviderSelection AS package_service_allowProviderSelection,
                ps.position AS package_service_position,
                                
                s.id AS service_id,
                s.price AS service_price,
                s.minCapacity AS service_minCapacity,
                s.maxCapacity AS service_maxCapacity,
                s.name AS service_name,
                s.status AS service_status,
                s.categoryId AS service_categoryId,
                s.duration AS service_duration,
                s.timeBefore AS service_timeBefore,
                s.timeAfter AS service_timeAfter,
                s.pictureFullPath AS service_picture_full,
                s.pictureThumbPath AS service_picture_thumb,
                s.show AS service_show,
                
                l.id AS location_id,
                l.name AS location_name,
                l.address AS location_address,
                l.phone AS location_phone,
                l.latitude AS location_latitude,
                l.longitude AS location_longitude,

                pu.id AS provider_id,
                pu.firstName AS provider_firstName,
                pu.lastName AS provider_lastName,
                pu.email AS provider_email,
                pu.translations AS provider_translations,
                pu.stripeConnect AS provider_stripeConnect,
                                
                g.id AS gallery_id,
                g.pictureFullPath AS gallery_picture_full,
                g.pictureThumbPath AS gallery_picture_thumb,
                g.position AS gallery_position
                
                FROM {$this->table} p
                LEFT JOIN {$packageServicesTable} ps ON ps.packageId = p.id
                LEFT JOIN {$servicesTable} s ON ps.serviceId = s.id
                LEFT JOIN {$packageServicesProvidersTable} psp ON psp.packageServiceId = ps.id
                LEFT JOIN {$packageServicesLocationsTable} psl ON psl.packageServiceId = ps.id
                LEFT JOIN {$usersTable} pu ON pu.id = psp.userId
                LEFT JOIN {$locationsTable} l ON l.id = psl.locationId
                LEFT JOIN {$galleriesTable} g ON g.entityId = p.id AND g.entityType = 'package'
                WHERE p.id = :id"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find by id in ' . __CLASS__, $e->getCode(), $e);
        }

        return call_user_func([static::FACTORY, 'createCollection'], $rows)->getItem($id);
    }

    /**
     * @param $serviceId
     * @param $status
     *
     * @throws QueryExecutionException
     */
    public function updateStatusById($serviceId, $status)
    {
        $params = [
            ':id'     => $serviceId,
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
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to save data in ' . __CLASS__, $e->getCode(), $e);
        }
    }
}
