<?php

	return array(
	
		'default' => array(
			'dsn' => 'mysql:host=localhost;dbname=test',
			'username' => 'test',
			'password' => 'test',
			'table_prefix' => 't__',
			
			'charset' => 'utf8',
			'caching' => false,
			'profiling' => false,
			'persistent' => false,
		),
		
		'dev' => array(
			'dsn' => 'mysql:host=localhost;dbname=test',
			'username' => 'test',
			'password' => 'test',
			'table_prefix' => 't__',
			
			'charset' => 'utf8',
			'caching' => false,
			'profiling' => true,
			'persistent' => false,
		)
		
	);


?>