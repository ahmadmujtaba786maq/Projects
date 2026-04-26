<?php

namespace AmeliaBooking\Infrastructure\DB\MySQLi;

use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use mysqli;

/**
 * Class Statement
 *
 * @package AmeliaBooking\Infrastructure\DB\MySQLi
 */
class Statement
{
    /** @var mysqli $mysqli */
    private $mysqli;

    /** @var Query $query */
    private $query;

    /** @var Result $result */
    private $result;

    /** @var array $params */
    private $params = [];

    /**
     * @param mysqli $mysqli
     * @param Result $result
     * @param Query  $query
     */
    public function __construct($mysqli, $result, $query)
    {
        $this->mysqli = $mysqli;
        $this->result = $result;
        $this->query  = $query;
    }

    /**
     *
     * @return mixed
     */
    public function fetch()
    {
        return $this->result->getValue()->fetch_assoc();
    }

    /**
     *
     * @return mixed
     */
    public function fetchAll()
    {
        $rows = [];

        while ($row = $this->result->getValue()->fetch_assoc()) {
            $rows[] = $row;
        }

        return $rows;
    }
    /**
     *
     * @return mixed
     */
    public function rowCount()
    {
        return  $this->result->getValue()->num_rows;
    }


    /**
     * @param array $params
     *
     * @return mixed
     */
    public function execute($params = [])
    {
        $this->params = array_merge($this->params, $params);

        $paramsKeys   = [];
        $paramsValues = [];
        $paramsTypes  = [];

        foreach ($this->params as $key => $value) {
            $index = strpos($this->query->getValue(), $key);

            $paramsKeys[$index]   = $key;
            $paramsValues[$index] = $value;
            $paramsTypes[$index]  = gettype($this->params[$key])[0];
        }

        usort(
            $paramsKeys,
            function ($a, $b) {
                return strlen($b) - strlen($a);
            }
        );

        ksort($paramsValues);
        ksort($paramsTypes);

        $referencedQueryParams = [];

        foreach ($paramsValues as $key => &$value) {
            $referencedQueryParams[$key] = &$value;
        }

        $parsedQuery = str_replace($paramsKeys, '?', $this->query->getValue());

        if ($stmt = $this->mysqli->prepare($parsedQuery)) {
            if ($referencedQueryParams) {
                call_user_func_array(
                    array($stmt, 'bind_param'),
                    array_merge([str_replace('N', 'i', implode('', $paramsTypes))], $referencedQueryParams)
                );
            }

            $success = $stmt->execute();

            $this->result->setValue($stmt->get_result());
        } else {
            throw new \Exception();
        }

        $this->params = [];

        return $stmt && $success;
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return mixed
     */
    public function bindParam($key, $value)
    {
        $this->params[$key] = $value;
    }
}
