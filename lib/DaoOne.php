<?php
namespace eftec;
use DateTime;
use Exception;


/**
 * Class DaoOne
 * This class wrappes MySQLi but it could be used for another framework/library.
 * @version 2.6 20180606
 * @package eftec
 * @author Jorge C.
 * @copyright (c) Jorge Castro C. MIT License  https://github.com/EFTEC/DaoOne
 * 2.6 cleaned date functions.  function error changed to lastError
 */
class DaoOne
{
    /** @var string server ip. Ex. 127.0.0.1 */
    var $server;
    var $user;
    var $pwd;
    var $db;
    /** @var  \mysqli */
    var $conn1;
    /** @var  bool */
    var $transactionOpen;
    /** @var bool if the database is in READ ONLY mode or not. If true then we must avoid to write in the database. */
    var $readonly=false;
    /** @var string full filename of the log file */
    var $logFile="";

    var $lastQuery;

    /**
     * ClassUtilDB constructor.
     * @param string $server server ip. Ex. 127.0.0.1
     * @param string $user Ex. root
     * @param string $pwd Ex. 12345
     * @param string $db Ex. mybase
     * @param string $logFile Optional  log file. Example c:\\temp\log.log
     */
    public function __construct($server, $user, $pwd, $db,$logFile="")
    {
        $this->server = $server;
        $this->user = $user;
        $this->pwd = $pwd;
        $this->db = $db;

        $this->logFile=$logFile;


    }
    // if the database is in read only mode.
    public function readonly() {
        return $this->readonly;
    }

    /**
     * Connects to the database.
     * @throws \Exception
     */
    public function connect()
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        if ($this->conn1!=null) {
            throw new Exception("Already connected");
        }
        $this->conn1=new \mysqli($this->server,$this->user,$this->pwd,$this->db);

        // Check connection
        if (mysqli_connect_errno()) {
            throw new Exception("Failed to connect to MySQL: " . mysqli_connect_error());
        }
    }

    /**
     * @param $query string
     * @return \mysqli_stmt returns the statement if correct otherwise null
     * @throws Exception
     */
    public function prepare($query) {
        $this->lastQuery=$query;
        if ($this->readonly) {
            if (stripos($query,'insert ')===0 || stripos($query,'update ')===0 || stripos($query,'delete ')===0) {
                // we aren't checking SQL-DCL queries.
                throw new Exception("Database is in READ ONLY MODE");
            }
        }
        $stmt = $this->conn1->prepare($query );
        if ($stmt===false) {
            throw new Exception("Unable to prepare query ".$this->lastQuery);
        }
        return $stmt;
    }

    /**
     * Run a prepared statement.
     * @param $stmt \mysqli_stmt
     * @return bool returns true if the operation is correct, otherwise false
     * @throws Exception
     */
    public function runQuery($stmt) {
        $r=$stmt->execute();
        if ($r===false) {
            $this->debugFile("exception query ".$this->lastQuery);
            throw new Exception("Unable to run query ".$this->lastQuery);
        }
        return true;
    }

    /**
     * Run an unprepared query.
     * @param string $rawSql
     * @return bool|\mysqli_result
     * @throws Exception
     */
    public function runRawQuery($rawSql) {
        if ($this->readonly) {
            if (stripos($rawSql,'insert ')===0 || stripos($rawSql,'update ')===0 || stripos($rawSql,'delete ')===0) {
                // we aren't checking SQL-DCL queries. Also, "insert into" is stopped but "  insert into" not.
                throw new Exception("Database is in READ ONLY MODE");
            }
        }
        try {
            $r = $this->conn1->query($rawSql);
        } catch(Exception $ex) {
            $this->debugFile("exception raw ".$rawSql);
            throw new Exception("Unable to run raw query ".$rawSql);
        }
        if ($r===false) {
            throw new Exception("Unable to run raw query ".$rawSql);
        }
        return  $r;
    }

    /**
     * Run many  unprepared query separated by ;
     * @param $listSql
     * @param bool $continueOnError
     * @return bool
     * @throws Exception
     */
    public function runMultipleRawQuery($listSql,$continueOnError=false) {
        $arr=explode(';',$listSql);
        $ok=true;
        foreach($arr as $rawSql) {
            if ($this->readonly) {
                if (stripos($rawSql, 'insert ') === 0 || stripos($rawSql, 'update ') === 0 || stripos($rawSql, 'delete ') === 0) {
                    // we aren't checking SQL-DCL queries. Also, "insert into" is stopped but "  insert into" not.
                    $ok=false;
                    if (!$continueOnError) {
                        throw new Exception("Database is in READ ONLY MODE");
                    }
                }
            }
            $r = $this->conn1->query($rawSql);
            if ($r === false) {
                $ok=false;
                if (!$continueOnError) {
                    throw new Exception("Unable to run raw query " . $this->lastQuery);
                }
            }
        }
        return  $ok;
    }

    /**
     * Returns the last error.
     * @return string
     */
    public function lastError() {
        if ($this->conn1==null) return "No connection";
        return $this->conn1->error;
    }

    /**
     * Returns the last inserted identity.
     * @return mixed
     */
    public function insert_id() {
        return $this->conn1->insert_id;
    }


    /**
     * @param mixed $txt
     * @param $type 0=sql->text, 1=text->sql,  2=text->phpDate  , 3=phpDate->text
     */
    const DATESQL2TEXT=0;
    const DATETEXT2SQL=1;
    const DATETEXT2PHP=2;
    const DATEPHP2TEXT=3;

    /**
     * @param int $flag MYSQLI_TRANS_START_READ_ONLY,MYSQLI_TRANS_START_READ_WRITE,MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT
     */
    public function startTransaction($flag=MYSQLI_TRANS_START_READ_WRITE) {
        $this->transactionOpen=true;
        $this->conn1->begin_transaction($flag);
    }

    public function commit() {
        $this->transactionOpen=false;
        $this->conn1->commit();
    }

    public function rollback() {
        $this->transactionOpen=false;
        $this->conn1->rollback();
    }


    //<editor-fold desc="date functions">


    /**
     * @param DateTime $date
     * @return string
     */
    public static function dateTimePHP2Sql($date) {
        // 31/01/2016 20:20:00 --> 2016-01-31 00:00
        if ($date==null) {
            return "2000-01-01 00:00:00";
        }
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * @param integer $dateNum
     * @return string
     */
    public static function unixtime2Sql($dateNum) {
        // 31/01/2016 20:20:00 --> 2016-01-31 00:00

        if ($dateNum==null) {
            return "2000-01-01 00:00:00.00000";
        }


        $date2 = new DateTime(date("Y-m-d H:i:s.u", $dateNum));

        // $now = DateTime::createFromFormat('U.u', microtime(true));

        //$now = DateTime::createFromFormat('U.u', microtime(true));
        return  $date2->format('Y-m-d H:i:s.u');
    }

    /**
     * @param $txt
     * @return bool|DateTime
     */
    public static function dateTimeSql2PHP($txt) {
        // 3  2016-01-31 00:00:00 -> 01/01/2016 00:00:00
        if ($txt=="") {
            $txt="2000-01-01 00:00:00";
        }
        return DateTime::createFromFormat('Y-m-d H:i:s', $txt);
        /*
        if (strpos($txt,'.')) {
            // con microseconds
            echo $txt;
            return DateTime::createFromFormat('Y-m-d H:i:s.u', $txt);
        } else {
            echo $txt;
            return DateTime::createFromFormat('Y-m-d H:i:s', $txt);
        }
        */

    }

    //</editor-fold>
    /**
     * Write a log line for debug
     * @param $txt
     */
    function debugFile($txt) {
        if ($this->logFile=='') return;

        $fz=@filesize($this->logFile);

        if (is_object($txt) || is_array($txt)) {
            $txtW=print_r($txt,true);
        } else {
            $txtW=$txt;
        }
        if ($fz>100000) {
            // mas de 100kb = reducirlo a cero.
            $fp = @fopen($this->logFile, 'w');
        } else {
            $fp = @fopen($this->logFile, 'a');
        }
        @fwrite($fp, $txtW."\n");
        @fclose($fp);
    }

}