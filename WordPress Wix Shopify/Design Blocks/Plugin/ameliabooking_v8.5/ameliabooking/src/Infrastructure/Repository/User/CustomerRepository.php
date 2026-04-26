<?php

namespace AmeliaBooking\Infrastructure\Repository\User;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Repository\User\CustomerRepositoryInterface;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Domain\ValueObjects\String\Status;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\AppointmentsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\CustomerBookingsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\WPUsersTable;

/**
 * Class UserRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository
 */
class CustomerRepository extends UserRepository implements CustomerRepositoryInterface
{
    /**
     * @param     $criteria
     * @param int $itemsPerPage
     *
     * @return array
     * @throws QueryExecutionException
     * @throws \Exception
     */
    public function getFiltered($criteria, $itemsPerPage = null)
    {
        try {
            $wpUserTable       = WPUsersTable::getTableName();
            $bookingsTable     = CustomerBookingsTable::getTableName();
            $appointmentsTable = AppointmentsTable::getTableName();

            $params = [
                ':type_customer'        => AbstractUser::USER_ROLE_CUSTOMER,
                ':type_admin'           => AbstractUser::USER_ROLE_ADMIN,
            ];

            $joinWithBookings = empty($criteria['ignoredBookings']);

            $where = [
                'u.type IN (:type_customer, :type_admin)',
            ];

            $order = '';
            if (!empty($criteria['sort'])) {
                $column         = $criteria['sort'][0] === '-' ? substr($criteria['sort'], 1) : $criteria['sort'];
                $orderColumn    = $column === 'customer' ? 'CONCAT(u.firstName, " ", u.lastName)' : 'lastAppointment';
                $orderDirection = $criteria['sort'][0] === '-' ? 'DESC' : 'ASC';
                $order          = "ORDER BY {$orderColumn} {$orderDirection}";

                $joinWithBookings = $column !== 'customer' || $joinWithBookings;
            }

            if (!empty($criteria['search'])) {
                $params[':search1'] = $params[':search2'] = $params[':search3'] = $params[':search4'] = $params[':search5'] =
                    "%{$criteria['search']}%";

                $where[] = "((CONCAT(u.firstName, ' ', u.lastName) LIKE :search1
                            OR wpu.display_name LIKE :search2
                            OR u.email LIKE :search3
                            OR u.phone LIKE :search4
                            OR u.note LIKE :search5))";
            }

            if (!empty($criteria['customers'])) {
                $customersCriteria = [];

                foreach ((array)$criteria['customers'] as $key => $customerId) {
                    $params[":customerId$key"] = $customerId;
                    $customersCriteria[]       = ":customerId$key";
                }

                $where[] = 'u.id IN (' . implode(', ', $customersCriteria) . ')';
            }

            $statsFields = '
                NULL as lastAppointment,
                0 as totalAppointments,
                0 as countPendingAppointments,
                0 as countAppointmentBookings,
                0 as countEventBookings,
            ';

            $statsJoins = '';

            $having = '';

            if ($joinWithBookings) {
                $params[':bookingPendingStatus'] = BookingStatus::PENDING;

                $statsFields = "
                    MAX(app.bookingStart) as lastAppointment,
                    COUNT(cb.id) as totalAppointments,
                    SUM(case when cb.status = :bookingPendingStatus then 1 else 0 end) as countPendingAppointments,
                    COUNT(DISTINCT CASE WHEN cb.appointmentId IS NOT NULL THEN cb.id ELSE NULL END) as countAppointmentBookings,
                    COUNT(DISTINCT CASE WHEN cb.appointmentId IS NULL THEN cb.id ELSE NULL END) as countEventBookings,
                ";

                $statsJoins = "
                    LEFT JOIN {$bookingsTable} cb ON u.id = cb.customerId
                    LEFT JOIN {$appointmentsTable} app ON app.id = cb.appointmentId
                ";

                if (!empty($criteria['noShow'])) {
                    $having = "HAVING (SUM(case when cb.status = 'no-show' then 1 else 0 end)) " . ($criteria['noShow'] === "3" ? '>=' : '=') . ":noShow";

                    $params[':noShow'] = $criteria['noShow'];
                }
            }

            $where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

            $limit = $this->getLimit(
                !empty($criteria['page']) ? (int)$criteria['page'] : 0,
                (int)$itemsPerPage
            );

            $statement = $this->connection->prepare(
                "SELECT 
                u.id as id,
                u.status as status,
                u.firstName as firstName,
                u.lastName as lastName,
                u.email as email,
                u.phone as phone,
                u.countryPhoneIso AS countryPhoneIso,
                u.gender as gender,
                u.externalId as externalId,
                u.translations as translations,
                IF(u.birthday IS NOT NULL, u.birthday , '') as birthday,
                u.note as note,
                u.customFields as customFields,
                {$statsFields}
                IF(wpu.display_name IS NOT NULL, wpu.display_name , '') as wpName
                FROM {$this->table} as u
                LEFT JOIN {$wpUserTable} wpu ON u.externalId = wpu.id
                {$statsJoins}
                {$where}
                GROUP BY u.id
                {$having}
                {$order}
                {$limit}"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        $items = [];
        foreach ($rows as $row) {
            $row['id']         = (int)$row['id'];
            $row['externalId'] = $row['externalId'] === null ? $row['externalId'] : (int)$row['externalId'];
            $row['lastAppointment'] = $row['lastAppointment'] ?
                DateTimeService::getCustomDateTimeFromUtc($row['lastAppointment']) : $row['lastAppointment'];

            // Fix for customFields being encoded multiple times
            if ($row['customFields'] && !is_array(json_decode($row['customFields'], true))) {
                $row['customFields'] = null;
            }

            $items[(int)$row['id']] = $row;
        }

        return $items;
    }

    /**
     * @param $criteria
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function getCount($criteria)
    {
        $wpUserTable = WPUsersTable::getTableName();

        $params = [
            ':type_customer' => AbstractUser::USER_ROLE_CUSTOMER,
            ':type_admin'    => AbstractUser::USER_ROLE_ADMIN,
            ':statusVisible' => Status::VISIBLE,
        ];

        $where = [
            'u.type IN (:type_customer, :type_admin)',
            'u.status = :statusVisible'
        ];

        if (!empty($criteria['search'])) {
            $params[':search1'] = $params[':search2'] = $params[':search3'] = $params[':search4'] =
                "%{$criteria['search']}%";

            $where[] = "((CONCAT(u.firstName, ' ', u.lastName) LIKE :search1
                            OR wpu.display_name LIKE :search2
                            OR u.email LIKE :search3
                            OR u.note LIKE :search4))";
        }

        if (!empty($criteria['customers'])) {
            $customersCriteria = [];

            foreach ((array)$criteria['customers'] as $key => $customerId) {
                $params[":customerId$key"] = $customerId;
                $customersCriteria[]       = ":customerId$key";
            }

            $where[] = 'u.id IN (' . implode(', ', $customersCriteria) . ')';
        }

        if (!empty($criteria['noShow'])) {
            $bookingsTable = CustomerBookingsTable::getTableName();

            $params[':noShow'] = $criteria['noShow'];

            $where[] =
                "(SELECT COUNT(*) FROM {$bookingsTable} cb WHERE cb.status='no-show' AND cb.customerId=u.id)" .
                ($criteria['noShow'] === "3" ? '>=' : '=') . " :noShow";
        }

        $where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        try {
            $statement = $this->connection->prepare(
                "SELECT COUNT(*) as count
                FROM {$this->table} as u 
                LEFT JOIN {$wpUserTable} wpu ON u.externalId = wpu.id
                $where
                "
            );

            $statement->execute($params);

            $rows = $statement->fetch()['count'];
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        return $rows;
    }

    /**
     * @param string $phone
     *
     * @return array
     * @throws QueryExecutionException
     * @throws \Exception
     */
    public function getByPhoneNumber($phone)
    {
        try {
            $params[':phone'] = '+' . $phone;

            $statement = $this->connection->prepare(
                "SELECT 
                u.id as id,
                u.status as status,
                u.firstName as firstName,
                u.lastName as lastName,
                u.email as email,
                u.phone as phone,
                u.countryPhoneIso AS countryPhoneIso,
                u.gender as gender,
                u.externalId as externalId,
                IF(u.birthday IS NOT NULL, u.birthday , '') as birthday,
                u.note as note 
                FROM {$this->table} as u
                WHERE u.type = 'customer' AND phone = :phone"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        return $rows;
    }

    /**
     * @param array $criteria
     *
     * @return Collection
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws InvalidArgumentException
     */
    public function getByCriteria($criteria = [])
    {
        $params = [];

        $where = [];

        $fields = '
            u.id AS id,
            u.type AS type,
            u.firstName AS firstName,
            u.lastName AS lastName,
            u.email AS email,
            u.note AS note,
            u.phone AS phone,
            u.gender AS gender,
            u.birthday AS birthday,
            u.status AS status
        ';

        if (!empty($criteria['ids'])) {
            $queryIds = [];

            foreach ($criteria['ids'] as $index => $value) {
                $param = ':id' . $index;

                $queryIds[] = $param;

                $params[$param] = $value;
            }

            $where[] = 'u.id IN (' . implode(', ', $queryIds) . ')';
        }

        $where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        try {
            $statement = $this->connection->prepare(
                "SELECT
                {$fields}
                FROM {$this->table} u
                {$where}"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find event by id in ' . __CLASS__, $e->getCode(), $e);
        }

        $items = new Collection();

        foreach ($rows as $row) {
            $row['type'] = 'customer';

            $items->addItem(call_user_func([static::FACTORY, 'create'], $row), $row['id']);
        }

        return $items;
    }
}
