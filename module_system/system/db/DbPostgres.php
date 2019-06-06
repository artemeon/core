<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\System\Db;

use Kajona\System\System\Carrier;
use Kajona\System\System\Db\Schema\Table;
use Kajona\System\System\Db\Schema\TableColumn;
use Kajona\System\System\Db\Schema\TableIndex;
use Kajona\System\System\Db\Schema\TableKey;
use Kajona\System\System\DbConnectionParams;
use Kajona\System\System\DbDatatypes;
use Kajona\System\System\Exception;
use Kajona\System\System\Logger;
use Kajona\System\System\StringUtil;


/**
 * db-driver for postgres using the php-postgres-interface
 *
 * @package module_system
 * @author sidler@mulchprod.de
 */
class DbPostgres extends DbBase
{

    private $linkDB; //DB-Link

    /** @var DbConnectionParams */
    private $objCfg = null;

    private $strDumpBin = "pg_dump"; //Binary to dump db (if not in path, add the path here)
    private $strRestoreBin = "psql"; //Binary to restore db (if not in path, add the path here)

    private $arrCxInfo = array();

    /**
     * @inheritdoc
     */
    public function dbconnect(DbConnectionParams $objParams)
    {
        if ($objParams->getIntPort() == "" || $objParams->getIntPort() == 0) {
            $objParams->setIntPort(5432);
        }
        $this->objCfg = $objParams;
        $this->linkDB = @pg_connect("host='".$objParams->getStrHost()."' port='".$objParams->getIntPort()."' dbname='".$objParams->getStrDbName()."' user='".$objParams->getStrUsername()."' password='".$objParams->getStrPass()."'");

        if ($this->linkDB !== false) {
            $this->_pQuery("SET client_encoding='UTF8'", array());

            $this->arrCxInfo = pg_version($this->linkDB);
            return true;
        } else {
            throw new Exception("Error connecting to database", Exception::$level_FATALERROR);
        }
    }

    /**
     * Closes the connection to the database
     *
     * @return void
     */
    public function dbclose()
    {
        @pg_close($this->linkDB);
    }


    /**
     * Sends a prepared statement to the database. All params must be represented by the ? char.
     * The params themself are stored using the second params using the matching order.
     *
     * @param string $strQuery
     * @param array $arrParams
     *
     * @return bool
     * @since 3.4
     */
    public function _pQuery($strQuery, $arrParams)
    {
        $strQuery = $this->processQuery($strQuery);
        $strName = $this->getPreparedStatementName($strQuery);
        if ($strName === false) {
            return false;
        }

        $objResult = @pg_execute($this->linkDB, $strName, $arrParams);

        if ($objResult !== false) {
            $this->intAffectedRows = @pg_affected_rows($objResult);

            return true;
        } else {
            return false;
        }
    }

    /**
     * This method is used to retrieve an array of resultsets from the database using
     * a prepared statement
     *
     * @param string $strQuery
     * @param array $arrParams
     *
     * @since 3.4
     * @return array|bool
     */
    public function getPArray($strQuery, $arrParams)
    {
        $arrReturn = array();
        $intCounter = 0;

        $strQuery = $this->processQuery($strQuery);
        $strName = $this->getPreparedStatementName($strQuery);
        if ($strName === false) {
            return false;
        }

        $resultSet = @pg_execute($this->linkDB, $strName, $arrParams);

        if ($resultSet === false) {
            return false;
        }

        while ($arrRow = @pg_fetch_array($resultSet, null, PGSQL_ASSOC)) {
            //conversions to remain compatible:
            //   count --> COUNT(*)
            if (isset($arrRow["count"])) {
                $arrRow["COUNT(*)"] = $arrRow["count"];
            }

            $arrReturn[$intCounter++] = $arrRow;
        }

        @pg_free_result($resultSet);

        return $arrReturn;
    }

    /**
     * Postgres supports UPSERTS since 9.5, see http://michael.otacoo.com/postgresql-2/postgres-9-5-feature-highlight-upsert/.
     * A fallback is the base select / update method.
     *
     * @inheritDoc
     */
    public function insertOrUpdate($strTable, $arrColumns, $arrValues, $arrPrimaryColumns)
    {

        //get the current postgres version to validate the upsert features
        if (version_compare($this->arrCxInfo["server"], "9.5", "<")) {
            //base implementation
            return parent::insertOrUpdate($strTable, $arrColumns, $arrValues, $arrPrimaryColumns);
        }

        $arrPlaceholder = array();
        $arrMappedColumns = array();
        $arrKeyValuePairs = array();

        foreach ($arrColumns as $intI => $strOneCol) {
            $arrPlaceholder[] = "?";
            $arrMappedColumns[] = $this->encloseColumnName($strOneCol);

            if (!in_array($strOneCol, $arrPrimaryColumns)) {
                $arrKeyValuePairs[] = $this->encloseColumnName($strOneCol)." = ?";
                $arrValues[] = $arrValues[$intI];
            }
        }

        if (empty($arrKeyValuePairs)) {
            $strQuery = "INSERT INTO ".$this->encloseTableName($strTable)." (".implode(", ", $arrMappedColumns).") VALUES (".implode(", ", $arrPlaceholder).")
                        ON CONFLICT ON CONSTRAINT ".$strTable."_pkey DO NOTHING";
        } else {
            $strQuery = "INSERT INTO ".$this->encloseTableName($strTable)." (".implode(", ", $arrMappedColumns).") VALUES (".implode(", ", $arrPlaceholder).")
                        ON CONFLICT ON CONSTRAINT ".$strTable."_pkey DO UPDATE SET ".implode(", ", $arrKeyValuePairs);
        }

        return $this->_pQuery($strQuery, $arrValues);
    }

    /**
     * Returns the last error reported by the database.
     * Is being called after unsuccessful queries
     *
     * @return string
     */
    public function getError()
    {
        $strError = @pg_last_error($this->linkDB);
        return $strError;
    }

    /**
     * Returns ALL tables in the database currently connected to
     *
     * @return mixed
     */
    public function getTables()
    {
        return $this->getPArray("SELECT *, table_name as name FROM information_schema.tables WHERE table_schema = 'public'", array());
    }

    /**
     * Fetches the full table information as retrieved from the rdbms
     * @param $tableName
     * @return Table
     */
    public function getTableInformation(string $tableName): Table
    {
        $table = new Table($tableName);

        // fetch all columns
        $columnInfo = $this->getPArray("SELECT * FROM information_schema.columns WHERE table_name = ?", [$tableName]) ?: [];
        foreach ($columnInfo as $arrOneColumn) {
            $col = new TableColumn($arrOneColumn["column_name"]);
            $col->setInternalType($this->getCoreTypeForDbType($arrOneColumn));
            $col->setDatabaseType($this->getDatatype($col->getInternalType()));
            $col->setNullable($arrOneColumn["is_nullable"] == "YES");
            $table->addColumn($col);
        }

        //fetch all indexes
        $indexes = $this->getPArray("select * from pg_indexes where tablename  = ? AND indexname NOT LIKE '%_pkey'", [$tableName]) ?: [];
        foreach ($indexes as $indexInfo) {
            $index = new TableIndex($indexInfo['indexname']);
            //scrape the columns from the indexdef
            $cols = StringUtil::substring($indexInfo['indexdef'], StringUtil::indexOf($indexInfo['indexdef'], "(")+1, StringUtil::indexOf($indexInfo['indexdef'], ")")-StringUtil::indexOf($indexInfo['indexdef'], "(")-1);
            $index->setDescription($cols);
            $table->addIndex($index);
        }

        //fetch all keys
        $query = "SELECT a.attname as column_name
                    FROM pg_class t,
                         pg_class i,
                         pg_index ix,
                         pg_attribute a
                   WHERE t.oid = ix.indrelid
                     AND i.oid = ix.indexrelid
                     AND a.attrelid = t.oid
                     AND a.attnum = ANY(ix.indkey)
                     AND t.relkind = 'r'
                     AND ix.indisprimary = 't'
                     AND t.relname LIKE ?
                ORDER BY t.relname, i.relname";

        $keys = $this->getPArray($query, [$tableName]) ?: [];
        foreach ($keys as $keyInfo) {
            $key = new TableKey($keyInfo['column_name']);
            $table->addPrimaryKey($key);
        }

        return $table;
    }


    /**
     * Tries to convert a column provided by the database back to the Kajona internal type constant
     * @param $infoSchemaRow
     * @return null|string
     */
    private function getCoreTypeForDbType($infoSchemaRow)
    {
        if ($infoSchemaRow["data_type"] == "integer") {
            return DbDatatypes::STR_TYPE_INT;
        } elseif ($infoSchemaRow["data_type"] == "bigint") {
            return DbDatatypes::STR_TYPE_LONG;
        } elseif ($infoSchemaRow["data_type"] == "numeric") {
            return DbDatatypes::STR_TYPE_DOUBLE;
        } elseif ($infoSchemaRow["data_type"] == "character varying") {
            if ($infoSchemaRow["character_maximum_length"] == "10") {
                return DbDatatypes::STR_TYPE_CHAR10;
            } elseif ($infoSchemaRow["character_maximum_length"] == "20") {
                return DbDatatypes::STR_TYPE_CHAR20;
            } elseif ($infoSchemaRow["character_maximum_length"] == "100") {
                return DbDatatypes::STR_TYPE_CHAR100;
            } elseif ($infoSchemaRow["character_maximum_length"] == "254") {
                return DbDatatypes::STR_TYPE_CHAR254;
            } elseif ($infoSchemaRow["character_maximum_length"] == "500") {
                return DbDatatypes::STR_TYPE_CHAR500;
            }
        } elseif ($infoSchemaRow["data_type"] == "text") {
            return DbDatatypes::STR_TYPE_TEXT;
        }
        return null;
    }

    /**
     * Returns the db-specific datatype for the kajona internal datatype.
     *
     * @param string $strType
     *
     * @return string
     */
    public function getDatatype($strType)
    {
        $strReturn = "";

        if ($strType == DbDatatypes::STR_TYPE_INT) {
            $strReturn .= " INT ";
        } elseif ($strType == DbDatatypes::STR_TYPE_LONG) {
            $strReturn .= " BIGINT ";
        } elseif ($strType == DbDatatypes::STR_TYPE_DOUBLE) {
            $strReturn .= " NUMERIC ";
        } elseif ($strType == DbDatatypes::STR_TYPE_CHAR10) {
            $strReturn .= " VARCHAR( 10 ) ";
        } elseif ($strType == DbDatatypes::STR_TYPE_CHAR20) {
            $strReturn .= " VARCHAR( 20 ) ";
        } elseif ($strType == DbDatatypes::STR_TYPE_CHAR100) {
            $strReturn .= " VARCHAR( 100 ) ";
        } elseif ($strType == DbDatatypes::STR_TYPE_CHAR254) {
            $strReturn .= " VARCHAR( 254 ) ";
        } elseif ($strType == DbDatatypes::STR_TYPE_CHAR500) {
            $strReturn .= " VARCHAR( 500 ) ";
        } elseif ($strType == DbDatatypes::STR_TYPE_TEXT) {
            $strReturn .= " TEXT ";
        } elseif ($strType == DbDatatypes::STR_TYPE_LONGTEXT) {
            $strReturn .= " TEXT ";
        } else {
            $strReturn .= " VARCHAR( 254 ) ";
        }

        return $strReturn;
    }

    /**
     * Renames a single column of the table
     *
     * @param $strTable
     * @param $strOldColumnName
     * @param $strNewColumnName
     * @param $strNewDatatype
     *
     * @return bool
     * @since 4.6
     */
    public function changeColumn($strTable, $strOldColumnName, $strNewColumnName, $strNewDatatype)
    {
        if ($strOldColumnName != $strNewColumnName) {
            $bitReturn = $this->_pQuery("ALTER TABLE ".($this->encloseTableName($strTable))." RENAME COLUMN ".($this->encloseColumnName($strOldColumnName)." TO ".$this->encloseColumnName($strNewColumnName)), array());
        } else {
            $bitReturn = true;
        }

        return $bitReturn && $this->_pQuery("ALTER TABLE ".$this->encloseTableName($strTable)." ALTER COLUMN ".$this->encloseColumnName($strNewColumnName)." TYPE ".$this->getDatatype($strNewDatatype), array());
    }


    /**
     * Used to send a create table statement to the database
     * By passing the query through this method, the driver can
     * add db-specific commands.
     * The array of fields should have the following structure
     * $array[string columnName] = array(string datatype, boolean isNull [, default (only if not null)])
     * whereas datatype is one of the following:
     *         int
     *         long
     *         double
     *         char10
     *         char20
     *         char100
     *         char254
     *      char500
     *         text
     *      longtext
     *
     * @param string $strName
     * @param array $arrFields array of fields / columns
     * @param array $arrKeys array of primary keys
     *
     * @return bool
     */
    public function createTable($strName, $arrFields, $arrKeys)
    {
        $strQuery = "";

        //build the mysql code
        $strQuery .= "CREATE TABLE ".$this->encloseTableName($strName)." ( \n";

        //loop the fields
        foreach ($arrFields as $strFieldName => $arrColumnSettings) {
            $strQuery .= " ".$strFieldName." ";

            $strQuery .= $this->getDatatype($arrColumnSettings[0]);

            //any default?
            if (isset($arrColumnSettings[2])) {
                $strQuery .= "DEFAULT ".$arrColumnSettings[2]." ";
            }

            //nullable?
            if ($arrColumnSettings[1] === true) {
                $strQuery .= " NULL ";
            } else {
                $strQuery .= " NOT NULL ";
            }

            $strQuery .= " , \n";

        }

        //primary keys
        $strQuery .= " PRIMARY KEY ( ".implode(" , ", $arrKeys)." ) \n";
        $strQuery .= ") ";

        return $this->_pQuery($strQuery, array());
    }

    /**
     * @inheritdoc
     */
    public function hasIndex($strTable, $strName): bool
    {
        $arrIndex = $this->getPArray("SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?", [$strTable, $strName]);
        return count($arrIndex) > 0;
    }


    /**
     * Starts a transaction
     *
     * @return void
     */
    public function transactionBegin()
    {
        $strQuery = "BEGIN";
        $this->_pQuery($strQuery, array());
    }

    /**
     * Ends a successful operation by Committing the transaction
     *
     * @return void
     */
    public function transactionCommit()
    {
        $str_pQuery = "COMMIT";
        $this->_pQuery($str_pQuery, array());
    }

    /**
     * Ends a non-successful transaction by using a rollback
     *
     * @return void
     */
    public function transactionRollback()
    {
        $strQuery = "ROLLBACK";
        $this->_pQuery($strQuery, array());
    }

    /**
     * @return array|mixed
     */
    public function getDbInfo()
    {
        return @pg_version($this->linkDB);
    }



    //--- DUMP & RESTORE ------------------------------------------------------------------------------------

    /**
     * Dumps the current db
     *
     * @param string $strFilename
     * @param array $arrTables
     *
     * @return bool
     */
    public function dbExport(&$strFilename, $arrTables)
    {
        $strFilename = _realpath_.$strFilename;
        $strTables = "-t ".implode(" -t ", $arrTables);

        if ($this->objCfg->getStrPass() != "") {
            if ($this->isWinOs()) {
                $strCommand = "SET \"PGPASSWORD=".$this->objCfg->getStrPass()."\" && ";
            } else {
                $strCommand = "PGPASSWORD=\"".$this->objCfg->getStrPass()."\" ";
            }
        }

        if ($this->handlesDumpCompression()) {
            $strFilename .= ".gz";
            $strCommand .= $this->strDumpBin." --clean --no-owner -h".$this->objCfg->getStrHost().($this->objCfg->getStrUsername() != "" ? " -U".$this->objCfg->getStrUsername() : "")." -p".$this->objCfg->getIntPort()." ".$strTables." ".$this->objCfg->getStrDbName()." | gzip > \"".$strFilename."\"";
        } else {
            $strCommand .= $this->strDumpBin." --clean --no-owner -h".$this->objCfg->getStrHost().($this->objCfg->getStrUsername() != "" ? " -U".$this->objCfg->getStrUsername() : "")." -p".$this->objCfg->getIntPort()." ".$strTables." ".$this->objCfg->getStrDbName()." > \"".$strFilename."\"";
        }
        //Now do a systemfork
        $intTemp = "";
        $strResult = system($strCommand, $intTemp);
        Logger::getInstance(Logger::DBLOG)->info($this->strDumpBin." exited with code ".$intTemp);
        return $intTemp == 0;
    }

    /**
     * Imports the given db-dump to the database
     *
     * @param string $strFilename
     *
     * @return bool
     */
    public function dbImport($strFilename)
    {
        $strFilename = _realpath_.$strFilename;

        if ($this->objCfg->getStrPass() != "") {
            if ($this->isWinOs()) {
                $strCommand = "SET \"PGPASSWORD=".$this->objCfg->getStrPass()."\" && ";
            } else {
                $strCommand = "PGPASSWORD=\"".$this->objCfg->getStrPass()."\" ";
            }
        }


        if ($this->handlesDumpCompression() && StringUtil::endsWith($strFilename, ".gz")) {
            $strCommand .= " gunzip -c \"".$strFilename."\" | ".$this->strRestoreBin." -q -h".$this->objCfg->getStrHost().($this->objCfg->getStrUsername() != "" ? " -U".$this->objCfg->getStrUsername() : "")." -p".$this->objCfg->getIntPort()." ".$this->objCfg->getStrDbName()."";
        } else {
            $strCommand .= $this->strRestoreBin." -q -h".$this->objCfg->getStrHost().($this->objCfg->getStrUsername() != "" ? " -U".$this->objCfg->getStrUsername() : "")." -p".$this->objCfg->getIntPort()." ".$this->objCfg->getStrDbName()." < \"".$strFilename."\"";
        }

        $intTemp = "";
        $strResult = system($strCommand, $intTemp);
        Logger::getInstance(Logger::DBLOG)->info($this->strRestoreBin." exited with code ".$intTemp);
        return $intTemp == 0;
    }

    public function encloseTableName($strTable)
    {
        return "\"{$strTable}\"";
    }


    /**
     * @param string $strValue
     *
     * @return mixed
     */
    public function escape($strValue)
    {
        return str_replace("\\", "\\\\", $strValue);
    }

    /**
     * Transforms the query into a valid postgres-syntax
     *
     * @param string $strQuery
     *
     * @return string
     */
    protected function processQuery($strQuery)
    {
        $strQuery = preg_replace_callback('/\?/', function($strValue){
            static $intI = 0;
            $intI++;
            return '$' . $intI;
        }, $strQuery);

        $strQuery = StringUtil::replace(" LIKE ", " ILIKE ", $strQuery, true, true);

        return $strQuery;
    }

    /**
     * Does as cache-lookup for prepared statements.
     * Reduces the number of pre-compiles at the db-side.
     *
     * @param string $strQuery
     *
     * @return resource|bool
     * @since 3.4
     */
    private function getPreparedStatementName($strQuery)
    {
        $strSum = md5($strQuery);
        if (in_array($strSum, $this->arrStatementsCache)) {
            return $strSum;
        }

        if (@pg_prepare($this->linkDB, $strSum, $strQuery)) {
            $this->arrStatementsCache[] = $strSum;
        } else {
            return false;
        }

        return $strSum;
    }

    /**
     * @inheritdoc
     */
    public function appendLimitExpression($strQuery, $intStart, $intEnd)
    {
        //calculate the end-value:
        $intEnd = $intEnd - $intStart + 1;
        //add the limits to the query
        return $strQuery." LIMIT  ".$intEnd." OFFSET ".$intStart;
    }

    /**
     * @inheritDoc
     */
    public function flushQueryCache()
    {
        $this->_pQuery("DISCARD ALL", array());
        parent::flushQueryCache();
    }
}
