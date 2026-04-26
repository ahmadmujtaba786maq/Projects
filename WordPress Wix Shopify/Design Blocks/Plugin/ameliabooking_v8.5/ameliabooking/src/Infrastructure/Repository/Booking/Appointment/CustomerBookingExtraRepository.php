<?php

namespace AmeliaBooking\Infrastructure\Repository\Booking\Appointment;

use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBookingExtra;
use AmeliaBooking\Domain\Factory\Booking\Appointment\CustomerBookingExtraFactory;
use AmeliaBooking\Domain\Repository\Booking\Appointment\CustomerBookingExtraRepositoryInterface;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;

/**
 * Class CustomerBookingExtraRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Booking\Appointment
 */
class CustomerBookingExtraRepository extends AbstractRepository implements CustomerBookingExtraRepositoryInterface
{
    public const FACTORY = CustomerBookingExtraFactory::class;

    /**
     * @param CustomerBookingExtra $entity
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function add($entity)
    {
        $data = $entity->toArray();

        $params = [
            ':customerBookingId' => $data['customerBookingId'],
            ':extraId'           => $data['extraId'],
            ':quantity'          => $data['quantity'],
            ':price'             => $data['price'],
            ':tax'               => !empty($data['tax']) ? json_encode($data['tax']) : null,
            ':aggregatedPrice'   => $data['aggregatedPrice'] ? 1 : 0,
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO {$this->table} 
                (
                `customerBookingId`,
                `extraId`,
                `quantity`,
                `aggregatedPrice`,
                `price`,
                `tax`
                )
                VALUES (
                :customerBookingId, 
                :extraId, 
                :quantity,
                :aggregatedPrice,
                :price,
                :tax
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
     * @param int                  $id
     * @param CustomerBookingExtra $entity
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function update($id, $entity)
    {
        $data = $entity->toArray();

        $params = [
            ':id'                => $id,
            ':customerBookingId' => $data['customerBookingId'],
            ':extraId'           => $data['extraId'],
            ':quantity'          => $data['quantity'],
            ':aggregatedPrice'   => $data['aggregatedPrice'] ? 1 : 0,
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET
                `customerBookingId` = :customerBookingId,
                `extraId` = :extraId,
                `aggregatedPrice` = :aggregatedPrice,
                `quantity` = :quantity
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
}
