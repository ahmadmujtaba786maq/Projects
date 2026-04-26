<?php

namespace AmeliaBooking\Infrastructure\Repository\User;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Entity\User\Admin;
use AmeliaBooking\Domain\Entity\User\Customer;
use AmeliaBooking\Domain\Entity\User\Manager;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Factory\User\UserFactory;
use AmeliaBooking\Domain\Repository\User\UserRepositoryInterface;
use AmeliaBooking\Domain\ValueObjects\Json;
use AmeliaBooking\Domain\ValueObjects\String\Password;
use AmeliaBooking\Infrastructure\Licence;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\CustomerBookingsTable;

/**
 * Class UserRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository
 */
class UserRepository extends AbstractRepository implements UserRepositoryInterface
{
    public const FACTORY = UserFactory::class;

    /**
     * @param AbstractUser $entity
     *
     * @return int
     * @throws QueryExecutionException
     */
    public function add($entity)
    {
        $data = $entity->toArray();

        $params = [
            ':type'                  => $data['type'],
            ':status'                => $data['status'] ?: 'visible',
            ':externalId'            => $data['externalId'] ?: null,
            ':firstName'             => $data['firstName'],
            ':lastName'              => $data['lastName'],
            ':email'                 => $data['email'],
            ':note'                  => isset($data['note']) ? $data['note'] : null,
            ':description'           => isset($data['description']) ? $data['description'] : null,
            ':phone'                 => isset($data['phone']) ? $data['phone'] : null,
            ':gender'                => isset($data['gender']) ? $data['gender'] : null,
            ':birthday'              => $data['birthday'] ? $data['birthday']->format('Y-m-d') : null,
            ':pictureFullPath'       => $data['pictureFullPath'],
            ':pictureThumbPath'      => $data['pictureThumbPath'],
            ':password'              => isset($data['password']) ? $data['password'] : null,
            ':usedTokens'            => isset($data['usedTokens']) ? $data['usedTokens'] : null,
            ':countryPhoneIso'       => isset($data['countryPhoneIso']) ? $data['countryPhoneIso'] : null,
            ':stripeConnect'         => !empty($data['stripeConnect']) ? json_encode($data['stripeConnect']) : null,
            ':employeeAppleCalendar' => !empty($data['employeeAppleCalendar']) ? json_encode($data['employeeAppleCalendar']) : null,
            ':error'                 => '',
            ':customFields'          => isset($data['customFields']) ? $data['customFields'] : null,
        ];

        $additionalData = Licence\DataModifier::getUserRepositoryData($data);

        $params = array_merge($params, $additionalData['values']);

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO {$this->table} (
                {$additionalData['columns']}
                `type`,
                `status`,
                `externalId`,
                `firstName`,
                `lastName`,
                `email`,
                `note`,
                `description`,
                `phone`,
                `gender`,
                `birthday`,
                `pictureFullPath`,
                `pictureThumbPath`,
                `countryPhoneIso`,
                `usedTokens`,
                `stripeConnect`,
                `employeeAppleCalendar`,   
                `password`,
                `error`,
                `customFields`
                ) VALUES (
                {$additionalData['placeholders']}
                :type,
                :status,
                :externalId,
                :firstName,
                :lastName,
                :email,
                :note,
                :description,
                :phone,
                :gender,
                STR_TO_DATE(:birthday, '%Y-%m-%d'),
                :pictureFullPath,
                :pictureThumbPath,
                :countryPhoneIso,
                :usedTokens,
                :stripeConnect,
                :employeeAppleCalendar,
                :password,
                :error,
                :customFields
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
     * @param AbstractUser|array $entity
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function update($id, $entity)
    {
        if (!is_array($entity)) {
            $data = $entity->toArray();
        } else {
            $data = $entity;
        }

        $params = [
            ':externalId'       => $data['externalId'] ?: null,
            ':firstName'        => $data['firstName'],
            ':lastName'         => $data['lastName'],
            ':email'            => isset($data['email']) ? $data['email'] : null,
            ':note'             => isset($data['note']) ? $data['note'] : null,
            ':description'      => isset($data['description']) ? $data['description'] : null,
            ':phone'            => isset($data['phone']) ? $data['phone'] : null,
            ':gender'           => isset($data['gender']) ? $data['gender'] : null,
            ':birthday'         => isset($data['birthday']) ? $data['birthday']->format('Y-m-d') : null,
            ':pictureFullPath'  => $data['pictureFullPath'],
            ':pictureThumbPath' => $data['pictureThumbPath'],
            ':countryPhoneIso'  => isset($data['countryPhoneIso']) ? $data['countryPhoneIso'] : null,
            ':password'         => isset($data['password']) ? $data['password'] : null,
            ':stripeConnect'    => !empty($data['stripeConnect']) ? json_encode($data['stripeConnect']) : null,
            ':employeeAppleCalendar' => !empty($data['employeeAppleCalendar']) ? json_encode($data['employeeAppleCalendar']) : null,
            ':id'               => $id,
            ':customFields'     => isset($data['customFields']) ? $data['customFields'] : null,
        ];

        $additionalData = Licence\DataModifier::getUserRepositoryData($data);

        $params = array_merge($params, $additionalData['values']);

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET
                {$additionalData['columnsPlaceholders']}
                `externalId` = :externalId,
                `firstName` = :firstName,
                `lastName` = :lastName,
                `email` = :email,
                `note` = :note, 
                `description` = :description,    
                `phone` = :phone,
                `gender` = :gender,
                `birthday` = STR_TO_DATE(:birthday, '%Y-%m-%d'),
                `countryPhoneIso` = :countryPhoneIso,
                `pictureFullPath` = :pictureFullPath,
                `pictureThumbPath` = :pictureThumbPath,
                `stripeConnect` = :stripeConnect,     
                `employeeAppleCalendar` = :employeeAppleCalendar,           
                `customFields` = :customFields,
                `password` = IFNULL(:password, `password`)
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
     * @param $externalId
     *
     * @return Admin|Customer|Manager|Provider|bool
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function findByExternalId($externalId)
    {
        try {
            $statement = $this->connection->prepare("SELECT * FROM {$this->table} WHERE externalId = :id");
            $statement->bindParam(':id', $externalId);
            $statement->execute();
            $row = $statement->fetch();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find by external id in ' . __CLASS__, $e->getCode(), $e);
        }

        if (!$row) {
            return false;
        }

        return UserFactory::create($row);
    }

    /**
     * @param $type
     *
     * @return Collection
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getAllByType($type)
    {
        $params = [
            ':type' => $type,
        ];

        try {
            $statement = $this->connection->prepare($this->selectQuery() . ' WHERE type = :type');

            $statement->execute($params);

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
     * Returns Collection of all customers and other users that have at least one booking
     *
     * @return Collection
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getAllWithAllowedBooking()
    {
        try {
            $bookingsTable = CustomerBookingsTable::getTableName();

            $statement = $this->connection->query(
                "
            SELECT
            u.id AS id,
            u.firstName AS firstName,
            u.lastName AS lastName,
            u.email AS email,
            u.note AS note,
            u.description AS description,
            u.phone AS phone,
            u.gender AS gender,
            u.status AS status,
            u.translations AS translations
            FROM {$this->table} u
            LEFT JOIN {$bookingsTable} cb ON cb.customerId = u.id
            WHERE u.type = 'customer' OR (cb.id IS NOT NULL AND u.type IN ('admin', 'provider', 'manager'))
            GROUP BY u.id
            ORDER BY CONCAT(firstName, ' ', lastName) 
            "
            );

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        $items = [];
        foreach ($rows as $row) {
            $items[$row['id']] = call_user_func([static::FACTORY, 'create'], $row);
        }

        return new Collection($items);
    }

    /**
     * Returns Collection of all customers and other users that have at least one booking
     *
     * @return Collection
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getAllWithoutBookings()
    {
        $bookingsTable = CustomerBookingsTable::getTableName();

        try {
            $statement = $this->connection->query(
                "
                SELECT
                    u.id AS id,
                    u.firstName AS firstName,
                    u.lastName AS lastName,
                    u.email AS email,
                    u.note AS note,
                    u.phone AS phone,
                    u.gender AS gender,
                    u.status AS status,
                    u.translations AS translations
                FROM {$this->table} u
                LEFT JOIN {$bookingsTable} cb ON cb.customerId = u.id
                WHERE
                (u.type = 'customer' OR (cb.id IS NOT NULL AND u.type IN ('admin', 'provider', 'manager')))
                AND cb.appointmentId IS NULL
                AND u.id NOT IN (
                    SELECT u2.id
                    FROM {$this->table} u2
                    INNER JOIN {$bookingsTable} cb2 ON cb2.customerId = u2.id
                    WHERE cb2.appointmentId IS NOT NULL
                )
                GROUP BY u.id
                ORDER BY CONCAT(firstName, ' ', lastName)
            "
            );

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        $items = [];
        foreach ($rows as $row) {
            $items[$row['id']] = call_user_func([static::FACTORY, 'create'], $row);
        }

        return new Collection($items);
    }

    /**
     * @param string  $email
     * @param boolean $setPassword
     * @param boolean $setUsedTokens
     *
     * @return Admin|Customer|Manager|Provider
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function getByEmail($email, $setPassword = false, $setUsedTokens = false)
    {
        try {
            $statement = $this->connection->prepare($this->selectQuery() . ' WHERE LOWER(email) = LOWER(:email)');

            $statement->execute(
                array(
                ':email' => $email
                )
            );

            $row = $statement->fetch();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        if (!$row) {
            return null;
        }

        /** @var Admin|Customer|Manager|Provider $user */
        $user = UserFactory::create($row);

        if ($setPassword) {
            $user->setPassword(Password::createFromHashedPassword($row['password']));
        }

        if ($setUsedTokens) {
            $user->setUsedTokens(new Json($row['usedTokens']));
        }
        return $user;
    }

    /**
     * @param string $phone
     *
     * @return Admin|Customer|Manager|Provider
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function getByPhone($phone)
    {
        try {
            $statement = $this->connection->prepare($this->selectQuery() . ' WHERE phone = :phone');

            $statement->execute(
                array(
                    ':phone' => $phone
                )
            );

            $row = $statement->fetch();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        if (!$row) {
            return null;
        }

        /** @var Admin|Customer|Manager|Provider $user */
        $user = UserFactory::create($row);

        return $user;
    }

    /**
     * @return array
     * @throws QueryExecutionException
     */
    public function getAllEmailsByType($type)
    {
        try {
            $params[':type'] = $type;
            $statement       = $this->connection->prepare(
                "
                SELECT DISTINCT 
                    u.email AS email
                FROM {$this->table} u
                WHERE u.type = :type
                "
            );
            $statement->execute($params);
            $rows = $statement->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        return $rows;
    }

    /**
     * @param string   $email
     * @param array $data
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function updateFieldsByEmail($email, $data)
    {
        $fields = [];

        $params = [':email' => $email];

        foreach ($data as $key => $item) {
            $params[":$key"] = $item;
            $fields[]        = "`$key` = :$key";
        }

        $fields = implode(', ', $fields);

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table} SET {$fields} WHERE email = :email"
            );

            $res = $statement->execute($params);

            if (!$res) {
                throw new QueryExecutionException('Unable to save data in ' . __CLASS__);
            }

            return true;
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to save data in ' . __CLASS__, $e->getCode(), $e);
        }
    }
}
