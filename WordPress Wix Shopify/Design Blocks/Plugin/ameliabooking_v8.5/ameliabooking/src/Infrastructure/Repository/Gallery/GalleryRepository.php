<?php

namespace AmeliaBooking\Infrastructure\Repository\Gallery;

use AmeliaBooking\Domain\Entity\Gallery\GalleryImage;
use AmeliaBooking\Domain\Repository\Gallery\GalleryRepositoryInterface;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;

/**
 * Class GalleryRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository
 */
class GalleryRepository extends AbstractRepository implements GalleryRepositoryInterface
{
    /**
     * @param GalleryImage $entity
     *
     * @return int
     * @throws QueryExecutionException
     */
    public function add($entity)
    {
        $data = $entity->toArray();

        $params = [
            ':entityId'         => $data['entityId'],
            ':entityType'       => $data['entityType'],
            ':pictureFullPath'  => $data['pictureFullPath'],
            ':pictureThumbPath' => $data['pictureThumbPath'],
            ':position'         => $data['position']
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO {$this->table} (
                `entityId`,
                `entityType`,
                `pictureFullPath`,
                `pictureThumbPath`,
                `position`
                ) VALUES (
                :entityId,
                :entityType,
                :pictureFullPath,
                :pictureThumbPath,
                :position
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
     * @param int          $id
     * @param GalleryImage $entity
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function update($id, $entity)
    {
        $data = $entity->toArray();

        $params = [
            ':id'               => $id,
            ':pictureFullPath'  => $data['pictureFullPath'],
            ':pictureThumbPath' => $data['pictureThumbPath'],
            ':position'         => $data['position']
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET
                `pictureFullPath` = :pictureFullPath,
                `pictureThumbPath` = :pictureThumbPath,
                `position` = :position
                WHERE 
                id = :id"
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
     *
     * It will delete all relations for one entity except ones that are sent in images array
     *
     * @param array  $imagesIds
     * @param int    $entityId
     * @param string $entityType
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function deleteAllNotInImagesArray($imagesIds, $entityId, $entityType)
    {
        $images = ' ';

        if (!empty($imagesIds)) {
            foreach ($imagesIds as $index => $value) {
                ++$index;
                $images .= ':id' . $index . ', ';
                $params[':id' . $index] = $value;
            }
            $images = 'AND `id` NOT IN (' . rtrim($images, ', ') . ')';
        }

        $params[':entityType'] = $entityType;
        $params[':entityId']   = $entityId;

        try {
            $statement = $this->connection->prepare(
                "DELETE FROM {$this->table} WHERE entityType = :entityType AND entityId = :entityId $images"
            );

            return $statement->execute($params);
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to delete data from ' . __CLASS__, $e->getCode(), $e);
        }
    }
}
