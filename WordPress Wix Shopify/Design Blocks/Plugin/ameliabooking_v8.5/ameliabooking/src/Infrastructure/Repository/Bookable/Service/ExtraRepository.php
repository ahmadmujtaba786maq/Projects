<?php

namespace AmeliaBooking\Infrastructure\Repository\Bookable\Service;

use AmeliaBooking\Domain\Entity\Bookable\Service\Extra;
use AmeliaBooking\Domain\Factory\Bookable\Service\ExtraFactory;
use AmeliaBooking\Domain\Repository\Bookable\Service\ExtraRepositoryInterface;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;

/**
 * Class ExtraRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Bookable\Service
 */
class ExtraRepository extends AbstractRepository implements ExtraRepositoryInterface
{
    public const FACTORY = ExtraFactory::class;

    /**
     * @param Extra $entity
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function add($entity)
    {
        $data = $entity->toArray();

        $params = [
            ':name'            => $data['name'],
            ':description'     => $data['description'],
            ':price'           => $data['price'],
            ':maxQuantity'     => $data['maxQuantity'],
            ':duration'        => $data['duration'],
            ':serviceId'       => $data['serviceId'],
            ':aggregatedPrice' => $data['aggregatedPrice'] ? 1 : 0,
            ':position'        => $data['position'],
            ':translations'    => $data['translations'],
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO 
                {$this->table} 
                (
                `name`,
                `description`,
                `price`,
                `maxQuantity`,
                `duration`,
                `serviceId`,
                `aggregatedPrice`,
                `position`,
                `translations`
                ) VALUES (
                :name,
                :description,
                :price,
                :maxQuantity,
                :duration,
                :serviceId,
                :aggregatedPrice,
                :position,
                :translations
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
     * @param int   $id
     * @param Extra $entity
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function update($id, $entity)
    {
        $data = $entity->toArray();

        $params = [
            ':name'            => $data['name'],
            ':description'     => $data['description'],
            ':price'           => $data['price'],
            ':maxQuantity'     => $data['maxQuantity'],
            ':duration'        => $data['duration'],
            ':serviceId'       => $data['serviceId'],
            ':aggregatedPrice' => $data['aggregatedPrice'] === null ?
                $data['aggregatedPrice'] : ((int)$data['aggregatedPrice']),
            ':position'        => $data['position'],
            ':translations'    => $data['translations'],
            ':id'              => $id
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET 
                `name` = :name,
                `description` = :description,
                `price` = :price,
                `maxQuantity` = :maxQuantity,
                `duration` = :duration,
                `serviceId` = :serviceId,
                `aggregatedPrice` = :aggregatedPrice,
                `position` = :position,
                `translations` = :translations
                WHERE id = :id"
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
}
