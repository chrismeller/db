<?php

	namespace DB;

	class DB {
		
		protected static $instances = array();
		
		private function __construct ( $name = 'default', $config = null ) {
			
			// don't instantiate DB!
			
		}
		
		public static function factory ( $name = 'default', $config = null ) {
			
			// load the db config into a group called 'db';
			\Fuel\Core\Config::load('db', true);
			
			// if there's already a connection available for this environment, use it
			if ( isset( self::$instances[ $name ] ) ) {
				return self::$instances[ $name ];
			}
			
			// if they didn't pass in a configuration, load the one we want
			if ( $config == null ) {
				$config = \Fuel\Core\Config::get( 'db.' . $name );
			}
			
			$class = 'DB\DB_Connection_' . $config['type'];
			
			// create the instance
			$instance = new $class( $name, $config );
			
			// save it for later
			self::$instances[ $name ] = $instance;
			
			return $instance;
			
		}
		
		public static function instance ( $name = 'default', $config = null ) {
			
			return DB::factory( $name, $config );
			
		}
		
	}

?>