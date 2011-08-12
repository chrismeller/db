<?php

	namespace DB;

	abstract class Model {
		
		protected $_db;
		protected $db;
		
		public static function factory ( $name, $db = null ) {
			
			$class = 'Model_' . $name;
			
			return new $class($db);
			
		}
		
		public function __construct ( $db = null ) {
			
			// if there was no db object passed in, we need to create a default instance
			if ( $db == null ) {
				// we don't need to handle db parsing here, tis' done in DB::factory()
				$db = DB::instance();
			}
			
			// save the db for the model to access
			$this->db = $db;
			
			// alias for compatibility with kohana... stupid _
			$this->_db = $this->db;
			
			// if there's an initialize() method (presumably from a child class), call it
			if ( is_callable( array( $this, 'initialize' ) ) ) {
				$this->initialize();
			}
			
		}
		
	}

?>
