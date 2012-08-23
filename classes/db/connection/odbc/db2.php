<?php

	namespace DB;

	class DB_Connection_ODBC_DB2 extends DB_Connection_ODBC {

		public function sql_t ( $query ) {

			// if there's an offset with an order by, we need a special callback to replace it
			$query = preg_replace_callback( '/^(.*)\s+(ORDER BY\s+.*\s)LIMIT\s+(\d+),\s+(\d+)$/is', array( $this, 'replace_offset_order' ), $query );

			// if there's an offset but no order by, we have a slightly simpler callback to replace it
			$query = preg_replace_callback( '/^(.*)LIMIT\s+(\d+),\s+(\d+)$/is', array( $this, 'replace_offset' ), $query );

			// if there's no offset, it's a simple replace
			$query = preg_replace( '/LIMIT\s+(\d+)/ims', 'FETCH FIRST ${1} ROWS ONLY', $query );

			$query = parent::sql_t( $query );

			return $query;

		}

		public function replace_offset ( $matches ) {

			// the matches should be:
			// 		1: the full query, missing its LIMIT clause
			// 		2: the LIMIT offset (ie: start)
			// 		3: the LIMIT row count (ie: stop + 1 )

			$start = $matches[2] + 1;
			$stop = $start + $matches[3] - 1;

			$query = 'select * from ( select OFFSET_TEMP1.*, rownumber() OVER() as ROW_NUM FROM ( ' . $matches[1] . ' ) AS OFFSET_TEMP1 ) AS OFFSET_TEMP2 where ROW_NUM between ' . $start . ' and ' . $stop;

			return $query;

		}

		public function replace_offset_order ( $matches ) {

			// the matches should be:
			// 		1: the full query, missing its ORDER BY and LIMIT clauses
			// 		2: the ORDER BY clause
			// 		3: the LIMIT offset (ie: start)
			// 		4: the LIMIT row count (ie: stop + 1 )

			// pull out our matches, for clarity
			$query = $matches[1];
			$order_by = $matches[2];
			$offset = $matches[3] + 1;      // increment by one because ROW_NUM is 1-indexed, but LIMIT's offset is 0-indexed
			$row_count = $matches[4];

			// row_count is the raw number of results we want to return, so calculate the high end of that based on the offset. subtract 1 because BETWEEN is *inclusive*
			$stop = $offset + ( $row_count - 1 );

			// the order by was trimmed from our query, so make sure it gets put back where it belongs
			$query = $query . ' ' . $order_by;

			// build the full query
			$full_query = <<<QUERY
select
	*
from (
	select
		OFFSET_TEMP1.*,
		rownumber() OVER( {$order_by} ) as ROW_NUM
	from (
		{$query}
	) as OFFSET_TEMP1
) as OFFSET_TEMP2
where
	ROW_NUM between {$offset} and {$stop}
QUERY;

			return $full_query;

		}

	}

?>