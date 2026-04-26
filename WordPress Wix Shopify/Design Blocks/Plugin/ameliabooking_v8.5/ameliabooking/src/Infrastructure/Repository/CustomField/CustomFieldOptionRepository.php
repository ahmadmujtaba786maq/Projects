<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Repository\CustomField;

use AmeliaBooking\Domain\Entity\CustomField\CustomFieldOption;
use AmeliaBooking\Domain\Factory\CustomField\CustomFieldOptionFactory;
use AmeliaBooking\Domain\Repository\CustomField\CustomFieldOptionRepositoryInterface;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;

/**
 * Class CustomFieldOptionRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\CustomField
 */
class CustomFieldOptionRepository extends AbstractRepository implements CustomFieldOptionRepositoryInterface
{
    public const FACTORY = CustomFieldOptionFactory::class;

    /**
     * @param CustomFieldOption $entity
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function add($entity)
    {
        $data = $entity->toArray();

        $params = [
            ':customFieldId' => $data['customFieldId'],
            ':label'         => isset($data['label']) ? $data['label'] : '',
            ':position'      => $data['position'],
            ':translations'  => $data['translations'],
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO
                {$this->table}
                (
                `customFieldId`, `label`, `position`, `translations`
                ) VALUES (
                :customFieldId, :label, :position, :translations
                )"
            );


            $response = $statement->execute($params);
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to add data in ' . __CLASS__, $e->getCode(), $e);
        }

        if (!$response) {
            throw new QueryExecutionException('Unable to add data in ' . __CLASS__);
        }

        return $this->connection->lastInsertId();
    }

    /**
     * @param int               $id
     * @param CustomFieldOption $entity
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function update($id, $entity)
    {
        $data = $entity->toArray();

        $params = [
            ':customFieldId' => $data['customFieldId'],
            ':label'         => $data['label'],
            ':position'      => $data['position'],
            ':translations'  => $data['translations'],
            ':id'            => $id,
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET
                `customFieldId` = :customFieldId,
                `label`         = :label,
                `position`      = :position,
                `translations`  = :translations
                WHERE
                id = :id"
            );

            $response = $statement->execute($params);
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to save data in ' . __CLASS__, $e->getCode(), $e);
        }

        if (!$response) {
            throw new QueryExecutionException('Unable to save data in ' . __CLASS__);
        }

        return $response;
    }
}
