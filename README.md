# DaoOne
Database Access Object wrapper for PHP and MySqli in a single class

It's a simple wrapper for Mysqli

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

### Run a unprepared query

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

## Query Builder
You could also build procedure query.

Example:
```php
$results = $dao->select("*")->from("producttype")
    ->where('name=?', ['s', 'Cocacola'])
    ->where('idproducttype=?', ['i', 1])
    ->toList();   
```

### insert($table,$schema,$values)
Generates a insert command.

Where
* **$table** = is the name of the table
* **$schema**= is an array of the style [column,type,column2,type2...] where column is the name of the column and type is i=integer,d=double,s=string,b=blob
* **$values**= is an array with values.

```php
$dao->insert("producttype",['idproducttype','i','name','s','type','i'],[1,'cocacola',1]);
```
> Generates the query: **insert into productype(idproducttype,name,type) values(?,?,?)** ....


### update($$table,$schema,$values,$schemaWhere,$valuesWhere)
Generates a insert command.

Where
* **$table** = is the name of the table
* **$schema**= is an array of the style [column,type,column2,type2...] where column is the name of the column and type is i=integer,d=double,s=string,b=blob
* **$values**= is an array with values.
* **$schemaWhere**= is an array of the style [column,type,column2,type2...] where column is the name of the column and type is i=integer,d=double,s=string,b=blob
* **$valuesWhere**= is an array with values.

```php
$dao->update("producttype",['name','s','type','i'],[6,'Captain-Crunch',2],['idproducttype','i'],[6]);
```
> Generates the query: **update producttype set `name`=?,`type`=? where `idproducttype`=?** ....

### delete($table,$schemaWhere,$valuesWhere)
Generates a delete command.

Where
* **$table** = is the name of the table
* **$schemaWhere**= is an array of the style [column,type,column2,type2...] where column is the name of the column and type is i=integer,d=double,s=string,b=blob
* **$valuesWhere**= is an array with values.
```php
$dao->delete("producttype",['idproducttype','i'],[7]);
```
> Generates the query: **delete from producttype where `idproducttype`=?** ....



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

### where($where,[$arrayParameters=array()])
Generates a where command.
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
>Note: It resets the current parameters (such as current select,from,where,etc.)

### toList()
It's a macro of runGen. It returns an associative array or null

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

## Changelist

* 3.2 Insert, Update,Delete
* 3.0 Major overhaul. It adds Query Builder features.
* 2.6.4 Better correction of error.
* 2.6.3 Fixed transaction. Now a nested transanction is not nested (and returns a false).
* 2.6 first public version


