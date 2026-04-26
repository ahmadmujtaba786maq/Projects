<?php

namespace AmeliaBooking\Infrastructure\Repository\Bookable\Service;

use AmeliaBooking\Domain\Entity\Bookable\Service\PackageService;
use AmeliaBooking\Domain\Factory\Bookable\Service\PackageServiceFactory;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;

/**
 * Class PackageServiceRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Bookable\Service
 */
class PackageServiceRepository extends AbstractRepository
{
    public const FACTORY = PackageServiceFactory::class;

    /**
     * @param PackageService $entity
     * @param int            $packageId
     *
     * @return int
     * @throws QueryExecutionException
     */
    public function add($entity, $packageId)
    {
        $data = $entity->toArray();

        $params = [
            ':packageId'        => $packageId,
            ':serviceId'        => $data['service']['id'],
            ':quantity'         => $data['quantity'],
            ':minimumScheduled' => $data['minimumScheduled'],
            ':maximumScheduled' => $data['maximumScheduled'],
            ':allowProviderSelection' => $data['allowProviderSelection'] ? 1 : 0,
            ':position'         => $data['position']
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO {$this->table}
                (`packageId`, `serviceId`, `quantity`, `minimumScheduled`, `maximumScheduled`, `allowProviderSelection`, `position`)
                VALUES
                (:packageId, :serviceId, :quantity, :minimumScheduled, :maximumScheduled, :allowProviderSelection, :position)"
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
     * @param int            $id
     * @param PackageService $entity
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function update($id, $entity)
    {
        $data = $entity->toArray();

        $params = [
            ':quantity'         => $data['quantity'],
            ':minimumScheduled' => $data['minimumScheduled'],
            ':maximumScheduled' => $data['maximumScheduled'],
            ':allowProviderSelection' => $data['allowProviderSelection'] ? 1 : 0,
            ':id'               => $id,
            ':position'         => $data['position'],
        ];


        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET
                `quantity`         = :quantity,
                `minimumScheduled` = :minimumScheduled,
                `maximumScheduled` = :maximumScheduled,
                `allowProviderSelection` = :allowProviderSelection,
                `position`         = :position
                WHERE
                id = :id"
            );

            $result = $statement->execute($params);

            if (!$result) {
                throw new QueryExecutionException('Unable to save data in ' . __CLASS__);
            }

            return $result;
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to save data in ' . __CLASS__, $e->getCode(), $e);
        }
    }

    /**
     *
     * It will delete all relations for one package except ones that are sent in services array
     *
     * @param array $servicesIds
     * @param int   $packageId
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function deleteAllNotInServicesArrayForPackage($servicesIds, $packageId)
    {
        $services = ' ';

        if (!empty($servicesIds)) {
            foreach ($servicesIds as $index => $value) {
                ++$index;
                $services .= ':serviceId' . $index . ', ';
                $params[':serviceId' . $index] = (int)$value;
            }
            $services = 'AND `serviceId` NOT IN (' . rtrim($services, ', ') . ')';
        }

        $params[':packageId'] = $packageId;

        try {
            $statement = $this->connection->prepare(
                "DELETE FROM {$this->table} WHERE 1 = 1 {$services} AND packageId = :packageId"
            );

            return $statement->execute($params);
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to delete data from ' . __CLASS__, $e->getCode(), $e);
        }
    }
}
