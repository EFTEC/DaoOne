# Database Access Object wrapper for PHP and MySqli in a single class

DaoOne. It's a simple wrapper for Mysqli

[![Packagist](https://img.shields.io/packagist/v/eftec/daoone.svg)](https://packagist.org/packages/eftec/bladeone)
[![Maintenance](https://img.shields.io/maintenance/yes/2018.svg)]()
[![composer](https://img.shields.io/badge/composer-%3E1.6-blue.svg)]()
[![php](https://img.shields.io/badge/php->5.6-green.svg)]()
[![php](https://img.shields.io/badge/php-7.x-green.svg)]()
[![CocoaPods](https://img.shields.io/badge/docs-70%25-yellow.svg)]()

## Table of Content

- [DaoOne](#daoone)
  * [Install (using composer)](#install--using-composer-)
  * [Install (manually)](#install--manually-)
  * [Usage](#usage)
    + [Start a connection](#start-a-connection)
    + [Run an unprepared query](#run-an-unprepared-query)
    + [Run a prepared query](#run-a-prepared-query)
    + [Run a prepared query with parameters.](#run-a-prepared-query-with-parameters)
    + [Return data (first method)](#return-data--first-method-)
    + [Return data (second method)](#return-data--second-method-)
    + [Running a transaction](#running-a-transaction)
  * [Query Builder (DQL)](#query-builder--dql-)
    + [select($columns)](#select--columns-)
    + [distinct($distinct='distinct')](#distinct--distinct--distinct--)
    + [from($tables)](#from--tables-)
    + [where($where,[$arrayParameters=array()])](#where--where---arrayparameters-array----)
    + [order($order)](#order--order-)
    + [group($group)](#group--group-)
    + [having($having,[$arrayParameters])](#having--having---arrayparameters--)
    + [runGen($returnArray=true)](#rungen--returnarray-true-)
    + [toList()](#tolist--)
    + [toResult()](#toresult--)
    + [first()](#first--)
    + [last()](#last--)
    + [sqlGen()](#sqlgen--)
  * [Query Builder (DML), i.e. insert, update,delete](#query-builder--dml---ie-insert--update-delete)
    + [insert($table,$schema,[$values])](#insert--table--schema---values--)
    + [update($$table,$schema,$values,[$schemaWhere],[$valuesWhere])](#update---table--schema--values---schemawhere----valueswhere--)
    + [delete($table,$schemaWhere,[$valuesWhere])](#delete--table--schemawhere---valueswhere--)
  * [Changelist](#changelist)



## Install (using composer)

Add to composer.json the next requirement, then update composer.

```json
  {
      "require": {
        "eftec/daoone": "2.*"
      }
  }
```
or install it via cli using

> composer require eftec/daoone

## Install (manually)

Just download the file lib/DaoOne.php and save it in a folder.

## Usage

### Start a connection

```php
$dao=new DaoOne("127.0.0.1","root","abc.123","sakila","");
$dao->connect();
```

where 
* 127.0.0.1 is the server where is the database.
* root is the user   
* abc.123 is the password of the user root.
* sakila is the database used.
* "" (optional) it could be a log file, such as c:\temp\log.txt

### Run an unprepared query

```php
$sql="CREATE TABLE `product` (
    `idproduct` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(45) NULL,
    PRIMARY KEY (`idproduct`));";
$dao->runRawQuery($sql);  
```

### Run a prepared query
```php
$sql="insert into `product`(name) values(?)";
$stmt=$dao->prepare($sql);
$productName="Cocacola";
$stmt->bind_param("s",$productName); // s stand for string. Also i =integer, d = double and b=blob
$dao->runQuery($stmt);
```

### Run a prepared query with parameters.
```php
$dao->runRawQuery('insert into `product` (name) values(?)'
    ,array('s','cocacola'));
```



### Return data (first method)

```php
    $sql="select * from `product` order by name";
    $stmt=$dao->prepare($sql);
    $dao->runQuery($stmt);
    $rows = $stmt->get_result();
    while ($row = $rows->fetch_assoc()) {
        var_dump($row);
    }
    
```    


### Return data (second method)

```php
    $sql="select * from `product` order by name";
    $stmt=$dao->prepare($sql);
    $dao->runQuery($stmt);
    $rows = $stmt->get_result();
    $allRows=$rows->fetch_all(MYSQLI_ASSOC);
    var_dump($allRows);
```    

### Running a transaction
```php
try {
    $sql="insert into `product`(name) values(?)";
    $dao->startTransaction();
    $stmt=$dao->prepare($sql);
    $productName="Fanta";
    $stmt->bind_param("s",$productName); 
    $dao->runQuery($stmt);
    $dao->commit(); // transaction ok
} catch (Exception $e) {
    $dao->rollback(); // error, transaction cancelled.
}
```   

## Query Builder (DQL)
You could also build a procedural query.

Example:
```php
$results = $dao->select("*")->from("producttype")
    ->where('name=?', ['s', 'Cocacola'])
    ->where('idproducttype=?', ['i', 1])
    ->toList();   
```

### select($columns)
Generates a select command.
```php
$results = $dao->select("col1,col2")->...
```
> Generates the query: **select col1,col2** ....

### distinct($distinct='distinct')
Generates a select command.
```php
$results = $dao->select("col1,col2")->distinct()...
```
> Generates the query: select **distinct** col1,col2 ....

>Note: ->distinct('unique') returns select **unique** ..

### from($tables)
Generates a from command.
```php
$results = $dao->select("*")->from('table')...
```
> Generates the query: select * **from table**

**$tables** could be a single table or a sql construction. For examp, the next command is valid:

```php
$results = $dao->select("*")->from('table t1 inner join t2 on t1.c1=t2.c2')...
```


### where($where,[$arrayParameters=array()])
Generates a where command.

* $where is an array or a string. If it's a string, then it's evaluated by using the parameters. if any

```php
$results = $dao->select("*")
->from('table')
->where('p1=1')...
```
> Generates the query: select * **from table** where p1=1

> Note: ArrayParameters is an array as follow: **type,value.**     
>   Where type is i=integer, d=double, s=string or b=blob. In case of doubt, use "s"   
> Example of arrayParameters:   
> ['i',1 ,'s','hello' ,'d',20.3 ,'s','world']

```php
$results = $dao->select("*")
->from('table')
->where('p1=?',['i',1])...
```
> Generates the query: select * from table **where p1=?(1)**

```php
$results = $dao->select("*")
->from('table')
->where('p1=? and p2=?',['i',1,'s','hello'])...
```

> Generates the query: select * from table **where p1=?(1) and p2=?('hello')**

> Note. where could be nested.
```php
$results = $dao->select("*")
->from('table')
->where('p1=?',['i',1])
->where('p2=?',['s','hello'])...
```
> Generates the query: select * from table **where p1=?(1) and p2=?('hello')**

You could also use:
```php
$results = $dao->select("*")->from("table")
    ->where(['p1'=>'Coca-Cola','p2'=>1])
    ->toList();
```
> Generates the query: select * from table **where p1=?(Coca-Cola) and p2=?(1)**        

### order($order)
Generates a order command.
```php
$results = $dao->select("*")
->from('table')
->order('p1 desc')...
```
> Generates the query: select * from table **order by p1 desc**

### group($group)
Generates a group command.
```php
$results = $dao->select("*")
->from('table')
->group('p1')...
```
> Generates the query: select * from table **group by p1**

### having($having,[$arrayParameters])
Generates a group command.
```php
$results = $dao->select("*")
->from('table')
->group('p1')
->having('p1>?',array('i',1))...
```
> Generates the query: select * from table group by p1 having p1>?(1)

> Note: Having could be nested having()->having()  
> Note: Having could be without parameters having('col>10') 

### runGen($returnArray=true)
Run the query generate.

>Note if returnArray is true then it returns an associative array.
> if returnArray is false then it returns a mysqli_result  
>Note: It resets the current parameters (such as current select, from, where,etc.)

### toList()
It's a macro of runGen. It returns an associative array or null.

```php
$results = $dao->select("*")
->from('table')
->toList()
```
### toResult()
It's a macro of runGen. It returns a mysqli_result or null.

```php
$results = $dao->select("*")
->from('table')
->toResult()
```

### first()
It's a macro of runGen. It returns the first row (if any, if not, it returns false) as an associative array.

```php
$results = $dao->select("*")
->from('table')
->first()
```

### last()
It's a macro of runGen. It returns the last row (if any, if not, it returns false) as an associative array.

```php
$results = $dao->select("*")
->from('table')
->last()
```
> Sometimes is more efficient to run order() and first() because last() reads all values.

### sqlGen()

It returns the sql command.
```php
$sql = $dao->select("*")
->from('table')
->sqlGen();
echo $sql; // returns select * from table
$results=$dao->toList(); // executes the query
```
> Note: it doesn't reset the query.

## Query Builder (DML), i.e. insert, update,delete

There are four ways to execute each command.

Let's say that we want to add an **integer** in the column **col1** with the value **20**

__Schema and values using a list of values__: Where the first value is the column, the second is the type of value (i=integer,d=double,s=string,b=blob) and second array contains the values.
```php
$dao->insert("table"
    ,['col1','i']
    ,[20]);
```
__Schema and values in the same list__: Where the first value is the column, the second is the type of value (i=integer,d=double,s=string,b=blob) and the third is the value.
```php
$dao->insert("table"
    ,['col1','i',20]);
```

__Schema and values using two associative arrays__:

```php
$dao->insert("table"
    ,['col1'=>'i']
    ,['col1'=>20]);
```
__Schema and values using a single associative array__: The type is calculated automatically.

```php
$dao->insert("table"
    ,['col1'=>20]);
```

### insert($table,$schema,[$values])
Generates a insert command.

```php
$dao->insert("producttype"
    ,['idproducttype','i','name','s','type','i']
    ,[1,'cocacola',1]);
```

Using nested chain (single array)
```php
    $dao->from("producttype")
        ->set(['idproducttype','i',0 ,'name','s','Pepsi' ,'type','i',1])
        ->insert();
```

Using nested chain multiple set
```php
    $dao->from("producttype")
        ->set("idproducttype=?",['i',101])
        ->set('name=?',['s','Pepsi'])
        ->set('type=?',['i',1])
        ->insert();
```
    
Using nested chain declarative set
```php
    $dao->from("producttype")
        ->set('(idproducttype,name,type) values (?,?,?)',['i',100,'s','Pepsi','i',1])
        ->insert();
```


> Generates the query: **insert into productype(idproducttype,name,type) values(?,?,?)** ....


### update($$table,$schema,$values,[$schemaWhere],[$valuesWhere])
Generates a insert command.

```php
$dao->update("producttype"
    ,['name','s','type','i'] //set
    ,[6,'Captain-Crunch',2] //set
    ,['idproducttype','i'] // where
    ,[6]); // where
```

```php
$dao->update("producttype"
    ,['name'=>'Captain-Crunch','type'=>2] // set
    ,['idproducttype'=>6]); // where
```

```php
$dao->from("producttype")
    ->set("name=?",['s','Captain-Crunch']) //set
    ->set("type=?",['i',6]) //set
    ->where('idproducttype=?',['i',6]) // where
    ->update(); // update
```


> Generates the query: **update producttype set `name`=?,`type`=? where `idproducttype`=?** ....

### delete([$table],[$schemaWhere],[$valuesWhere])
Generates a delete command.

```php
$dao->delete("producttype"
    ,['idproducttype','i'] // where
    ,[7]); // where
```
```php
$dao->delete("producttype"
    ,['idproducttype'=>7]); // where
```
> Generates the query: **delete from producttype where `idproducttype`=?** ....

You could also delete via a DQL builder chain.
```php
$dao->from("producttype")
    ->where('idproducttype=?',['i',7]) // where
    ->delete(); 
```
```php
$dao->from("producttype")
    ->where(['idproducttype'=>7]) // where
    ->delete(); 
```
> Generates the query: **delete from producttype where `idproducttype`=?** ....

## Changelist

* 3.9 2018-09-24 Some fixes
* 3.7 Added charset.
* 3.6 More fixes.
* 3.5 Small fixed.
* 3.4 DML new features. It allows nested operations 
    + ->from()->where()->delete()
    + ->from()->set()->where()->update()
    + ->from()->set()->insert()
* 3.3 DML modified. It allows a different kind of parameters.
* 3.2 Insert, Update,Delete
* 3.0 Major overhaul. It adds Query Builder features.
* 2.6.4 Better correction of error.
* 2.6.3 Fixed transaction. Now a nested transaction is not nested (and returns a false).
* 2.6 first public version


