<?php

namespace eftec;

use DateTime;
use Exception;


/**
 * Class DaoOne
 * This class wrappes MySQLi but it could be used for another framework/library.
 * @version 3.7 20180922
 * @package eftec
 * @author Jorge Castro Castillo
 * @copyright (c) Jorge Castro C. MIT License  https://github.com/EFTEC/DaoOne
 * @see https://github.com/EFTEC/DaoOne
 */
class DaoOne
{
    /**
     * @param mixed $txt
     * @param $type 0=sql->text, 1=text->sql,  2=text->phpDate  , 3=phpDate->text
     */
    const DATESQL2TEXT = 0;
    const DATETEXT2SQL = 1;
    const DATETEXT2PHP = 2;
    const DATEPHP2TEXT = 3;
    /** @var string|null Static date (when the date is empty) */
    static $dateEpoch = "2000-01-01 00:00:00.00000";
    /** @var string server ip. Ex. 127.0.0.1 */
    var $server;
    var $user;
    var $pwd;
    var $db;
    var $charset='';
    //<editor-fold desc="date fields">
    /** @var  \mysqli */
    var $conn1;
    //</editor-fold>
    //<editor-fold desc="encryption fields">
    /** @var  bool */
    var $transactionOpen;
    /** @var bool if the database is in READ ONLY mode or not. If true then we must avoid to write in the database. */
    var $readonly = false;
    /** @var string full filename of the log file. If it's empty then it doesn't store a log file. The log file is limited to 1mb */
    var $logFile = "";
    /** @var string last query executed */
    var $lastQuery;
    var $dateFormat = 'aa';
    //</editor-fold>
    //<editor-fold desc="query builder fields">
    /** @var bool Encryption enabled */
    var $encEnabled = false;
    /** @var string Encryption password */
    var $encPassword = '';
    /** @var string Encryption salt */
    var $encSalt = '';
    /** @var string Encryption method, See http://php.net/manual/en/function.openssl-get-cipher-methods.php */
    var $encMethod = '';
    private $select = '';
    private $from = '';
    /** @var array */
    private $where = array();
    /** @var array */
    private $whereParamType = array();
    private $whereCounter = 0;
    /** @var array  */
    private $whereParamValue = array();
    /** @var array */
    private $set = array();
    private $group = '';
    //</editor-fold>
    /** @var array */
    private $having = array();
    private $limit = '';

    // if the database is in read only mode.
    private $distinct = '';
    private $order = '';

    /**
     * ClassUtilDB constructor.
     * @param string $server server ip. Ex. 127.0.0.1
     * @param string $user Ex. root
     * @param string $pwd Ex. 12345
     * @param string $db Ex. mybase
     * @param string $logFile Optional  log file. Example c:\\temp\log.log
     * @param string $charset Example utf8mb4
     */
    public function __construct($server, $user, $pwd, $db, $logFile = "",$charset='')
    {
        $this->server = $server;
        $this->user = $user;
        $this->pwd = $pwd;
        $this->db = $db;
        $this->logFile = $logFile;
        $this->charset=$charset;
    }

    /**
     * It changes default database.
     * @param $dbName
     */
    public function db($dbName) {
        $this->db=$dbName;
        $this->conn1->select_db($dbName);
    }

    public function setCharset($charset) {
        $this->charset=$charset;
        $this->conn1->set_charset($this->charset);
    }

    /**
     * Conver date from php -> mysql
     * @param DateTime $date
     * @return string
     */
    public static function dateTimePHP2Sql($date)
    {
        // 31/01/2016 20:20:00 --> 2016-01-31 00:00
        if ($date == null) {
            return DaoOne::$dateEpoch;
        }
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Convert date from unix -> mysql
     * @param integer $dateNum
     * @return string
     */
    public static function unixtime2Sql($dateNum)
    {
        // 31/01/2016 20:20:00 --> 2016-01-31 00:00
        if ($dateNum == null) {
            return DaoOne::$dateEpoch;
        }
        $date2 = new DateTime(date("Y-m-d H:i:s.u", $dateNum));
        return $date2->format('Y-m-d H:i:s.u');
    }

    /**
     * Convert date, from mysql -> php
     * @param $sqlField
     * @return bool|DateTime
     */
    public static function dateTimeSql2PHP($sqlField)
    {
        // 3  2016-01-31 00:00:00 -> 01/01/2016 00:00:00
        // mysql always returns the date/datetime/timestmamp in ansi format.
        if ($sqlField == "") {
            return DaoOne::$dateEpoch;
        }
        if (strpos($sqlField, '.')) {
            // with date with time and microseconds
            return DateTime::createFromFormat('Y-m-d H:i:s.u', $sqlField);
        } else {
            if (strpos($sqlField, ':')) {
                // date with time
                return DateTime::createFromFormat('Y-m-d H:i:s', $sqlField);
            } else {
                // only date
                return DateTime::createFromFormat('Y-m-d', $sqlField);
            }
        }
    }

    /**
     * @param string $password
     * @param string $salt
     * @param string $encMethod . Example : AES-128-CTR See http://php.net/manual/en/function.openssl-get-cipher-methods.php
     * @throws Exception
     */
    public function setEncryption($password, $salt, $encMethod)
    {
        if (!extension_loaded('openssl')) {
            $this->encEnabled = false;
            $this->throwError("OpenSSL not loaded, encryption disabled");
        } else {
            $this->encEnabled = true;
            $this->encPassword = $password;
            $this->encSalt = $salt;
            $this->encMethod = $encMethod;
        }
    }

    public function readonly()
    {
        return $this->readonly;
    }

    /**
     * Connects to the database.
     * @throws \Exception
     */
    public function connect()
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        if ($this->conn1 != null) {
            $this->throwError("Already connected");
        }
        try {
            $this->conn1 = new \mysqli($this->server, $this->user, $this->pwd, $this->db);
            if ($this->charset!='') {
                $this->setCharset($this->charset);
            }
        } catch (Exception $ex) {
            $this->throwError("Failed to connect to MySQL:\t" . $ex->getMessage());
        }
        // Check connection
        if (mysqli_connect_errno()) {
            $this->throwError("Failed to connect to MySQL:\t" . mysqli_connect_error());
        }
    }

    /**
     * Write a log line for debug then throw an error.
     * @param $txt
     * @throws Exception
     */
    function throwError($txt)
    {
        if ($this->logFile == '') {
            throw new Exception($txt);
        }

        $fz = @filesize($this->logFile);

        if (is_object($txt) || is_array($txt)) {
            $txtW = print_r($txt, true);
        } else {
            $txtW = $txt;
        }
        if ($fz > 10000000) {
            // mas de 10mb = reducirlo a cero.
            $fp = @fopen($this->logFile, 'w');
        } else {
            $fp = @fopen($this->logFile, 'a');
        }
        $txtW = str_replace("\r\n", " ", $txtW);
        $txtW = str_replace("\n", " ", $txtW);
        $now = new DateTime();
        @fwrite($fp, $now->format('c') . "\t" . $txtW . "\n");
        @fclose($fp);
        throw new Exception($txt);
    }

    /**
     * Run many  unprepared query separated by ;
     * @param $listSql
     * @param bool $continueOnError
     * @return bool
     * @throws Exception
     */
    public function runMultipleRawQuery($listSql, $continueOnError = false)
    {
        $arr = explode(';', $listSql);
        $ok = true;
        foreach ($arr as $rawSql) {
            if ($this->readonly) {
                if (stripos($rawSql, 'insert ') === 0 || stripos($rawSql, 'update ') === 0 || stripos($rawSql, 'delete ') === 0) {
                    // we aren't checking SQL-DCL queries. Also, "insert into" is stopped but "  insert into" not.
                    $ok = false;
                    if (!$continueOnError) {
                        $this->throwError("Database is in READ ONLY MODE");
                    }
                }
            }
            $r = $this->conn1->query($rawSql);
            if ($r === false) {
                $ok = false;
                if (!$continueOnError) {
                    $this->throwError("Unable to run raw query\t" . $this->lastQuery);
                }
            }
        }
        return $ok;
    }

    /**
     * Returns the last error.
     * @return string
     */
    public function lastError()
    {
        if ($this->conn1 == null) return "No connection";
        return $this->conn1->error;
    }

    /**
     * @param int $flag MYSQLI_TRANS_START_READ_ONLY,MYSQLI_TRANS_START_READ_WRITE,MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT
     * @return bool
     */
    public function startTransaction($flag = MYSQLI_TRANS_START_READ_WRITE)
    {
        if ($this->transactionOpen) return false;
        $this->transactionOpen = true;
        $this->conn1->begin_transaction($flag);
        return true;
    }

    /**
     * Commit and close a transaction
     * @throws Exception
     */
    public function commit()
    {
        if (!$this->transactionOpen) $this->throwError("Transaction not open to commit()");
        $this->transactionOpen = false;
        $this->conn1->commit();
    }

    /**
     * Rollback and close a transaction
     * @throws Exception
     */
    public function rollback()
    {
        if (!$this->transactionOpen) $this->throwError("Transaction not open  to rollback()");
        $this->transactionOpen = false;
        $this->conn1->rollback();
    }


    //<editor-fold desc="Date functions" defaultstate="collapsed" >

    /**
     * @param $sql
     * @return DaoOne
     */
    public function select($sql)
    {

        $this->select = $sql;
        return $this;
    }

    /**
     * It generates an inner join
     * @param string $sql Example "tablejoin on table1.field=tablejoin.field"
     * @return DaoOne
     */
    public function join($sql)
    {
        if ($this->from == '') return $this->from($sql);
        $this->from .= ($sql) ? " inner join $sql " : '';
        return $this;
    }

    /**
     * Macro of join.
     * @param $sql
     * @return DaoOne
     */
    public function innerjoin($sql)
    {
        return $this->join($sql);
    }


    /**
     * @param $sql
     * @return DaoOne
     */
    public function from($sql)
    {
        $this->from = ($sql) ? " $sql ": '';
        return $this;
    }
    //</editor-fold>

    //<editor-fold desc="Query Builder functions" defaultstate="collapsed" >

    /**
     * @param $sql
     * @return DaoOne
     */
    public function left($sql)
    {
        if ($this->from == '') return $this->from($sql);
        $this->from .= ($sql) ? " left join $sql" : '';
        return $this;
    }

    /**
     * @param $sql
     * @return DaoOne
     */
    public function right($sql)
    {
        if ($this->from == '') return $this->from($sql);
        $this->from .= ($sql) ? " right join $sql" : '';
        return $this;
    }

    /**
     * @param string|array $sql
     * @param array $param
     * @return DaoOne
     */
    public function where($sql, $param = null)
    {
        if (is_string($sql)) {
            $this->where[] = $sql;
            if ($param === null) return $this;
            for ($i = 0; $i < count($param); $i += 2) {
                $this->whereParamType[] = $param[$i];
                $this->whereParamValue['i_' . $this->whereCounter] = $param[$i + 1];
                $this->whereCounter++;
            }
        } else {
            $col=array();
            $colT=array();
            $p=array();
            $this->constructParam($sql,$param,$col,$colT,$p);

            foreach($col as $k=>$c) {
                $this->where[] = "`$c`=?";
                $this->whereParamType[] = $p[$k*2];
                $this->whereParamValue['i_' . $this->whereCounter] = $p[$k*2+1];
                $this->whereCounter++;
            }
        }
        return $this;
    }

    /**
     * @param string|array $sql
     * @param array $param
     * @return DaoOne
     * @throws Exception
     */
    public function set($sql, $param = null)
    {
        if (count($this->where)) {
            $this->throwError("you can't execute set() after a where()");
        }
        if (is_string($sql)) {
            $this->set[] = $sql;
            if ($param === null) return $this;
            for ($i = 0; $i < count($param); $i += 2) {
                $this->whereParamType[] = $param[$i];
                $this->whereParamValue['i_' . $this->whereCounter] = $param[$i + 1];
                $this->whereCounter++;
            }
        } else {
            $col=array();
            $colT=array();
            $p=array();
            $this->constructParam($sql,$param,$col,$colT,$p);
            foreach($col as $k=>$c) {
                $this->set[] = "`$c`=?";
                $this->whereParamType[] = $p[$k*2];
                $this->whereParamValue['i_' . $this->whereCounter] = $p[$k*2+1];
                $this->whereCounter++;
            }
        }
        return $this;
    }
    /**
     * @param $sql
     * @return DaoOne
     */
    public function group($sql)
    {
        $this->group = ($sql) ? ' group by ' . $sql : '';
        return $this;
    }

    /**
     * @param $sql
     * @param array $param
     * @return DaoOne
     */
    public function having($sql, $param)
    {
        $this->having[] = $sql;
        if ($param === null) return $this;
        for ($i = 0; $i < count($param); $i += 2) {
            $this->whereParamType[] = $param[$i];
            $this->whereParamValue['i_' . $this->whereCounter] = $param[$i + 1];
            $this->whereCounter++;
        }
        return $this;
    }

    /**
     * @param $sql
     * @return DaoOne
     */
    public function order($sql)
    {
        $this->order = ($sql) ? ' order by ' . $sql : '';
        return $this;
    }

    /**
     * @param $sql
     * @return DaoOne
     */
    public function limit($sql)
    {
        $this->limit = ($sql) ? ' limit ' . $sql : '';
        return $this;
    }

    /**
     * @param $sql
     * @return DaoOne
     */
    public function distinct($sql = 'distinct')
    {
        $this->distinct = ($sql) ? $sql . ' ' : '';
        return $this;
    }

    /**
     * It returns an array of rows.
     * @return array|bool
     * @throws Exception
     */
    public function toList()
    {
        return $this->runGen(true);
    }

    /**
     * Run builder query.
     * @param bool $returnArray true=return an array. False return a mysqli_result
     * @return bool|\mysqli_result
     * @throws Exception
     */
    public function runGen($returnArray = true)
    {
        $sql = $this->sqlGen();

        /** @var \mysqli_stmt $stmt */
        $stmt = $this->prepare($sql);
        $parType = implode('', $this->whereParamType);
        foreach ($this->whereParamValue as $key => $value) {
            $$key = $value;
        }
        $tmp2 = implode(',$', array_keys($this->whereParamValue));

        $tmp3 = '$stmt->bind_param("' . $parType . '",$' . $tmp2 . ');';

        if ($parType != "") {
            // Empty means that the query doesn't use parameters. Such as select * from table
            $reval = @eval($tmp3 . '; return true;');
            if (!$reval) {
                $this->throwError("Error in bind");
            }
        }
        $this->runQuery($stmt);
        $rows = $stmt->get_result();
        $stmt->close();

        $this->builderReset();
        if ($returnArray) {
            $r = $rows->fetch_all(MYSQLI_ASSOC);
            $rows->free_result();
            return $r;
        } else {
            return $rows;
        }
    }

    /**
     * Generates the sql to run.
     * @return string
     */
    public function sqlGen()
    {
        if (count($this->where)) {
            $where = ' where ' . implode(' and ', $this->where);
        } else {
            $where = '';
        }
        if (count($this->having)) {
            $having = ' having ' . implode(' and ', $this->having);
        } else {
            $having = '';
        }
        $sql = 'select ' . $this->distinct . $this->select . ' from '.$this->from . $where . $this->group . $having . $this->order . $this->limit;
        return $sql;
    }

    /**
     * @param $query string
     * @return \mysqli_stmt returns the statement if correct otherwise null
     * @throws Exception
     */
    public function prepare($query)
    {
        $this->lastQuery = $query;
        if ($this->readonly) {
            if (stripos($query, 'insert ') === 0 || stripos($query, 'update ') === 0 || stripos($query, 'delete ') === 0) {
                // we aren't checking SQL-DCL queries.
                $this->throwError("Database is in READ ONLY MODE");
            }
        }
        try {
            $stmt = $this->conn1->prepare($query);
        } catch (Exception $ex) {
            $this->throwError("Failed to prepare:\t" . $ex->getMessage());
        }
        if ($stmt === false) {
            $this->throwError("Unable to prepare query\t" . $this->lastQuery);
        }
        return $stmt;
    }

    /**
     * Run a prepared statement.
     * @param $stmt \mysqli_stmt
     * @return bool returns true if the operation is correct, otherwise false
     * @throws Exception
     */
    public function runQuery($stmt)
    {
        try {
            $r = $stmt->execute();
        } catch (Exception $ex) {

            $this->throwError("Failed to run query\t" . $this->lastQuery . " Cause:" . $ex->getMessage());
        }
        if ($r === false) {
            $this->throwError("exception query\t" . $this->lastQuery);
        }
        return true;
    }

    /**
     * It reset the parameters used to Build Query.
     */
    private function builderReset()
    {
        $this->select = '';
        $this->from = '';
        $this->where = [];
        $this->whereParamType = array();
        $this->whereCounter = 0;
        $this->whereParamValue = array();
        $this->set = [];
        $this->group = '';
        $this->having = [];
        $this->limit = '';
        $this->distinct = '';
        $this->order = '';
    }

    /**
     * It returns a mysqli_result.
     * @return \mysqli_result
     * @throws Exception
     */
    public function toResult()
    {
        return $this->runGen(false);
    }

    /**
     * It returns the first row.  If there is not row then it returns empty.
     * @return array|null
     * @throws Exception
     */
    public function first()
    {
        /** @var \mysqli_result $rows */
        $rows = $this->runGen(false);
        if ($rows === false) return null;
        while ($row = $rows->fetch_assoc()) {
            $rows->free_result();
            return $row;
        }
        return null;
    }

    /**
     * Executes the query, and returns the first column of the first row in the result set returned by the query. Additional columns or rows are ignored.
     * @return mixed|null
     * @throws Exception
     */
    public function firstScalar()
    {
        /** @var \mysqli_result $rows */
        $rows = $this->runGen(false);
        if ($rows === false) return null;
        while ($row = $rows->fetch_assoc()) {
            $rows->free_result();
            return reset($row);
        }
        return null;
    }


    /**
     * Returns the last row. It's not recommended. Use instead first() and change the order.
     * @return array|null
     * @throws Exception
     */
    public function last()
    {
        /** @var \mysqli_result $rows */
        $rows = $this->runGen(false);
        if ($rows === false) return null;
        $row = null;
        while ($row = $rows->fetch_assoc()) {
            $rows->free_result();
        }
        return $row;
    }


    /**
     * Run an unprepared query.
     * @param string $rawSql
     * @param array|null $param
     * @param bool $returnArray
     * @return bool|\mysqli_result
     * @throws Exception
     */
    public function runRawQuery($rawSql, $param = null, $returnArray = true)
    {
        if ($this->readonly) {
            if (stripos($rawSql, 'insert ') === 0 || stripos($rawSql, 'update ') === 0 || stripos($rawSql, 'delete ') === 0) {
                // we aren't checking SQL-DCL queries. Also, "insert into" is stopped but "  insert into" not.
                $this->throwError("Database is in READ ONLY MODE");
            }
        }
        $this->lastQuery=$rawSql;
        if ($param === null) {
            try {
                $r = $this->conn1->query($rawSql);
            } catch (Exception $ex) {
                $this->throwError("Exception raw\t" . $rawSql);
            }
            if ($r === false) {
                $this->throwError("Unable to run raw query\t" . $rawSql);
            }

            return $r;
        }
        // the where has parameters.
        $stmt = $this->prepare($rawSql);
        $parType = '';
        $values = [];

        for ($i = 0; $i < count($param); $i += 2) {
            $parType .= $param[$i];
            $values['i_' . $i] = $param[$i + 1];
        }
        // set values
        foreach ($values as $key => $value) {
            $$key = $value;
        }
        $tmp2 = implode(',$', array_keys($values));
        $tmp3 = '$stmt->bind_param("' . $parType . '",$' . $tmp2 . ');';
        if ($parType != "") {
            // Empty means that the query doesn't use parameters. Such as select * from table
            $reval = @eval($tmp3 . '; return true;');
            if (!$reval) {
                $this->throwError("Error in bind");
            }
        }
        $this->runQuery($stmt);
        $rows = $stmt->get_result();
        $stmt->close();
        if ($returnArray && $rows) {
            return $rows->fetch_all(MYSQLI_ASSOC);
        } else {
            return $rows;
        }
    }

    /**
     * Returns the last inserted identity.
     * @return mixed
     */
    public function insert_id()
    {
        return $this->conn1->insert_id;
    }
    /**
     * Returns the number of affected rows.
     * @return mixed
     */
    public function affected_rows()
    {
        return $this->conn1->affected_rows;
    }

    /**
     * Generate and run an update in the database.
     * <code>
     * update('table',['col1','i',10,'col2','s','hello world'],['where','i',10]);
     * // or
     * update('table',['col1','i','col2','s'],[10,'hello world'],['where','i'],[10]);
     * </code>
     * @param string $table
     * @param string[] $tableDef
     * @param string[] $value
     * @param string[] $tableDefWhere
     * @param string[] $valueWhere
     * @return mixed
     * @throws Exception
     */
    public function update($table=null, $tableDef=null, $value=null, $tableDefWhere=null, $valueWhere=null)
    {
        if ($table===null) {
            // using builder. from()->set()->where()->update()
            if ($this->from=="") $this->throwError("you can't execute an empty update() without a from()");
            if (count($this->set)===0) $this->throwError("you can't execute an empty update() without a set()");
            if (count($this->where)===0) $this->throwError("you can't execute an empty update() without a where()");
            $sql="update ".$this->from." ".$this->constructSet().' '.$this->constructWhere();
            $param=[];
            for($i=0;$i<count($this->whereParamType);$i++) {
                $param[]=$this->whereParamType[$i];
                $param[]=$this->whereParamValue['i_'.$i];
            }
            $this->builderReset();
            $this->runRawQuery($sql, $param,true);
            return $this->affected_rows();
        } else {
            $col = [];
            $colT = null;
            $colWhere = [];
            $param = [];
            if ($tableDefWhere === null) {
                $this->constructParam($tableDef, null, $col, $colT, $param);
                $this->constructParam($value, null, $colWhere, $colT, $param);
            } else {
                $this->constructParam($tableDef, $value, $col, $colT, $param);
                $this->constructParam($tableDefWhere, $valueWhere, $colWhere, $colT, $param);
            }
            $sql = "update $table set " . implode(',', $col) . " where " . implode(' and ', $colWhere);
            $this->builderReset();
            $this->runRawQuery($sql, $param);
            return $this->insert_id();
        }
    }
    /**
     * Generates and execute an insert command. Example:
     * <code>
     * insert('table',['col1','i',10,'col2','s','hello world']);
     * // or
     * insert('table',['col1','i','col2','s'],[10,'hello world']);
     * // or
     * insert('table',['col1'=>'i','col2'=>'s'],['col1'=>10,'col2'=>'hello world']);
     * // or
     * ->set(..)->from('table')->insert();
     * </code>
     * @param string $table
     * @param string[] $tableDef
     * @param string[] $value
     * @return mixed
     * @throws Exception
     */
    public function insert($table=null, $tableDef=null, $value = null)
    {
        if ($table===null) {
            // using builder. from()->set()->insert()
            if ($this->from=="") $this->throwError("you can't execute an empty insert() without a from()");
            if (count($this->set)===0) $this->throwError("you can't execute an empty insert() without a set()");
            $sql= /** @lang text */"insert into ".$this->from.' '.$this->constructInsert();
            $param=[];
            for($i=0;$i<count($this->whereParamType);$i++) {
                $param[]=$this->whereParamType[$i];
                $param[]=$this->whereParamValue['i_'.$i];
            }
            $this->builderReset();
            $this->runRawQuery($sql, $param,true);
            return $this->affected_rows();
        } else {
            $col = [];
            $colT = [];
            $param = [];
            $this->constructParam($tableDef, $value, $col, $colT, $param);
            $sql = "insert into $table (" . implode(',', $col) . ") values(" . implode(',', $colT) . ")";
            $this->builderReset();
            $this->runRawQuery($sql, $param);
            return $this->insert_id();
        }
    }


    /**
     * <code>
     * delete('table',['col1','i',10,'col2','s','hello world']);
     * // or
     * delete('table',['col1','i','col2','s'],[10,'hello world']);
     * // or
     * delete() if run on a chain $db->from('table')->where('..')->delete()
     * </code>
     * @param string $table
     * @param string[] $tableDefWhere
     * @param string[] $valueWhere
     * @return mixed
     * @throws Exception
     */
    public function delete($table=null, $tableDefWhere=null, $valueWhere=null)
    {
        if ($table===null) {
            // using builder. from()->where()->delete()
            if ($this->from=="") $this->throwError("you can't execute an empty delete() without a from()");
            if (count($this->where)===0) $this->throwError("you can't execute an empty delete() without a where()");
            $sql="delete from ".$this->from." ".$this->constructWhere();
            $param=[];
            for($i=0;$i<count($this->whereParamType);$i++) {
                $param[]=$this->whereParamType[$i];
                $param[]=$this->whereParamValue['i_'.$i];
            }
            $this->builderReset();
            $this->runRawQuery($sql, $param,true);
            return $this->affected_rows();
        } else {
            // using table/tabldefwhere/valuewhere
            $colWhere = [];
            $colT = null;
            $param = [];
            $this->constructParam($tableDefWhere, $valueWhere, $colWhere, $colT, $param);
            $sql = "delete from $table where " . implode(' and ', $colWhere);
            $this->builderReset();
            $this->runRawQuery($sql, $param,true);
            return $this->affected_rows();
        }
    }

    /**
     * @return string
     */
    private function constructWhere() {
        if (count($this->where)) {
            $where = ' where ' . implode(' and ', $this->where);
        } else {
            $where = '';
        }
        return $where;
    }

    /**
     * @return string
     */
    private function constructSet() {
        if (count($this->set)) {
            $where = " set " . implode(',', $this->set);
        } else {
            $where = '';
        }
        return $where;
    }

    /**
     * @return string
     */
    private function constructInsert() {
        if (count($this->set)) {
            $arr=[];
            $val=[];
            $first=$this->set[0];
            if (strpos($first,'=')!==false) {
                // set([])
                foreach($this->set as $v) {
                    $tmp=explode('=',$v);
                    $arr[]=$tmp[0];
                    $val[]=$tmp[1];
                }
                $where = "(".implode(',',$arr).') values ('.implode(',', $val).')';
            } else {
                // set('(a,b,c) values(?,?,?)',[])
                $where=$first;
            }
        } else {
            $where = '';
        }
        return $where;
    }

    /**
     * @param array $array1
     * @param array|null $array2
     * @param array $col
     * @param array $colT
     * @param array $param
     */
    private function constructParam($array1,$array2,&$col,&$colT,&$param) {
        if ($this->isAssoc($array1)) {
            if ($array2 === null) {
                // the type is calculated automatically. It could fails and it doesn't work with blob
                foreach ($array1 as $k => $v) {
                    if ($colT===null) {
                        $col[] = "`$k`=?";
                    } else {
                        $col[] = $k;
                        $colT[] = '?';
                    }
                    $vt='s';
                    $this->getType($vt,$v);

                    $param[] = $vt;
                    $param[] = $v;
                }
            } else {
                // it uses two associative array, one for the type and another for the value
                foreach ($array1 as $k => $v) {
                    if ($colT===null) {
                        $col[] = "`$k`=?";
                    } else {
                        $col[] = $k;
                        $colT[] = '?';
                    }

                    $param[] = $v;
                    $param[] = @$array2[$k];
                }
            }
        } else {
            if ($array2 === null) {
                // it uses a single list, the first value is the column, the second value
                // is the type and the third is the value
                for ($i = 0; $i < count($array1); $i += 3) {
                    if ($colT===null) {
                        $col[] = "`".$array1[$i]."`=?";
                    } else {
                        $col[] = $array1[$i];
                        $colT[] = '?';
                    }
                    $param[] = $array1[$i + 1];
                    $param[] = $array1[$i + 2];
                }
            } else {
                // it uses two list, the first value of the first list is the column, the second value is the type
                // , the second list only contains values.
                for ($i = 0; $i < count($array1); $i += 2) {
                    if ($colT===null) {
                        $col[] = "`".$array1[$i]."`=?";
                    } else {
                        $col[] = $array1[$i];
                        $colT[] = '?';
                    }
                    $param[] = $array1[$i + 1];
                    $param[] = $array2[$i / 2];
                }
            }
        }
    }

    private function getType(&$vt,&$v) {
        switch (1) {
            case (is_double($v)):
                $vt='d';
                break;
            case (is_numeric($v)):
                $vt='i';
                break;
            case (is_bool($v)):
                $vt='i';
                $v=($v)?1:0;
                break;
            case (is_object($v) && get_class($v)=='DateTime'):
                $vt='s';
                $v=DaoOne::dateTimePHP2Sql($v);
                break;
            default:
                $vt='s';
        }
    }

    private function isAssoc($array){
        return (array_values($array) !== $array);
    }
    //</editor-fold>

    //<editor-fold desc="Encryption functions" defaultstate="collapsed" >



    public function encrypt($data)
    {
        if (!$this->encEnabled) return $data; // no encryption
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->encMethod));
        $encrypted_string = bin2hex($iv) . openssl_encrypt($this->encSalt . $data, $this->encMethod, $this->encPassword, 0, $iv);
        return urlencode($encrypted_string);
    }
    //</editor-fold>

    //<editor-fold desc="Log functions" defaultstate="collapsed" >

    public function decrypt($data)
    {
        if (!$this->encEnabled) return $data; // no encryption
        $iv_strlen = 2 * openssl_cipher_iv_length($this->encMethod);
        if (preg_match("/^(.{" . $iv_strlen . "})(.+)$/", $data, $regs)) {
            list(, $iv, $crypted_string) = $regs;
            $decrypted_string = openssl_decrypt($crypted_string, $this->encMethod, $this->encPassword, 0, hex2bin($iv));
            return urldecode(substr($decrypted_string, strlen($this->encSalt)));
        } else {
            return false;
        }
    }
    //</editor-fold>

}