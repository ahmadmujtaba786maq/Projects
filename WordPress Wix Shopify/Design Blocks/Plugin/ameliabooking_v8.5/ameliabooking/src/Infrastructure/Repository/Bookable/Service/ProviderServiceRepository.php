<?php

namespace AmeliaBooking\Infrastructure\Repository\Bookable\Service;

use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Factory\Bookable\Service\ServiceFactory;
use AmeliaBooking\Infrastructure\Licence;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;

/**
 * Class ProviderServiceRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Bookable\Service
 */
class ProviderServiceRepository extends AbstractRepository
{
    public const FACTORY = ServiceFactory::class;

    /**
     * @param Service $entity
     * @param int     $userId
     *
     * @return int
     * @throws QueryExecutionException
     */
    public function add($entity, $userId)
    {
        $data = $entity->toArray();

        $params = [
            ':userId'        => $userId,
            ':serviceId'     => $data['id'],
            ':minCapacity'   => $data['minCapacity'],
            ':maxCapacity'   => $data['maxCapacity'],
            ':price'         => $data['price'],
        ];

        $additionalData = Licence\DataModifier::getProviderServiceRepositoryData($data);

        $params = array_merge($params, $additionalData['values']);

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO {$this->table}
                (
                 {$additionalData['columns']}
                 `userId`,
                 `serviceId`,
                 `minCapacity`,
                 `maxCapacity`,
                 `price`
                 )
                VALUES
                (
                 {$additionalData['placeholders']}
                 :userId,
                 :serviceId,
                 :minCapacity,
                 :maxCapacity,
                 :price
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
     * @param Service $entity
     * @param int     $id
     *
     * @return int
     * @throws QueryExecutionException
     */
    public function update($entity, $id)
    {
        $data = $entity->toArray();

        $params = [
            ':id'            => $id,
            ':minCapacity'   => $data['minCapacity'],
            ':maxCapacity'   => $data['maxCapacity'],
            ':price'         => $data['price'],
        ];

        $additionalData = Licence\DataModifier::getProviderServiceRepositoryData($data);

        $params = array_merge($params, $additionalData['values']);

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET
                {$additionalData['columnsPlaceholders']}
                `minCapacity` = :minCapacity,
                `maxCapacity` = :maxCapacity,
                `price` = :price
                WHERE id = :id"
            );

            $res = $statement->execute($params);
            if (!$res) {
                throw new QueryExecutionException('Unable to save data in ' . __CLASS__);
            }
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to save data in ' . __CLASS__, $e->getCode(), $e);
        }

        return $this->connection->lastInsertId();
    }

    /**
     * @param int    $id
     * @param string $type
     *
     * @return array
     * @throws QueryExecutionException
     */
    public function getAllForEntity($id, $type)
    {
        $columnName = '';

        switch ($type) {
            case (Entities::EMPLOYEE):
                $columnName = 'userId';

                break;

            case (Entities::SERVICE):
                $columnName = 'serviceId';

                break;
        }

        try {
            $statement = $this->connection->prepare(
                "SELECT
                ps.id,
                ps.userId,
                ps.serviceId,
                ps.minCapacity,
                ps.maxCapacity,
                ps.price,
                ps.customPricing
              FROM {$this->table} ps 
              WHERE ps.{$columnName} = :entityId"
            );

            $params = array(
                ':entityId' => $id
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();

            foreach ($rows as &$row) {
                $row['id']          = (int)$row['id'];
                $row['userId']      = (int)$row['userId'];
                $row['serviceId']   = (int)$row['serviceId'];
                $row['minCapacity'] = (int)$row['minCapacity'];
                $row['maxCapacity'] = (int)$row['maxCapacity'];
            }

            return $rows;
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find by id in ' . __CLASS__, $e->getCode(), $e);
        }
    }

    /**
     *
     * It will delete all relations for one service except ones that are sent in providers array
     *
     * @param array $providersIds
     * @param int   $serviceId
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function deleteAllNotInProvidersArrayForService($providersIds, $serviceId)
    {
        $providers = ' ';

        if (!empty($providersIds)) {
            foreach ($providersIds as $index => $value) {
                ++$index;
                $providers .= ':providerId' . $index . ', ';
                $params[':providerId' . $index] = (int)$value;
            }
            $providers = 'AND `userId` NOT IN (' . rtrim($providers, ', ') . ')';
        }

        $params[':serviceId'] = $serviceId;

        try {
            $statement = $this->connection->prepare(
                "DELETE FROM {$this->table} WHERE 1 = 1 $providers AND serviceId = :serviceId"
            );

            return $statement->execute($params);
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to delete data from ' . __CLASS__, $e->getCode(), $e);
        }
    }

    /**
     *
     * It will delete all relations for one service except ones that are sent in providers array
     *
     * @param array $servicesIds
     * @param int   $providerId
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function deleteAllNotInServicesArrayForProvider($servicesIds, $providerId)
    {
        $services = ' ';

        if (!empty($servicesIds)) {
            foreach ($servicesIds as $index => $value) {
                ++$index;
                $services .= ':serviceId' . $index . ', ';
                $params[':serviceId' . $index] = $value;
            }
            $services = 'AND `serviceId` NOT IN (' . rtrim($services, ', ') . ')';
        }

        $params[':providerId'] = $providerId;

        try {
            $statement = $this->connection->prepare(
                "DELETE FROM {$this->table} WHERE 1 = 1 $services AND userId = :providerId"
            );

            return $statement->execute($params);
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to delete data from ' . __CLASS__, $e->getCode(), $e);
        }
    }

    /**
     * @param int $entityId
     * @param int $entityType
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function deleteDuplicated($entityId, $entityType)
    {
        $matchColumnName = '';

        $entityColumnName = '';

        switch ($entityType) {
            case (Entities::EMPLOYEE):
                $matchColumnName = 'serviceId';

                $entityColumnName = 'userId';

                break;

            case (Entities::SERVICE):
                $matchColumnName = 'userId';

                $entityColumnName = 'serviceId';

                break;
        }

        $params = [
            ':entityId1' => $entityId,
            ':entityId2' => $entityId,
        ];

        try {
            $statement = $this->connection->prepare(
                "DELETE t1 FROM {$this->table} t1, {$this->table} t2 WHERE
                t1.{$entityColumnName} = :entityId1 AND
                t2.{$entityColumnName} = :entityId2 AND
                t1.id < t2.id AND
                t1.{$matchColumnName} = t2.{$matchColumnName}"
            );

            return $statement->execute($params);
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to delete data from ' . __CLASS__, $e->getCode(), $e);
        }
    }

    /**
     * @param Service $entity
     * @param int     $serviceId
     *
     * @return boolean
     * @throws QueryExecutionException
     */
    public function updateServiceForAllProviders($entity, $serviceId)
    {
        $data = $entity->toArray();

        $params = [
            ':serviceId'     => $serviceId,
            ':minCapacity'   => $data['minCapacity'],
            ':maxCapacity'   => $data['maxCapacity'],
            ':price'         => $data['price'],
            ':customPricing' => $data['customPricing'],
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET `minCapacity` = :minCapacity, `maxCapacity` = :maxCapacity, `price` = :price, `customPricing` = :customPricing
                WHERE serviceId = :serviceId"
            );

            $res = $statement->execute($params);
            if (!$res) {
                throw new QueryExecutionException('Unable to save data in ' . __CLASS__);
            }
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to save data in ' . __CLASS__, $e->getCode(), $e);
        }

        return true;
    }

    /**
     * @param Service $entity
     * @param int     $serviceId
     * @param int     $providerId
     *
     * @return boolean
     * @throws QueryExecutionException
     */
    public function updateServiceForProvider($entity, $serviceId, $providerId)
    {
        $data = $entity->toArray();

        $params = [
            ':serviceId'     => $serviceId,
            ':providerId'    => $providerId,
            ':minCapacity'   => $data['minCapacity'],
            ':maxCapacity'   => $data['maxCapacity'],
            ':price'         => $data['price'],
            ':customPricing' => $data['customPricing'],
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET `minCapacity` = :minCapacity, `maxCapacity` = :maxCapacity, `price` = :price, `customPricing` = :customPricing
                WHERE serviceId = :serviceId AND userId = :providerId"
            );

            $res = $statement->execute($params);

            if (!$res) {
                throw new QueryExecutionException('Unable to save data in ' . __CLASS__);
            }
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to save data in ' . __CLASS__, $e->getCode(), $e);
        }

        return true;
    }

    /**
     * @param int    $providerId
     *
     * @return array
     * @throws QueryExecutionException
     */
    public function getMandatoryServicesIdsForProvider($providerId)
    {

        try {
            $statement = $this->connection->prepare(
                "SELECT
              ps.serviceId, ps.userId
              FROM {$this->table} ps
              GROUP BY ps.serviceId
              HAVING COUNT(*) = 1"
            );

            $statement->execute();

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find data from ' . __CLASS__, $e->getCode(), $e);
        }

        $items = [];

        foreach ($rows as $row) {
            if ($row['userId'] == $providerId) {
                $items[] = $row['serviceId'];
            }
        }

        return $items;
    }
}
