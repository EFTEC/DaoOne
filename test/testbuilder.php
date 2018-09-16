<?php

use eftec\DaoOne;

include "../vendor/autoload.php";


// connecting to database sakila at 127.0.0.1 with user root and password abc.123
$dao=new DaoOne("127.0.0.1","root","abc.123","sakila","");
try {
    echo "<h1>connection. The instance 127.0.0.1, base:sakile  user:root and password:abc.123 must exists</h1>";
    $dao->connect();
    echo "Connected A-OK!<br>";
} catch (Exception $e) {
    echo "<h2>connection error:</h2>";
    echo $dao->lastError()."-".$e->getMessage()."<br>";
    die(1);
}
$sql="CREATE TABLE `producttype` (
    `idproducttype` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(45) NULL,
    `type` int not NULL,
    PRIMARY KEY (`idproducttype`));";

$now=new DateTime();
// running a raw query (unprepared statement)
try {
    echo "<h1>Table creation:</h1>";
    $dao->runRawQuery($sql);
    $dao->runRawQuery('insert into `producttype`(idproducttype,name,type) values(?,?,?)',array('i',1,'s','cocacola','i',1));
    $dao->runRawQuery('insert into `producttype`(idproducttype,name,type) values(?,?,?)',array('i',2,'s','fanta','i',1));
    $dao->runRawQuery('insert into `producttype`(idproducttype,name,type) values(?,?,?)',array('i',3,'s','sprite','i',1));
    $dao->runRawQuery('insert into `producttype`(idproducttype,name,type) values(?,?,?)',array('i',4,'s','Kellows','i',2));
    $dao->runRawQuery('insert into `producttype`(idproducttype,name,type) values(?,?,?)',array('i',5,'s','Chocapic','i',2));
} catch (Exception $e) {
    echo "<h2>Table creation error:</h2>";
    echo $dao->lastError()."-".$e->getMessage()."<br>";
}
try {
    echo "<hr>toList:";
    $results = $dao->select("*")->from("producttype")
        ->where('name=?', ['s', 'Cocacola'])
        ->where('idproducttype=?', ['i', 1])
        ->toList();
    echo $dao->lastQuery;
    echo build_table($results);

    echo "<hr>toList: ";
    $results = $dao->select("*")->from("producttype")
        ->where('idproducttype>=?', ['i', 1])
        ->order('idproducttype desc')
        ->toList();
    echo $dao->lastQuery;
    echo build_table($results);


    echo "<hr>toResult: ";
    $resultsQuery = $dao->select("*")->from("producttype")
        ->where('name=?', ['s', 'Cocacola'])
        ->where('idproducttype=?', ['i', 1])
        ->toResult();
    echo $dao->lastQuery;
    $results=$resultsQuery->fetch_all(MYSQLI_ASSOC);
    echo build_table($results);
    $resultsQuery->free_result();

    echo "<hr>first: ";
    $results = $dao->select("*")->from("producttype")
        ->where('name=?', ['s', 'Cocacola'])
        ->where('idproducttype=?', ['i', 1])
        ->limit('1')
        ->first();
    echo $dao->lastQuery;
    echo "<br><pre>";
    var_dump($results);
    echo "</pre>";

    echo "<hr>first returns nothing :";
    $results = $dao->select("*")->from("producttype")
        ->where('name=?', ['s', 'Cocacola'])
        ->where('idproducttype=?', ['i', 55])
        ->limit('1')
        ->first();
    echo $dao->lastQuery;
    echo "<br><pre>";
    var_dump($results);
    echo "</pre>";

    echo "<hr>";
    $results = $dao->select("*")->from("producttype")
        ->where('idproducttype=1')
        ->runGen();
    echo $dao->lastQuery;
    echo build_table($results);

    echo "<hr>";
    $results = $dao->select("*")->from("producttype p")
        ->where('idproducttype between ? and ?', ['i', 1, 'i', 3])
        ->toList();
    echo $dao->lastQuery;

    echo build_table($results);

    echo "<hr>";
    $results = $dao->select("p.type,count(*) c")->from("producttype p")
        ->group("p.type")
        ->having('p.type>?',['i',0])
        ->toList();
    echo $dao->lastQuery;
    echo build_table($results);


} catch(Exception $ex) {
    echo $ex->getMessage();
    echo $ex->getTraceAsString();
}


function build_table($array){
    // start table
    $html = '<table style="border: 1px solid black;">';
    // header row
    $html .= '<tr>';
    foreach($array[0] as $key=>$value){
        $html .= '<th>' . htmlspecialchars($key) . '</th>';
    }
    $html .= '</tr>';

    // data rows
    foreach( $array as $key=>$value){
        $html .= '<tr>';
        foreach($value as $key2=>$value2){
            $html .= '<td>' . htmlspecialchars($value2) . '</td>';
        }
        $html .= '</tr>';
    }

    // finish table and return it

    $html .= '</table>';
    return $html;
}