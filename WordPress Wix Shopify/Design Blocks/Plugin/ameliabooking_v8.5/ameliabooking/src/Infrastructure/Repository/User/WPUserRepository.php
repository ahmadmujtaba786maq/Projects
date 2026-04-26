<?php

namespace AmeliaBooking\Infrastructure\Repository\User;

use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Connection;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\UsersTable;

/**
 * Class WPUserRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\User
 */
class WPUserRepository
{
    /** @var \PDO */
    protected $connection;

    /** @var string */
    protected $table;

    /** @var string */
    protected $metaTable;

    /** @var string */
    protected $prefix;

    /**
     * WPUserRepository constructor.
     *
     * @param Connection $connection
     * @param            $table
     * @param            $metaTable
     * @param            $prefix
     */
    public function __construct(Connection $connection, $table, $metaTable, $prefix)
    {
        $this->connection = $connection();
        $this->table      = $table;
        $this->metaTable  = $metaTable;
        $this->prefix     = $prefix;
    }

    /**
     * @param array $params
     * @param array $excludeUserIds
     *
     * @return array
     * @throws QueryExecutionException
     */
    public function getAllNonRelatedWPUsers($params, $excludeUserIds)
    {
        $excludeIdParams = [];

        try {
            $params = [
                ':id'   => $params['id'],
                ':role' => '%wpamelia-' . $params['role'] . '%',
            ];

            foreach ((array)$excludeUserIds as $key => $id) {
                $excludeIdParams[":id$key"] = $id;
            }

            $whereExcludeIds = $excludeIdParams ?
                ' AND wp_user.ID NOT IN (' . implode(',', $excludeIdParams) . ')' : '';

            $usersTable = UsersTable::getTableName();

            global $wpdb;

            $statement = $this->connection->prepare(
                "SELECT wp_user.ID AS value, wp_user.display_name AS label
                FROM {$this->table} AS wp_user 
                INNER JOIN {$this->metaTable} AS wp_usermeta
                ON wp_user.ID = wp_usermeta.user_id 
                WHERE 
                wp_user.ID NOT IN (
                    SELECT externalId 
                    FROM $usersTable 
                    WHERE externalId IS NOT NULL
                    AND externalId != :id
                ) 
                {$whereExcludeIds}
                AND wp_usermeta.meta_key = '{$wpdb->prefix}capabilities' 
                AND wp_usermeta.meta_value LIKE :role
                GROUP BY wp_user.ID"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        $items = [];
        foreach ($rows as $row) {
            $row['value'] = (int)$row['value'];
            $items[]      = $row;
        }

        return $items;
    }
}
