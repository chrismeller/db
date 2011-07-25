<?php

	class DB_Connection_ODBC_DB2 extends DB_Connection_ODBC {
		
		public function sql_t ( $query ) {
			
			$query = preg_replace( '/LIMIT\s+(\d+)/ims', 'FETCH FIRST ${1} ROWS ONLY', $query );
			
			return $query;
			
		}
		
	}

?>