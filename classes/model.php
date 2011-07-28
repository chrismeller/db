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
			
			// we don't need to handle $db parsing here, it's done in DB::factory().
			$this->db = DB::instance();
			
			// alias for compatibility with kohana... stupid _
			$this->_db = $this->db;
			
			// if there's an initialize() method (presumably from a child class), call it
			if ( is_callable( array( $this, 'initialize' ) ) ) {
				$this->initialize();
			}
			
		}
		
	}

?>
