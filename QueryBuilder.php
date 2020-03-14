<?php

/**
 * Query Builder Class
 * 
 * An easy way to build up your SQL queries
 */

class QueryBuilder {

    private $_connection = null;

    private $_limit = "";
    private $_order = "";
    private $_where = "";
    private $_select = "*";

    private $_table = "";

    public function __construct(mysqli $connection)
    {
        $this->_connection = $connection;
        $this->init();
    }

    private function init()
    {
        $vars = get_class_vars(get_called_class());
        
        foreach ($vars as $key => $value)
        {
            if($key != "_connection")
            {
                $this->$key = "";
            }
        }

        $this->_select = "*";
    }

    private function conError()
    {
        if($this->_connection->error)
        {
            $this->init();
            throw new Exception($this->_connection->error, 1);
        }
    }

    private function escape($key_value, bool $trim = true, bool $toLower = true)
    {
        if(is_null($key_value))
        {
            throw new Exception("Expected a value, null given", 1);
        }

        if(is_string($key_value))
        {
            if($trim)
            {
                $key_value = trim($key_value);
            }
    
            if($toLower)
            {
                $key_value = strtolower($key_value);
            }
        }

        $key_value = $this
        ->_connection
        ->real_escape_string($key_value);

        return $key_value;
    }

    private function setWhere()
    {
        if(!empty($this->_where))
        {
            $this->_where = "WHERE " . trim($this->_where);
        }
    }

    private function setLimit()
    {
        if($this->_limit > 0)
        {
            $this->_limit = "LIMIT $this->_limit";
        }
    }

    private function setOrder()
    {
        if(!empty($this->_order))
        {
            $this->_order = "ORDER BY $this->_order";
        }
    }

    private function setClauses()
    {
        $this->setLimit();
        $this->setOrder();
        $this->setWhere();
    }

    /**
     * Initial and essential method to select and operate on a table
     * 
     * @return this
     */

    public function table(string $table_name)
    {
        $table_name = $this->escape($table_name);
        $this->_table = $table_name;

        return $this;
    }

    /**
     * In result, only return the columns provided in the array `columns`
     * 
     * @return this
     */

    public function select(array $columns)
    {
        $i = 0;
        $length = Count($columns);

        if ($length <= 0)
        {
            $this->_select = "*";
            return $this;
        }
        else
        {
            $this->_select = "";
        }

        foreach ($columns as $key => $value)
        {
            if ($i == $length - 1)
            {
                $this->_select .= $value;
            }
            else {
                $this->_select .= $value . ", ";
            }

            $i++;
        }

        return $this;
    }

    /**
     * This method will build WHERE clause for your query
     * 
     * @return this
     */

    public function where(string $key, $value, string $case = "=")
    {
        $key = $this->escape($key);
        $value = $this->escape($value, false, false);

        $this->_where .= "`$key` $case '$value' ";
        
        return $this;
    }

    /**
     * This method will build OR WHERE clause for your query.
     * 
     * @return this
     */

    public function orWhere(string $key, $value, string $case = "=")
    {
        $this->_where .= " OR ";
        $this->where($key, $value, $case);

        return $this;
    }

    /**
     * This method will build AND WHERE clause for your query.
     * 
     * @return this
     */

    public function andWhere(string $key, $value, string $case = "=")
    {
        $this->_where .= " AND ";
        $this->where($key, $value, $case);
        
        return $this;
    }

    /**
     * This method will limit the amount of rows for your query.
     * 
     * @return this
     */

    public function limit(int $limit)
    {
        $this->_limit = $limit;
        
        return $this;
    }

    public function orderBy(string $key, bool $ascendingOrder = true)
    {
        $key = $this->escape($key);

        if($ascendingOrder)
        {
            $this->_order = "`$key` ASC";
        }
        else {
            $this->_order = "`$key` DESC";
        }
        
        return $this;
    }

    /**
     * This method will return a single row for a given query.
     * 
     * @return array
     */

    public function findOne()
    {
        $this->setClauses();

        $query = "SELECT * FROM `$this->_table` $this->_where $this->_order LIMIT 1";

        $result = $this->_connection->query($query);
        
        $this->conError();

        if(!$result) {
            $this->init();
            return false;
        }

        $this->init();
        return $result->fetch_assoc();
    }

    /**
     * This will return an array of row(s) for a given query.
     * 
     * @return array
     */

    public function find(Bool $asArray = true)
    {
        $this->setClauses();
        
        $query = "SELECT $this->_select FROM `$this->_table` $this->_where $this->_order $this->_limit";
        
        $results = $this->_connection->query($query);

        $this->conError();

        if(!$results)
        {
            $this->init();
            return false;
        }

        if(!$asArray)
        {
            $this->init();
            return $results;
        }

        $array = array();

        while($row = $results->fetch_assoc())
        {
            $array[] = $row;
        }

        $this->init();
        return $array;
    }

    /**
     * This method will return sum for a given column for a given query.
     * 
     * @return int
     */

    public function sum(String $column_name)
    {
        $this->setClauses();

        $column_name = $this->escape($column_name);
        $query = "SELECT SUM(`$column_name`) FROM $this->_table $this->_where $this->_order $this->_limit";
        $result = $this->_connection->query($query);

        $this->conError();

        if(!$result)
        {
            return -1;
        }

        $this->init();
        return $result->fetch_assoc()["SUM(`".$column_name."`)"];
    }

    /**
     * This method will insert a new row to the database.
     * 
     * @return int
     */

    public function insert(array $data, bool $lastRow = false)
    {
        $length = Count($data);

        if($length <= 0)
        {
            $this->init();
            return false;
        }

        $keys = "";
        $values = "";

        $query = "INSERT INTO `$this->_table`";

        $i = 0;
        foreach($data as $key => $value)
        {
            $key = $this->escape($key);
            $value = $this->escape($value, false, false);

            if($i == $length - 1)
            {
                $keys .= "`$key`";
                $values .= "'$value'";

                $keys = "(" . $keys . ")";
                $values = "(" .$values. ")";
            }
            else
            {
                $keys .= "`$key`, ";
                $values .= "'$value', ";
            }

            $i++;
        }

        $query .= " $keys VALUES $values";
        $result = $this->_connection->query($query);

        $this->conError();

        if(!$result)
        {
            $this->init();
            return false;
        }

        $id = $this->_connection->insert_id;

        if(!$lastRow) {
            $this->init();
            return $id;
        }
        
        $lastInserted =  $this
        ->table($this->_table)
        ->where("id", $id)
        ->findOne();

        $this->init();

        return $lastInserted;
    }
    
    /**
     * This method will update a row for a given query
     * 
     * @return int
     */

    public function update(array $data, bool $lastRow = false)
    {
        $length = Count($data);

        if($length <= 0)
        {
            $this->init();
            return false;
        }

        $query = "UPDATE `$this->_table` SET ";

        $i = 0;
        foreach($data as $key => $value)
        {
            $key = $this->escape($key);
            $value = $this->escape($value, false, false);

            if($i == $length - 1)
            {
                $query .= "`$key` = '$value' ";
            }
            else
            {
                $query .= "`$key` = '$value', ";
            }

            $i++;
        }
        
        $query .= "WHERE $this->_where $this->_order $this->_limit";

        $this->_connection->query($query);
        $this->conError();

        $table_name = $this->_table;
        
        $this->init();

        $this->table($table_name);

        $i = 0;
        foreach ($data as $key => $value) {
            if($i == 0)
            {
                $this->where($key, $value);
            }
            else
            {
                $this->andWhere($key, $value);
            }

            $i++;
        }

        if(!$lastRow)
        {
            return $this->findOne()["id"];
        }

        return $this->findOne();
    }

    /**
     * This method will delete row(s) for a given query.
     * 
     * @return bool
     */

    public function delete()
    {
        $this->setClauses();

        $query = "DELETE FROM `$this->_table` $this->_where $this->_order $this->_limit";

        $result = $this->_connection->query($query);

        $this->conError();

        $this->init();
        return $result;
    }

    /**
     * This method will return the number of rows found for a given query.
     *
     * @return int
     */

    public function length()
    {
        $this->setClauses();
        $query = "SELECT * FROM `$this->_table` $this->_where $this->_order $this->_limit";

        $result = $this->_connection->query($query);

        $this->conError();

        if(!$result) {
            $this->init();
            return -1;
        }

        $this->init();
        return $result->num_rows;
    }

}

?>