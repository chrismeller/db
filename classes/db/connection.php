<?php

	abstract class DB_Connection {
		
		protected $pdo;
		protected $statements = array();
		
		public $fetch_mode = PDO::FETCH_CLASS;
		public $fetch_mode_class = 'stdClass';
		
		protected $profile = false;
		public $table_prefix = '';
		
		public function __construct ( $environment = 'default', $config = null ) {
			
			$this->connect( $environment, $config );
			
		}
		
		public function connect ( $environment = 'default', $config = null ) {
			
			// if they didn't pass in a configuration, load the one we want
			if ( $config == null ) {
				$config = Kohana::config( 'db' )->$environment;
			}
			
			if ( isset( $config['profiling'] ) && $config['profiling'] == true ) {
				$this->profile = true;
			}
			
			$attrs = array(
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,	// use exceptions, not warnings
			);
			
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
		
		public function exec ( $query ) {
			
			$query = $this->sql_t( $query );
			
			if ( $this->profile ) {
				$benchmark = Profiler::start('Database (' . $this->pdo . ')', $query);
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
		
		public function query ( $query, $args = array() ) {
			
			$query = $this->sql_t( $query );
			
			if ( $this->profile ) {
				$benchmark = Profiler::start('Database ({$this->pdo})', $query);
			}
			
			// if we don't have the statement previously prepared, prepare it and store it
			if ( !isset( $this->statements[ md5( $query ) ] ) ) {
				$this->statements[ md5( $query ) ] = $this->pdo->prepare( $query );
			}
			
			// now snag it back from the 'cache'
			$statement = $this->statements[ md5( $query ) ];
			
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
		
		public function table_prefix ( $table_name = '' ) {
			
			return $this->table_prefix . $table_name;
			
		}
		
		public function get_driver_name ( ) {
			
			return $this->pdo->getAttribute( PDO::ATTR_DRIVER_NAME );
			
		}
		
		public function get_driver_version ( ) {
			
			return $this->pdo->getAttribute( PDO::ATTR_SERVER_VERSION );
			
		}
		
	}

?>