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

## Install (manually)

Just download the file lib/DaoOne.php and save it in a folder.

## Usage

### Start a connection

```php
$dao=new DaoOne("127.0.0.1","root","abc.123","sakila","");
$dao->connect();
```

where 127.0.0.1 is the server where is the database.
root is the user   
abc.123 is the password of the user root.
sakila is the database used.
"" (optional) it could be a log file, such as c:\temp\log.txt

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

## Changelist

2.6 first public version
2.6.3 Fixed transaction. Now a nested transanction is not nested (and returns a false).

