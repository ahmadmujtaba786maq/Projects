<?php

namespace AmeliaBooking\Infrastructure\Repository\Outlook;

use AmeliaBooking\Domain\Entity\Outlook\OutlookCalendar;
use AmeliaBooking\Domain\Factory\Outlook\OutlookCalendarFactory;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;
use Exception;

/**
 * Class OutlookCalendarRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Outlook
 */
class OutlookCalendarRepository extends AbstractRepository
{
    public const FACTORY = OutlookCalendarFactory::class;

    /**
     * @param OutlookCalendar $outlookCalendar
     * @param int            $userId
     *
     * @return string
     * @throws QueryExecutionException
     */
    public function add($outlookCalendar, $userId)
    {
        $data = $outlookCalendar->toArray();

        $params = [
            ':userId' => $userId,
            ':token'  => $data['token']
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO {$this->table}
                (`userId`, `token`)
                VALUES
                (:userId, :token)"
            );

            $res = $statement->execute($params);

            if (!$res) {
                throw new QueryExecutionException('Unable to add data in ' . __CLASS__);
            }
        } catch (Exception $e) {
            throw new QueryExecutionException('Unable to add data in ' . __CLASS__, $e->getCode(), $e);
        }

        return $this->connection->lastInsertId();
    }

    /**
     * @param OutlookCalendar $outlookCalendar
     * @param int            $id
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function update($outlookCalendar, $id)
    {
        $data = $outlookCalendar->toArray();

        $params = [
            ':token'      => $data['token'],
            ':calendarId' => $data['calendarId'],
            ':id'         => $id
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET `token` = :token, `calendarId` = :calendarId WHERE id = :id"
            );

            $res = $statement->execute($params);
            if (!$res) {
                throw new QueryExecutionException('Unable to save data in ' . __CLASS__);
            }

            return $res;
        } catch (Exception $e) {
            throw new QueryExecutionException('Unable to save data in ' . __CLASS__, $e->getCode(), $e);
        }
    }

    /**
     * @param $userId
     *
     * @return mixed
     * @throws NotFoundException
     * @throws QueryExecutionException
     */
    public function getByProviderId($userId)
    {
        try {
            $statement = $this->connection->prepare($this->selectQuery() . " WHERE {$this->table}.userId = :userId");
            $statement->bindParam(':userId', $userId);
            $statement->execute();
            $row = $statement->fetch();
        } catch (Exception $e) {
            throw new QueryExecutionException('Unable to find by id in ' . __CLASS__, $e->getCode(), $e);
        }

        if (!$row) {
            throw new NotFoundException('Data not found in ' . __CLASS__);
        }

        return call_user_func([static::FACTORY, 'create'], $row);
    }
}
