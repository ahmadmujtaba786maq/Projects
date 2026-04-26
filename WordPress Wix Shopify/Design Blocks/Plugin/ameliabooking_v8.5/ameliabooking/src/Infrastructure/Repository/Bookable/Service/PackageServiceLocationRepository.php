<?php

namespace AmeliaBooking\Infrastructure\Repository\Bookable\Service;

use AmeliaBooking\Domain\Entity\Location\Location;
use AmeliaBooking\Domain\Factory\Bookable\Service\PackageServiceFactory;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;

/**
 * Class PackageServiceLocationRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Bookable\Service
 */
class PackageServiceLocationRepository extends AbstractRepository
{
    public const FACTORY = PackageServiceFactory::class;

    /**
     * @param Location $entity
     * @param int      $packageServiceId
     *
     * @return int
     * @throws QueryExecutionException
     */
    public function add($entity, $packageServiceId)
    {
        $data = $entity->toArray();

        $params = [
            ':packageServiceId' => $packageServiceId,
            ':locationId'       => $data['id'],
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO {$this->table}
                (`packageServiceId`, `locationId`)
                VALUES
                (:packageServiceId, :locationId)"
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
     *
     * It will delete all relations for one package service except ones that are sent in locations array
     *
     * @param array $locationsIds
     * @param int   $packageServiceId
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function deleteAllNotInLocationsServicesArrayForPackage($locationsIds, $packageServiceId)
    {
        $locations = ' ';

        if (!empty($locationsIds)) {
            foreach ($locationsIds as $index => $value) {
                ++$index;
                $locations .= ':locationId' . $index . ', ';
                $params[':locationId' . $index] = (int)$value;
            }
            $locations = 'AND `locationId` NOT IN (' . rtrim($locations, ', ') . ')';
        }

        $params[':packageServiceId'] = $packageServiceId;

        try {
            $statement = $this->connection->prepare(
                "DELETE FROM {$this->table} WHERE 1 = 1 {$locations} AND packageServiceId = :packageServiceId"
            );

            return $statement->execute($params);
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to delete data from ' . __CLASS__, $e->getCode(), $e);
        }
    }
}
