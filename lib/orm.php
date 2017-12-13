<?php

class ORMRecord {
    public $inTable;
    
    // We must always have a reference to a table object
    public function __construct($tableObject) {
        $this->inTable = $tableObject;
        // Converting dates to DateTime objects
        foreach ($tableObject->listOfFields as $field) {
            if ($field['Type'] == 'timestamp') {
                $this->$field['Field'] = new DateTime($this->$field['Field']);
            }
        }
        // And We must fill related object fields
        $kField = $tableObject->keyField;
        // If it's a not new record
        if ($this->$kField) {
            foreach ($tableObject->relations as $rel=>$class) {
                $this->$class = $this->getOriginal($rel);
            }
        }
    }
    
    // Inserting new data
    protected function Insert($key) {
        // Creating list of fields
        $list_fields = "";
        $values_list = "";
        foreach ($this->inTable->listOfFields as $field) {
            $list_fields.= ", ".$field['Field'];
            // Converting DateTime to timestamps
            if ($field['Type'] == 'timestamp') {
                $values_list .= ', "'.$this->$field['Field']->format('Y-m-d H:i:s').'"';
            } else {
                $values_list .= ', "'.$this->$field['Field'].'"';  
            }
        }
        $list_fields = substr($list_fields, 1);
        $values_list = substr($values_list, 1); 
        
        // Formatting sql string based on field description
        $sql = "INSERT INTO ".$this->inTable->tableName." (".$list_fields.") VALUES (".$values_list.")";
        // Perfoming insert
        $result = mysql_query($sql);
         if (!$result) {
            $this->inTable->errorMessage = 'Error inserting into table: ' . mysql_error();
            exit;
        } 
        // Now we must get new id of a record
        $this->$key = mysql_insert_id();
    }
    
    // Updating a table
    protected function Update($key) {
        // Creating list of fields
        $update_list = "";
        foreach ($this->inTable->listOfFields as $field) {
            if ($field['Type'] == 'timestamp') {
                $update_list.= ', '.$field['Field'].' = "'.$this->$field['Field']->format('Y-m-d H:i:s').'"'; 
            } else {
                $update_list.= ', '.$field['Field'].' = "'.$this->$field['Field'].'"';                 
            }
        }
        $update_list = substr($update_list, 1); 
        // Formatting sql string based on field description
        $sql = 'UPDATE '.$this->inTable->tableName.' SET '.$update_list.' WHERE '.$key.' = '.$this->$key;
        // Perfoming insert
        $result = mysql_query($sql);
         if (!$result) {
            $this->inTable->errorMessage = 'Error updating table: ' . mysql_error();
            exit;
        }  
    }

    // Common public function for saving
    public function Save() {
        $kField = $this->inTable->keyField;
        // Filling id fields
        foreach ($this->inTable->relations as $field=>$val) {
            // If there not empty object values
            if (is_object($this->$val)) {
               // Get related key field
               $other_key = $this->$val->inTable->keyField; 
               $this->$field = $this->$val->$other_key; 
            }
        }
        // Perfoming database action
        if (!$this->$kField) {
            $this->Insert($kField);
        } 
        else {
            $this->Update($kField);
        }    
    }
    // Function returns object referenced by foreign key
    public function getOriginal($field_name) {
        if (!is_array($this->inTable->relations)) {
            $this->inTable->errorMessage = "There is no relations in table;";
            return false;
        }
        // If there is no such link
        if (!array_key_exists($field_name, $this->inTable->relations)) {
            $this->inTable->errorMessage = "There is no such relation in table: $field_name;";
            return false; 
        }
        $classlink = $this->inTable->relations[$field_name];
        $link = new $classlink();
        // Perfoming data selection
        if ($link->Where(array($link->keyField,'=',$this->$field_name))->getData()) {
            return array_pop($link->data);
        }
        else {
            return false;
        }
    }
    
    // Function returns objects set related to current in linked table
    public function getRelated($classname) {
        $link = new $classname();
        if (!is_array($link->relations)) {
            $this->inTable->errorMessage = "There is no relations in target table;";
            return false;
        }
        // If there is no such link
        if (!$field = array_search($this->inTable->tableName, $link->relations)) {
            $this->inTable->errorMessage = "There is no such relation $this->inTable->tableName in target table: $link->tableName;";
            return false; 
        }
        $key_field = $this->inTable->keyField;
        // Perfoming data selection
        if ($link->Where(array($field,'=',$this->$key_field))->getData()) {
            return $link;
        }
        else {
            return false;
        }        
    }
}

class ORMTable {
	public $tableName;
	public $data;
	public $countRows;
	public $errorMessage;
        public $keyField;
        public $dataBase;
        public $listOfFields;
        // Must be array with keys matched field names and values - is classname 
        // of another tables
        public $relations = array();
	
	protected $where_sql;
	protected $order_sql;
	protected $limit_sql;
	protected $last_sql;
	
	protected function resetInternals() {
		$this->data = array();
		$this->errorMessage = '';
		$this->where_sql = '';
		$this->order_sql = '';
		$this->limit_sql = '';
	}	
	
	public function __construct($table, $config, $ddlscript) {
		// Connecting to DB
		mysql_connect($config['host'], $config['username'], $config['password']);
		mysql_select_db($config['database']);
		$this->tableName = $table;
                $this->dataBase = $config['database'];
                $this->listOfFields = array();
		$this->resetInternals();
                // If there is no such table, we can create it
                $result = mysql_query("SHOW TABLES FROM ".$this->dataBase." LIKE '".$table."'");
                if (mysql_num_rows($result) == 0) {
                    if ($ddlscript) {
                        $result = mysql_query($ddlscript);
                        if (!$result) {
                            echo 'Error initializing table: ' . mysql_error();
                            exit;
                        }
                    }
                    else {
                        echo 'Error initializing table: No DDL script';
                        exit; 
                    }
                }
                // Identifying key field
                $result = mysql_query("SHOW COLUMNS FROM ".$this->tableName);
                if (!$result) {
                    echo 'Error initializing table: ' . mysql_error();
                    exit;
                }
                if (mysql_num_rows($result) > 0) {
                    while ($row = mysql_fetch_assoc($result)) {
                        if (trim($row['Key']) == 'PRI') {
                            $this->keyField = $row['Field'];
                        }
                        $this->listOfFields[] = $row; 
                    }
                }
	}
	
	protected function setConditions($field, $cond, $val, $op) {
		// Let's validate condition
		if (!in_array($cond, array('>','<','=','!=','<=','>=','<>'))) {
			$this->errorMessage .= " Invalid condition set: $field $cond $val ; ";
			return false;
		}
		// Normalising value, if its a string it has to be qouted
		if (!eregi("/(^[0-9]{1,8}|(^[0-9]{1,8}\.{0,1}[0-9]{1,2}))$/",$val)) {
			$val = '"'.$val.'"';
		}
		// Constructing where sql part
		if (strlen($this->where_sql) > 0) {
			$this->where_sql .= $op.$field.$cond.$val;
		}
		else {
			$this->where_sql = $field.$cond.$val;				
		}
		return true;
	}
	
	public function Where($cond_arr, $operand = ' AND ') {
		// Preventing invalid calls
		if (!is_array($cond_arr)) {
			return false;
		}
		// Check if we have to set multiple conditions
		if (!is_array($cond_arr[0])) {
			if (!$this->setConditions($cond_arr[0], $cond_arr[1], $cond_arr[2], $operand))
				return false;
		}
		else {
			foreach ($cond_arr as $sub_cond) {
				if (!$this->setConditions($sub_cond[0], $sub_cond[1], $sub_cond[2], $operand))
					return false;
			}
		}
		// Returning reference to self for chained calls
		return $this;
	}
	
	public function Order ($params) {
		if (is_array($params)) {
			foreach ($params as $value) {
				if (strlen($this->order_sql) > 0) {
					$this->order_sql .= ', '.$value;
				}
				else {
					$this->order_sql = $value;				
				}				
			}
		}
		else {
			if (strlen($this->order_sql) > 0) {
				$this->order_sql .= ', '.$params;
			}
			else {
				$this->order_sql = $params;				
			}
		}
		// Returning reference to self for chained calls
		return $this;	
	}
	
	public function Limit($from, $quantity = 0) {
		if ($quantity == 0) {
			$this->limit_sql = '0, '.$from;
		}
		else {
			$this->limit_sql = $from.', '.$quantity;
		}
		// Returning reference to self for chained calls
		return $this;			
	}
	
	public function getData() {
		// Preventing uninitialized calls
		if (strlen($this->tableName) < 1) 
			return false;
		// Generating sql string
		$sql = 'SELECT * FROM '.$this->tableName;
		if (strlen($this->where_sql) > 0) 
			$sql .= ' WHERE '.$this->where_sql;
		if (strlen($this->order_sql) > 0)
			$sql .= ' ORDER BY '.$this->order_sql;
		if (strlen($this->limit_sql) > 0)
			$sql .= ' LIMIT '.$this->limit_sql;
		// Populating data
		if (!$this->populateData($sql))
			return false;
			
		return true;
	}
	
	protected function populateData($sql) {
		// Executing query
		$result = mysql_query($sql);
		if (!$result) {
			$this->errorMessage = 'Invalid query: ' . mysql_error();
			return false;
		}
		// Get info data
		$this->countRows = mysql_num_rows($result);
		
		//Reseting internals
		$this->resetInternals();
		$this->last_sql = $sql;
		// Populating data, each record has reference to table
		while ($row = mysql_fetch_object($result, $this->tableName."Record", array($this))) {
			$this->data[] = $row;
		}
		// Cleaning resources
		mysql_free_result($result);	
                return true;
	}
	
	public function deleteData() {
		// Preventing uninitialized calls
		if (strlen($this->tableName) < 1) 
			return false;
		// Generating sql string
		$sql = 'DELETE FROM '.$this->tableName;
		if (strlen($this->where_part) > 0) 
			$sql .= ' WHERE '.$this->where_sql;
			
		// Executing query
		$result = mysql_query($sql);
		if (!$result) {
			$this->errorMessage = 'Invalid query: ' . mysql_error();
			return false;
		}
		
		// Populating data
		if (!$this->populateData($this->last_sql))
			return false;
			
		return true;
	}
        
}

?>