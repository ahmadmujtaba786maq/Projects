<?php

namespace AmeliaBooking\Infrastructure\Repository\Notification;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Notification\Notification;
use AmeliaBooking\Domain\Factory\Notification\NotificationFactory;
use AmeliaBooking\Domain\Repository\Notification\NotificationRepositoryInterface;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventRepository;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Notification\NotificationsToEntitiesTable;

/**
 * Class NotificationRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Notification
 */
class NotificationRepository extends AbstractRepository implements NotificationRepositoryInterface
{
    public const FACTORY = NotificationFactory::class;

    public const CUSTOM = true;

    /**
     * @param Notification $entity
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function add($entity)
    {
        $data = $entity->toArray();

        $params = [
            ':name'         => $data['name'],
            ':customName'   => $data['customName'],
            ':sendTo'       => $data['sendTo'],
            ':status'       => $data['status'],
            ':type'         => $data['type'],
            ':entity'       => $data['entity'],
            ':time'         => $data['time'],
            ':timeBefore'   => $data['timeBefore'],
            ':timeAfter'    => $data['timeAfter'],
            ':subject'      => $data['subject'],
            ':content'      => $data['content'],
            ':translations' => $data['translations'],
            ':sendOnlyMe'   => $data['sendOnlyMe'] ? 1 : 0,
            ':whatsAppTemplate' => $data['whatsAppTemplate'],
            ':minimumTimeBeforeBooking' => $data['minimumTimeBeforeBooking']
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO {$this->table} 
                (`name`, `customName`, `sendTo`, `status`, `type`, `entity`, `time`, `timeBefore`,
                 `timeAfter`, `subject`, `content`, `translations`, `sendOnlyMe`, `whatsAppTemplate`, `minimumTimeBeforeBooking`)
                VALUES (:name, :customName, :sendTo, :status, :type, :entity, :time, :timeBefore,
                        :timeAfter, :subject, :content, :translations, :sendOnlyMe, :whatsAppTemplate, :minimumTimeBeforeBooking)"
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
     * @param int          $id
     * @param Notification $entity
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function update($id, $entity)
    {
        $data = $entity->toArray();

        $params = [
            ':name'         => $data['name'],
            ':customName'   => $data['customName'],
            ':status'       => $data['status'],
            ':time'         => $data['time'],
            ':timeBefore'   => $data['timeBefore'],
            ':timeAfter'    => $data['timeAfter'],
            ':subject'      => $data['subject'],
            ':content'      => $data['content'],
            ':translations' => $data['translations'],
            ':sendOnlyMe'   => $data['sendOnlyMe'] ? 1 : 0,
            ':whatsAppTemplate' => $data['whatsAppTemplate'],
            ':minimumTimeBeforeBooking' => $data['minimumTimeBeforeBooking'],
            ':id'           => $id,
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table} SET 
                `name` = :name,
                `customName` = :customName,
                `status` = :status,
                `time` = :time,
                `timeBefore` = :timeBefore,
                `timeAfter` = :timeAfter,
                `subject` = :subject,
                `content` = :content,
                `translations` = :translations,
                `sendOnlyMe` = :sendOnlyMe,
                `whatsAppTemplate` = :whatsAppTemplate,
                `minimumTimeBeforeBooking` = :minimumTimeBeforeBooking
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
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getAll()
    {
        $custom = !self::CUSTOM ? ' WHERE customName IS NULL' : '';

        try {
            $statement = $this->connection->query($this->selectQuery() . $custom);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        $items = [];
        foreach ($rows as $row) {
            $items[] = call_user_func([static::FACTORY, 'create'], $row);
        }

        return new Collection($items);
    }

    /**
     * @param $name
     * @param $type
     *
     * @return Collection
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getByNameAndType($name, $type)
    {
        $custom = !self::CUSTOM ? 'customName IS NULL AND ' : '';

        try {
            $statement = $this->connection->prepare(
                $this->selectQuery() . " WHERE {$custom}{$this->table}.name LIKE :name AND {$this->table}.type = :type"
            );

            $params = [
                ':name' => $name,
                ':type' => $type
            ];

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find by name and type in ' . __CLASS__, $e->getCode(), $e);
        }

        $items = new Collection();
        foreach ($rows as $row) {
            $items->addItem(call_user_func([static::FACTORY, 'create'], $row), $row['id']);
        }

        return $items;
    }


    /**
     * @param int $notificationId
     *
     * @return bool
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function delete($notificationId)
    {
        $notificationsToEntities = NotificationsToEntitiesTable::getTableName();
        $params = [
            ':id'  => $notificationId,
        ];

        try {
            $statement = $this->connection->prepare(
                "DELETE FROM {$this->table} WHERE id = :id"
            );
            $success1  = $statement->execute($params);
            $statement = $this->connection->prepare(
                "DELETE FROM {$notificationsToEntities} WHERE notificationId = :id"
            );
            $success2  = $statement->execute($params);

            return $success1 && $success2;
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to delete data from ' . __CLASS__, $e->getCode(), $e);
        }
    }
}
