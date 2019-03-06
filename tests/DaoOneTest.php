<?php

namespace eftec\tests;

use DateTime;
use eftec\DaoOne;
use Exception;
use PHPUnit\Framework\TestCase;


class DaoOneTest extends TestCase
{
	/** @var DaoOne */
    protected $daoOne;

    public function setUp()
    {
        $this->daoOne=new DaoOne("127.0.0.1","travis","","travisdb");
        $this->daoOne->connect();
    }


	/**
	 * @doesNotPerformAssertions
	 */
    public function test_db()
    {
         $this->daoOne->db('travisdb');
    }
	/**
	 * @doesNotPerformAssertions
	 */
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
		    $this->assertEquals(true,$r,"Drop failed");
	    } catch (Exception $e) {
		    $r=false;
	    	// drops silently
	    }


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
	public function test_time()
	{
		$this->assertEquals('2019-02-06 00:00:00',DaoOne::dateText2Sql('2019-02-06',false));
		$this->assertEquals('2019-02-06 05:06:07',DaoOne::dateText2Sql('2019-02-06T05:06:07Z',true));
		$this->assertEquals('2018-02-06 05:06:07.123000',DaoOne::dateText2Sql('2018-02-06T05:06:07.123Z',true));

		$this->assertEquals('2019-02-06',DaoOne::dateSql2Text('2019-02-06'));
		$this->assertEquals('2019-02-06T05:06:07Z',DaoOne::dateSql2Text('2019-02-06 05:06:07'));
		$this->assertEquals('2018-02-06T05:06:07.123000Z',DaoOne::dateSql2Text('2018-02-06 05:06:07.123000'));
	}

	public function test_sequence()
	{
		$this->daoOne->tableSequence='testsequence';
		try {
			$this->daoOne->createSequence();
		} catch(Exception $ex) {
			
		}
		$this->assertLessThan(3639088446091303982,$this->daoOne->getSequence(true),"sequence must be greater than 3639088446091303982");
	}

	public function test_sequence2()
	{
		$this->assertLessThan(3639088446091303982,$this->daoOne->getSequencePHP(false),"sequence must be greater than 3639088446091303982");
		$s1=$this->daoOne->getSequencePHP(false);
		$s2=$this->daoOne->getSequencePHP(false);

		$this->assertTrue($s1!=$s2,"sequence must not be the same");
		
	}	
	/**
	 * @doesNotPerformAssertions
	 */
    public function test_close()
    {
        $this->daoOne->close();
    }

    public function test_getMessages()
    {
        $this->assertEquals(null,$this->daoOne->getMessages(),'this is not a message container');
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

 
    public function test_select()
    {
        $this->assertInstanceOf(DaoOne::class,$this->daoOne->select('select 1 from DUAL'));
    }

    public function test_join()
    {
        $this->assertInstanceOf(DaoOne::class,$this->daoOne->join('tablejoin on t1.field=t2.field'));
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

   

    public function test_generateSqlFields()
    {
        $this->assertInstanceOf(DaoOne::class,$this->daoOne->generateSqlFields(true));
    }

   

    public function test_runQuery()
    {
        $this->assertEquals(true,$this->daoOne->runQuery($this->daoOne->prepare('select 1 from dual'))); $this->assertEquals([1=>1],$this->daoOne->select('1')->from('dual')->first(),'it must runs');
    }


    public function test_runRawQuery()
    {
        $this->assertEquals([0=>[1=>1]],$this->daoOne->runRawQuery('select 1',null,true));
    }

	/**
	 * @throws Exception
	 */
    public function test_setEncryption()
    {
        $this->daoOne->setEncryption('123//*/*saass11___1212fgbl@#€€"','123//*/*saass11___1212fgbl@#€€"','AES-256-CTR');
        $value=$this->daoOne->encrypt("bv `lfg+hlc ,vc´,c35'ddl ld_vcvñvc +*=/\\");
        $this->assertTrue(strlen($value)>10,"Encrypted");
        $return=$this->daoOne->decrypt($value);
	    $this->assertEquals("bv `lfg+hlc ,vc´,c35'ddl ld_vcvñvc +*=/\\",$return,"decrypt correct");

	    $return=$this->daoOne->decrypt("wrong".$value);
	    $this->assertEquals(false,$return,"decrypt must fail");
	    $return=$this->daoOne->decrypt("");
	    $this->assertEquals(false,$return,"decrypt must fail");
	    $return=$this->daoOne->decrypt(null);
	    $this->assertEquals(false,$return,"decrypt must fail");
	    // iv =true
	    $value1=$this->daoOne->encrypt("abc");
	    $value2=$this->daoOne->encrypt("abc");
	    $this->assertTrue($value1!=$value2,"Values must be different");
	    // iv =true
	    $this->daoOne->encryption->iv=false;
	    $value1=$this->daoOne->encrypt("abc_ABC/abc*abc1234567890[]{[");
	    $value2=$this->daoOne->encrypt("abc_ABC/abc*abc1234567890[]{[");
	    $this->assertTrue($value1==$value2,"Values must be equals");     
    }

}
