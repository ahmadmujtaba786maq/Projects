<?php

namespace AmeliaBooking\Infrastructure\Repository\Google;

use AmeliaBooking\Domain\Entity\Google\GoogleCalendar;
use AmeliaBooking\Domain\Factory\Google\GoogleCalendarFactory;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;

/**
 * Class GoogleRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Google
 */
class GoogleCalendarRepository extends AbstractRepository
{
    public const FACTORY = GoogleCalendarFactory::class;

    /**
     * @param GoogleCalendar $googleCalendar
     * @param int            $userId
     *
     * @return string
     * @throws QueryExecutionException
     */
    public function add($googleCalendar, $userId)
    {
        $data = $googleCalendar->toArray();

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
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to add data in ' . __CLASS__, $e->getCode(), $e);
        }

        return $this->connection->lastInsertId();
    }

    /**
     * @param GoogleCalendar $googleCalendar
     * @param int            $id
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function update($googleCalendar, $id)
    {
        $data = $googleCalendar->toArray();

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
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find by id in ' . __CLASS__, $e->getCode(), $e);
        }

        if (!$row) {
            throw new NotFoundException('Data not found in ' . __CLASS__);
        }

        return call_user_func([static::FACTORY, 'create'], $row);
    }
}
