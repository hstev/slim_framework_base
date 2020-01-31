<?php
	require 'Slim/Slim.php';

	\Slim\Slim::registerAutoloader();

	$app = new \Slim\Slim();

	$app->get('/',
	    function () 
		{
			Functions::generateJson(array("error"=>"", "msj"=>"API Working perfectly"));	
	    }
	);


	require("backend/db.php");
	require("backend/functions.php");
	
	$app->run();



