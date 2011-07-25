<?php

	return array(
	
		'default' => array(
			'type' => 'mysql',
			'dsn' => 'mysql:host=localhost;dbname=test',
			'username' => 'test',
			'password' => 'test',
			'table_prefix' => 't__',
			
			'charset' => 'utf8',
			'caching' => false,
			'profiling' => false,
			'persistent' => false,
	
			'attributes' => array()
		),
		
		'dev' => array(
			'type' => 'mysql',
			'dsn' => 'mysql:host=localhost;dbname=test',
			'username' => 'test',
			'password' => 'test',
			'table_prefix' => 't__',
			
			'charset' => 'utf8',
			'caching' => false,
			'profiling' => true,
			'persistent' => false,
		
			'attributes' => array()
		)
		
	);


?>