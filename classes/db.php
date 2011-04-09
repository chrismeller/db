<?php

	class DB {
		
		protected static $instances = array();
		
		private function __construct ( $environment = 'default', $config = null ) {
			
			// don't instantiate DB!
			
		}
		
		public static function factory ( $environment = null, $config = null ) {
			
			if ( $environment == null ) {
				
				$e = Kohana::$environment;
				
				// first, see if there's a config value matching our environment
				if ( Kohana::config( 'db.' . $e ) ) {
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
				$config = Kohana::config( 'db' )->$environment;
			}
			
			list($engine) = explode(':', $config['dsn'], 2);
			
			$class = 'DB_Connection_' . $engine;
			
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