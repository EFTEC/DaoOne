<?php
namespace eftec\tests;
use eftec\DaoOne;
use PHPUnit\Framework\TestCase;


class DaoOneTest extends TestCase
{
    var $daoOne;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        //you could change it.
        $this->daoOne=new DaoOne("127.0.0.1","travis","","travisdb");
        $this->daoOne->connect();
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
        $this->expectException(\Exception::class);
        $this->daoOne->connect();
    }

    public function test_open()
    {

    }

    public function test_close()
    {

    }

    public function test_getMessages()
    {

    }

    public function test_runMultipleRawQuery()
    {

    }

    public function test_startTransaction()
    {

    }

    public function test_commit()
    {

    }

    public function test_rollback()
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

    public function test_where()
    {

    }

    public function test_set()
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

    public function test_runGen()
    {

    }

    public function test_generateSqlFields()
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
        $this->assertEquals(true,$this->daoOne->runQuery($this->daoOne->prepare('select 1 from dual'))); 
        $this->assertEquals([1=>1],$this->daoOne->select('1')->from('dual')->first(),'it must runs');
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

    public function test_runRawQuery()
    {
        $this->assertEquals([0=>[1=>1]],$this->daoOne->runRawQuery('select 1',null,true));
    }

    public function test_insert_id()
    {

    }

    public function test_affected_rows()
    {

    }

    public function test_update()
    {

    }

    public function test_insert()
    {

    }

    public function test_delete()
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
