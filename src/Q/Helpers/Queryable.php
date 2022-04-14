<?php

/**
 * MySQL Query Builder (query syntax is like Laravel Query Builder)
 * @author quantm
 * @date: 26/03/2016 11:13
 */

namespace Q\Helpers;

class Queryable {

    const VERSION = '1.1';

    private static $queryHistory = [];
    private $_lastSql = null;
    private $_pdo = null;
    private $_limit = null;
    private $_offset = null;
    private $_order = [];
    private $_group = [];
    private $_table = null;
    private $_stmt = null;
    private $fetchClass = 'array';
    private $config = null;
    private $autoScheme = false;
    private $fromStates = array();
    private $selectFields = array();
    private $whereStates = array();
    private $havingStates = array();
    private $joinStates = array();
    private $values = array();
    private static $allTables = null;
    private static $tableSchemes = [];
    private $operators = array(
        '>' => true,
        '<' => true,
        '>=' => true,
        '<=' => true,
        '=' => true,
        '!=' => true,
        '<>' => true,
        'IN' => true,
        'LIKE' => true,
        'BETWEEN' => true,
        'NOT BETWEEN' => true,
        'NOT IN' => true,
        'IS NULL' => true,
        'IS NOT NULL' => true
    );
    private $joinTypes = array(
        'INNER' => true,
        'LEFT' => true,
        'RIGHT' => true
    );

    /**
     * Queryable constructor.
     * @param  $config \PDO
     * @param  $fetchClass
     * @param  bool $autoScheme
     */
    public function __construct(\PDO $pdo = null, $fetchClass = 'array', $autoScheme = false) {
        $this->fetchClass = $fetchClass;
        $this->autoScheme = $autoScheme;
        if ($pdo) {
            $this->_pdo = $pdo;
            $this->_pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }
    }

    public function setFetchClass(string $class)
    {
        $this->fetchClass = $class;
    }

    /*     * *
     * Sets config manually
     * @param array $config
     */

    public function setConfig(array $config) {
        $this->config = $config;
    }

    /**
     * Access table from magic method
     * @param $name string
     * @return $this
     */
    public function __get($name) {
        return $this->table($name);
    }

    /**
     * Create new connection
     */
    public function connect() {
        if ($this->_pdo === null) {

            $config = $this->config;

            $db = $config['database'];
            $host = $config['host'];
            $username = $config['username'];
            $port = isset($config['port']) ? $config['port'] : 3306;
            $password = isset($config['password']) ? $config['password'] : '';

            $this->_pdo = new \PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $username, $password);
            $this->_pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }

        return $this;
    }

    /**
     * @deprecated
     * @param $args string | array
     * @param $_ string | array
     * @return $this
     */
    public function from($args, $_ = null) {
        $this->initQuery();
        $this->fromStates = func_get_args();
        $this->_table = $this->fromStates[0];
        return $this;
    }

    /**
     * Resets all query states
     */
    protected function initQuery() {
        $this->_lastSql = null;
        $this->_limit = null;
        $this->_offset = null;
        $this->_order = array();
        $this->_group = array();
        $this->_table = null;
        $this->_stmt = null;

        $this->fromStates = array();
        $this->selectFields = array();
        $this->whereStates = array();
        $this->havingStates = array();
        $this->values = array();
        $this->joinStates = array();
    }

    public function setAutoScheme($autoScheme) {
        $this->autoScheme = (bool) $autoScheme;
    }

    /**
     * @param $table
     * @return $this
     * @throws \Exception
     */
    public function table(string $table) {

        $instance = db(true);
        $instance->initQuery();
        $instance->_table = $table;
        $instance->fromStates = array($table);
        return $instance;
    }

    /**
     * @param $field array|string
     * @param $_ array|string
     * @return $this
     */
    public function select($field, $_ = null) {
        if (is_array($field)) {
            $this->selectFields = array_merge($this->selectFields, $field);
        } else {
            $this->selectFields = array_merge($this->selectFields, func_get_args());
        }

        return $this;
    }

    /**
     * @param $sql
     * @param array $values
     * @return $this
     */
    public function selectRaw($sql, array $values = array()) {
        $this->selectFields[] = $this->raw($sql, $values);
        return $this;
    }

    /**
     * @param string $type
     * @param $field
     * @param null $opt
     * @param null $value
     * @return $this
     * @throws \Exception
     */
    private function addWhereQuery($type = 'AND', $field, $opt = null, $value = null) {
        if ($field instanceof \Closure) {
            if ($opt !== null) {
                throw new \Exception("$opt query can not be a callback");
            }

            $callback = $field;

            $this->whereStates[] = array(
                'type' => $type,
                'query' => $callback
            );

            return $this;
        }

        if (func_num_args() === 3) {
            $value = $opt;
            $opt = '=';
        } else {
            if (!isset($this->operators[$opt])) {
                throw new \Exception('Invalid operator: ' . $opt);
            }
        }

        $opt = trim(strtoupper($opt));

        $this->whereStates[] = array(
            'type' => $type,
            'field' => $field,
            'operator' => $opt,
            'value' => $value
        );

        return $this;
    }

    /**
     * @return $this
     */
    public function whereNotBetween($field, $fromValue, $toValue) {
        return $this->addWhereQuery('AND', $field, 'NOT BETWEEN', [$fromValue, $toValue]);
    }

    /**
     * @return $this
     */
    public function orWhereNotBetween($field, $fromValue, $toValue) {
        return $this->addWhereQuery('OR', $field, 'NOT BETWEEN', [$fromValue, $toValue]);
    }

    /**
     * @return $this
     */
    public function orWhereNotNull($field) {
        return $this->addWhereQuery('OR', $field, 'IS NOT NULL', null);
    }

    /**
     * @return $this
     */
    public function orWhereNull($field) {
        return $this->addWhereQuery('OR', $field, 'IS NULL', null);
    }

    /**
     * @return $this
     */
    public function whereNotNull($field) {
        return $this->addWhereQuery('AND', $field, 'IS NOT NULL', null);
    }

    /**
     * @return $this
     */
    public function whereNull($field) {
        return $this->addWhereQuery('AND', $field, 'IS NULL', null);
    }

    /**
     * @return $this
     */
    public function whereBetween($field, $fromValue, $toValue) {
        return $this->addWhereQuery('AND', $field, 'BETWEEN', [$fromValue, $toValue]);
    }

    /**
     * @return $this
     */
    public function orWhereBetween($field, $fromValue, $toValue) {
        return $this->addWhereQuery('OR', $field, 'BETWEEN', [$fromValue, $toValue]);
    }

    /**
     * @return $this
     */
    public function whereIn($field, array $values) {
        return $this->addWhereQuery('AND', $field, 'IN', $values);
    }

    /**
     * @return $this
     */
    public function whereNotIn($field, array $values) {
        return $this->addWhereQuery('AND', $field, 'NOT IN', $values);
    }

    /**
     * @return $this
     */
    public function whereNotEmpty($field) {
        return $this->whereNotNull($field)->where($field, '!=', '');
    }

    /**
     * @return $this
     */
    public function orWhereNotEmpty($field) {
        return $this->orWhere(function (Queryable $query) use($field) {
                    return $query->whereNotNull($field)->where($field, '!=', '');
                });
    }

    /**
     * @return $this
     */
    public function orWhereNotIn($field, array $values) {
        return $this->addWhereQuery('OR', $field, 'NOT IN', $values);
    }

    /**
     * @return $this
     */
    public function whereLike($field, $value) {
        return $this->addWhereQuery('AND', $field, 'LIKE', $value);
    }

    /**
     * @return $this
     */
    public function orWhereLike($field, $value) {
        return $this->addWhereQuery('OR', $field, 'LIKE', $value);
    }

    /**
     * @return $this
     */
    public function orWhereIn($field, array $values) {
        return $this->addWhereQuery('OR', $field, 'IN', $values);
    }

    /**
     * @param $field
     * @param null $opt
     * @param null $value
     * @return $this
     */
    public function where($field, $opt = null, $value = null) {
        if (func_num_args() === 2) {
            return $this->addWhereQuery('AND', $field, $opt);
        }

        return $this->addWhereQuery('AND', $field, $opt, $value);
    }

    /**
     * @param $field
     * @param null $opt
     * @param null $value
     * @return $this
     */
    public function orWhere($field, $opt = null, $value = null) {
        if (func_num_args() === 2) {
            return $this->addWhereQuery('OR', $field, $opt);
        }

        return $this->addWhereQuery('OR', $field, $opt, $value);
    }

    /**
     * @param $sql
     * @param array $values
     * @return $this
     */
    public function whereRaw($sql, array $values = array()) {
        $this->whereStates[] = array(
            'type' => 'AND',
            'rawSql' => $this->raw($sql, $values)
        );

        return $this;
    }

    /**
     * @param $sql
     * @param array $values
     * @return $this
     */
    public function orWhereRaw($sql, array $values = array()) {
        $this->whereStates[] = array(
            'type' => 'OR',
            'rawSql' => $this->raw($sql, $values)
        );

        return $this;
    }

    /**
     * @param $field
     * @param $keyword
     * @return $this
     */
    public function whereSearch($field, $keyword) {
        if (is_array($keyword)) {
            $keywords = $keyword;
        } else {
            $keywords = preg_split('/\s+/', trim((string) $keyword));
        }

        $this->where(function(Queryable $db) use($keywords, $field) {
            foreach ($keywords as $k) {
                $db->where($field, 'LIKE', '%' . $k . '%');
            }
        });
        return $this;
    }

    /**
     * @param $field
     * @param $keyword
     * @return $this
     */
    public function orWhereSearch($field, $keyword) {
        if (is_array($keyword)) {
            $keywords = $keyword;
        } else {
            $keywords = preg_split('/\s+/', trim((string) $keyword));
        }

        $this->orWhere(function(Queryable $db) use($keywords, $field) {
            foreach ($keywords as $k) {
                $db->where($field, 'LIKE', '%' . $k . '%');
            }
        });
        return $this;
    }

    /**
     * @param $table
     * @param $onRawCondition
     * @param array $values
     * @param string $type
     * @return $this
     * @throws \Exception
     */
    public function joinRaw($table, $onRawCondition, array $values = array(), $type = 'INNER') {
        if (!isset($this->joinTypes[strtoupper($type)])) {
            throw new \Exception('Invalid join type');
        }

        $this->joinStates[] = array(
            'type' => $type,
            'table' => $table,
            'onRaw' => $this->raw($onRawCondition, $values),
        );

        return $this;
    }

    /**
     * @return $this
     */
    public function innerJoinRaw($table, $onRawCondition, array $values = array()) {
        return $this->joinRaw($table, $onRawCondition, $values, 'INNER');
    }

    /**
     * @return $this
     */
    public function leftJoinRaw($table, $onRawCondition, array $values = array()) {
        return $this->joinRaw($table, $onRawCondition, $values, 'LEFT');
    }

    /**
     * @return $this
     */
    public function rightJoinRaw($table, $onRawCondition, array $values = array()) {
        return $this->joinRaw($table, $onRawCondition, $values, 'RIGHT');
    }

    /**
     * @param $table
     * @param $key
     * @param $operator
     * @param $value
     * @param string $type
     * @return $this
     * @throws \Exception
     */
    public function join($table, $key, $operator, $value, $type = 'INNER') {
        if (!isset($this->joinTypes[strtoupper($type)])) {
            throw new \Exception('Invalid join type');
        }

        if (!isset($this->operators[$operator])) {
            throw new \Exception('Invalid operator: ' . $operator);
        }

        $this->joinStates[] = array(
            'type' => $type,
            'table' => $table,
            'key' => $key,
            'operator' => $operator,
            'value' => $value
        );

        return $this;
    }

    /**
     * @return $this
     */
    public function innerJoin($table, $key, $operator, $value) {
        return $this->join($table, $key, $operator, $value, 'INNER');
    }

    /**
     * @return $this
     */
    public function leftJoin($table, $key, $operator, $value) {
        return $this->join($table, $key, $operator, $value, 'LEFT');
    }

    /**
     * @return $this
     */
    public function rightJoin($table, $key, $operator, $value) {
        return $this->join($table, $key, $operator, $value, 'RIGHT');
    }

    /**
     * @param $limit
     * @return $this
     */
    public function limit($limit) {
        if ($limit !== null) {
            $this->_limit = (int) $limit;
        }

        return $this;
    }

    /**
     * @param $offset
     * @return $this
     */
    public function offset($offset) {
        if ($offset !== null) {
            $this->_offset = (int) $offset;
        }

        return $this;
    }

    /**
     * @param $field
     * @param string $direction
     * @throws \Exception
     * @return $this
     */
    public function orderBy($field, $direction = 'ASC') {
        $direction = strtoupper($direction);

        if ($direction !== 'ASC' && $direction !== 'DESC') {
            throw new \Exception('Invalid order direction');
        }

        $this->_order[] = array(
            'orderBy' => $field,
            'direction' => $direction
        );

        return $this;
    }

    /**
     * @param $field array|string
     * @param $_ array|string
     * @return $this
     */
    public function groupBy($field = null, $_ = null) {

        $this->_group = array_merge($this->_group, func_get_args());

        return $this;
    }

    /**
     * @param string $type
     * @param $field
     * @param null $opt
     * @param null $value
     * @return $this
     * @throws \Exception
     */
    private function addHavingQuery($type = 'AND', $field, $opt = null, $value = null) {
        if ($field instanceof \Closure) {

            $callback = $field;

            $this->havingStates[] = array(
                'type' => $type,
                'query' => $callback
            );

            return $this;
        }


        if (func_num_args() === 3) {
            $value = $opt;
            $opt = '=';
        } else {
            if (!isset($this->operators[$opt])) {
                throw new \Exception('Invalid operator: ' . $opt);
            }
        }

        $opt = trim(strtoupper($opt));

        $this->havingStates[] = array(
            'type' => $type,
            'field' => $field,
            'operator' => $opt,
            'value' => $value
        );

        return $this;
    }

    /**
     * @param $field
     * @param null $opt
     * @param null $value
     * @return $this
     * @throws \Exception
     */
    public function having($field, $opt = null, $value = null) {
        if (func_num_args() === 2) {
            return $this->addHavingQuery('AND', $field, $opt);
        }

        return $this->addHavingQuery('AND', $field, $opt, $value);
    }

    /**
     * @param $field
     * @return $this
     * @throws \Exception
     */
    public function havingNull($field) {
        return $this->addHavingQuery('AND', $field, 'IS NULL', null);
    }

    /**
     * @param $field
     * @return $this
     * @throws \Exception
     */
    public function orHavingNull($field) {
        return $this->addHavingQuery('OR', $field, 'IS NULL', null);
    }

    /**
     * @param $field
     * @return $this
     * @throws \Exception
     */
    public function havingNotNull($field) {
        return $this->addHavingQuery('AND', $field, 'IS NOT NULL', null);
    }

    /**
     * @param $field
     * @return $this
     * @throws \Exception
     */
    public function orHavingNotNull($field) {
        return $this->addHavingQuery('OR', $field, 'IS NOT NULL', null);
    }

    /**
     * @param $field
     * @param null $opt
     * @param null $value
     * @return $this
     * @throws \Exception
     */
    public function orHaving($field, $opt = null, $value = null) {
        if (func_num_args() === 2) {
            return $this->addHavingQuery('OR', $field, $opt);
        }

        return $this->addHavingQuery('OR', $field, $opt, $value);
    }

    /**
     * @param $sql
     * @param array $values
     * @return $this
     */
    public function havingRaw($sql, array $values = array()) {
        $this->havingStates[] = array(
            'type' => 'AND',
            'rawSql' => $this->raw($sql, $values)
        );

        return $this;
    }

    /**
     * @param $sql
     * @param array $values
     * @return $this
     */
    public function orHavingRaw($sql, array $values = array()) {
        $this->havingStates[] = array(
            'type' => 'OR',
            'rawSql' => $this->raw($sql, $values)
        );

        return $this;
    }

    /**
     * @param bool $hasHaving
     * @return string
     * @throws \Exception
     */
    private function getHavingState($hasHaving = true) {
        if (empty($this->havingStates)) {
            return '';
        }

        $havingStates = array();

        foreach ($this->havingStates as $i => $having) {
            $first = count($havingStates) === 0;

            if (isset($having['field'])) {
                $having['field'] = $this->quoteColumn($having['field']);
            }

            $statement = '';

            if (isset($having['rawSql'])) {
                $statement = $having['rawSql']->sql;
                $this->mergeValues($having['rawSql']->values);
            } else if (isset($having['query'])) {
                $query = new static();
                $having['query']($query);

                if (!empty($query->havingStates)) {
                    $statement = '(' . $query->getHavingState(false) . ')';
                    $this->mergeValues($query->values);
                }
            } else if ($having['operator'] === 'IS NULL' || $having['operator'] === 'IS NOT NULL') {
                $statement = $having['field'] . ' ' . $having['operator'];
            } else if ($having['operator'] === 'BETWEEN' || $having['operator'] === 'NOT BETWEEN') {
                if (count($having['value']) < 2) {
                    throw new \Exception('Missing BETWEEN values');
                }

                $statement = $having['field'] . ' ' . $having['operator'] . ' ? AND ?';
                $this->mergeValues($having['value']);
            } else if ($having['operator'] === 'IN' || $having['operator'] === 'NOT IN') {
                if (!isset($having['value'])) {
                    throw new \Exception('Missing WHERE in values');
                }

                $inValueSet = array();

                foreach ($having['value'] as $v) {
                    $this->pushValue($v);
                    $inValueSet[] = '?';
                }

                $statement = $having['field'] . ' ' . $having['operator'] . ' (' . implode(',', $inValueSet) . ')';
            } else {
                $statement = $having['field'] . ' ' . $having['operator'] . ' ?';
                $this->pushValue($having['value']);
            }

            if (!$first && $statement) {
                $statement = $having['type'] . ' ' . $statement;
            }

            $havingStates[] = $statement;
        }

        return $hasHaving ? 'HAVING ' . implode(' ', $havingStates) : implode(' ', $havingStates);
    }

    /**
     * @return string
     */
    private function getJoinState() {
        if (empty($this->joinStates)) {
            return '';
        }

        $joins = array();

        foreach ($this->joinStates as $join) {
            if (isset($join['onRaw'])) {
                $raw = $join['onRaw'];
                $joins[] = $join['type'] . ' JOIN ' . $this->quoteColumn($join['table'])
                        . ' ON ' . $raw->sql;
                $this->values = array_merge($this->values, $raw->values);
            } else {
                $joins[] = $join['type'] . ' JOIN '
                        . $this->quoteColumn($join['table'])
                        . ' ON '
                        . $this->quoteColumn($join['key']) . ' '
                        . $join['operator'] . ' ' . $this->quoteColumn($join['value']);
            }
        }

        return implode(' ', $joins);
    }

    /**
     * @return string
     */
    private function getOrderByState() {
        if (!empty($this->_order)) {
            $orderByStates = array();

            foreach ($this->_order as $order) {
                $orderByStates[] = $this->quoteColumn($order['orderBy']) . ' ' . $order['direction'];
            }

            return 'ORDER BY ' . implode(',', $orderByStates);
        }

        return '';
    }

    /**
     * @return string
     */
    private function getLimitState() {
        if ($this->_limit !== null && $this->_offset === null) {
            return 'LIMIT ' . $this->_limit;
        }

        if ($this->_limit !== null && $this->_offset !== null) {
            return 'LIMIT ' . $this->_offset . ',' . $this->_limit;
        }

        return '';
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function toSql() {
        if (empty($this->fromStates)) {
            throw new \Exception('Missing FROM statement');
        }

        $query = array(
            $this->getSelectState(),
            $this->getFromState(),
            $this->getJoinState(),
            $this->getWhereState(),
            $this->getGroupByState(),
            $this->getHavingState(),
            $this->getOrderByState(),
            $this->getLimitState(),
        );

        return trim(implode(' ', array_filter($query)));
    }

    /**
     * @param array $data
     * @return string
     * @throws \Exception
     */
    public function getInsertSql(array $data) {
        if (empty($this->_table)) {
            throw new \Exception('Table name is not specified');
        }

        if (empty($data)) {
            throw new \Exception('Insert data can not be empty');
        }

        $insertStates = array();
        $valueStates = array();
        $this->values = array();

        foreach ($data as $k => $v) {
            $insertStates[] = $this->quoteColumn($k);
            $valueStates[] = '?';
            $this->pushValue($v);
        }

        $query = array(
            'INSERT INTO ' . $this->quoteColumn($this->_table) . '(' . implode(',', $insertStates) . ')',
            'VALUES(' . implode(',', $valueStates) . ')',
        );

        return trim(implode(' ', $query));
    }

    /**
     * @param array $data
     * @return string
     * @throws \Exception
     */
    public function getUpdateSql(array $data) {
        if (empty($this->_table)) {
            throw new \Exception('Table name is not specified');
        }

        if (empty($data)) {
            throw new \Exception('Update data can not be empty');
        }

        $updateSetStates = array();
        $this->values = array();

        foreach ($data as $k => $v) {
            if (self::isRawObject($v)) {
                $updateSetStates[] = $this->quoteColumn($k) . '=' . $v->sql;
                $this->mergeValues($v->values);
            } else {
                $updateSetStates[] = $this->quoteColumn($k) . '=?';
                $this->pushValue($v);
            }
        }

        $query = array(
            'UPDATE ' . $this->quoteColumn($this->_table),
            'SET ' . implode(',', $updateSetStates),
            $this->getWhereState(),
        );

        return trim(implode(' ', $query));
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getDeleteSql() {

        if (empty($this->_table)) {
            throw new \Exception('Table name is not specified');
        }

        $this->values = array();

        $query = array(
            'DELETE FROM ' . $this->quoteColumn($this->_table),
            $this->getWhereState()
        );

        return trim(implode(' ', $query));
    }

    /**
     * @param array $data
     * @return int
     * @throws \Exception
     */
    public function update(array $data) {
        if ($this->autoScheme) {
            $this->autoScheme($data);
        }

        $this->query($this->getUpdateSql($data), $this->values);

        return $this->_stmt->rowCount();
    }

    /**
     * @param $data
     * @return int
     * @throws \Exception
     */
    public function insert(array $data) {
        if ($this->autoScheme) {
            $tables = $this->getAllTables();

            if (!in_array($this->_table, $tables)) {
                $this->createTable($this->_table);
            }

            $this->autoScheme($data);
        }

        $this->query($this->getInsertSql($data), $this->values);
        return $this->_pdo->lastInsertId();
    }

    /**
     * Auto created table scheme if not exist
     * @param $data
     */
    private function autoScheme($data) {
        $scheme = array_flip($this->scheme());
        $allSql = [];

        foreach ($data as $columnName => $v) {
            if (!isset($scheme[$columnName])) {

                if (is_int($v)) {
                    $dataType = 'INT';
                } else if (is_double($v)) {
                    $dataType = 'DOUBLE';
                } else if ($v instanceof \DateTime) {
                    $dataType = 'DATETIME';
                } else if (is_bool($v)) {
                    $dataType = 'TINYINT(1) UNSIGNED';
                } else {
                    $dataType = 'TEXT';
                }

                $allSql[] = sprintf('ALTER TABLE `%s` ADD `%s` %s', $this->_table, $columnName, $dataType);
                self::$tableSchemes[$this->_table][] = $columnName;
            }
        }

        if (!empty($allSql)) {
            $this->query(implode(';', $allSql));
        }
    }

    /**
     * @param $table
     */
    public function createTable($table) {
        $sql = sprintf('CREATE TABLE `%s`( `id` INT UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY (`id`) )', $table);
        $this->query($sql);
        self::$allTables[] = $table;
    }

    /**
     * Notes that insert columns is based on array_keys of $data[0]
     * All others keys after $data[0] will be ignored
     * @param $data
     * @return int
     * @throws \Exception
     */
    public function bulkInsert(array $data) {
        if (empty($data)) {
            throw new \Exception('Can not insert empty data');
        }

        if (!is_array($data[0])) {
            throw new \Exception('Invalid data');
        }

        $insertColumns = array_keys($data[0]);


        $insertStates = array();

        $valueStates = array();

        foreach ($insertColumns as $column) {
            $insertStates[] = $this->quoteColumn($column);
            if ($data[0][$column] === null) {
                $valueStates[] = 'NULL';
            } else {
                $valueStates[] = "'" . $this->quoteValue($data[0][$column]) . "'";
            }
        }

        $query = array(
            'INSERT INTO ' . $this->quoteColumn($this->_table) . '(' . implode(',', $insertStates) . ')',
            'VALUES(' . implode(',', $valueStates) . ')',
        );

        $otherValues = array();

        for ($i = 1; $i < count($data); $i++) {
            $row = $data[$i];

            $valueStates = array();

            foreach ($insertColumns as $column) {
                if (isset($row[$column])) {
                    $valueStates[] = "'" . $this->quoteValue($row[$column]) . "'";
                } else {
                    $valueStates[] = 'NULL';
                }
            }
            $otherValues[] = '(' . implode(',', $valueStates) . ')';
        }

        if (empty($otherValues)) {
            $sql = implode(' ', $query);
        } else {
            $sql = implode(' ', $query) . ',' . implode(',', $otherValues);
        }

        $this->query($sql);
        return $this->_stmt->rowCount();
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function delete() {
        $this->query($this->getDeleteSql(), $this->values);
        return $this->_stmt->rowCount();
    }

    /**
     * @return string
     */
    private function getSelectState() {
        if (empty($this->selectFields)) {
            return 'SELECT *';
        }

        $selectFields = array_map(array($this, 'quoteColumn'), $this->selectFields);

        return 'SELECT ' . implode(',', $selectFields);
    }

    /**
     * @return string
     */
    private function getFromState() {
        $fromTables = array_map(array($this, 'quoteColumn'), $this->fromStates);
        return 'FROM ' . implode(',', $fromTables);
    }

    /**
     * @param $hasWhere
     * @return string
     * @throws \Exception
     */
    private function getWhereState($hasWhere = true) {
        if (empty($this->whereStates)) {
            return '';
        }

        $whereStates = array();

        foreach ($this->whereStates as $i => $where) {
            $first = count($whereStates) === 0;

            if (isset($where['field'])) {
                $where['field'] = $this->quoteColumn($where['field']);
            }

            $statement = '';

            if (isset($where['rawSql'])) {
                $statement = $where['rawSql']->sql;
                $this->mergeValues($where['rawSql']->values);
            } else if (isset($where['query'])) {
                $query = new static();

                $where['query']($query);

                if (!empty($query->whereStates)) {
                    $statement = '(' . $query->getWhereState(false) . ')';
                    $this->mergeValues($query->values);
                }
            } else if ($where['operator'] === 'IS NULL' || $where['operator'] === 'IS NOT NULL') {
                $statement = $where['field'] . ' ' . $where['operator'];
            } else if ($where['operator'] === 'BETWEEN' || $where['operator'] === 'NOT BETWEEN') {
                if (count($where['value']) < 2) {
                    throw new \Exception('Missing BETWEEN values');
                }

                $statement = $where['field'] . ' ' . $where['operator'] . ' ? AND ?';
                $this->mergeValues($where['value']);
            } else if ($where['operator'] === 'IN' || $where['operator'] === 'NOT IN') {
                if (!isset($where['value'])) {
                    throw new \Exception('Missing WHERE in values');
                }

                $inValueSet = array();

                foreach ($where['value'] as $v) {
                    $this->pushValue($v);
                    $inValueSet[] = '?';
                }

                $statement = $where['field'] . ' ' . $where['operator'] . ' (' . implode(',', $inValueSet) . ')';
            } else {
                $statement = $where['field'] . ' ' . $where['operator'] . ' ?';
                $this->pushValue($where['value']);
            }

            if (!$first && $statement) {
                $statement = $where['type'] . ' ' . $statement;
            }

            $whereStates[] = $statement;
        }

        return $hasWhere ? 'WHERE ' . implode(' ', $whereStates) : implode(' ', $whereStates);
    }

    /**
     * @return string
     */
    private function getGroupByState() {
        if (!empty($this->_group)) {
            $groupByStates = array_map(array($this, 'quoteColumn'), $this->_group);

            return 'GROUP BY ' . implode(',', $groupByStates);
        }

        return '';
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function get($limit = null, $offset = null) {
        $this->limit($limit);
        $this->offset($offset);

        return $this->fetchAll($this->fetchClass);
    }

    /**
     * @param string $fetchClass
     * @deprecated Use fetch() instead of this
     * @return null
     */
    public function fetchFirst($fetchClass = 'array') {
        return $this->fetch($fetchClass);
    }

    /**
     * @param string $fetchClass
     * @return mixed
     */
    public function fetch($fetchClass = 'array') {
        if ($this->_stmt === null) {
            $this->limit(1);
            $this->query($this->toSql(), $this->values);
        }

        $entry = $this->_stmt->fetch(\PDO::FETCH_ASSOC);
        $this->_stmt = null;

        if ($entry === false) {
            return null;
        }

        if ($fetchClass === 'array') {
            return $entry;
        } else if ($fetchClass === 'stdClass') {
            return (object) $entry;
        } else if (is_string($fetchClass)) {
            return new $fetchClass($entry);
        }

        return $entry;
    }

    /**
     * @param $fetchClass 'array'|'stdClass'
     * @return mixed
     * @throws \Exception
     */
    public function fetchAll($fetchClass = 'array') {
        if ($this->_stmt === null) {
            $this->query($this->toSql(), $this->values);
        }

        if ($fetchClass === 'stdClass') {
            $entries = $this->_stmt->fetchAll(\PDO::FETCH_CLASS);
            $this->_stmt = null;
            return $entries;
        }

        $entries = $this->_stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->_stmt = null;

        if ($fetchClass === 'array') {
            return $entries;
        } else if (is_string($fetchClass)) {
            $result = array();

            foreach ($entries as $entry) {
                $obj = new $fetchClass($entry);
                $result[] = $obj;
            }

            return $result;
        }

        return $entries;
    }

    /**
     * @return null | array | \stdClass
     * @throws \Exception
     */
    public function first() {
        return $this->fetch($this->fetchClass);
    }

    /**
     * @throws \Exception
     */
    public function firstOrFail() {
        $entry = $this->first();

        if (!$entry) {
            throw new \Exception('Entry not found');
        }

        return $entry;
    }

    /**
     * Returns [key => value]
     * @param $value
     * @param $key
     * @return array
     */
    public function lists($key, $value = null) {
        $result = $this->fetchAll('array');
        $lists = array();

        if (!empty($result)) {
            foreach ($result as $data) {
                $lists[$data[$key]] = ($value === null) ? $data : $data[$value];
            }
        }

        return $lists;
    }

    /**
     * @param $func
     * @param $field
     * @return int
     */
    private function aggregate($func, $field) {
        $this->limit(1);
        $raw = $func . '(' . $this->quoteColumn($field) . ')';
        $this->selectFields = array($this->raw($raw));
        $result = $this->fetchAll('array');

        if (count($result) === 0) {
            return null;
        }

        return $result[0][$raw];
    }

    /**
     * @param $field
     * @return int
     */
    public function count($field = '*') {
        return (int) $this->aggregate('COUNT', $field);
    }

    /**
     * @param $field
     * @return int
     */
    public function exists() {
        $query = "SELECT EXISTS (SELECT 1 {$this->getFromState()} {$this->getWhereState()} ) AS e";

        $exist = $this->query($query, $this->getBindValues())->fetchAll();

        $obj = (object) current($exist);
        return $obj->e ? TRUE : FALSE;
    }

    /**
     * @param $field
     * @return int
     */
    public function max($field) {
        return $this->aggregate('MAX', $field);
    }

    /**
     * @param $field
     * @return int
     */
    public function min($field) {
        return $this->aggregate('MIN', $field);
    }

    /**
     * @param $field
     * @return int
     */
    public function avg($field) {
        return $this->aggregate('AVG', $field);
    }

    /**
     * @param $field
     * @return int
     */
    public function sum($field) {
        return $this->aggregate('SUM', $field);
    }

    /**
     * Executes sql query, all query is call this method
     * @param $sql
     * @param array $values
     * @return $this
     */
    public function query($sql, $values = array()) {
        $this->connect();
        $this->beforeQuery($sql, $values);
        $this->_lastSql = $sql;
        $this->_stmt = $this->_pdo->prepare($sql);
        $this->bindValues($values);
        $this->_stmt->execute();
        $this->afterQuery($sql, $values);
        self::$queryHistory[] = array($sql, $values);

        return $this;
    }

    /**
     * @return array
     */
    public static function getQueryHistory() {
        $queries = [];
        foreach (self::$queryHistory as $query) {
            $queries[] = preg_replace('/\s+/', ' ', self::toInterpolatedSql($query[0], $query[1]));
        }
        return $queries;
    }

    /**
     * Before query callback
     * @param $sql
     * @param array $values
     */
    protected function beforeQuery($sql, $values = array()) {

    }

    /**
     * After query callback
     * @param $sql
     * @param array $values
     */
    protected function afterQuery($sql, $values = array()) {

    }

    /**
     * Iterates all table records
     * @param $callback
     * @param int $chunkSize
     * @throws \Exception
     */
    public function chunk($chunkSize, $callback) {
        if (empty($this->_table)) {
            throw new \Exception('Table name is not specified');
        }

        if (!is_callable($callback)) {
            throw new \Exception('Invalid $callback argument');
        }

        $offset = 0;
        $limit = $chunkSize;
        $entries = $this->table($this->_table)->get($limit, $offset);

        while (!empty($entries)) {
            $callback($entries, $offset);

            $offset += $limit;
            $entries = $this->table($this->_table)->get($limit, $offset);
        }
    }

    /**
     * Iterates all table records
     * @param $callback
     * @param int $chunkSize Number of record for each query
     * @throws \Exception
     */
    public function each($callback, $chunkSize = 1000) {

        $this->chunk($chunkSize, function($entries, $offset) use ($callback) {
            foreach ($entries as $i => $e) {
                $callback($e, $offset + $i);
            }
        });
    }

    /**
     * @param int $page
     * @param int $perPage
     * @return Paginator
     */
    public function paginate($page = null, $perPage = 25) {
        if ($page === null) {
            $page = @$_GET['page'];
        }
        if ($page <= 0) {
            $page = 1;
        }

        $this->_limit = null;
        $this->_offset = null;

        $query = clone $this;

        $count = $query->count();
        $lastPage = ceil($count / $perPage);
        $limit = $perPage;
        $offset = ($page - 1) * $perPage;
        $data = $this->get($limit, $offset);

        return new Paginator($data, $page, $count, $perPage);
    }

    /**
     * @return string | null
     */
    public function getLastSql() {
        return $this->_lastSql;
    }

    /**
     * @return int
     */
    public function getRowCount() {
        if ($this->_stmt) {
            return $this->_stmt->rowCount();
        }
        return 0;
    }

    /**
     * @return array
     */
    public function getLastQuery() {
        return array($this->_lastSql, $this->values);
    }

    /**
     * Tries to convert value to string if value is an array or stdClass or \DateTime object
     * @param mixed $value
     * @return string
     */
    private function convertPrepareValue($value) {
        if (is_array($value) || $value instanceof \stdClass) {
            $value = json_encode($value);
        } else if ($value instanceof \DateTime) {
            $value = $value->format('Y-m-d H:i:s');
        } else if (is_bool($value)) {
            $value = $value ? 1 : 0;
        } else if ($value !== null && !is_int($value)) {
            $value = (string) $value;
        }

        return $value;
    }

    /**
     * Push value to pdo prepare params
     * @param mixed $value
     */
    private function pushValue($value) {
        $this->values[] = $this->convertPrepareValue($value);
    }

    /**
     * Merged value to pdo prepare params
     * @param array $values
     */
    private function mergeValues(array $values) {
        foreach ($values as $v) {
            $this->values[] = $this->convertPrepareValue($v);
        }
    }

    /**
     * @return array
     */
    public function getBindValues() {
        return $this->values;
    }

    /**
     * @param $sql
     * @param array $values
     * @return object
     */
    public function raw($sql, array $values = array()) {
        return (object) array(
                    'rawSql' => true,
                    'sql' => (string) $sql,
                    'values' => $values
        );
    }

    /**
     * Binds values
     * @param array | null $values
     */
    private function bindValues(array $values = array()) {
        $this->values = $values;

        foreach ($this->values as $i => &$value) {
            if (is_string($value)) {
                $this->_stmt->bindValue($i + 1, $value, \PDO::PARAM_STR);
            } else if (is_int($value)) {
                $this->_stmt->bindValue($i + 1, $value, \PDO::PARAM_INT);
            } else if ($value === null) {
                $this->_stmt->bindValue($i + 1, null, \PDO::PARAM_NULL);
            } else {
                $value = $this->convertPrepareValue($value);
                $this->_stmt->bindValue($i + 1, $value, \PDO::PARAM_STR);
            }
        }
    }

    /**
     * @return \PDO
     */
    public function pdo() {
        $this->connect();
        return $this->_pdo;
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        $this->connect();
        self::$queryHistory[] = array('START TRANSACTION', array());
        $this->_pdo->beginTransaction();
    }

    /**
     * Commit
     */
    public function commit() {
        self::$queryHistory[] = array('COMMIT', array());
        $this->_pdo->commit();
    }

    /**
     * rollback
     */
    public function rollBack() {
        self::$queryHistory[] = array('ROLLBACK', array());
        $this->_pdo->rollBack();
    }

    /**
     * @param $callback
     */
    public function transaction($callback) {
        if (is_callable($callback)) {
            $this->beginTransaction();
            $callback($this);
            $this->commit();
        }
    }

    /**
     * @param $field
     * @return string
     */
    private function quoteColumn($field) {
        if (self::isRawObject($field)) {
            $this->values = array_merge($this->values, $field->values);
            return $field->sql;
        }

        if ($field === '*') {
            return $field;
        }

        if (strpos($field, '.') !== false) {
            list($table, $field) = explode('.', $field);
            return "`" . str_replace("`", "``", $table) . "`." . "`" . str_replace("`", "``", $field) . "`";
        }

        return "`" . str_replace("`", "``", $field) . "`";
    }

    /**
     * @param $value
     * @param string $field
     * @return mixed
     * @throws \Exception
     */
    public function find($value, $field = 'id') {
        if (empty($this->_table)) {
            throw new \Exception('Table name is not specified');
        }

        return $this->where($field, $value)->first();
    }

    /**
     * @param bool $fullScheme
     * @return array
     * @throws \Exception
     */
    public function scheme($fullScheme = false) {

        if (empty($this->_table)) {
            throw new \Exception('Table name is not specified');
        }

        if (!$fullScheme && isset(self::$tableSchemes[$this->_table])) {
            return self::$tableSchemes[$this->_table];
        }

        $scheme = $this->query('SHOW COLUMNS FROM ' . $this->quoteColumn($this->_table))->fetchAll();

        if ($fullScheme) {
            return $scheme;
        }

        self::$tableSchemes[$this->_table] = array_map(function($item) {
            return $item['Field'];
        }, $scheme);
        return self::$tableSchemes[$this->_table];
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function getStatus() {
        if (empty($this->_table)) {
            throw new \Exception('Table name is not specified');
        }

        return $this->query('SHOW TABLE STATUS WHERE NAME = ?', [$this->_table])->fetchFirst();
    }

    /**
     * @return mixed
     */
    public function getAllTables() {
        if (isset(self::$allTables)) {
            return self::$allTables;
        }

        self::$allTables = array_map('current', $this->query('SHOW TABLES')->fetchAll());

        return self::$allTables;
    }

    /**
     * @param $obj
     * @return bool
     */
    private static function isRawObject($obj) {
        return is_object($obj) && isset($obj->rawSql, $obj->sql, $obj->values);
    }

    /**
     * @return string
     */
    public function toString() {
        return self::toInterpolatedSql($this->toSql(), $this->values);
    }

    /**
     * @return string
     */
    public function __toString() {
        return self::toInterpolatedSql($this->toSql(), $this->values);
    }

    /**
     * Interpolate Query:  for debug only
     * @param string $query Sql query
     * @param array $params Bind params
     * @return string The interpolated query
     */
    public static function toInterpolatedSql($query, $params) {

        $keys = array();
        $values = $params;

        # build a regular expression for each parameter
        foreach ($params as $key => $value) {
            if (is_string($key)) {
                $keys[] = '/:' . $key . '/';
            } else {
                $keys[] = '/[?]/';
            }

            if (is_array($value) || $value instanceof \stdClass) {
                $value = json_encode($value);
            } else if ($value instanceof \DateTime) {
                $value = $value->format('Y-m-d H:i:s');
            } else if (is_bool($value)) {
                $value = $value ? 1 : 0;
            } else {
                $value = (string) $value;
            }


            if (is_string($value)) {
                $values[$key] = "'" . self::quoteValue($value) . "'";
            }

            if (is_null($value)) {
                $values[$key] = 'NULL';
            }
        }

        $query = preg_replace($keys, $values, $query, 1, $count);

        return $query;
    }

    /**
     * Quotes value, for debug only
     * @param $inp
     * @return array|mixed
     */
    public static function quoteValue($inp) {
        if (is_array($inp)) {
            return array_map(__METHOD__, $inp);
        }

        if (!empty($inp) && is_string($inp)) {
            return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $inp);
        }

        return $inp;
    }

}
