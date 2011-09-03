#Description#
A simple Fuel 1.x package that provides a basic PDO interface, for developers who actually know SQL.

##Why not use the default classes?##
First and foremost, it lets you use any database server that PDO supports with just a switch of the DSN in your config file. Even the default Fuel PDO driver contains MySQL-specific code (see the set_charset method).

Secondly, performance and security. This is why PDO was introduced. Using prepared statements allows us significantly improved performance for queries that are run multiple times (particularly on RDBMS platforms that natively support them) and bound parameters automatically protect us from SQL Injection. Why are we *still* performing string replacement and escaping on queries? It's slower, more error prone, and totally unnecessary.

Fuel, like Kohana before it, calls their queries 'prepared statements' but this is not true, they merely emulate bound parameters with string replacement operations (they use strtr, hence a warning in the docs not to use dollar signs) - you won't get any of the improved performance benefits. Even if you use their 'PDO' connection type, you're still wrapping all their string escaping and replacement code around your PDO connection. Every operation uses the PDO::query() method, there are no prepared statements or PDO bound parameters. Bad form...

Lastly, and actually most importantly for me, Fuel assumes you're incapable or afraid of writing even the simplest SQL on your own and they go out of their way to hide it from you and make it difficult to execute your own queries. From the online guide:

	$query = DB::select()->from('users')->where('username', '=', 'john');
	
This isn't their ORM layer, either, this is the raw database 'query builder' functionality. Even their totally 'raw' query functionality is peculiarly complex:

	$query = DB::query(Database::SELECT, 'SELECT * FROM users WHERE username = :user');
	
	foreach ( $query->execute() as $user ) {
		echo $user->get('username');
	}

##The PDO Way##
This seems quite cumbersome to me, when you could be using a more basic PDO interface:

	// instantiate your database connection
	$db = DB::factory();
	
	// only getting a single row? then get a single row...
	$user = $db->get_row( 'SELECT username, password FROM users WHERE username = :user', array( ':user' => 'foo' ) );
	echo $user->username;

#Installation#
The easiest way is to add a submodule to your existing git-based project:

	git submodule add https://github.com/chrismeller/db.git fuel/packages/db
	git submodule update --init fuel/packages/db
	
Then you'll probably want to tell Fuel to auto load the DB package in your ``fuel/app/bootstrap.php`` file:

	'always_load' => array(
		
		'packages' => array(
			'db',
		),
		
	),

##Configuration##
Copy the ``config/db.php`` file to your ``fuel/app/config`` directory and edit its values as appropriate.

You'll note that there are multiple environments available. You can add different settings for as many environments as you like. By default the library will load the settings for the environment declared in the ``Fuel::$env`` variable, which is set to the ``environment`` value you specify in config.php. If no environment setting is defined (or there are not settings for the current environment) it will load the 'default' environment settings.