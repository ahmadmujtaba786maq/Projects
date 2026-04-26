<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Repository;

use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;

/**
 * Class AbstractEntityRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository
 */
class AbstractEntityRepository extends AbstractRepository
{
    /**
     * @param int    $entityId
     * @param string $entityType
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function deleteByEntityIdAndEntityType($entityId, $entityType)
    {
        $params = [
            ':entityId'   => $entityId,
            ':entityType' => $entityType,
        ];

        try {
            $statement = $this->connection->prepare(
                "DELETE FROM {$this->table} WHERE entityId = :entityId AND entityType = :entityType"
            );

            return $statement->execute($params);
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to delete entities in ' . __CLASS__, $e->getCode(), $e);
        }
    }
}
