<?php

	namespace DB;

	class DB_Connection {
		
		protected $pdo;
		protected $statements = array();
		
		public $fetch_mode = \PDO::FETCH_CLASS;
		public $fetch_mode_class = 'stdClass';
		
		protected $profile = false;
		public $table_prefix = '';

		protected $tables = array();
		
		protected $in_transaction = false;
		
		public function __construct ( $environment = 'default', $config = null ) {
			
			$this->connect( $environment, $config );
			
		}
		
		public function connect ( $environment = 'default', $config = null, $attrs = array() ) {
			
			// if they didn't pass in a configuration, load the one we want
			if ( $config == null ) {
				// load the db config into a group called 'db';
            	\Fuel\Core\Config::load('db', 'db');
            	
            	$config = \Fuel\Core\Config::get( 'db.' . $environment );
			}
			
			if ( isset( $config['profiling'] ) && $config['profiling'] == true ) {
				$this->profile = true;
			}
			
			$attrs[ \PDO::ATTR_ERRMODE ] = \PDO::ERRMODE_EXCEPTION;	// use exceptions, not warnings
			
			if ( $config['persistent'] == true ) {
				$attrs[ \PDO::ATTR_PERSISTENT ] = true;
			}
			
			// if there is an array of options in the config, merge those in
			if ( isset( $config['attributes'] ) ) {
				// note that array_merge screws up numeric keys, which attributes are, but + does not
				$attrs = $attrs + $config['attributes'];
			}
			
			$this->table_prefix = $config['table_prefix'];
			
			try {
				
				if ( $this->profile ) {
					$benchmark = \Fuel\Core\Profiler::start('Database', 'connect');
				}
				
				$this->pdo = new \PDO( $config['dsn'], $config['username'], $config['password'], $attrs );
				
				if ( isset($benchmark) ) {
					\Fuel\Core\Profiler::stop($benchmark);
				}
				
			}
			catch ( \PDOException $e ) {
				
				if ( isset($benchmark) ) {
					\Fuel\Core\Profiler::delete($benchmark);
				}
				
				// just re-throw it - be sure to catch any connection errors in extension classes
				throw $e;
			}
			
		}
		
		/**
		 * Disconnect from the database.
		 */
		public function disconnect ( ) {
			
			$this->pdo = null;
			
			return true;
			
		}
		
		/**
		 * Provides a mechanism for individual database engines to translate a SQL query before execution,
		 * just as i18n allows for language translation before display.
		 *
		 * So your code would be written for MySQL (the generally accepted standard) and could be manipulated for other
		 * database platforms seamlessly.
		 */
		public function sql_t ( $query ) {
			
			// replace any registered table names in the query
			$replace = array();
			foreach ( $this->tables as $alias => $table ) {
				$replace[ '{' . $alias . '}' ] = $this->table_prefix( $table );
			}

			$query = str_replace( array_keys( $replace ), array_values( $replace ), $query );
			
			return $query;
			
		}

		/**
		 * Register a table alias that will be processed in sql_t().
		 *
		 * After registering, you can simply use {alias} in your SQL query, and it will be translated to $table and have the db prefix prepended to it before execution.
		 *
		 * @param string|array $alias Either a single alias name, or an array of alias => table values.
		 * @param string|null $table A table name, or null if it's the same as the alias (in which case the table prefix is simply prepended).
		 */
		public function register_table ( $alias, $table = null ) {

			if ( is_array( $alias ) ) {
				foreach ( $alias as $a => $t ) {

					// for a numeric array, assume no alias
					if ( is_numeric( $a ) ) {
						$a = $t;
						$t = null;
					}

					$this->register_table( $a, $t );
				}
			}

			// if there is no table, the alias is actually the table name - use it for both
			if ( $table == null ) {
				$table = $alias;
			}
			
			$this->tables[ $alias ] = $table;

		}
		
		/**
		 * Blindly execute a SQL query without bound parameters, etc.
		 * 
		 * @param string $query The SQL query to execute.
		 * @return boolean Whether the query was executed successfully or not.
		 */
		public function exec ( $query ) {
			
			$query = $this->sql_t( $query );
			
			if ( $this->profile ) {
				$benchmark = \Fuel\Core\Profiler::start('Database', $query);
			}
			
			if ( $this->pdo->exec( $query ) === false ) {
				
				// delete the benchmark
				if ( isset( $benchmark ) ) {
					\Fuel\Core\Profiler::delete($benchmark);
				}
				
				return false;
			}
			else {
				
				if ( isset( $benchmark ) ) {
					\Fuel\Core\Profiler::stop($benchmark);
				}
				
				return true;
			}
			
		}
		
		/**
		 * Alias of exec();
		 */
		public function execute ( $query ) {
			
			return $this->exec( $query );
			
		}

		/**
		 * Execute an SQL query, with optional arguments for a prepared statement, and return the statement handle.
		 *
		 * @param $query The SQL query.
		 * @param array $args An array of bound parameter values. Depending on the naming of parameter variables it could be associative or not.
		 * @param null $fetch_class The name of the class to return results as.
		 * @param array $attribs An array of attributes to hand to PDO::prepare().
		 *
		 * @return \PDOStatement The prepared PDOStatement.
		 * @throws \PDOException
		 */
		public function query ( $query, $args = array(), $fetch_class = null, $attribs = array() ) {
			
			if ( $fetch_class == null ) {
				$fetch_class = 'stdClass';
			}
			
			$query = $this->sql_t( $query );
			
			if ( $this->profile ) {
				$benchmark = \Fuel\Core\Profiler::start('Database', $query);
			}
			
			// if we don't have the statement previously prepared, prepare it and store it
			$query_hash = md5( $query . implode( '', array_keys( $attribs ) ) . implode( '', array_values( $attribs ) ) );
			
			if ( !isset( $this->statements[ $query_hash ] ) ) {
				$this->statements[ $query_hash ] = $this->pdo->prepare( $query, $attribs );
			}
			
			// now snag it back from the 'cache'
			$statement = $this->statements[ $query_hash ];
			
			if ( $this->fetch_mode == \PDO::FETCH_CLASS ) {
				// we blindly try and fetch as a class right now. if it doesn't already exist, oh well
				// @todo be nicer about this - habari has some logic for ensuring a class is autoloaded first
				$statement->setFetchMode( \PDO::FETCH_CLASS, $fetch_class, array() );
			}
			else {
				$statement->setFetchMode( $this->fetch_mode );
			}
			
			try {
				$statement->execute( $args );
			}
			catch ( \PDOException $e ) {
				
				// delete the benchmark
				if ( isset( $benchmark ) ) {
					\Fuel\Core\Profiler::delete($benchmark);
				}
				
				throw $e;
			}
			
			if ( isset( $benchmark ) ) {
				\Fuel\Core\Profiler::stop($benchmark);
			}
			
			// return the successful statement handle
			return $statement;
			
		}

		/**
		 * Prepare a PDOStatement and return it without executing.
		 *
		 * @param $query The SQL query.
		 * @param null $fetch_class The name of the class to fetch results as, after you execute the handle yourself.
		 * @param array $attribs AN array of attributes to hand to PDO::prepare().
		 *
		 * @return \PDOStatement The statement handle.
		 */
		public function prepare ( $query, $fetch_class = null, $attribs = array() ) {
			
			if ( $fetch_class == null ) {
				$fetch_class = 'stdClass';
			}
			
			$query = $this->sql_t( $query );
			
			if ( $this->profile ) {
				$benchmark = \Fuel\Core\Profiler::start('Database', $query);
			}
			
			// if we don't have the statement previously prepared, prepare it and store it
			$query_hash = md5( $query . implode( '', array_keys( $attribs ) ) . implode( '', array_values( $attribs ) ) );
			
			if ( !isset( $this->statements[ $query_hash ] ) ) {
				$this->statements[ $query_hash ] = $this->pdo->prepare( $query, $attribs );
			}
			
			// now snag it back from the 'cache'
			$statement = $this->statements[ $query_hash ];
			
			if ( $this->fetch_mode == \PDO::FETCH_CLASS ) {
				// we blindly try and fetch as a class right now. if it doesn't already exist, oh well
				// @todo be nicer about this - habari has some logic for ensuring a class is autoloaded first
				$statement->setFetchMode( \PDO::FETCH_CLASS, $fetch_class, array() );
			}
			else {
				$statement->setFetchMode( $this->fetch_mode );
			}
			
			if ( isset( $benchmark ) ) {
				\Fuel\Core\Profiler::stop($benchmark);
			}
			
			// return the successful statement handle
			return $statement;
			
		}

		/**
		 * Execute a query and return an array of all the results.
		 *
		 * @param $query The SQL query.
		 * @param array $args An array of bound parameter values.
		 * @param null $class The class to return each individual result as.
		 *
		 * @return array
		 */
		public function get_results ( $query, $args = array(), $class = null ) {
			
			$statement = $this->query( $query, $args, $class );
			
			return $statement->fetchAll();
			
		}

		/**
		 * Execute a query and return the first result.
		 *
		 * @param $query The SQL query.
		 * @param array $args An array of bound parameter values.
		 * @param null $class The class to return the result as.
		 *
		 * @return mixed Either your custom $class, or stdClass.
		 */
		public function get_row ( $query, $args = array(), $class = null ) {
			
			$statement = $this->query( $query, $args, $class );
			
			return $statement->fetch();
			
		}

		/**
		 * Execute a query and return the first column of each result.
		 *
		 * @param $query The SQL query.
		 * @param array $args An array of bound parameter values.
		 * @param null $class The class to return each result as.
		 *
		 * @return array
		 */
		public function get_column ( $query, $args = array(), $class = null ) {
			
			$statement = $this->query( $query, $args, $class );
			
			return $statement->fetchAll( \PDO::FETCH_COLUMN );
			
		}

		/**
		 * Execute a query and return the first column of the first result.
		 *
		 * @param $query The SQL query.
		 * @param array $args An array of bound parameters.
		 *
		 * @return bool|mixed The single value, or false if there isn't one.
		 */
		public function get_value ( $query, $args = array() ) {
			
			$statement = $this->query( $query, $args );
			
			$result = $statement->fetch( \PDO::FETCH_ASSOC );
			
			if ( $result ) {
				return array_shift( $result );
			}
			else {
				return false;
			}
			
		}
		
		public function last_insert_id ( $sequence_name = '' ) {
			
			return $this->pdo->lastInsertId( $sequence_name );
			
		}
		
		/**
		 * Prefix a table name with the defined table_prefix.
		 * 
		 * <code>
		 * 	$sql = 'select foo from ' . $db->table_prefix('bar') . ' where baz = 1';
		 * 	// result: select foo from prefix__bar where baz = 1
		 * </code>
		 * 
		 * @param string $table_name Optional table name to add to the prefix.
		 * @return string The prefix, or a prefixed table name, if one is provided.
		 */
		public function table_prefix ( $table_name = '' ) {
			
			return $this->table_prefix . $table_name;
			
		}
		
		/**
		 * Returns the name of the PDO driver in use.
		 * 
		 * If you need to know exactly which type of database you're working against, this is the way.
		 * 
		 * @return string 
		 */
		public function get_driver_name ( ) {
			
			return $this->pdo->getAttribute( \PDO::ATTR_DRIVER_NAME );
			
		}
		
		/**
		 * Slightly misnamed, returns the version of the DB server in use.
		 * 
		 * @return string
		 */
		public function get_driver_version ( ) {
			
			return $this->pdo->getAttribute( \PDO::ATTR_SERVER_VERSION );
			
		}
		
		/**
		 * Turns off auto-commit mode. No changes will be committed until you call commit().
		 * 
		 * Note that behavior of transactions can vary between database platforms.
		 * Some operations, like CREATE / DROP table may include an implicit commit on platforms such as MySQL.
		 * 
		 * @return boolean True on success, false on failure.
		 */
		public function begin_transaction ( ) {
			
			// if we're already in a transaction, abort, you can't nest them
			if ( $this->in_transaction ) {
				return false;
			}
			
			if ( $this->pdo->beginTransaction() ) {
				$this->in_transaction = true;
				return true;
			}
			else {
				return false;
			}
			
		}
		
		/**
		 * Commit all changes from the current transaction and return the connection to auto-commit mode.
		 * 
		 * @return boolean True on success, false on failure.
		 */
		public function commit ( ) {
			
			// you can't commit a non-existent transaction
			if ( !$this->in_transaction ) {
				return false;
			}
			
			$this->in_transaction = false;
			
			return $this->pdo->commit();
			
		}
		
		/**
		 * Rollback all changes from the current transaction and return the connection to auto-commit mode.
		 * 
		 * @return boolean True on success, false on failure.
		 */
		public function rollback ( ) {
			
			// you can't rollback a non-existent transaction
			if ( !$this->in_transaction ) {
				return false;
			}
			
			$this->in_transaction = false;
			
			return $this->pdo->rollback();
			
		}

		/**
		 * Set a PDO attribute on the current connection.
		 *
		 * @param $key Attribute key.
		 * @param $value Attribute value.
		 *
		 * @return mixed The result of PDO::setAttribute().
		 *
		 * @see http://php.net/pdo.setattribute
		 */
		public function set_attribute ( $key, $value ) {

			return $this->pdo->setAttribute( $key, $value );

		}

		/**
		 * Get a PDO attribute for the current connection.
		 *
		 * @param $key Attribute key.
		 *
		 * @return mixed The result of PDO::getAttribute().
		 *
		 * @see http://php.net/pdo.getattribute
		 */
		public function get_attribute ( $key ) {

			return $this->pdo->getAttribute( $key );

		}
		
	}

?>