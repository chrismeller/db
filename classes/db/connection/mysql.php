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
			
			// theoretically this should be the new way to enable the query cache?
			//$this->pdo->setAttribute( PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true );
			
			// make sure to set our character sets
			$this->exec( 'SET NAMES ' . $config['charset'] );
			$this->exec( 'SET CHARACTER SET ' . $config['charset'] );
		
			return true;
			
		}
		
	}

?>