<?php

	abstract class DB_Connection {
		
		protected $pdo;
		protected $statements = array();
		
		public $fetch_mode = PDO::FETCH_CLASS;
		public $fetch_mode_class = 'stdClass';
		
		protected $profile = false;
		public $table_prefix = '';
		
		protected $in_transaction = false;
		
		public function __construct ( $environment = 'default', $config = null ) {
			
			$this->connect( $environment, $config );
			
		}
		
		public function connect ( $environment = 'default', $config = null, $attrs = array() ) {
			
			// if they didn't pass in a configuration, load the one we want
			if ( $config == null ) {
				$config = Kohana::config( 'db' )->$environment;
			}
			
			if ( isset( $config['profiling'] ) && $config['profiling'] == true ) {
				$this->profile = true;
			}
			
			$attrs[ PDO::ATTR_ERRMODE ] = PDO::ERRMODE_EXCEPTION;	// use exceptions, not warnings
			
			if ( $config['persistent'] == true ) {
				$attrs[ PDO::ATTR_PERSISTENT ] = true;
			}
			
			$this->table_prefix = $config['table_prefix'];
			
			try {
				
				if ( $this->profile ) {
					$benchmark = Profiler::start('Database', 'connect');
				}
				
				$this->pdo = new PDO( $config['dsn'], $config['username'], $config['password'], $attrs );
				
				if ( isset($benchmark) ) {
					Profiler::stop($benchmark);
				}
				
			}
			catch ( PDOException $e ) {
				
				if ( isset($benchmark) ) {
					Profiler::delete($benchmark);
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
			
			return $query;
			
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
				$benchmark = Profiler::start('Database', $query);
			}
			
			if ( $this->pdo->exec( $query ) === false ) {
				
				// delete the benchmark
				if ( isset( $benchmark ) ) {
					Profiler::delete($benchmark);
				}
				
				return false;
			}
			else {
				
				if ( isset( $benchmark ) ) {
					Profiler::stop($benchmark);
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
		 * Execute a SQL query, with optional arguments for a prepared statement, and return the statement handle.
		 * 
		 * @param string $query The SQL query.
		 * @param array $args An array of bound parameter values. Depending on the naming of parameter variables it could be associative or not.
		 * @param array $attribs An array of attributes to hand to PDO::prepare().
		 * @return PDOStatement The prepared PDOStatement.
		 * @throws PDOException
		 */
		public function query ( $query, $args = array(), $attribs = array() ) {
			
			$query = $this->sql_t( $query );
			
			if ( $this->profile ) {
				$benchmark = Profiler::start('Database', $query);
			}
			
			// if we don't have the statement previously prepared, prepare it and store it
			$query_hash = md5( $query . implode( '', array_keys( $attribs ) ) . implode( '', array_values( $attribs ) ) );
			
			if ( !isset( $this->statements[ $query_hash ] ) ) {
				$this->statements[ $query_hash ] = $this->pdo->prepare( $query, $attribs );
			}
			
			// now snag it back from the 'cache'
			$statement = $this->statements[ $query_hash ];
			
			if ( $this->fetch_mode == PDO::FETCH_CLASS ) {
				// we blindly try and fetch as a class right now. if it doesn't already exist, oh well
				// @todo be nicer about this - habari has some logic for ensuring a class is autoloaded first
				$statement->setFetchMode( PDO::FETCH_CLASS, $this->fetch_mode_class, array() );
			}
			else {
				$statement->setFetchMode( $this->fetch_mode );
			}
			
			try {
				$statement->execute( $args );
			}
			catch ( PDOException $e ) {
				
				// delete the benchmark
				if ( isset( $benchmark ) ) {
					Profiler::delete($benchmark);
				}
				
				throw $e;
			}
			
			if ( isset( $benchmark ) ) {
				Profiler::stop($benchmark);
			}
			
			// return the successful statement handle
			return $statement;
			
		}
		
		public function get_results ( $query, $args = array() ) {
			
			$statement = $this->query( $query, $args );
			
			return $statement->fetchAll();
			
		}
		
		public function get_row ( $query, $args = array() ) {
			
			$statement = $this->query( $query, $args );
			
			return $statement->fetch();
			
		}
		
		public function get_column ( $query, $args = array() ) {
			
			$statement = $this->query( $query, $args );
			
			return $statement->fetchAll( PDO::FETCH_COLUMN );
			
		}
		
		public function get_value ( $query, $args = array() ) {
			
			$statement = $this->query( $query, $args );
			
			$result = $statement->fetch( PDO::FETCH_ASSOC );
			
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
			
			return $this->pdo->getAttribute( PDO::ATTR_DRIVER_NAME );
			
		}
		
		/**
		 * Slightly misnamed, returns the version of the DB server in use.
		 * 
		 * @return string
		 */
		public function get_driver_version ( ) {
			
			return $this->pdo->getAttribute( PDO::ATTR_SERVER_VERSION );
			
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
		
	}

?>