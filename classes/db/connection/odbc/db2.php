<?php

	namespace DB;

	class DB_Connection_ODBC_DB2 extends DB_Connection_ODBC {
		
		public function sql_t ( $query ) {
			
			// if there's an offset, we need a callback to replace it
			$query = preg_replace_callback( '/^(.*)LIMIT\s+(\d+),\s+(\d+)$/is', array( $this, 'replace_offset' ), $query );
			
			// if there's no offset, it's a simple replace
			$query = preg_replace( '/LIMIT\s+(\d+)/ims', 'FETCH FIRST ${1} ROWS ONLY', $query );
			
			return $query;
			
		}
		
		public function replace_offset ( $matches ) {
			
			$start = $matches[2];
			$stop = $start + $matches[3];
			
			$query = 'select * from ( select OFFSET_TEMP1.*, rownumber() OVER() as ROW_NUM FROM ( ' . $matches[1] . ' ) AS OFFSET_TEMP1 ) AS OFFSET_TEMP2 where ROW_NUM between ' . $start . ' and ' . $stop;
			
			return $query;
			
		}
		
	}

?>