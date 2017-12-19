#Reference: SQL

While working with different database vendors we have gathered some experience
about the SQL difference of each vendor. This guide contains common tips to help 
writing SQL which is compatible to the following database vendors: sqlite, mysql, 
postgres, oracle, mssql.

* Oracle: The table name length must not exceed 30 characters. Keep in mind that
  `_dbprefix_` adds also some characters to the table name so it is recommended that 
  the actual table name should not exceed 20 characters.
* Mssql: When using aggregate functions you must specify an alias otherwise the result 
  of the function is placed in the result array as empty key
* Mssql: Spalten vom Typ `text` können nicht sortiert werden. Ein Workaround ist: 
  `ORDER BY CAST(TEXT_COLUMN as VARCHAR(100))`
* Mssql: Spalten in einer `ORDER BY`-Liste dürfen nicht doppelt angegeben werden
* Mssql: `DISTINCT` kann nicht benutzt werden wenn das `SELECT` eine `TEXT` Spalte 
  enthält
* Mssql: TEXT column is deprecated use VARCHAR(MAX) instead

##Generators
The Database-class support Generators to iterate over larget sets of data. Internally,
the `getGenerator()` method uses a paged query, e.g. by adding a `LIMIT` expression.
Therefore it's essential to pass a SQL statement including an `ORDER BY` definition, 
otherwise it it's not guaranteed to have unique entries per iteration. 