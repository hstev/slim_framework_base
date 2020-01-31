<?php
	//ini_set('max_execution_time', '9999');
	//ini_set('memory_limit', '99999M');

	function connect()
	{
		
		$host = "mysql:host=localhost;dbname=prueba_db";
		$user = "root";
		$password = "";

		$dbh = new PDO($host, $user, $password);
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		
		$dbh->exec("SET CHARACTER SET utf8");
		return $dbh;
	}

?>