<?php

	class DB_Connection_MySQL extends DB_Connection {
		
		public function connect ( $environment = 'default', $config = null ) {
			
			try {
				parent::connect( $environment, $config );
			}
			catch ( PDOException $e ) {
				throw $e;
			}
			
			// according to some sources this is required to enable mysql's query cache... unfortunately it's difficult
			// to confirm, but we'll work under that assumption
			$this->pdo->setAttribute( PDO::ATTR_EMULATE_PREPARES, true );
			
			// make sure to set our character sets
			$this->exec( 'SET NAMES ' . $config['charset'] );
			$this->exec( 'SET CHARACTER SET ' . $config['charset'] );
		
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
		public function query ( $query, $args = array(), $attribs = array() ) {
			
			// support multiple concurrent unbuffered queries - we use this so we can actually use transactions
			$attribs[ PDO::MYSQL_ATTR_USE_BUFFERED_QUERY ] = true;
			
			return parent::query( $query, $args, $attribs );
			
		}
		
	}

?>