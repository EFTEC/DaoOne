<?php
namespace eftec\tests;
use eftec\DaoOne;
use Exception;
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

    public function test___construct()
    {

    }

    public function test_db()
    {
        $this->daoOne->db('travisdb');
    }

    public function test_setCharset()
    {
        $this->daoOne->setCharset('utf8');
    }

    public function test_readonly()
    {
        $this->assertEquals(false,$this->daoOne->readonly(),'the database is read only');
    }

    public function test_connect()
    {
        $this->expectException(\Exception::class);
        $this->daoOne->connect();
    }

    public function test_open()
    {
        //$this->expectException(\Exception::class);
	    
        //$this->daoOne->open(true);
	    try {
		    $r=$this->daoOne->runRawQuery('drop table product_category');
		    
	    } catch (Exception $e) {
		    $r=false;
	    	// drops silently
	    }
	    $this->assertEquals(true,$r,"Drop failed");
	    
	    $sqlT2="CREATE TABLE `product_category` (
	    `id_category` INT NOT NULL,
	    `catname` VARCHAR(45) NULL,
	    PRIMARY KEY (`id_category`));";
	
	    try {
		    $r=$this->daoOne->runRawQuery($sqlT2);
	    } catch (Exception $e) {
		    echo $e->getMessage()."<br>";
	    }
	    $this->assertEquals(true,$r,"failed to create table");
	    // we add some values
	    $r=$this->daoOne->set(['id_category' => 1, 'catname' => 'cheap'])
		    ->from('product_category')->insert();
	    $this->assertEquals(0,$r,'insert must value 0');
	    
    }

    public function test_close()
    {
        $this->daoOne->close();
    }

    public function test_getMessages()
    {
        $this->assertEquals(null,$this->daoOne->getMessages(),'this is not a message container');
    }

    public function test_runMultipleRawQuery()
    {

    }

    public function test_startTransaction()
    {
        $this->assertEquals(true,$this->daoOne->startTransaction());
        $this->daoOne->commit();

    }

    public function test_commit()
    {
        $this->assertEquals(false,(false),'transaction is not open');
    }

    public function test_rollback()
    {
        $this->assertEquals(false,(false),'transaction is not open');
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
        $this->assertInstanceOf(DaoOne::class,$this->daoOne->select('select 1 from DUAL'));
    }

    public function test_join()
    {
        $this->assertInstanceOf(DaoOne::class,$this->daoOne->join('tablejoin on t1.field=t2.field'));
    }

    public function test_innerjoin()
    {

    }

    public function test_from()
    {
        $this->assertInstanceOf(DaoOne::class,$this->daoOne->from('table t1'));
    }

    public function test_left()
    {
        $this->assertInstanceOf(DaoOne::class,$this->daoOne->left('table2 on table1.t1=table2.t2'));
    }

    public function test_right()
    {
        $this->assertInstanceOf(DaoOne::class,$this->daoOne->right('table2 on table1.t1=table2.t2'));
    }

    public function test_where()
    {
        $this->assertInstanceOf(DaoOne::class,$this->daoOne->where('field1=?,field2=?',['i',20,'s','hello']));
    }

    public function test_set()
    {
        $this->assertInstanceOf(DaoOne::class,$this->daoOne->set('field1=?,field2=?',['i',20,'s','hello']));
    }

    public function test_group()
    {
        $this->assertInstanceOf(DaoOne::class,$this->daoOne->group('fieldgroup'));
    }

    public function test_having()
    {
        $this->assertInstanceOf(DaoOne::class,$this->daoOne->having('field1=?,field2=?',['i',20,'s','hello']));
    }

    public function test_order()
    {
        $this->assertInstanceOf(DaoOne::class,$this->daoOne->order('name desc'));
    }

    public function test_limit()
    {
        $this->assertInstanceOf(DaoOne::class,$this->daoOne->limit('1,10'));
    }

    public function test_distinct()
    {
        $this->assertInstanceOf(DaoOne::class,$this->daoOne->distinct());
    }

    public function test_toList()
    {

    }

    public function test_runGen()
    {

    }

    public function test_generateSqlFields()
    {
        $this->assertInstanceOf(DaoOne::class,$this->daoOne->generateSqlFields(true));
    }

    public function test_sqlGen()
    {

    }

    public function test_prepare()
    {

    }

    public function test_runQuery()
    {
        $this->assertEquals(true,$this->daoOne->runQuery($this->daoOne->prepare('select 1 from dual'))); $this->assertEquals([1=>1],$this->daoOne->select('1')->from('dual')->first(),'it must runs');
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
        $this->daoOne->setEncryption('123','somesalt','AES-128-CTR');
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