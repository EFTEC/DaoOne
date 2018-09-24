<?php

use eftec\DaoOne;

include "../vendor/autoload.php";


// connecting to database sakila at 127.0.0.1 with user root and password abc.123
$dao=new DaoOne("127.0.0.1","root","abc.123","sakila","");
try {
    echo "<h1>connection. The instance 127.0.0.1, base:sakila  user:root and password:abc.123 must exists</h1>";
    $dao->connect();
    echo "Connected A-OK!<br>";
} catch (Exception $e) {
    echo "<h2>connection error:</h2>";
    echo $dao->lastError()."-".$e->getMessage()."<br>";
    die(1);
}
$sql="CREATE TABLE `product` (
  `idproduct` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(45) NULL,
  PRIMARY KEY (`idproduct`));";

$now=new DateTime();
// running a raw query (unprepared statement)
try {
    echo "<h1>Table creation:</h1>";
    $dao->runRawQuery($sql);
} catch (Exception $e) {
    echo "<h2>Table creation error:</h2>";
    echo $dao->lastError()."-".$e->getMessage()."<br>";
}

// running a prepared statement
try {
    echo "<h1>Inserting Cocacola (prepared)</h1>";
    $sql="insert into `product`(name) values(?)";
    $stmt=$dao->prepare($sql);
    $productName="Cocacola";
    $stmt->bind_param("s",$productName); // s stand for string. Also i =integer, d = double and b=blob
    $dao->runQuery($stmt);
    echo "Last id inserted :".$dao->insert_id()."<br>";
} catch (Exception $e) {
    echo "<h2>Insert Cocacola (prepared) error:</h2>";
    echo $dao->lastError()."-".$e->getMessage()."<br>";
}
try {
    echo "<h1>Inserting O'Hara (prepared)</h1>";
    $sql="insert into `product`(name) values(?)";
    $stmt=$dao->prepare($sql);
    $productName="O'Hara";
    $stmt->bind_param("s",$productName); // s stand for string. Also i =integer, d = double and b=blob
    $dao->runQuery($stmt);
    echo "Last id inserted :".$dao->insert_id()."<br>";
} catch (Exception $e) {
    echo "<h2>Insert (prepared) error:</h2>";
    echo $dao->lastError()."-".$e->getMessage()."<br>";
}


// returning data (using a prepared statement)
try {
    echo "<h1>select (prepared)</h1>";
    $sql="select * from `product` order by name";
    $stmt=$dao->prepare($sql);
    $dao->runQuery($stmt);

    $rows = $stmt->get_result();

    echo "<table><tr><th>Id</th><th>Name</th></tr>";

    // first method
    while ($row = $rows->fetch_assoc()) {
        echo "<tr><td>".$row['idproduct']."</td><td>".$row['name']."</td></tr>";
    }
    echo "</table><br>";
    // second method (fetch all fields)
    //$allRows=$rows->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    echo "<h1>select (prepared) error:</h1>";
    echo $dao->lastError()."-".$e->getMessage()."<br>";
}

// running a transaction
try {
    echo "<h1>Insert transactional (prepared)</h1>";
    $sql="insert into `product`(name) values(?)";
    $dao->startTransaction();
    $stmt=$dao->prepare($sql);
    $productName="Fanta";
    $stmt->bind_param("s",$productName); // s stand for string. Also i =integer, d = double and b=blob
    $dao->runQuery($stmt);
    echo "Last id inserted :".$dao->insert_id()."<br>";
    $dao->commit(); // end transaction
} catch (Exception $e) {
    echo "<h2>Insert transactional error:</h2>";
    echo $dao->lastError()."-".$e->getMessage()."<br>";

    $dao->rollback(); // cancel transaction

}