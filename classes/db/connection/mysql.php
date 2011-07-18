<?php

	class DB_Connection_MySQL extends DB_Connection {
		
		public function connect ( $environment = 'default', $config = null, $attrs = array() ) {
			
			// add our mysql-specific values to the connection attributes
			
			// according to some sources this is required to enable mysql's query cache... unfortunately it's difficult
			// to confirm, but we'll work under that assumption. a good (if dated) reference: http://wezfurlong.org/blog/2006/apr/using-pdo-mysql/
			$attrs[ PDO::ATTR_EMULATE_PREPARES ] = true;
			
			// support multiple concurrent unbuffered queries - we use this so we can actually use transactions
			$attrs[ PDO::MYSQL_ATTR_USE_BUFFERED_QUERY ] = true;
			
			if ( $this->profile ) {
				$benchmark = Profiler::start('Database', 'mysql-connect');
			}
			
			try {
				parent::connect( $environment, $config, $attrs );
				$this->exec( 'SET CHARACTER SET ' . $config['charset'] );
				$this->exec( 'SET NAMES ' . $config['charset'] );
			}
			catch ( PDOException $e ) {
				
				if ( isset($benchmark) ) {
					Profiler::delete($benchmark);
				}
				
				throw $e;
			}
			
			if ( isset($benchmark) ) {
				Profiler::stop($benchmark);
			}
			
			return true;
			
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
		public function query ( $query, $args = array(), $fetch_class = null, $attribs = array() ) {
			
			$attribs[ PDO::MYSQL_ATTR_USE_BUFFERED_QUERY ] = true;
			
			return parent::query( $query, $args, $fetch_class, $attribs );
			
		}
		
	}

?>