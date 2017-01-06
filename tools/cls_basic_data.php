<?php
namespace Tools;

class cls_basic_data{
    /**
     * Collection data.
     *
     * @var array
     */
    public $data;

    /**
     * Constructor.
     *
     * @param array $data Initial data
     */
    public function __construct(array $data = array()) {
        $this->data = $data;
    }

    /**
     * Gets an item.
     *
     * @param string $key Key
     * @return mixed Value
     */
    public function __get($key) {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    /**
     * Set an item.
     *
     * @param string $key Key
     * @param mixed $value Value
     */
    public function __set($key, $value) {
        $this->data[$key] = $value;
    }

    /**
     * Checks if an item exists.
     *
     * @param string $key Key
     * @return bool Item status
     */
    public function __isset($key) {
        return isset($this->data[$key]);
    }

    /**
     * Removes an item.
     *
     * @param string $key Key
     */
    public function __unset($key) {
        unset($this->data[$key]);
    }

    /**
     * Gets the item keys.
     *
     * @return array Collection keys
     */
    public function keys() {
        return array_keys($this->data);
    }

    /**
     * Gets the collection data.
     *
     * @return array Collection data
     */
    public function getData() {
        return $this->data;
    }

	/**
     * Set items.
     *
     * @param string $data items
     */
    public function setData($data){
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }
}


?>
