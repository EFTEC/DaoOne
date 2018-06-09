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

