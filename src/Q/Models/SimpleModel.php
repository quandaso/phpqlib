<?php
/**
 * @author quantm.tb@gmail.com
 * @date: 2/10/2017 10:07 AM
 */

namespace Q\Models;


use Q\Helpers\Queryable;

/**
 * Class BaseModel.
 *
 * @method static Queryable where($field, $opt = null, $value = null)
 * @method static static find($id)
 * @method static static first()
 */
class SimpleModel implements \ArrayAccess, \JsonSerializable,  \Serializable
{
    protected $readonly = false;
    protected $_db = null;
    private $_data = array();
    private $_originalData = array();
    private $_id;
    private  $_fillableMap = array();
    protected $primaryKey = 'id';
    protected $fillable = null;
    protected $table = null;
    private static $pdo;

    private static $queryableCache = [];
    private static function getQueryableInstance() {
        if (isset(self::$queryableCache[static::class])) {
            return self::$queryableCache[static::class];
        }

        self::$queryableCache[static::class] = new Queryable(self::$pdo, static::class);
        return self::$queryableCache[static::class];
    }

    /**
     * Model constructor.
     * @param null|array|object $id Specified id or data
     * @param string|null $table
     * @throws \Exception
     */
    public function __construct($id = null, $table = null)
    {
        if (empty ($this->table)) {
            $this->table = (string) $table;
        }

        if (empty ($this->table)) {
            $classes = explode('\\', get_class($this));
            $this->table = self::camelCaseToUnderscore(end($classes));
        }

        if (!empty ($this->fillable)) {
            $this->_fillableMap = array_flip($this->fillable);
        }

        if (empty(self::$pdo)) {
            $dbConfig = config('mysql')['default'];
            extract($dbConfig);

            self::$pdo = new \PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4", $username, $password);
            self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }

        $this->_db = self::getQueryableInstance();

        if (is_object($id)) {
            $id = (array) $id;
        }

        if (is_array($id)) {
            $this->_data = $id;
            $this->_originalData = $id;
            if (isset ($this->_data[$this->primaryKey])) {
                $this->_id = $this->_data[$this->primaryKey];
            }

        } else {
            $this->_id = $id;
            if (isset ($id)) {
                $data = $this->_db->table($this->table)
                    ->where($this->primaryKey, $id)
                    ->fetch('array');
                if (!empty ($data)) {
                    $this->_data = $data;
                    $this->_originalData = $data;
                }
            }
        }
    }

    /**
     * @param \PDO $pdo
     */
    public static function setPdoInstance(\PDO $pdo) {
        self::$pdo = $pdo;
    }

    /**
     * Set entry readonly or not
     * @param bool $readonly
     */
    public function setReadonly($readonly = true) {
        $this->readonly = $readonly;
    }

    /**
     * Check if entry is readonly or not
     * @return bool
     */
    public function isReadonly() {
        return $this->readonly;
    }


    /**
     * Sets record data
     * @param array $data
     * @throws \Exception
     */
    public function fill(array $data)
    {
        foreach ($data as $k => $v) {
            if (!isset ($this->_fillableMap[$k])) {
                throw new \Exception('Could not fill: ' . $k);
            }
        }

        if (isset ($data[$this->primaryKey])) {
            $this->_id = $data[$this->primaryKey];
        }
        if (empty ($this->_data)) {
            $this->_data = $data;
        } else {
            $this->_data = array_merge($this->_data, $data);
        }
    }

    /**
     * @param $name
     * @return null
     */
    public function getAttr($name)
    {
        if (isset ($this->_data[$name])) {
            return $this->_data[$name];
        }
        return null;
    }

    /**
     * @param $name
     * @param $value
     */
    public function setAttr($name, $value)
    {
        if ($name === $this->primaryKey) {
            $this->_id = $value;
        }
        $this->_data[$name] = $value;
    }
    /**
     * Gets record attr
     * @param $name
     * @return null
     */
    public function __get($name)
    {
        return $this->getAttr($name);
    }
    /**
     * Sets record attr
     * @param $name
     * @return null
     * @throws \Exception
     */
    public function __set($name, $value)
    {
        $this->setAttr($name, $value);
    }
    /**
     * Implements __unset() method.
     * @param $name
     */
    public function __unset($name)
    {
        unset($this->_data[$name]);
    }
    /**
     * Implements __isset() method.
     * @param $name
     * @return boolean
     */
    public function __isset($name)
    {
        return isset ($this->_data[$name]);
    }

    /**
     * Gets changed data by diff $_originalData and $_data
     * @return array
     */
    public function getChangedData()
    {
        if (empty($this->_data[$this->primaryKey])) {
            return $this->_data;
        }

        $changedData = array();
        foreach ($this->_data as $k => $v) {
            if (!array_key_exists($k, $this->_originalData) || $this->_originalData[$k] !== $v) {
                $changedData[$k] = $v;
            }
        }
        return $changedData;
    }

    /**
     * @param array $fields
     * @return int
     */
    public function save(array $fields = array())
    {
        if ($this->readonly) {
            throw new \Exception('This entity is readonly');
        }

        $_data = array();
        if (!empty ($fields)) {
            foreach ($fields as $field) {
                if (array_key_exists($field, $this->_data)) {
                    $_data[$field] = $this->_data[$field];
                }
            }
        } else {
            $_data = $this->getChangedData();

        }

        if (!empty ($_data)) {
            if (empty ($this->_id)) {

                $this->_id = $this->_db->table($this->table)->insert($_data);
                $this->_data[$this->primaryKey] = $this->_id;
            } else {
                $this->_db->table($this->table)
                    ->where($this->primaryKey, $this->_id)
                    ->update($_data);
                if (isset ($_data[$this->primaryKey])) {
                    $this->_id = $_data[$this->primaryKey];
                }
            }
            $this->_originalData = $this->_data;
        }
        return $this->_id;
    }
    /**
     * Deletes data
     */
    public function delete()
    {
        if (!empty ($this->_id)) {
            $this->_db->table($this->table)->where($this->primaryKey, $this->_id)->delete();
            unset ($this->_id);
            unset($this->_data[$this->primaryKey]);
            $this->_originalData = array();
            return true;
        }
        return false;
    }

    /**
     * Reloads data from db
     */
    public function reload()
    {
        $this->_data = $this->_db
            ->table($this->table)
            ->where($this->primaryKey, $this->_id)
            ->fetch('array');
        $this->_originalData = $this->_data;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $_data = array();
        foreach ($this->_data as $name => $value) {
            $_data[$name] = $this->getAttr($name);
        }
        return $_data;
    }

    /**
     * @return Queryable
     */
    public function getQueryable() {
        return $this->_db;
    }

    /**
     * @return array
     */
    public function getLastSql()
    {
        return array($this->_db->getLastSql(), $this->_db->getBindValues());
    }

    /**
     * Gets original data
     * @return array
     */
    public function getOriginalData()
    {
        return $this->_originalData;
    }

    /**
     * Gets dirty data
     * @return array
     */
    public function getDirtyData()
    {
        return $this->_data;
    }

    /**
     * @return null | string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Implements ArrayAccess
     */
    public function offsetSet($offset, $value)
    {
        $this->setAttr($offset, $value);
    }

    /**
     * Implements ArrayAccess
     */
    public function offsetExists($offset)
    {
        return isset($this->_data[$offset]);
    }

    /**
     * Implements ArrayAccess
     */
    public function offsetUnset($offset)
    {
        unset($this->_data[$offset]);
    }

    /**
     * Implements ArrayAccess
     */
    public function offsetGet($offset)
    {
        return $this->getAttr($offset);
    }

    /**
     * Implements JsonSerializable
     */
    public function jsonSerialize ()
    {
        return $this->toArray();
    }

    public function serialize() {
        return serialize($this->_data);
    }
    public function unserialize($data) {
        $this->_data = unserialize($data);
    }

    /**
     * Gets table scheme
     * @param $table
     * @param string $dbConfig
     * @deprecated Use Queryable::scheme
     * @return array
     */
    public static function scheme($table, $dbConfig = 'master')
    {
        $scheme = (array) db($dbConfig)->query('SHOW COLUMNS FROM ' . $table)->get();
        return array_map(function($item) { return $item->Field; }, $scheme);
    }

    /**
     * Creates new instance from given data source
     * @param $data
     * @return static | null
     */
    public static function create($data = null)
    {
        if (empty ($data)) {
            return null;
        }
        $instance = new static();
        if (!is_array($data)) {
            $data = (array) $data;
        }
        $instance->fill($data);
        return $instance;
    }

    /**
     * @param $field
     * @param $value
     * @return mixed
     */
    public static function findBy($field, $value)
    {
        $obj = new static();
        $instance = db()->table($obj->table);
        $instance->setFetchClass(static::class);

        return $instance->where($field, $value)->first();
    }

    /**
     * Converts a string to camelCase
     * @param $string
     * @param string $delimiter
     * @return mixed|string
     */
    public static function strCamelCase($string, $delimiter = '-')
    {
        $str = str_replace(' ', '', ucwords(str_replace($delimiter, ' ', $string)));
        $str = lcfirst($str);
        return $str;
    }

    /**
     * Converts a string to from camelCase to underscore_case
     * @param $input
     * @return string
     */
    public static function camelCaseToUnderscore($input)
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }

    public static function query(): Queryable
    {
        $obj = new static();

        $instance = db()->table($obj->table);
        $instance->setFetchClass(static::class);
        return $instance;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws \Exception
     */
    public static function __callStatic($name, $arguments)
    {
        $obj = new static();

        if (method_exists($obj->_db, $name) || preg_match('/^(where|orWhere)(.+)/', $name)) {
            $instance =  db()->table($obj->table);
            $instance->setFetchClass(static::class);
            return call_user_func_array([$instance, $name], $arguments);
        }

        throw new \Exception('Method ' . static::class . '::' . $name . ' does not exist');
    }
}
