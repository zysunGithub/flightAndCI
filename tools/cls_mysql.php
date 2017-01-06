<?php

//use Logger;

/**
 * MYSQL 公用类库
 */


class cls_mysql
{
    var $link_id    = NULL;

    var $queryCount = 0;
    var $queryTime  = '';
    var $queryLog   = array();

    var $max_cache_time = 300; // 最大的缓存时间，以秒为单位

    var $cache_data_dir = 'templates/caches/';
    var $root_path      = '';

    var $error_message  = array();
    var $platform       = '';
    var $version        = '';
    var $dbhash         = '';
    var $starttime      = 0;
    var $timeline       = 0;
    var $timezone       = 0;

    var $mysql_config_cache_file_time = 0;

    var $mysql_disable_cache_tables = array(); // 不允许被缓存的表，遇到将不会进行缓存

    private $writable = true;	// 标记 该数据库是否可写

    function __construct($dbhost, $dbuser, $dbpw, $dbname = '', $writable = true, $charset = 'utf8', $pconnect = 0)
    {
        $this->cls_mysql($dbhost, $dbuser, $dbpw, $dbname, $writable, $charset, $pconnect);
    }

    function cls_mysql($dbhost, $dbuser, $dbpw, $dbname = '', $writable = true, $charset = 'utf8', $pconnect = 0)
    {
        $this->writable = $writable;

        if ($pconnect)
        {
            if (!($this->link_id = @mysql_pconnect($dbhost, $dbuser, $dbpw)))
            {
                if ($this->writable) {
                    $this->ErrorMsg("Can't pConnect MySQL Server($dbhost)!");
                }

                return false;
            }
        }
        else
        {
            if (PHP_VERSION >= '4.2')
            {
                $this->link_id = @mysql_connect($dbhost, $dbuser, $dbpw, true);
            }
            else
            {
                $this->link_id = @mysql_connect($dbhost, $dbuser, $dbpw);

                mt_srand((double)microtime() * 1000000); // 随机数函数初始化
            }
            if (!$this->link_id)
            {
                if ($this->writable) {
                    $this->ErrorMsg("Can't Connect Database Server !"); // Zandy 2007-11-27
                }

                return false;
            }
        }

        $this->dbhash  = md5($this->root_path . $dbhost . $dbuser . $dbpw . $dbname);
        $this->version = mysql_get_server_info($this->link_id);

        //如果mysql 版本是 4.1+ 以上，需要对字符集进行初始化
        if ($this->version > '4.1')
        {
            if ($charset != 'latin1')
            {
                mysql_query("SET character_set_connection=$charset, character_set_results=$charset, character_set_client=binary", $this->link_id);
            }
            if ($this->version > '5.0.1')
            {
                mysql_query("SET sql_mode=''", $this->link_id);
            }
        }

        $sqlcache_config_file = $this->root_path . $this->cache_data_dir . 'sqlcache_config_file_' . $this->dbhash . '.php';

        @include($sqlcache_config_file);

        $this->starttime = time();

        if ($this->max_cache_time && $this->starttime > $this->mysql_config_cache_file_time + $this->max_cache_time)
        {
            if ($dbhost != '.')
            {
                $result = mysql_query("SHOW VARIABLES LIKE 'basedir'", $this->link_id);
                $row    = mysql_fetch_assoc($result);
                if (!empty($row['Value']{1}) && $row['Value']{1} == ':' && !empty($row['Value']{2}) && $row['Value']{2} == "\\")
                {
                    $this->platform = 'WINDOWS';
                }
                else
                {
                    $this->platform = 'OTHER';
                }
            }
            else
            {
                $this->platform = 'WINDOWS';
            }

            if ($this->platform == 'OTHER' &&
                ($dbhost != '.' && strtolower($dbhost) != 'localhost:3306' && $dbhost != '127.0.0.1:3306') ||
                (PHP_VERSION >= '5.1' && date_default_timezone_get() == 'UTC'))
            {
                $result = mysql_query("SELECT UNIX_TIMESTAMP() AS timeline, UNIX_TIMESTAMP('" . date('Y-m-d H:i:s', $this->starttime) . "') AS timezone", $this->link_id);
                $row    = mysql_fetch_assoc($result);

                if ($dbhost != '.' && strtolower($dbhost) != 'localhost:3306' && $dbhost != '127.0.0.1:3306')
                {
                    $this->timeline = $this->starttime - $row['timeline'];
                }

                if (PHP_VERSION >= '5.1' && date_default_timezone_get() == 'UTC')
                {
                    $this->timezone = $this->starttime - $row['timezone'];
                }
            }

            $content = '<' . "?php\r\n" .
                       '$this->mysql_config_cache_file_time = ' . $this->starttime . ";\r\n" .
                       '$this->timeline = ' . $this->timeline . ";\r\n" .
                       '$this->timezone = ' . $this->timezone . ";\r\n" .
                       '$this->platform = ' . "'" . $this->platform . "';\r\n?" . '>';

            @file_put_contents($sqlcache_config_file, $content);
        }

        /* 选择数据库 */
        if ($dbname)
        {
            if (mysql_select_db($dbname, $this->link_id) === false )
            {
                $this->ErrorMsg("Can't select MySQL database($dbname)!");

                return false;
            }
            else
            {
                return true;
            }
        }
        else
        {
            return true;
        }
    }

    function select_database($dbname)
    {
        return mysql_select_db($dbname, $this->link_id);
    }

    function set_mysql_charset($charset)
    {
        /* 如果mysql 版本是 4.1+ 以上，需要对字符集进行初始化 */
        if ($this->version > '4.1')
        {
            if (in_array(strtolower($charset), array('gbk', 'big5', 'utf-8', 'utf8')))
            {
                $charset = str_replace('-', '', $charset);
            }
            if ($charset != 'latin1')
            {
                mysql_query("SET character_set_connection=$charset, character_set_results=$charset, character_set_client=binary", $this->link_id);
            }
        }
    }

    function fetch_array($query, $result_type = MYSQL_ASSOC)
    {
        return mysql_fetch_array($query, $result_type);
    }

    function query($sql, $type = '')
    {

        $this->check_writable_query($sql);
        $this->queryCount++;
        $this->queryLog[] = $sql;
        if ($this->queryTime == '')
        {
            $this->queryTime = microtime();
        }

        /* 当当前的时间大于类初始化时间的时候，自动执行 ping 这个自动重新连接操作 */
        if (PHP_VERSION >= '4.3' && time() > $this->starttime + 1)
        {
            mysql_ping($this->link_id);
        }

        if (!($query = mysql_query($sql, $this->link_id)) && $type != 'SILENT')
        {
            $this->error_message[]['message'] = 'MySQL Query Error';
            $this->error_message[]['sql'] = $sql;
            $this->error_message[]['error'] = mysql_error($this->link_id);
            $this->error_message[]['errno'] = mysql_errno($this->link_id);

            $this->ErrorMsg();

            return false;
        }

        if (defined('DEBUG_MODE') && (DEBUG_MODE & 8) == 8)
        {
            $logfilename = $this->root_path . 'data/mysql_query_' . $this->dbhash . '_' . date('Y_m_d') . '.log';
            $str = $sql . "\n\n";

            if (PHP_VERSION >= '5.0')
            {
                file_put_contents($logfilename, $str, FILE_APPEND);
            }
            else
            {
                $fp = @fopen($logfilename, 'ab+');
                if ($fp)
                {
                    fwrite($fp, $str);
                    fclose($fp);
                }
            }
        }

        return $query;
    }

    /**
     * add by Zandy at 2010.12
     * i like simple
     * @param string $sql
     */
    function exec($sql)
    {
        if (empty($sql)) {
            return null;
        }
        $q = $this->query($sql);
        if (stripos($sql, 'insert') === 0) {
            return $this->insert_id();
        } elseif (stripos($sql, 'update') === 0 || stripos($sql, 'delete') === 0) {
            return $this->affected_rows();
        } else {
            return $q;
        }
    }

    /**
     * add by Zandy at 2010.12
     * i like simple
     * @param string $tablename
     * @param mixed $values
     * @param boolean $replace
     * @param boolean $ignore
     */
    function insert($tablename, $values, $replace = false, $ignore = false)
    {
        if (empty($values)) {
            return null;
        }
        $sql = $replace ? 'REPLACE ' : 'INSERT ';
        $sql .= $ignore ? 'IGNORE ' : '';
        $sql .= 'INTO ' . $tablename . ' SET ';
        $sql .= $this->prepareSQL($values, ', ');
        $q = $this->query($sql);
        return $this->insert_id();
    }

    /**
     * add by Zandy at 2010.12
     * i like simple
     * @param string $tablename
     * @param mixed $values
     * @param mixed $where
     * @param int $limit
     */
    function update($tablename, $values, $where, $limit = 0)
    {
        $sql = 'UPDATE ' . $tablename . ' SET ';
        $sql .= $this->prepareSQL($values);
        $sql .= " WHERE " . $this->prepareSQL($where, ' AND ');
        if ($limit > 0) {
            $sql .= " LIMIT " . $limit;
        }
        $q = $this->query($sql);
        return $this->affected_rows();
    }

    /**
     * add by Zandy at 2010.12
     * i like simple
     * @param string $tablename
     * @param mixed $where
     * @param int $limit
     */
    function delete($tablename, $where, $limit = 0)
    {
        $sql = 'DELETE FROM ' . $tablename;
        $sql .= " WHERE " . $this->prepareSQL($where, ' AND ');
        if ($limit > 0) {
            $sql .= " LIMIT " . $limit;
        }
        $q = $this->query($sql);
        return $this->affected_rows();
    }

    function start_transaction()
    {
        return $this->query("START TRANSACTION");
    }

    function commit()
    {
        return $this->query("COMMIT");
    }

    function rollback()
    {
        return $this->query("ROLLBACK");
    }

    function affected_rows()
    {
        return mysql_affected_rows($this->link_id);
    }

    function error()
    {
        return mysql_error($this->link_id);
    }

    function errno()
    {
        return mysql_errno($this->link_id);
    }

    function result($query, $row)
    {
        return @mysql_result($query, $row);
    }

    function num_rows($query)
    {
        return mysql_num_rows($query);
    }

    function num_fields($query)
    {
        return mysql_num_fields($query);
    }

    function free_result($query)
    {
        return mysql_free_result($query);
    }

    function insert_id()
    {
        return mysql_insert_id($this->link_id);
    }

    function fetchRow($query)
    {
        return mysql_fetch_assoc($query);
    }

    function fetch_object($query)
    {
        return mysql_fetch_object($query);
    }

    function fetch_fields($query)
    {
        return mysql_fetch_field($query);
    }

    function version()
    {
        return $this->version;
    }

    function ping()
    {
        if (PHP_VERSION >= '4.3') {
            return mysql_ping($this->link_id);
        } else {
            return false;
        }
    }

    function close()
    {
        return mysql_close($this->link_id);
    }

    function ErrorMsg($message = '', $sql = '')
    {
        if ((defined('SHOW_MYSQL_ERROR') && SHOW_MYSQL_ERROR)) {
            if ($message) {
                echo "<b>OUKU info</b>: $message\n\n";
            } else {
                echo '<pre>';
                echo '<b>MySQL server error report:';
                print_r($this->error_message);
                echo '</pre>';
            }
        } else {
            print_r(debug_backtrace());
            echo "呃，很抱歉，目前食堂的锅出了点问题。。。。伙计们正在抢修呢。";
        }

        exit();
    }

    function escape_string($unescaped_string)
    {
        return mysql_real_escape_string($unescaped_string, $this->link_id);
    }

    /**
     * add by Zandy at 2010.12
     * i like quote
     * @param string $sql
     */
    function quote($unescaped_string)
    {
        return mysql_real_escape_string($unescaped_string, $this->link_id);
    }

    /**
     * 处理之后可以直接拼接在 sql 里
     * add by Zandy at 2010.12
     * @param mixed $data  string int float array
     * @param string $join
     */
    function prepareSQL($data, $join = ', ')
    {
        if (is_int($data) || is_float($data)) {
            return $data;
        } elseif (is_bool($data)) {
            return $data ? 1 : 0;
        } elseif (is_null($data)) {
            return '';
        } elseif (is_string($data)) {
            return $data;
        } elseif (is_array($data)) {
            foreach ($data as $k => $v) {
                if (preg_match('/^\d+$/', $k)) {
                    $data[$k] = $this->prepareSQL($v, $join);
                } elseif (stripos($v, "$k + ") === 0) {
                    // like "UPDATE xxx SET clicks = clicks + 1 WHERE yyy"
                    $data[$k] = "$k = $v";
                } elseif (stripos($v, "ZYMI::") === 0) {
                    // use mysql internal function or constant with param
                    // usage: $order['userId'] = 'ZYMI::replace(uuid(), "-", "")';
                    // return userId = replace(uuid(), "-", "")
                    $data[$k] = "$k = " . substr($v, 6);
                } elseif (preg_match('/[A-Z_]+\s*\(\s*\)/', $v)) {
                    // use mysql internal function with no param
                    $data[$k] = "$k = $v";
                } else {
                    $tmp = $this->prepareSQL($v, $join);
                    $data[$k] = "$k = '" . $this->quote($tmp) . "'";
                }
            }
            return join($join, $data);
        } else {
            return (string) $data;
        }
    }

    /**
     * 转义字符串
     *
     * @param string $value
     *
     * @return mixed
     */
    function qstr($value)
    {
        if (is_int($value) || is_float($value)) { return $value; }
        if (is_bool($value)) { return $value ? 1 : 0 ; }
        if (is_null($value)) { return NULL; }
        return "'" . mysql_real_escape_string($value, $this->link_id) . "'";
    }

    /* 仿真 Adodb 函数 */
    function selectLimit($sql, $num, $start = 0)
    {
        if ($start == 0) {
            $sql .= ' LIMIT ' . $num;
        } else {
            $sql .= ' LIMIT ' . $start . ', ' . $num;
        }

        return $this->query($sql);
    }

    function getOne($sql, $limited = false)
    {
        if ($limited == true) {
            $sql = trim($sql . ' LIMIT 1');
        }

        $res = $this->query($sql);
        if ($res !== false) {
            $row = mysql_fetch_row($res);
            return $row[0];
        } else {
            return false;
        }
    }

    function getOneCached($sql, $cached = 'FILEFIRST')
    {
        $sql = trim($sql . ' LIMIT 1');

        $cachefirst = ($cached == 'FILEFIRST' || ($cached == 'MYSQLFIRST' && $this->platform != 'WINDOWS')) && $this->max_cache_time;
        if (!$cachefirst){
            return $this->getOne($sql, true);
        }else{
            $result = $this->getSqlCacheData($sql, $cached);
            if (empty($result['storecache']) == true){
                return $result['data'];
            }
        }

        $arr = $this->getOne($sql, true);

        if ($arr !== false && $cachefirst)
        {
            $this->setSqlCacheData($result, $arr);
        }

        return $arr;
    }

    function getAll($sql)
    {
        $res = $this->query($sql);
        if ($res !== false)
        {
            $arr = array();
            while ($row = mysql_fetch_assoc($res))
            {
                $arr[] = $row;
            }

            return $arr;
        }
        else
        {
            return false;
        }
    }

    function getAllByFieldAsKey($sql, $field_name)
    {
        $res = $this->query($sql);
        if ($res !== false)
        {
            $arr = array();
            while ($row = mysql_fetch_assoc($res))
            {
                $arr[$row[$field_name]] = $row;
            }

            return $arr;
        }
        else
        {
            return false;
        }
    }

	/**
	 * 返回记录集和指定字段的值集合，以及以该字段值作为索引的结果集
	 *
	 * 假设数据表 posts 有字段 post_id 和 title，并且包含下列数据：
	 *
	 * @code
	 * +---------+-----------------------+
	 * | post_id | title                 |
	 * +---------+-----------------------+
	 * |       1 | It's live             |
	 * +---------+-----------------------+
	 * |       2 | Recipes        |
	 * +---------+-----------------------+
	 * |       7 | User manual    |
	 * +---------+-----------------------+
	 * |      15 | Quickstart     |
	 * +---------+-----------------------+
	 * @endcode
	 *
	 * 现在我们查询 posts 表的数据，并以 post_id 的值为结果集的索引值：
	 *
	 * 用法:
	 * @code
	 * $sql = "SELECT * FROM posts";
	 * $fields_value = array();
	 * $ref = array();
	 * $rowset = $handle->fetchAllRefby($sql, array('post_id'), $fields_value, $ref);
	 * @endcode
	 *
	 * 上述代码执行后，$rowset 包含 posts 表中的全部 4 条记录。
	 * 最后，$fields_value 和 $ref 是如下形式的数组：
	 *
	 * @code
	 * $fields_value = array(
	 *     'post_id' => array(1, 2, 7, 15),
	 * );
	 *
	 * $ref = array(
	 *     'post_id' => array(
	 *          1 => & array(array(...)),
	 *          2 => & array(array(...), array(...)),
	 *          7 => & array(array(...), array(...)),
	 *         15 => & array(array(...), array(...), array(...))
	 *     ),
	 * );
	 * @endcode
	 *
	 * $ref 用 post_id 字段值作为索引值，并且指向 $rowset 中 post_id 值相同的记录。
	 * 由于是以引用方式构造的 $ref 数组，因此并不会占用双倍内存。
	 *
	 * 返回的 $fields_value 可以用来构造关联表的in查询
	 * 返回的 $ref 可方便用于多表查询后的快速组装数据
	 * 用这样的单表查询后组装数据来替代多表关联查询可以提高查询效率
	 *
	 * @param string $sql
	 * @param array $fields
	 * @param array $fields_value
	 * @param array $ref
	 * @param boolean $clean_up
	 *
	 * @return array
	 */
	function getAllRefby($sql, $fields, & $fields_value, & $ref, $clean_up = false)
	{
		$ref = $fields_value = $arr = array();
		$offset = 0;

		$res = $this->query($sql);
		if ($res === false)
		{
			return false;
		}

		if ($clean_up)
		{
			while ($row = mysql_fetch_assoc($res))
			{
				$arr[$offset] = $row;
				foreach ($fields as $field)
				{
					$field_value = $row[$field];
					$fields_value[$field][$offset] = $field_value;
					$ref[$field][$field_value][] =& $arr[$offset];
					unset($arr[$offset][$field]);
				}
				$offset++;
			}
		}
		else
		{
			while ($row = mysql_fetch_assoc($res))
			{
				$arr[$offset] = $row;
				foreach ($fields as $field)
				{
					$field_value = $row[$field];
					$fields_value[$field][$offset] = $field_value;
					$ref[$field][$field_value][] =& $arr[$offset];
				}
				$offset++;
			}
		}

		return $arr;
	}

    /**
     * 查询所有及其关联
     *
     * 用法:
     * @code
     * // 查询订单的数据
     * $sql = "SELECT order_id, order_sn, carrier_bill_id FROM order_info ORDER BY order_time DESC LIMIT 10";
     * // 定义关联关系
     * $links = array(
     *     array(
     *         'sql' => 'SELECT goods_id, order_id, goods_name FROM order_goods WHERE :in',
     *         'source_key' => 'order_id',
     *         'target_key' => 'order_id',
     *         'mapping_name' => 'goods_list',
     *         'type' => 'HAS_MANY',
     *     ),
     *     array(
     *         'sql' => 'SELECT b.bill_id, b.carrier_id, b.bill_no, c.name FROM carrier_bill AS b LEFT JOIN carrier AS c ON c.carrier_id = b.carrier_id WHERE :in',
     *         'source_key' => 'carrier_bill_id',
     *         'target_key' => 'bill_id',
     *         'mapping_name' => 'carrier_bill',
     *         'type' => 'HAS_ONE',
     *     ),
     * );
     * $rowset = $db->findAll($sql, $links);
     * @endcode
     * 上述代码执行后，将查询出 10条订单记录, 并且每条记录包括其商品列表和面单记录。
     *
     * 关联查询：
     *   关联查询语句中的 ‘:in’ 表示基于主记录的IN查询，也可以不使用如果你明确滴知道查询范围
     *
     * 关联字段：
     *   主记录的 ‘source_key’ 与关联记录的 ‘target_key’ 定义了关联关系
     *
     * 关联类型：
     *   HAS_MANY  表示主记录有多条关联记录，比如一条订单记录有多个商品记录
     *   HAS_ONE   表示主记录只有一条关联记录，比如一条订单记录只有一个配送面单记录
     *
     * @param string $sql  要执行的sql
     * @param array $links 定义的关联
     * @param array $refs  通过source_key对结果集的引用
     *
     * @return array
     */
    function findAll($sql, $links = array(), & $ref_value = array(), & $ref_rowset = array())
    {
        if (empty($links)) { return $this->getAll($sql); }

        $ref_fields = $ref_value = $ref_rowset = array();
        foreach ($links as $link) { $ref_fields[] = $link['source_key']; }
        $rowset = $this->getAllRefby($sql, $ref_fields, $ref_value, $ref_rowset, false);
        if (empty($rowset)) return $rowset;

        // 查询关联
        $callback = create_function('& $r, $o, $m', '$r[$m] = null;');
        foreach ($links as $key => $link)
        {
            if (empty($link['sql']) || empty($ref_value[$link['source_key']]))
            {
                unset($links[$key]);
                continue;
            }
            // 构造关联数据的IN查询
            $mn = $link['mapping_name'];
            // 初始化关联为NULL
            array_walk($rowset, $callback, $mn);
            $_in = $link['target_key'] . ' IN ('. implode(',', array_unique(array_map(array(& $this, 'qstr'), $ref_value[$link['source_key']]))) .')';
            $_sql = str_replace(':in', $_in, $link['sql']);
            $assoc_value[$mn] = $assoc_rowset[$mn] = array();
            $this->getAllRefby($_sql, array($link['target_key']), $assoc_value[$mn], $assoc_rowset[$mn], false);
        }

        // 将关联数据组装到主记录集
        foreach ($links as $link)
        {
            $mn = $link['mapping_name'];
            if (empty($assoc_rowset[$mn])) { continue; }

            foreach(array_keys($assoc_rowset[$mn][$link['target_key']]) as $relat_value)
            {
                if (isset($ref_rowset[$link['source_key']][$relat_value]))
                {
                    $ref =& $ref_rowset[$link['source_key']][$relat_value];
                    foreach ($ref as $key => $row)
                    {
                        if ($link['type'] == 'HAS_ONE' || $link['type'] == 'BELONGS_TO')
                            $ref[$key][$mn] = reset($assoc_rowset[$mn][$link['target_key']][$relat_value]);
                        else
                            $ref[$key][$mn] = $assoc_rowset[$mn][$link['target_key']][$relat_value];
                    }
                }
            }
        }

        return $rowset;
    }

    function getAllCached($sql, $cached = 'FILEFIRST')
    {
        $cachefirst = ($cached == 'FILEFIRST' || ($cached == 'MYSQLFIRST' && $this->platform != 'WINDOWS')) && $this->max_cache_time;
        if (!$cachefirst)
        {
            return $this->getAll($sql);
        }
        else
        {
            $result = $this->getSqlCacheData($sql, $cached);
            if (empty($result['storecache']) == true)
            {
                return $result['data'];
            }
        }

        $arr = $this->getAll($sql);

        if ($arr !== false && $cachefirst)
        {
            $this->setSqlCacheData($result, $arr);
        }

        return $arr;
    }

    function getRow($sql, $limited = false)
    {
        if ($limited == true)
        {
            $sql = trim($sql . ' LIMIT 1');
        }

        $res = $this->query($sql);
        if ($res !== false)
        {
            return mysql_fetch_assoc($res);
        }
        else
        {
            return false;
        }
    }

    function getRowCached($sql, $cached = 'FILEFIRST')
    {
        $sql = trim($sql . ' LIMIT 1');

        $cachefirst = ($cached == 'FILEFIRST' || ($cached == 'MYSQLFIRST' && $this->platform != 'WINDOWS')) && $this->max_cache_time;
        if (!$cachefirst)
        {
            return $this->getRow($sql, true);
        }
        else
        {
            $result = $this->getSqlCacheData($sql, $cached);
            if (empty($result['storecache']) == true)
            {
                return $result['data'];
            }
        }

        $arr = $this->getRow($sql, true);

        if ($arr !== false && $cachefirst)
        {
            $this->setSqlCacheData($result, $arr);
        }

        return $arr;
    }

    function getCol($sql)
    {
        $res = $this->query($sql);
        if ($res !== false)
        {
            $arr = array();
            while ($row = mysql_fetch_row($res))
            {
                $arr[] = $row[0];
            }

            return $arr;
        }
        else
        {
            return false;
        }
    }

    function getColCached($sql, $cached = 'FILEFIRST')
    {
        $cachefirst = ($cached == 'FILEFIRST' || ($cached == 'MYSQLFIRST' && $this->platform != 'WINDOWS')) && $this->max_cache_time;
        if (!$cachefirst)
        {
            return $this->getCol($sql);
        }
        else
        {
            $result = $this->getSqlCacheData($sql, $cached);
            if (empty($result['storecache']) == true)
            {
                return $result['data'];
            }
        }

        $arr = $this->getCol($sql);

        if ($arr !== false && $cachefirst)
        {
            $this->setSqlCacheData($result, $arr);
        }

        return $arr;
    }

    function autoExecute($table, $field_values, $mode = 'INSERT', $where = '', $querymode = '')
    {
        $field_names = $this->getCol('DESC ' . $table);

        $sql = '';
        if ($mode == 'INSERT')
        {
            $fields = $values = array();
            foreach ($field_names AS $value)
            {
                if (array_key_exists($value, $field_values) == true)
                {
                    $fields[] = $value;
                    $values[] = "'" . $field_values[$value] . "'";
                }
            }

            if (!empty($fields))
            {
                $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
               // echo($sql);
            }
        }
        else
        {
            $sets = array();
            foreach ($field_names AS $value)
            {
                if (array_key_exists($value, $field_values) == true)
                {
                    $sets[] = $value . " = '" . $field_values[$value] . "'";
                }
            }

            if (!empty($sets))
            {
                $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $sets) . ' WHERE ' . $where;
            }
        }

        if ($sql)
        {
            return $this->query($sql, $querymode);
        }
        else
        {
            return false;
        }
    }

    function autoReplace($table, $field_values, $update_values, $where = '', $querymode = '')
    {
        $field_descs = $this->getAll('DESC ' . $table);

        $primary_keys = array();
        foreach ($field_descs AS $value)
        {
            $field_names[] = $value['Field'];
            if ($value['Key'] == 'PRI')
            {
                $primary_keys[] = $value['Field'];
            }
        }

        $fields = $values = array();
        foreach ($field_names AS $value)
        {
            if (array_key_exists($value, $field_values) == true)
            {
                $fields[] = $value;
                $values[] = "'" . $field_values[$value] . "'";
            }
        }

        $sets = array();
        foreach ($update_values AS $key => $value)
        {
            if (array_key_exists($key, $field_values) == true)
            {
                if (is_numeric($value))
                {
                    $sets[] = $key . ' = ' . $key . ' + ' . $value;
                }
                else
                {
                    $sets[] = $key . " = '" . $value . "'";
                }
            }
        }

        $sql = '';
        if (empty($primary_keys))
        {
            if (!empty($fields))
            {
                $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
            }
        }
        else
        {
            if ($this->version() >= '4.1')
            {
                if (!empty($fields))
                {
                    $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
                    if (!empty($sets))
                    {
                        $sql .=  'ON DUPLICATE KEY UPDATE ' . implode(', ', $sets);
                    }
                }
            }
            else
            {
                if (empty($where))
                {
                    $where = array();
                    foreach ($primary_keys AS $value)
                    {
                        if (is_numeric($value))
                        {
                            $where[] = $value . ' = ' . $field_values[$value];
                        }
                        else
                        {
                            $where[] = $value . " = '" . $field_values[$value] . "'";
                        }
                    }
                    $where = implode(' AND ', $where);
                }

                if ($where && (!empty($sets) || !empty($fields)))
                {
                    if (intval($this->getOne("SELECT COUNT(*) FROM $table WHERE $where")) > 0)
                    {
                        if (!empty($sets))
                        {
                            $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $sets) . ' WHERE ' . $where;
                        }
                    }
                    else
                    {
                        if (!empty($fields))
                        {
                            $sql = 'REPLACE INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
                        }
                    }
                }
            }
        }

        if ($sql)
        {
            return $this->query($sql, $querymode);
        }
        else
        {
            return false;
        }
    }

    function setMaxCacheTime($second)
    {
        $this->max_cache_time = $second;
    }

    function getMaxCacheTime()
    {
        return $this->max_cache_time;
    }

    function getSqlCacheData($sql, $cached = '')
    {
        $sql = trim($sql);

        $result = array();
        $result['filename'] = $this->root_path . $this->cache_data_dir . 'sqlcache_' . abs(crc32($this->dbhash . $sql)) . '_' . md5($this->dbhash . $sql) . '.php';

        $data = @file_get_contents($result['filename']);
        if (isset($data{23}))
        {
            $filetime = substr($data, 13, 10);
            $data     = substr($data, 23);

            if (($cached == 'FILEFIRST' && time() > $filetime + $this->max_cache_time) || ($cached == 'MYSQLFIRST' && $this->table_lastupdate($this->get_table_name($sql)) > $filetime))
            {
                $result['storecache'] = true;
            }
            else
            {
                $result['data'] = @unserialize($data);
                if ($result['data'] === false)
                {
                    $result['storecache'] = true;
                }
                else
                {
                    $result['storecache'] = false;
                }
            }
        }
        else
        {
            $result['storecache'] = true;
        }

        return $result;
    }

    function setSqlCacheData($result, $data)
    {
        if ($result['storecache'] === true && $result['filename'])
        {
            @file_put_contents($result['filename'], '<?php exit;?>' . time() . serialize($data));
            clearstatcache();
        }
    }

    /* 获取 SQL 语句中最后更新的表的时间，有多个表的情况下，返回最新的表的时间 */
    function table_lastupdate($tables)
    {
        $lastupdatetime = '0000-00-00 00:00:00';

        $tables = str_replace('`', '', $tables);
        $this->mysql_disable_cache_tables = str_replace('`', '', $this->mysql_disable_cache_tables);

        foreach ($tables AS $table)
        {
            if (in_array($table, $this->mysql_disable_cache_tables) == true)
            {
                $lastupdatetime = '2037-12-31 23:59:59';

                break;
            }

            if (strstr($table, '.') != NULL)
            {
                $tmp = explode('.', $table);
                $sql = 'SHOW TABLE STATUS FROM `' . trim($tmp[0]) . "` LIKE '" . trim($tmp[1]) . "'";
            }
            else
            {
                $sql = "SHOW TABLE STATUS LIKE '" . trim($table) . "'";
            }
            $result = mysql_query($sql, $this->link_id);

            $row = mysql_fetch_assoc($result);
            if ($row['Update_time'] > $lastupdatetime)
            {
                $lastupdatetime = $row['Update_time'];
            }
        }
        $lastupdatetime = strtotime($lastupdatetime) - $this->timezone + $this->timeline;

        return $lastupdatetime;
    }

    function get_table_name($query_item)
    {
        $query_item = trim($query_item);
        $table_names = array();

        /* 判断语句中是不是含有JOIN */
        if (stristr($query_item, ' JOIN ') == '')
        {
            /* 解析一般的SELECT FROM语句 */
            if (preg_match('/^SELECT.*?FROM\s*((?:`?\w+`?\s*\.\s*)?`?\w+`?(?:(?:\s*AS)?\s*`?\w+`?)?(?:\s*,\s*(?:`?\w+`?\s*\.\s*)?`?\w+`?(?:(?:\s*AS)?\s*`?\w+`?)?)*)/is', $query_item, $table_names))
            {
                $table_names = preg_replace('/((?:`?\w+`?\s*\.\s*)?`?\w+`?)[^,]*/', '\1', $table_names[1]);

                return preg_split('/\s*,\s*/', $table_names);
            }
        }
        else
        {
            /* 对含有JOIN的语句进行解析 */
            if (preg_match('/^SELECT.*?FROM\s*((?:`?\w+`?\s*\.\s*)?`?\w+`?)(?:(?:\s*AS)?\s*`?\w+`?)?.*?JOIN.*$/is', $query_item, $table_names))
            {
                $other_table_names = array();
                preg_match_all('/JOIN\s*((?:`?\w+`?\s*\.\s*)?`?\w+`?)\s*/i', $query_item, $other_table_names);

                return array_merge(array($table_names[1]), $other_table_names[1]);
            }
        }

        return $table_names;
    }

    /* 设置不允许进行缓存的表 */
    function set_disable_cache_tables($tables)
    {
        if (!is_array($tables))
        {
            $tables = explode(',', $tables);
        }

        foreach ($tables AS $table)
        {
            $this->mysql_disable_cache_tables[] = $table;
        }

        array_unique($this->mysql_disable_cache_tables);
    }

    /**
     * 判断该sql是否有写操作，如果该数据库链接不可写，且sql为写操作，将会中止
     *
     * @param unknown_type $sql
     */
    function check_writable_query ($sql)
    {
        $sql = trim($sql);
        if (!$this->writable
            && preg_match("/^(INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|TRUNCATE|REPLACE|LOAD|RENAME)/i", $sql) > 0) {
            die("cant not execute sql: {$sql} by a readonly connect");
        }
    }
}

?>
