<?php
namespace Framework\Models;

use Framework\Injectables\Injector;
use Exception;

class Model {

    /**
     * Instance of the connector from Connector class
     *
     * @var object
     */
    private $connector;

    /**
     * Schema is a string that contains the conditions
     * for CRUD operations.
     *
     * @var string
     */
    private $schema = "";

    /**
     * Schema that holds the sort functionality
     *
     * @var string
     */
    private $sortSchema = "";

    /**
     * Schema that holds the limit for the number of query elements
     * Works for paginating when you have a huge database query
     *
     * @var string
     */
    private $limitSchema = "";

    /**
     * Schema that holds the keys for the values that
     * that needs inserting or updated in the Database
     *
     * @var string
     */
    private $insertKeySchema = "";

    /**
     * Schema that holds the values for the insert or
     * update querys
     *
     * @var string
     */
    private $insertValueSchema = "";

    /**
     * Create schema for updating rows in database
     *
     * @var string
     */
    private $updateSchema = "";

    /**
     * Construct method requires an instance of
     * Framework\Database\Connector supplied by the Injector
     *
     * @var object
     */
    public function __construct()
    {
        $this->connect();
    }

    private function connect()
    {
        $connector = Injector::resolve("Connector");
        $this->connector = $connector->getConnector();
    }

    public function getTable()
    {
        return $this->table;
    }

    /**
     * This is the method that let's you chain conditions
     * before executing the database query.
     *
     * @var strings
     * @return self
     */
    public function where($valueA, $operand = null, $valueB)
    {
        if($this->schema == "")
        {
            $this->schema .= $valueA . $operand . "'" . $valueB . "'";
        } else {
            $this->schema .= " AND " . $valueA . $operand . "'" . $valueB . "'";
        }
        return $this;
    }

    /**
     * The same as "where" but introduces the OR element in the query.
     *
     * @var strings
     * @return self
     */
    public function orWhere($valueA, $operand, $valueB)
    {
        if($this->schema == "")
        {
            $this->schema .= $valueA . $operand . "'" . $valueB . "' OR ";
        } else {
            $this->schema .= " OR " . $valueA . $operand . "'" . $valueB . "'";
        }
        return $this;
    }

    /**
     * Insert data in the database
     *
     * @param array of keys and values
     * @return boolean
     */
    public function create(array $array)
    {
        $this->connect();
        $this->createSchema($array);
        $statement = $this->connector->prepare("INSERT INTO " . $this->getTable() . " ( " . $this->insertKeySchema . " ) VALUES ( " . $this->insertValueSchema . " )")->execute();
        $this->close();
        return $statement;
    }

    /**
     * Create the schema for the Create or Update functions
     *
     * @return null
     */
    public function createSchema(array $array)
    {
        $insertKeySchema = "";
        $insertValueSchema = "";
        $i = 0;

        /*
        $array = array_filter($array, function($index)
        {
            return array_search($index, $this->editables) > -1;
        }, ARRAY_FILTER_USE_KEY);
        */

        foreach($array as $index => $item)
        {
            if($i < count($array) - 1)
            {
                $insertKeySchema .= $index . ', ';
                $insertValueSchema .= "'" . $item . "', ";
            } else {
                $insertKeySchema .= $index;
                $insertValueSchema .= "'" . $item . "'";
            }
            $i++;
        }
        $this->insertKeySchema = $insertKeySchema;
        $this->insertValueSchema = $insertValueSchema;
    }

    /**
     * Update entry in the database
     *
     * @param array of keys and values
     * @return boolean
     */
    public function update(array $array)
    {
        $this->connect();
        $this->updateSchema($array);
        if($this->schema == "")
        {
            $this->schema = "id = '" . $this->id . "'";
        }
        $statement = $this->connector->prepare("UPDATE " . $this->getTable() . " SET " . $this->updateSchema . " WHERE " . $this->schema)->execute();
        $this->close();
        return $statement;
    }

    /**
     * Create the schema that holds the key = value for update
     *
     * @param array of keys and values
     * @return bull
     */
    private function updateSchema(array $array)
    {
        $updateSchema = '';
        $i = 0;
        foreach($array as $index => $item)
        {
            if($i < count($array) - 1)
            {
                $updateSchema .= $index . "= '" . $item . "', ";

            } else {
                $updateSchema .= $index . "= '" . $item . "'";
            }
            $i++;
        }
        $this->updateSchema = $updateSchema;
    }

    /**
     * Get the number of rows in a particular table
     * Works best with LIMIT and OFFSET to paginate the result
     *
     * @return object of model
     */
    public function count()
    {
        $this->connect();
        if($this->schema !== "")
        {
            $statement = $this->connector->query("SELECT COUNT(*) as count FROM " . $this->getTable() . " WHERE " . $this->schema)->fetchObject();
        } else {
            $statement = $this->connector->query("SELECT COUNT(*) as count FROM " . $this->getTable())->fetchObject();
        }
        $this->close();
        return intval($statement->count);
    }

    /**
     * Get one single row from the database and instantiate the model
     *
     * @return object of model
     */
    public function selectOne()
    {
        $this->connect();
        if($this->schema == "")
        {
            throw new Exception("No conditions where selected");
        }
        $statement = $this->connector->query("SELECT * FROM " . $this->getTable() . " WHERE " . $this->schema)->fetchObject(get_called_class());
        $this->close();
        return $statement;
    }

    /**
     * Get all rows that corespond to the "schema"
     *
     * @return array of objects / object
     */
    public function select()
    {
        $this->connect();
        if($this->schema == "")
        {
            throw new Exception("No conditions where selected");
        }
        $statement = $this->connector->query("SELECT * FROM " . $this->getTable() . " WHERE " . $this->schema . $this->sortSchema . $this->limitSchema)->fetchAll(\PDO::FETCH_CLASS, get_called_class());
        $this->close();
        return $statement;
    }

    /**
     * Get all rows in the database, no conditions
     *
     * @return array of objects
     */
    public function selectAll()
    {
        $this->connect();
        $statement = $this->connector->query("SELECT * FROM " . $this->getTable() . $this->sortSchema . $this->limitSchema)->fetchAll(\PDO::FETCH_CLASS, get_called_class());
        $this->close();
        return $statement;
    }

    /**
     * Add sorting features to the query
     *
     * @param $sortBy string
     * @param $order string
     * @return sorted array of objects
     */
    public function sortBy($sortBy, $order)
    {
        $sortSchema = "";
        if(in_array($order, ["ASC", "DESC"]) == false)
        {
            throw new Exception("Invalid sorting params.");
        }
        $this->sortSchema = " ORDER BY " . $sortBy . " " . strtoupper($order);
        return $this;
    }

    /**
     * Create sort schema (if null, then no limitSchema applied)
     *
     * @param $limit string
     * @param $offset string
     * @return sorted array of objects
     */
    public function limitBy($limit = null, $offset = null)
    {
        $this->limitSchema = "";

        // CHeck if limit is set
        if($limit !== null)
        {
            $this->limitSchema = " LIMIT $limit";

            // Check if offset is set
            if($offset !== null)
            {
                $this->limitSchema .= " OFFSET $offset";
            }
        }
        return $this;
    }

    /**
     * Delete row from database
     *
     * @return null
     */
    public function delete()
    {
        $this->connect();
        if($this->schema == "")
        {
            //throw new Exception("No conditions where selected");
            $this->schema = "id = '" . $this->id . "'";
        }
        $statement = $this->connector->prepare("DELETE FROM " . $this->getTable() . " WHERE " . $this->schema)->execute();
        $this->close();
        return $statement;
    }

    private function close()
    {
        $this->connector = null;
    }
}
