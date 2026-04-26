<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Repository\Tax;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Tax\Tax;
use AmeliaBooking\Domain\Factory\Booking\Event\EventFactory;
use AmeliaBooking\Domain\Factory\Tax\TaxFactory;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Connection;
use AmeliaBooking\Infrastructure\Repository\AbstractStatusRepository;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\EventsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Tax\TaxesToEntitiesTable;

/**
 * Class TaxRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Tax
 */
class TaxRepository extends AbstractStatusRepository
{
    public const FACTORY = TaxFactory::class;

    /** @var string */
    protected $taxesToEntitiesTable;

    /**
     * @param Connection $connection
     * @param string     $table
     * @throws InvalidArgumentException
     */
    public function __construct(
        Connection $connection,
        $table
    ) {
        parent::__construct($connection, $table);

        $this->taxesToEntitiesTable = TaxesToEntitiesTable::getTableName();
    }

    /**
     * @param Tax $entity
     *
     * @return string|false
     * @throws QueryExecutionException
     */
    public function add($entity)
    {
        $data = $entity->toArray();

        $params = [
            ':name'        => $data['name'],
            ':amount'      => $data['amount'],
            ':type'        => $data['type'],
            ':status'      => $data['status'],
            ':allServices' => !empty($data['allServices']) ? 1 : 0,
            ':allEvents'   => !empty($data['allEvents']) ? 1 : 0,
            ':allPackages' => !empty($data['allPackages']) ? 1 : 0,
            ':allExtras'   => !empty($data['allExtras']) ? 1 : 0,
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO
                {$this->table} 
                (
                `name`, `amount`, `type`, `status`, `allServices`, `allEvents`, `allPackages`, `allExtras`
                ) VALUES (
                :name, :amount, :type, :status, :allServices, :allEvents, :allPackages, :allExtras
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
     * @param int $id
     * @param Tax $entity
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function update($id, $entity)
    {
        $data = $entity->toArray();

        $params = [
            ':name'        => $data['name'],
            ':amount'      => $data['amount'],
            ':type'        => $data['type'],
            ':status'      => $data['status'],
            ':allServices' => !empty($data['allServices']) ? 1 : 0,
            ':allEvents'   => !empty($data['allEvents']) ? 1 : 0,
            ':allPackages' => !empty($data['allPackages']) ? 1 : 0,
            ':allExtras'   => !empty($data['allExtras']) ? 1 : 0,
            ':id'          => $id,
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET
                `name` = :name,
                `amount` = :amount,
                `type` = :type,
                `status` = :status,
                `allServices` = :allServices,
                `allEvents` = :allEvents,
                `allPackages` = :allPackages,
                `allExtras` = :allExtras
                WHERE
                id = :id"
            );

            $response = $statement->execute($params);
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to save data in ' . __CLASS__ . $e->getMessage());
        }

        if (!$response) {
            throw new QueryExecutionException('Unable to save data in ' . __CLASS__);
        }

        return $response;
    }

    /**
     * @param int $id
     *
     * @return Tax
     * @throws QueryExecutionException
     * @throws NotFoundException
     * @throws InvalidArgumentException
     */
    public function getById($id)
    {
        try {
            $statement = $this->connection->prepare(
                "SELECT
                    t.id AS tax_id,
                    t.name AS tax_name,
                    t.amount AS tax_amount,
                    t.type AS tax_type,
                    t.status AS tax_status,
                    t.allServices AS tax_allServices,
                    t.allEvents AS tax_allEvents,
                    t.allPackages AS tax_allPackages,
                    t.allExtras AS tax_allExtras,
                    te.entityId AS tax_entityId,
                    te.entityType AS tax_entityType
                FROM {$this->table} t
                LEFT JOIN {$this->taxesToEntitiesTable} te ON te.taxId = t.id AND te.entityType != 'event'
                WHERE t.id = :taxId"
            );

            $statement->bindParam(':taxId', $id);

            $statement->execute();

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find by id in ' . __CLASS__, $e->getCode(), $e);
        }

        if (!$rows) {
            throw new NotFoundException('Data not found in ' . __CLASS__);
        }

        $eventsTable = EventsTable::getTableName();

        /** @var Tax $tax */
        $tax = call_user_func([static::FACTORY, 'createCollection'], $rows)->getItem($id);

        $statement = $this->connection->prepare(
            "SELECT
                   e.id AS id,
                   e.name AS name
                FROM {$eventsTable} e
                INNER JOIN {$this->taxesToEntitiesTable} te ON te.entityId = e.id AND te.entityType = 'event'
                WHERE te.taxId = :taxId"
        );

        $statement->bindParam(':taxId', $id);

        $statement->execute();

        $rows = $statement->fetchAll();

        $tax->setEventList(new Collection());

        foreach ($rows as $row) {
            $tax->getEventList()->addItem(EventFactory::create($row));
        }

        return $tax;
    }

    /**
     * @param array $criteria
     *
     * @return Collection
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getWithEntities($criteria)
    {
        $where = !empty($criteria['ids']) ? "WHERE t.id IN (" . implode(', ', $criteria['ids']) . ")" : '';

        try {
            $statement = $this->connection->prepare(
                "SELECT
                    t.id AS tax_id,
                    t.name AS tax_name,
                    t.amount AS tax_amount,
                    t.type AS tax_type,
                    t.status AS tax_status,
                    t.allServices AS tax_allServices,
                    t.allEvents AS tax_allEvents,
                    t.allPackages AS tax_allPackages,
                    t.allExtras AS tax_allExtras,
                    te.entityId AS tax_entityId,
                    te.entityType AS tax_entityType
                    FROM {$this->table} t
                    LEFT JOIN {$this->taxesToEntitiesTable} te ON te.taxId = t.id
                    {$where}
                    ORDER BY t.id"
            );

            $statement->execute();

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        /** @var Collection $taxes */
        $taxes = call_user_func([static::FACTORY, 'createCollection'], $rows);

        $taxesIds = array_column($taxes->toArray(), 'id');

        if ($taxesIds && !empty($criteria['events'])) {
            $eventsTable = EventsTable::getTableName();

            $statement = $this->connection->prepare(
                "SELECT
                    e.id AS id,
                    e.name AS name
                FROM {$this->taxesToEntitiesTable} te
                INNER JOIN {$eventsTable} e ON te.entityId = e.id AND te.entityType = 'event'
                WHERE te.taxId IN (" . implode(', ', $taxesIds) . ")"
            );

            $statement->execute();

            $rows = $statement->fetchAll();

            /** @var Collection $events */
            $events = new Collection();

            foreach ($rows as $row) {
                if (!$events->keyExists($row['id'])) {
                    $events->addItem(EventFactory::create($row), $row['id']);
                }
            }

            /** @var Tax $tax */
            foreach ($taxes->getItems() as $tax) {
                /** @var Tax $taxEvent */
                foreach ($tax->getEventList()->getItems() as $taxEvent) {
                    if ($events->keyExists($taxEvent->getId()->getValue())) {
                        /** @var Event $event */
                        $event = $events->getItem($taxEvent->getId()->getValue());

                        $taxEvent->setName($event->getName());
                    }
                }
            }
        }

        return $taxes;
    }

    /**
     * @param array $criteria
     * @param int   $itemsPerPage
     *
     * @return Collection
     * @throws QueryExecutionException
     */
    public function getFiltered($criteria, $itemsPerPage)
    {
        $params = [];

        $where = [];

        if (!empty($criteria['search'])) {
            $params[':search'] = "%{$criteria['search']}%";

            $where[] = 'UPPER(t.name) LIKE UPPER(:search)';
        }

        if (!empty($criteria['services'])) {
            $queryServices = [];

            foreach ($criteria['services'] as $index => $value) {
                $param = ':service' . $index;

                $queryServices[] = $param;

                $params[$param] = $value;
            }

            $where[] = "t.id IN (
                    SELECT taxId FROM {$this->taxesToEntitiesTable} 
                    WHERE entityId IN (" . implode(', ', $queryServices) . ") AND entityType = 'service'
                )";
        }

        if (!empty($criteria['extras'])) {
            $queryExtras = [];

            foreach ($criteria['extras'] as $index => $value) {
                $param = ':extra' . $index;

                $queryExtras[] = $param;

                $params[$param] = $value;
            }

            $where[] = "t.id IN (
                    SELECT taxId FROM {$this->taxesToEntitiesTable} 
                    WHERE entityId IN (" . implode(', ', $queryExtras) . ") AND entityType = 'extra'
                )";
        }

        if (!empty($criteria['events'])) {
            $queryEvents = [];

            foreach ($criteria['events'] as $index => $value) {
                $param = ':event' . $index;

                $queryEvents[] = $param;

                $params[$param] = $value;
            }

            $where[] = "t.id IN (
                    SELECT taxId FROM {$this->taxesToEntitiesTable} 
                    WHERE entityId IN (" . implode(', ', $queryEvents) . ") AND entityType = 'event'
                )";
        }

        if (!empty($criteria['packages'])) {
            $queryPackages = [];

            foreach ((array)$criteria['packages'] as $index => $value) {
                $param = ':package' . $index;

                $queryPackages[] = $param;

                $params[$param] = $value;
            }

            $where[] = "t.id IN (
                    SELECT taxId FROM {$this->taxesToEntitiesTable} 
                    WHERE entityId IN (" . implode(', ', $queryPackages) . ") AND entityType = 'package'
                )";
        }


        $where = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $limit = $this->getLimit(
            !empty($criteria['page']) ? (int)$criteria['page'] : 0,
            (int)$itemsPerPage
        );

        try {
            $statement = $this->connection->prepare(
                "SELECT
                    t.id AS tax_id,
                    t.name AS tax_name,
                    t.amount AS tax_amount,
                    t.type AS tax_type,
                    t.status AS tax_status,
                    t.allServices AS tax_allServices,
                    t.allEvents AS tax_allEvents,
                    t.allPackages AS tax_allPackages,
                    t.allExtras AS tax_allExtras
                FROM {$this->table} t
                {$where}
                {$limit}"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        return call_user_func([static::FACTORY, 'createCollection'], $rows);
    }

    /**
     * @param array $criteria
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function getCount($criteria)
    {
        $params = [];

        $where = [];

        if (!empty($criteria['search'])) {
            $params[':search'] = "%{$criteria['search']}%";

            $where[] = 't.name LIKE :search';
        }

        if (!empty($criteria['services'])) {
            $queryServices = [];

            foreach ((array)$criteria['services'] as $index => $value) {
                $param = ':service' . $index;

                $queryServices[] = $param;

                $params[$param] = $value;
            }

            $where[] = "t.id IN (
                SELECT taxId FROM {$this->taxesToEntitiesTable} 
                WHERE entityId IN (" . implode(', ', $queryServices) . ") AND entityType = 'service'
            )";
        }

        if (!empty($criteria['events'])) {
            $queryEvents = [];

            foreach ((array)$criteria['events'] as $index => $value) {
                $param = ':event' . $index;

                $queryEvents[] = $param;

                $params[$param] = $value;
            }

            $where[] = "t.id IN (
                SELECT taxId FROM {$this->taxesToEntitiesTable} 
                WHERE entityId IN (" . implode(', ', $queryEvents) . ") AND entityType = 'event'
            )";
        }

        if (!empty($criteria['packages'])) {
            $queryPackages = [];

            foreach ((array)$criteria['packages'] as $index => $value) {
                $param = ':package' . $index;

                $queryPackages[] = $param;

                $params[$param] = $value;
            }

            $where[] = "t.id IN (
                SELECT taxId FROM {$this->taxesToEntitiesTable} 
                WHERE entityId IN (" . implode(', ', $queryPackages) . ") AND entityType = 'package'
            )";
        }

        $where = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        try {
            $statement = $this->connection->prepare(
                "SELECT COUNT(*) AS count
                FROM {$this->table} t
                {$where}"
            );

            $statement->execute($params);

            $row = $statement->fetch()['count'];
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        return $row;
    }
}
