<?php

use eftec\DaoOne;

include "../vendor/autoload.php";
include "dBug.php";

// connecting to database sakila at 127.0.0.1 with user root and password abc.123
$dao=new DaoOne("127.0.0.1","root","abc.123","sakila","logdaoone.txt");
try {
    echo "<h1>connection. The instance 127.0.0.1, base:sakila  user:root and password:abc.123 must exists</h1>";
    $dao->connect();
    echo "Connected A-OK!<br>";
} catch (Exception $e) {
    echo "<h2>connection error:</h2>";
    echo $dao->lastError()."-".$e->getMessage()."<br>";
    die(1);
}
$sqlT1="CREATE TABLE `typetable` (
    `type` INT NOT NULL,
    `name` VARCHAR(45) NULL,
    PRIMARY KEY (`type`));";

$sqlT2="CREATE TABLE `producttype` (
    `idproducttype` INT NOT NULL,
    `name` VARCHAR(45) NULL,
    `type` int not NULL,
    PRIMARY KEY (`idproducttype`));";

$now=new DateTime();
// running a raw query (unprepared statement)
try {
    echo "<h1>Table creation (it's ok if it fails if exists):</h1>";
    $dao->runRawQuery($sqlT1);
    echo $dao->lastQuery."<br>";
    $dao->runRawQuery($sqlT2);
    echo $dao->lastQuery."<br>";
} catch (Exception $e) {
    echo "<h2>Table creation error:</h2>";
    echo $dao->lastError()."-".$e->getMessage()."<br>";
}

try {
    echo "<h1>Table truncate :</h1>";
    $dao->runRawQuery("truncate table typetable");
    echo $dao->lastQuery."<br>";
    $dao->runRawQuery("truncate table producttype");
    echo $dao->lastQuery."<br>";
} catch (Exception $e) {
    echo "<h2>Table truncate error:</h2>";
    echo $dao->lastError()."-".$e->getMessage()."<br>";
}

try {
    echo "<h1>Table insert (it's ok if it fails if exists):</h1>";
    $dao->runRawQuery('insert into `typetable`(`type`,`name`) values(?,?)'
        ,array('i',1,'s','Drink'));
    echo $dao->lastQuery."<br>";
    $dao->runRawQuery('insert into `typetable`(`type`,`name`) values(?,?)'
        ,array('i',2,'s','Yummy'));
    echo $dao->lastQuery."<br>";

    // $dao->insert("producttype",['idproducttype','i','name','s','type','i'],[1,'Coca-Cola',1]);
    $dao->insert("producttype"
        ,['idproducttype','i',1 ,'name','s','Coca-Cola' ,'type','i',1]); // type1
    echo $dao->lastQuery."<br>";
    $dao->insert("producttype"
        ,['idproducttype','i','name','s','type','i']
        ,[2,'Fanta',1]); // type 2
    echo $dao->lastQuery."<br>";
    $dao->insert("producttype"
        ,['idproducttype'=>'i','name'=>'s','type'=>'i']
        ,['idproducttype'=>3,'name'=>'Sprite','type'=>'1']); // type3 arrays declarative
    echo $dao->lastQuery."<br>";
    $dao->insert("producttype"
        ,['idproducttype'=>4,'name'=>"Kellogg's",'type'=>2]); // type 4 array declarative, automatic type
    echo $dao->lastQuery."<br>";
    $dao->insert("producttype",['idproducttype','i','name','s','type','i'],[5,'Chocapic',2]);
    echo $dao->lastQuery."<br>";
    $dao->insert("producttype",['idproducttype','i','name','s','type','i'],[6,'CaptainCrunch',2]);
    echo $dao->lastQuery."<br>";
    $dao->insert("producttype",['idproducttype','i','name','s','type','i'],[7,'will be deleted 1',2]);
    echo $dao->lastQuery."<br>";
    $dao->insert("producttype",['idproducttype','i','name','s','type','i'],[8,'will be deleted 2',2]);
    echo $dao->lastQuery."<br>";

} catch (Exception $e) {
    echo "<h2>Table insert error:</h2>";
    echo $dao->lastError()."-".$e->getMessage()."<br>";
}

try {
    echo "<h1>Table update:</h1>";
    $dao->update("producttype"
        ,['name','s','type','i']
        ,['Captain-Crunch',2]
        ,['idproducttype','i']
        ,[6]);
    echo $dao->lastQuery."<br>";
    $dao->update("producttype"
        ,['Name'=>'Mountain Dew']
        ,['idproducttype'=>3]
        );
    echo $dao->lastQuery."<br>";
} catch (Exception $e) {
    echo "<h2>Table update error:</h2>";
    echo $dao->lastError()."-".$e->getMessage()."<br>";
}

try {
    echo "<h1>Table delete:</h1>";
    $dao->delete("producttype"
        ,['idproducttype','i']
        ,[7]);
    echo $dao->lastQuery."<br>";
    $dao->delete("producttype"
        ,['idproducttype'=>8]
    );
    echo $dao->lastQuery."<br>";
} catch (Exception $e) {
    echo "<h2>Table delete error:</h2>";
    echo $dao->lastError()."-".$e->getMessage()."<br>";
}


try {
    echo "<hr>toList:";
    $results = $dao->select("*")->from("producttype")
        ->where('name=?', ['s', 'Coca-Cola'])
        ->where('idproducttype=?', ['i', 1])
        ->toList();
    echo $dao->lastQuery;
    echo build_table($results);

    echo "<hr>toList using associative array:";
    $results = $dao->select("*")->from("producttype")
        ->where(['name'=>'Coca-Cola','idproducttype'=>1])
        ->toList();
    echo $dao->lastQuery;
    echo build_table($results);

    echo "<hr>toList using associative array:";
    $results = $dao->select("*")->from("producttype")
        ->where(['name'=>'s','idproducttype'=>'i'],
        ['name'=>'Coca-Cola','idproducttype'=>1])
        ->toList();
    echo $dao->lastQuery;
    echo build_table($results);

    echo "<hr>toList using associative array:";
    $results = $dao->select("*")->from("producttype")
        ->where(['name','s','idproducttype','i'],
            ['Coca-Cola',1])
        ->toList();
    echo $dao->lastQuery;
    echo build_table($results);

    echo "<hr>toList using associative array:";
    $results = $dao->select("*")->from("producttype")
        ->where(['name','s','Coca-Cola','idproducttype','i',1])
        ->toList();
    echo $dao->lastQuery;
    echo build_table($results);

    echo "<hr>toList (from join):";
    $results = $dao->select("pt.*,tt.name typetable_name")
        ->from("producttype pt")
        ->join("typetable tt on pt.type=tt.type")
        ->toList();
    echo $dao->lastQuery;
    echo build_table($results);

    echo "<hr>toList (join left):";
    $results = $dao->select("pt.*,tt.name typetable_name")
        ->join("producttype pt")
        ->left("typetable tt on pt.type=tt.type")
        ->toList();
    echo $dao->lastQuery;
    echo build_table($results);

    echo "<hr>toList (join left):";
    $results = $dao->select("pt.*,tt.name typetable_name")
        ->join("producttype pt")
        ->left("typetable tt on pt.type=tt.type")
        ->first();
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
        ->where('name=?', ['s', 'Coca-Cola'])
        ->where('idproducttype=?', ['i', 1])
        ->toResult();
    echo $dao->lastQuery;
    $results=$resultsQuery->fetch_all(MYSQLI_ASSOC);
    echo build_table($results);
    $resultsQuery->free_result();

    echo "<hr>first: ";
    $results = $dao->select("*")->from("producttype")
        ->where('name=?', ['s', 'Coca-Cola'])
        ->where('idproducttype=?', ['i', 1])
        ->limit('1')
        ->first();
    echo $dao->lastQuery;
    echo build_table($results);

    echo "<hr>first returns nothing :";
    $results = $dao->select("*")->from("producttype")
        ->where('name=?', ['s', 'Coca-Cola'])
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
    if (!isset($array[0])) {
        $tmp=$array;
        $array=array();
        $array[0]=$tmp;
    } // create an array with a single element
    if ($array[0]===null) {
        return "NULL<br>";
    }
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