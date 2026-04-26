<?php

namespace AmeliaBooking\Infrastructure\Repository\Bookable\Service;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Category;
use AmeliaBooking\Domain\Factory\Bookable\Service\CategoryFactory;
use AmeliaBooking\Domain\Repository\Bookable\Service\CategoryRepositoryInterface;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;

/**
 * Class CategoryRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Bookable\Service
 */
class CategoryRepository extends AbstractRepository implements CategoryRepositoryInterface
{
    public const FACTORY = CategoryFactory::class;

    /**
     * @param Category $entity
     *
     * @return int
     * @throws QueryExecutionException
     */
    public function add($entity)
    {
        $data = $entity->toArray();

        $params = [
            ':status'           => $data['status'],
            ':name'             => $data['name'],
            ':position'         => $data['position'],
            ':color'            => $data['color'],
            ':pictureFullPath'  => $data['pictureFullPath'],
            ':pictureThumbPath' => $data['pictureThumbPath'],
            ':translations'     => $data['translations'],
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO {$this->table} 
                (`status`, `name`, `position`, `translations`, `color`, `pictureFullPath`, `pictureThumbPath`)
                VALUES (:status, :name, :position, :translations, :color, :pictureFullPath, :pictureThumbPath)"
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
     * @param int      $id
     * @param Category $entity
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function update($id, $entity)
    {
        $data = $entity->toArray();

        $params = [
            ':status'           => $data['status'],
            ':name'             => $data['name'],
            ':position'         => $data['position'],
            ':translations'     => $data['translations'],
            ':color'            => $data['color'],
            ':pictureFullPath'  => $data['pictureFullPath'],
            ':pictureThumbPath' => $data['pictureThumbPath'],
            ':id'               => $id
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET 
                    `status` = :status,
                    `name` = :name,
                    `position` = :position,
                    `translations` = :translations,
                    `color` = :color,
                    `pictureFullPath` = :pictureFullPath,
                    `pictureThumbPath` = :pictureThumbPath
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
    public function getAllIndexedById()
    {
        try {
            $statement = $this->connection->query($this->selectQuery() . ' ORDER BY position ASC');
            $rows      = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        $collection = new Collection();
        foreach ($rows as $row) {
            $collection->addItem(
                call_user_func([static::FACTORY, 'create'], $row),
                $row['id']
            );
        }

        return $collection;
    }
}
