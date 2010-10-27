#Description#
A simple Kohana 3.x module that provides a basic PDO interface, for developers who actually know SQL.

##Why not use the default modules?##
First off, performance and security. This is why PDO was introduced. Using prepared statements allows us significantly improved performance for queries that are run multiple times (particularly on RDBMS platforms that natively support them) and bound parameters automatically protect us from SQL Injection. Why are we *still* performing string replacement and escaping on queries? It's slower, more error prone, and totally unnecessary.

Kohana calls their queries 'prepared statements' but this is not true, they merely emulate bound parameters with string replacement operations (they use strtr, hence a warning in the docs not to use dollar signs) - you won't get any of the improved performance benefits. Even if you use their 'PDO' connection type, you're still wrapping all their excess and dubious code around your PDO connection. Every operation uses the PDO::query() method, there are no prepared statements or PDO bound parameters. Bad form...

Secondly, and actually more importantly for me, Kohana assumes you're incapable or afraid of writing even the simplest SQL on your own and they go out of their way to hide it from you and make it difficult to execute your own queries. From the online guide:

	$query = DB::select()->from('users')->where('username', '=', 'john');
	
This isn't their ORM layer, either, this is the raw database 'query builder' functionality. Even their totally 'raw' query functionality is peculiarly complex:

	$query = DB::query(Database::SELECT, 'SELECT * FROM users WHERE username = :user');
	
	foreach ( $query->execute() as $user ) {
		echo $user->get('username');
	}

##The PDO Way##
This seems quite cumbersome to me, when you could be using PDO:

	// instantiate your database connection
	$db = DB::factory();
	
	// only getting a single row? then get a single row...
	$user = $db->get_row( 'SELECT username, password FROM users WHERE username = :user', array( ':user' => 'foo' ) );
	echo $user->username;

#Installation#
The easiest way is to add a submodule to your existing git-based project:

	git submodule add http://github.com/chrismeller/db.git modules/db
	git submodule update --init modules/db
	
Then be sure to enable the module in your ``application/bootstrap.php`` file:

	Kohana::modules( array(
		'db'	=> MODPATH . 'db'
	) );

##Configuration##
Copy the ``config/db.php`` file to your ``application/config`` directory and edit its values as appropriate.

You'll note that there are multiple environments available. You can add different settings for as many environments as you like. By default the library will load the settings for the environment declared in the ``Kohana::$environment`` variable (so you could set this in your bootstrap file as well). If no environment setting is defined (or there are not settings for the current environment) it will load the 'default' environment settings.

###Environment Example###
For example, my ``bootstrap.php`` file contains:

	if ( $_SERVER['SERVER_NAME'] == 'localhost' ) {
		Kohana::$environment = Kohana::DEVELOPMENT;
	}
	else {
		Kohana::$environment = Kohana::PRODUCTION;
	}
	
So when running on ``localhost``, the DB module would load the 'development' array of settings, otherwise it would load 'default' (as there is no 'production').

The handful of pre-defined constants are listed in ``system/classes/kohana/core.php``:

	// Common environment type constants for consistency and convenience
	const PRODUCTION  = 'production';
	const STAGING     = 'staging';
	const TESTING     = 'testing';
	const DEVELOPMENT = 'development';
	
But remember, you can use any string you like and its array will be loaded for Db settings.