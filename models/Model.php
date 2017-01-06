<?php
namespace Model;
use Flight;
use Logger;
use Tools;
use Tools\Cls_basic_data;

abstract class Model extends cls_basic_data {
	var $dbname = 'test';           // database name
	var $tablename = '';				// table name
	var $fields = array();				// list of fields in this table
	var $primary_key = array();         // column(s) which form the primary key
	var $required_keys = array();       // required key specifications (optional)
	var $unique_keys = array();         // unique key specifications (optional)
	var $with_stamp = false;			// created_at, updated_at
	var $errors = array();				// array of error messages

	public function __construct(array $data = array()) {
        parent::__construct($data);
    }

	public function getTableName () {
		return $this->tablename;
	}

    public static function paging_clause($params, $default_size=0) {
        $offset = 0;
	    if(isset($params['offset']) && is_numeric($params['offset']))
	        $offset = $params['offset'];
	    $pagesize = $default_size;
	    if(isset($params['size']) &&  is_numeric($params['size']))
	        $pagesize = $params['size'];

        /* NOTE: if we want to fetch all, we cannot specify offset */
        if($pagesize == 0)
            return "";
        else
            return " limit $offset, $pagesize";
    }

	// count
	public function getCount () {
		$conds = array();
        foreach ($this->keys() as $key) {
            $conds[] = $key . " = '{$this->$key}' ";
        }

		$sql = "select count(1) from " . $this->getTableName() . " where " . implode(" and ", $conds) . ";";
		return Flight::db()->getOne($sql);		
	}

	// get
	public function getRecord ($selected_fields=null, $first_row=true) {
		$conds = array();
		foreach ($this->keys() as $key) {
			$conds[] = $key . " = '{$this->$key}' ";
		}

		$query_fields = is_null($selected_fields) ? "*" : implode(",", $selected_fields);
		$sql = "select " . $query_fields. " from " . $this->getTableName() . " where " . implode(" and ", $conds) . ";";
		if ($first_row === true) {
			$data = Flight::db()->getRow($sql);
			if(!empty($data)){
				$this->setData($data);
			}
		} else {
			$data = Flight::db()->getAll($sql);
		}

        return $data;
	}

	// update
	public function updateRecord () {
        $this->reset();
		$this->preUpdate();
		$conds = array();
		foreach ($this->primary_key as $key) {
            $conds[] = $key . " = '{$this->$key}' ";
        }

		$where = implode(" and ", $conds);
        return Flight::db()->update($this->getTableName(), $this->getData(), $where);
    }

	// insert
    public function insertRecord () {
        $this->reset();
		$this->preInsert();
		$insert_id = 0;
        if($this->validate()) {
            $insert_id = Flight::db()->insert($this->getTableName(), $this->getData());
        }else{
            $insert_id = 0;
        }

        return $insert_id;
    }

	// delete
    protected function deleteRecord ($where_key) {
        $where = $where_key . "='" .$this->$where_key."'";
        return Flight::db()->delete($this->getTableName(), $where);
    }

	// prepare update
	protected function preUpdate () {
		if ($this->with_stamp === true) {
			$this->last_time = date("Y-m-d H:i:s", time());
		}

		return;
	}

	// prepare insert
	protected function preInsert () {
		if ($this->with_stamp === true) {
			$this->last_time = date("Y-m-d H:i:s", time());
			$this->create_time = $this->last_time;
		}

		return;
	}

	// reset
	protected function reset () {
        foreach ($this->keys() as $key) {
            if(!in_array($key, $this->fields)){
				unset($this->$key);
            }
        }
    }

	// validate
	protected function validate () {
		$errors = array();
        foreach ($this->required_keys as $key) {
			if(!isset($this->$key)) {
				$errors[$key] = "$key cannot be blank";						
			}
        }

		if (count($errors) > 0) {
			$this->errors = $errors;
			return false;
		}

		return true;
	}
}
?>
