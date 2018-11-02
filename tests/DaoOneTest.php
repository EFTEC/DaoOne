<?php

use eftec\DaoOne;

class DaoOneTest extends \PHPUnit_Framework_TestCase
{
    var $daoOne;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        //you could change it.
        $this->daoOne=new DaoOne("127.0.0.1","root","abc.123","travisdb");
    }

    public function test___construct()
    {

    }
    public function test_db()
    {

    }
    public function test_setCharset()
    {

    }
    public function test_readonly()
    {

    }
    public function test_connect()
    {
        //$this->expectException(\Exception::class);
        $this->daoOne->connect();
    }

    public function test_close()
    {

    }
    public function test_getMessages()
    {

    }
    public function test_false()
    {

    }
    public function test_MYSQLI_TRANS_START_READ_WRITE()
    {

    }

    public function test_dateTimePHP2Sql()
    {

    }
    public function test_unixtime2Sql()
    {

    }
    public function test_dateTimeSql2PHP()
    {

    }
    public function test_select()
    {

    }
    public function test_join()
    {

    }
    public function test_innerjoin()
    {

    }
    public function test_from()
    {

    }
    public function test_left()
    {

    }
    public function test_right()
    {

    }
    public function test_null()
    {

    }
 
    public function test_group()
    {

    }
    public function test_having()
    {

    }
    public function test_order()
    {

    }
    public function test_limit()
    {

    }
    public function test_distinct()
    {

    }
    public function test_toList()
    {

    }

    public function test_sqlGen()
    {

    }
    public function test_prepare()
    {

    }
    public function test_runQuery()
    {

    }
    public function test_toResult()
    {

    }
    public function test_first()
    {

    }
    public function test_firstScalar()
    {

    }
    public function test_last()
    {

    }

    public function test_insert_id()
    {

    }
    public function test_affected_rows()
    {

    }

    public function test_setEncryption()
    {

    }
    public function test_encrypt()
    {

    }
    public function test_decrypt()
    {

    }
    public function test_lastError()
    {

    }
}
