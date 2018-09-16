<?php
namespace eftec;
use DateTime;
use Exception;


/**
 * Class DaoOne
 * This class wrappes MySQLi but it could be used for another framework/library.
 * @version 3.2 20180916
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
    /** @var string full filename of the log file. If it's empty then it doesn't store a log file. The log file is limited to 1mb */
    var $logFile="";
    /** @var string last query executed */
    var $lastQuery;
    //<editor-fold desc="date fields">
    var $dateFormat='aa';
    //</editor-fold>
    //<editor-fold desc="encryption fields">
    /** @var string|null Static date (when the date is empty) */
    static $dateEpoch="2000-01-01 00:00:00.00000";
    /** @var bool Encryption enabled */
    var $encEnabled=false;
    /** @var string Encryption password */
    var $encPassword='';
    /** @var string Encryption salt */
    var $encSalt='';
    /** @var string Encryption method, See http://php.net/manual/en/function.openssl-get-cipher-methods.php */
    var $encMethod='';
    //</editor-fold>
    //<editor-fold desc="query builder fields">
    private $select='';
    private $from='';
    /** @var array  */
    private $where=array();
    /** @var array  */
    private $whereParamType=array();
    private $whereCounter=0;
    private $whereParamValue=array();
    private $group='';
    /** @var array  */
    private $having=array();
    private $limit='';
    private $distinct='';
    private $order='';
    //</editor-fold>
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

    /**
     * @param string $password
     * @param string $salt
     * @param string $encMethod . Example : AES-128-CTR See http://php.net/manual/en/function.openssl-get-cipher-methods.php
     * @throws Exception
     */
    public function setEncryption($password, $salt, $encMethod) {
        if (!extension_loaded('openssl')) {
            $this->encEnabled=false;
            throw new Exception("OpenSSL not loaded, encryption disabled");
        } else {
            $this->encEnabled=true;
            $this->encPassword=$password;
            $this->encSalt=$salt;
            $this->encMethod=$encMethod;
        }
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
            $this->debugFile("Already connected");
            throw new Exception("Already connected");
        }
        try {
            $this->conn1 = new \mysqli($this->server, $this->user, $this->pwd, $this->db);
        } catch(Exception $ex) {
            $this->debugFile("Failed to connect to MySQL:\t" .$ex->getMessage());
            throw new Exception("Failed to connect to MySQL: " .$ex->getMessage());
        }
        // Check connection
        if (mysqli_connect_errno()) {
            $this->debugFile("Failed to connect to MySQL:\t" . mysqli_connect_error());
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
                $this->debugFile("Database is in READ ONLY MODE");
                throw new Exception("Database is in READ ONLY MODE");
            }
        }
        try {
            $stmt = $this->conn1->prepare($query );
        } catch(Exception $ex) {
            $this->debugFile("Failed to prepare:\t" .$ex->getMessage());
            throw new Exception("Failed to prepare: " .$ex->getMessage());
        }
        if ($stmt===false) {
            $this->debugFile("Unable to prepare query\t".$this->lastQuery);
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
        try {
            $r = $stmt->execute();
        } catch(Exception $ex) {

            $this->debugFile("Failed to run query\t" .$this->lastQuery." Cause:".$ex->getMessage());
            throw new Exception("Failed to run query: " .$ex->getMessage());
        }
        if ($r===false) {
            $this->debugFile("exception query\t".$this->lastQuery);
            throw new Exception("Unable to run query ".$this->lastQuery);
        }
        return true;
    }

    /**
     * Run an unprepared query.
     * @param string $rawSql
     * @param array|null $param
     * @param bool $returnArray
     * @return bool|\mysqli_result
     * @throws Exception
     */
    public function runRawQuery($rawSql,$param=null,$returnArray=true) {
        if ($this->readonly) {
            if (stripos($rawSql,'insert ')===0 || stripos($rawSql,'update ')===0 || stripos($rawSql,'delete ')===0) {
                // we aren't checking SQL-DCL queries. Also, "insert into" is stopped but "  insert into" not.
                $this->debugFile("Database is in READ ONLY MODE");
                throw new Exception("Database is in READ ONLY MODE");
            }
        }
        if ($param==null) {

            try {
                $r = $this->conn1->query($rawSql);
            } catch(Exception $ex) {
                $this->debugFile("Exception raw\t".$rawSql);
                throw new Exception("Unable to run raw query ".$rawSql);
            }
            if ($r===false) {
                $this->debugFile("Unable to run raw query\t".$rawSql);
                throw new Exception("Unable to run raw query ".$rawSql);
            }
            return $r;
        }
        // the whery has parameters.
        $stmt=$this->prepare($rawSql);
        $parType='';
        $values=[];

        for($i=0;$i<count($param);$i+=2) {
            $parType.=$param[$i];
            $values['i_'.$i]=$param[$i+1];
        }
        // set values
        foreach($values as $key=>$value) {
            $$key = $value;
        }
        $tmp2=implode(',$',array_keys($values));
        $tmp3='$stmt->bind_param("'.$parType.'",$'.$tmp2.');';
        if ($parType!="") {
            // Empty means that the query doesn't use parameters. Such as select * from table
            $reval=@eval($tmp3.'; return true;');
            if (!$reval) {
                throw new Exception("Error in bind");
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
                        $this->debugFile("Database is in READ ONLY MODE");
                        throw new Exception("Database is in READ ONLY MODE");
                    }
                }
            }
            $r = $this->conn1->query($rawSql);
            if ($r === false) {
                $ok=false;
                if (!$continueOnError) {
                    $this->debugFile("Unable to run raw query\t" . $this->lastQuery);
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
     * @return bool
     */
    public function startTransaction($flag=MYSQLI_TRANS_START_READ_WRITE) {
        if ($this->transactionOpen) return false;
        $this->transactionOpen=true;
        $this->conn1->begin_transaction($flag);
        return true;
    }

    /**
     * Commit and close a transaction
     * @throws Exception
     */
    public function commit() {
        if (!$this->transactionOpen) throw new Exception("Transaction not open");
        $this->transactionOpen=false;
        $this->conn1->commit();
    }
    /**
     * Rollback and close a transaction
     * @throws Exception
     */
    public function rollback() {
        if (!$this->transactionOpen) throw new Exception("Transaction not open");
        $this->transactionOpen=false;
        $this->conn1->rollback();
    }


    //<editor-fold desc="Date functions" defaultstate="collapsed" >
    /**
     * Conver date from php -> mysql
     * @param DateTime $date
     * @return string
     */
    public static function dateTimePHP2Sql($date) {
        // 31/01/2016 20:20:00 --> 2016-01-31 00:00
        if ($date==null) {
            return DaoOne::$dateEpoch;
        }
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Convert date from unix -> mysql
     * @param integer $dateNum
     * @return string
     */
    public static function unixtime2Sql($dateNum) {
        // 31/01/2016 20:20:00 --> 2016-01-31 00:00
        if ($dateNum==null) {
            return DaoOne::$dateEpoch;
        }
        $date2 = new DateTime(date("Y-m-d H:i:s.u", $dateNum));
        return  $date2->format('Y-m-d H:i:s.u');
    }

    /**
     * Convert date, from mysql -> php
     * @param $sqlField
     * @return bool|DateTime
     */
    public static function dateTimeSql2PHP($sqlField) {
        // 3  2016-01-31 00:00:00 -> 01/01/2016 00:00:00
        // mysql always returns the date/datetime/timestmamp in ansi format.
        if ($sqlField=="") {
            return DaoOne::$dateEpoch;
        }
        if (strpos($sqlField,'.')) {
            // with date with time and microseconds
            return DateTime::createFromFormat('Y-m-d H:i:s.u', $sqlField);
        } else {
            if (strpos($sqlField,':')) {
                // date with time
                return DateTime::createFromFormat('Y-m-d H:i:s', $sqlField);
            } else {
                // only date
                return DateTime::createFromFormat('Y-m-d', $sqlField);
            }
        }
    }
    //</editor-fold>

    //<editor-fold desc="Query Builder functions" defaultstate="collapsed" >
    /**
     * @param $sql
     * @return DaoOne
     */
    public function select($sql) {

        $this->select=$sql;
        return $this;
    }
    /**
     * @param $sql
     * @return DaoOne
     */
    public function from($sql) {
        $this->from=($sql)?' from '.$sql:'';
        return  $this;
    }

    /**
     * @param $sql
     * @return DaoOne
     */
    public function join($sql) {
        if ($this->from=='') return $this->from($sql);
        $this->from.=($sql)?" inner join $sql":'';
        return  $this;
    }

    /**
     * @param $sql
     * @return DaoOne
     */
    public function left($sql) {
        if ($this->from=='') return $this->from($sql);
        $this->from.=($sql)?" left join $sql":'';
        return  $this;
    }
    /**
     * @param $sql
     * @return DaoOne
     */
    public function right($sql) {
        if ($this->from=='') return $this->from($sql);
        $this->from.=($sql)?" right join $sql":'';
        return  $this;
    }

    /**
     * @param $sql
     * @param array $param
     * @return DaoOne
     */
    public function where($sql,$param=null) {
        $this->where[]=$sql;
        if ($param===null) return $this;
        for($i=0;$i<count($param);$i+=2 ) {
            $this->whereCounter++;
            $this->whereParamType[] = $param[$i];
            $this->whereParamValue['i_' . $this->whereCounter] = $param[$i+1];
        }
        return  $this;
    }

    /**
     * @param $sql
     * @return DaoOne
     */
    public function group($sql) {
        $this->group=($sql)?' group by '.$sql:'';
        return  $this;
    }

    /**
     * @param $sql
     * @param array $param
     * @return DaoOne
     */
    public function having($sql,$param) {
        $this->having[]=$sql;
        if ($param===null) return $this;
        for($i=0;$i<count($param);$i+=2 ) {
            $this->whereCounter++;
            $this->whereParamType[] = $param[$i];
            $this->whereParamValue['i_' . $this->whereCounter] = $param[$i+1];
        }
        return  $this;
    }

    /**
     * @param $sql
     * @return DaoOne
     */
    public function order($sql) {
        $this->order=($sql)?' order by '.$sql:'';
        return  $this;
    }
    /**
     * @param $sql
     * @return DaoOne
     */
    public function limit($sql) {
        $this->limit=($sql)?' limit '.$sql:'';
        return  $this;
    }

    /**
     * @param $sql
     * @return DaoOne
     */
    public function distinct($sql='distinct') {
        $this->distinct=($sql)?$sql.' ':'';
        return  $this;
    }

    /**
     * Generates the sql to run.
     * @return string
     */
    public function sqlGen() {
        if (count($this->where)) {
            $where=' where '.implode(' and ',$this->where);
        } else {
            $where='';
        }
        if (count($this->having)) {
            $having=' having '.implode(' and ',$this->having);
        } else {
            $having='';
        }
        $sql='select '.$this->distinct.$this->select.$this->from.$where.$this->group.$having.$this->order.$this->limit;
        return $sql;
    }

    /**
     * It returns an array of rows.
     * @return bool
     * @throws Exception
     */
    public function toList() {
        return $this->runGen(true);
    }

    /**
     * It returns a mysqli_result.
     * @return \mysqli_result
     * @throws Exception
     */
    public function toResult() {
        return $this->runGen(false);
    }
    /**
     * It returns the first row.  If there is not row then it returns empty.
     * @return array|null
     * @throws Exception
     */
    public function first() {
        /** @var \mysqli_result $rows */
        $rows=$this->runGen(false);
        if ($rows===false) return null;
        while ($row = $rows->fetch_assoc()) {
            $rows->free_result();
            return $row;
        }
        return null;
    }
    /**
     * Returns the last row. It's not recommended. Use instead first() and change the order.
     * @return array|null
     * @throws Exception
     */
    public function last() {
        /** @var \mysqli_result $rows */
        $rows=$this->runGen(false);
        if ($rows===false) return null;
        $row=null;
        while ($row = $rows->fetch_assoc()) {
            $rows->free_result();
        }
        return $row;
    }

    /**
     * @param string $table
     * @param string[] $tableDef
     * @param string[] $value
     * @return mixed
     * @throws Exception
     */
    public function insert($table,$tableDef,$value) {
        $col=[];
        $colT=[];
        $param=[];
        for($i=0;$i<count($tableDef);$i+=2) {
            $col[]=$tableDef[$i];
            $colT[]='?';
            $param[]=$tableDef[$i+1];
            $param[]=$value[$i/2];
        }
        $sql="insert into $table (".implode(',',$col).") values(".implode(',',$colT).")";
        $this->runRawQuery($sql,$param);
        return $this->insert_id();
    }

    /**
     * @param string $table
     * @param string[] $tableDef
     * @param string[] $value
     * @param string[] $tableDefWhere
     * @param string[] $valueWhere
     * @return mixed
     * @throws Exception
     */
    public function update($table,$tableDef,$value,$tableDefWhere,$valueWhere) {
        $col=[];
        $colWhere=[];
        $param=[];
        for($i=0;$i<count($tableDef);$i+=2) {
            $col[] = '`' . $tableDef[$i] . '`=?';
            $param[] = $tableDef[$i + 1];
            $param[] = $value[$i / 2];
        }
        for($i=0;$i<count($tableDefWhere);$i+=2) {
            $colWhere[] = '`' . $tableDefWhere[$i] . '`=?';
            $param[] = $tableDefWhere[$i + 1];
            $param[] = $valueWhere[$i / 2];
        }
        $sql="update $table set ".implode(',',$col)." where ".implode(' and ',$colWhere);

        $this->runRawQuery($sql,$param);
        return $this->insert_id();
    }

    /**
     * @param string $table
     * @param string[] $tableDefWhere
     * @param string[] $valueWhere
     * @return mixed
     * @throws Exception
     */
    public function delete($table,$tableDefWhere,$valueWhere) {
        $colWhere=[];
        $param=[];

        for($i=0;$i<count($tableDefWhere);$i+=2) {
            $colWhere[] = '`'.$tableDefWhere[$i].'`=?';
            $param[] = $tableDefWhere[$i + 1];
            $param[] = $valueWhere[$i / 2];
        }
        $sql="delete from $table where ".implode(' and ',$colWhere);

        $this->runRawQuery($sql,$param);
        return $this->insert_id();
    }

    /**
     * Run builder query.
     * @param bool $returnArray true=return an array. False return a mysqli_result
     * @return bool|\mysqli_result
     * @throws Exception
     */
    public function runGen($returnArray=true) {
        $sql=$this->sqlGen();
        /** @var \mysqli_stmt $stmt */
        $stmt=$this->prepare($sql);
        $parType=implode('',$this->whereParamType);

        foreach($this->whereParamValue as $key=>$value) {
            $$key = $value;
        }
        $tmp2=implode(',$',array_keys($this->whereParamValue));

        $tmp3='$stmt->bind_param("'.$parType.'",$'.$tmp2.');';
        if ($parType!="") {
            // Empty means that the query doesn't use parameters. Such as select * from table
            $reval=@eval($tmp3.'; return true;');
            if (!$reval) {
                throw new Exception("Error in bind");
            }
        }
        $this->runQuery($stmt);
        $rows = $stmt->get_result();
        $stmt->close();

        $this->builderReset();
        if ($returnArray) {
            $r=$rows->fetch_all(MYSQLI_ASSOC);
            $rows->free_result();
            return $r;
        } else {
            return $rows;
        }
    }

    /**
     * It reset the parameters used to Build Query.
     */
    private function builderReset() {
        $this->select='';
        $this->from='';
        $this->where=[];
        $this->whereParamType=array();
        $this->whereCounter=0;
        $this->whereParamValue=array();
        $this->group='';
        $this->having=[];
        $this->limit='';
        $this->distinct='';
        $this->order='';
    }

    //</editor-fold>

    //<editor-fold desc="Encryption functions" defaultstate="collapsed" >
    public function encrypt($data)
    {
        if (!$this->encEnabled) return $data; // no encryption
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->encMethod));
        $encrypted_string = bin2hex($iv) . openssl_encrypt($this->encSalt.$data,$this->encMethod, $this->encPassword, 0, $iv);
        return urlencode($encrypted_string);
    }
    public function decrypt($data)
    {
        if (!$this->encEnabled) return $data; // no encryption
        $iv_strlen = 2  * openssl_cipher_iv_length($this->encMethod);
        if(preg_match("/^(.{" . $iv_strlen . "})(.+)$/", $data, $regs)) {
            list(, $iv, $crypted_string) = $regs;
            $decrypted_string = openssl_decrypt($crypted_string, $this->encMethod, $this->encPassword, 0, hex2bin($iv));
            return urldecode(substr($decrypted_string,strlen($this->encSalt)));
        } else {
            return false;
        }
    }
    //</editor-fold>

    //<editor-fold desc="Log functions" defaultstate="collapsed" >
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
        if ($fz>10000000) {
            // mas de 10mb = reducirlo a cero.
            $fp = @fopen($this->logFile, 'w');
        } else {
            $fp = @fopen($this->logFile, 'a');
        }
        $txtW=str_replace("\r\n"," ",$txtW);
        $txtW=str_replace("\n"," ",$txtW);
        $now=new DateTime();
        @fwrite($fp,$now->format('c')."\t".$txtW."\n");
        @fclose($fp);
    }
    //</editor-fold>

}