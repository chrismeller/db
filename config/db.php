<?php

	return array(
	
		'default' => array(
			'dsn' => 'mysql:host=localhost;dbname=uptime',
			'username' => 'uptime',
			'password' => 'uptime',
			'table_prefix' => 'u__',
			
			'charset' => 'utf8',
			'caching' => false,
			'profiling' => false,
			'persistent' => false,
		),
		
		'dev' => array(
			'dsn' => 'mysql:host=localhost;dbname=uptime',
			'username' => 'uptime',
			'password' => 'uptime',
			'table_prefix' => 'u__',
			
			'charset' => 'utf8',
			'caching' => false,
			'profiling' => true,
			'persistent' => false,
		)
		
	);


?>