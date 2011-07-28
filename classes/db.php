<?php

	namespace DB;

	class DB {
		
		protected static $instances = array();
		
		private function __construct ( $environment = 'default', $config = null ) {
			
			// don't instantiate DB!
			
		}
		
		public static function factory ( $environment = null, $config = null ) {
			
			// load the db config into a group called 'db';
			\Fuel\Core\Config::load('db', 'db');
			
			if ( $environment == null ) {
				
				$e = \Fuel\Core\Fuel::$env;
				
				// first, see if there's a config value matching our environment
				if ( \Fuel\Core\Config::get( 'db.' . $e ) ) {
					$environment = $e;
				}
				else {
					$environment = 'default';
				}
				
			}
			
			// if there's already a connection available for this environment, use it
			if ( isset( self::$instances[ $environment ] ) ) {
				return self::$instances[ $environment ];
			}
			
			// if they didn't pass in a configuration, load the one we want
			if ( $config == null ) {
				$config = \Fuel\Core\Config::get( 'db.' . $environment );
			}
			
			$class = 'DB\DB_Connection_' . $config['type'];
			
			// create the instance
			$instance = new $class( $environment, $config );
			
			// save it for later
			self::$instances[ $environment ] = $instance;
			
			return $instance;
			
		}
		
		public static function instance ( $environment = null, $config = null ) {
			
			return DB::factory( $environment, $config );
			
		}
		
	}

?>