<?php

namespace AmeliaBooking\Infrastructure\Repository\Bookable\Service;

use AmeliaBooking\Domain\Entity\Location\Location;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Factory\Bookable\Service\PackageServiceFactory;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;

/**
 * Class PackageServiceProviderRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Bookable\Service
 */
class PackageServiceProviderRepository extends AbstractRepository
{
    public const FACTORY = PackageServiceFactory::class;

    /**
     * @param Provider $entity
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
            ':userId'           => $data['id'],
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO {$this->table}
                (`packageServiceId`, `userId`)
                VALUES
                (:packageServiceId, :userId)"
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
     * It will delete all relations for one package service except ones that are sent in providers array
     *
     * @param array $providersIds
     * @param int   $packageServiceId
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function deleteAllNotInProvidersServicesArrayForPackage($providersIds, $packageServiceId)
    {
        $providers = ' ';

        if (!empty($providersIds)) {
            foreach ($providersIds as $index => $value) {
                ++$index;
                $providers .= ':userId' . $index . ', ';
                $params[':userId' . $index] = (int)$value;
            }
            $providers = 'AND `userId` NOT IN (' . rtrim($providers, ', ') . ')';
        }

        $params[':packageServiceId'] = $packageServiceId;

        try {
            $statement = $this->connection->prepare(
                "DELETE FROM {$this->table} WHERE 1 = 1 {$providers} AND packageServiceId = :packageServiceId"
            );

            return $statement->execute($params);
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to delete data from ' . __CLASS__, $e->getCode(), $e);
        }
    }
}
