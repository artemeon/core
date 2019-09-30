<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$	                                            *
********************************************************************************************************/

namespace Kajona\System\System;

use Kajona\Dbdump\System\DbExport;
use Kajona\Dbdump\System\DbImport;
use Kajona\Packagemanager\System\PackagemanagerManager;
use Kajona\System\System\Db\DbDriverInterface;
use Kajona\System\System\Db\Schema\Table;
use Kajona\System\System\Db\Schema\TableIndex;

/**
 * This class handles all traffic from and to the database and takes care of a correct tx-handling
 * CHANGE WITH CARE!
 * Since version 3.4, prepared statments are supported. As a parameter-escaping, only the ? char is allowed,
 * named params are not supported at the moment.
 * Old plain queries are still allows, but will be discontinued around kajona 3.5 / 4.0. Up from kajona > 3.4.0
 * a warning will be generated when using the old apis.
 * When using prepared statements, all escaping is done by the database layer.
 * When using the old, plain queries, you have to escape all embedded arguments yourself by using dbsafeString()
 *
 * @package module_system
 * @author sidler@mulchprod.de
 */
class Database
{
    private $arrQueryCache = array(); //Array to cache queries
    private $arrTablesCache = [];
    private $intNumber = 0; //Number of queries send to database
    private $intNumberCache = 0; //Number of queries returned from cache

    /**
     * Instance of the db-driver defined in the configs
     *
     * @var DbDriverInterface
     */
    private $objDbDriver = null; //An object of the db-driver defined in the configs
    private static $objDB = null; //An object of this class

    /**
     * The number of transactions currently opened
     *
     * @var int
     */
    private $intNumberOfOpenTransactions = 0; //The number of transactions opened

    /**
     * Set to true, if a rollback is requested, but there are still open tx.
     * In this case, the tx is rolled back, when the enclosing tx is finished
     *
     * @var bool
     */
    private $bitCurrentTxIsDirty = false;

    /**
     * Flag indicating if the internal connection was setup.
     * Needed to have a proper lazy-connection initialization.
     *
     * @var bool
     */
    private $bitConnected = false;

    /**
     * Enables or disables dbsafeString in total
     * @var bool
     * @internal
     */
    public static $bitDbSafeStringEnabled = true;

    /**
     * Simple boolean to cache the info whether logging is enabled or not
     * @var bool
     */
    private $loggingEnabled = false;

    /**
     * Constructor
     */
    private function __construct()
    {

        //Load the defined db-driver
        $strDriver = Config::getInstance()->getConfig("dbdriver");
        if ($strDriver != "%%defaultdriver%%") {
            //build a class-name & include the driver
            $strPath = Resourceloader::getInstance()->getPathForFile("/system/db/Db".ucfirst($strDriver).".php");
            $objDriver = Classloader::getInstance()->getInstanceFromFilename($strPath);
            if ($objDriver !== null) {
                $this->objDbDriver = $objDriver;
            } else {
                throw new Exception("db-driver Db".ucfirst($strDriver)." could not be loaded", Exception::$level_FATALERROR);
            }

        } else {
            //Do not throw any exception here, otherwise an endless loop will exit with an overloaded stack frame
            //throw new Exception("No db-driver defined!", Exception::$level_FATALERROR);
        }

        $this->loggingEnabled = Logger::getInstance(Logger::QUERIES)->getIntLogLevel() == Logger::$levelInfo;
    }

    /**
     * Destructor.
     * Handles the closing of remaining tx and closes the db-connection
     */
    public function __destruct()
    {
        if ($this->intNumberOfOpenTransactions != 0) {
            //something bad happened. rollback, plz
            $this->objDbDriver->transactionRollback();
            Logger::getInstance(Logger::DBLOG)->warning("Rolled back open transactions on deletion of current instance of Db!");
        }


        if ($this->objDbDriver !== null && $this->bitConnected) {
            Logger::getInstance(Logger::DBLOG)->info("closing database-connection");
            $this->objDbDriver->dbclose();
        }

    }

    /**
     * Method to get an instance of the db-class
     *
     * @return Database
     */
    public static function getInstance()
    {
        if (self::$objDB == null) {
            self::$objDB = new Database();
        }

        return self::$objDB;
    }


    /**
     * This method connects with the database
     *
     * @return void
     */
    private function dbconnect()
    {
        if ($this->objDbDriver !== null) {
            try {
                //Logger::getInstance(Logger::DBLOG)->info("creating database-connection using driver ".get_class($this->objDbDriver));
                $objCfg = Config::getInstance("module_system", "config.php");
                $this->objDbDriver->dbconnect(new DbConnectionParams($objCfg->getConfig("dbhost"), $objCfg->getConfig("dbusername"), $objCfg->getConfig("dbpassword"), $objCfg->getConfig("dbname"), $objCfg->getConfig("dbport")));
            } catch (Exception $objException) {
                echo(Exception::renderException($objException));
                die();
            }

            $this->bitConnected = true;
        }
    }

    /**
     * Creates a single query in order to insert multiple rows at one time.
     * For most databases, this will create s.th. like
     * INSERT INTO $strTable ($arrColumns) VALUES (?, ?), (?, ?)...
     *
     * @param string $strTable
     * @param string[] $arrColumns
     * @param array $arrValueSets
     * @param array|null $arrEscapes
     * @return bool
     */
    public function multiInsert(string $strTable, array $arrColumns, array $arrValueSets, ?array $arrEscapes = null)
    {
        if (count($arrValueSets) == 0) {
            return true;
        }

        //chunk columns down to less then 1000 params, could lead to errors on oracle and sqlite otherwise
        $bitReturn = true;
        $intSetsPerInsert = floor(970 / count($arrColumns));

        foreach (array_chunk($arrValueSets, $intSetsPerInsert) as $arrSingleValueSet) {
            $bitReturn = $bitReturn && $this->objDbDriver->triggerMultiInsert($strTable, $arrColumns, $arrSingleValueSet, $this, $arrEscapes);
        }

        return $bitReturn;
    }

    /**
     * Creates a simple insert for a single row where the values parameter is an associative array with column names to
     * value mapping
     *
     * @param string $tableName
     * @param array $values
     * @param array $escapes
     * @return bool
     */
    public function insert(string $tableName, array $values, ?array $escapes = null)
    {
        return $this->multiInsert($tableName, array_keys($values), [array_values($values)], $escapes);
    }

    /**
     * Updates a row on the provided table by the identifier columns
     *
     * @param string $tableName
     * @param array $values
     * @param array $identifier
     * @param array|null $escapes
     * @return bool
     */
    public function update(string $tableName, array $values, array $identifier, ?array $escapes = null): bool
    {
        if (empty($identifier)) {
            throw new \InvalidArgumentException('Empty identifier for update statement');
        }

        $columns = [];
        $params = [];
        foreach ($values as $column => $value) {
            $columns[] = $column . ' = ?';
            $params[] = $value;
        }

        $condition = [];
        foreach ($identifier as $column => $value) {
            $condition[] = $column . ' = ?';
            $params[] = $value;
        }

        $query = 'UPDATE ' . $tableName . ' SET ' . implode(', ', $columns) . ' WHERE ' . implode(' AND ', $condition);

        return $this->_pQuery($query, $params, $escapes);
    }

    /**
     * Deletes a row on the provided table by the identifier columns
     *
     * @param string $tableName
     * @param array $identifier
     * @return bool
     */
    public function delete(string $tableName, array $identifier): bool
    {
        if (empty($identifier)) {
            throw new \InvalidArgumentException('Empty identifier for delete statement');
        }

        $condition = [];
        $params = [];
        foreach ($identifier as $column => $value) {
            $condition[] = $column . ' = ?';
            $params[] = $value;
        }

        $query = 'DELETE FROM ' . $tableName . ' WHERE ' . implode(' AND ', $condition);

        return $this->_pQuery($query, $params);
    }

    /**
     * Fires an insert or update of a single record. it's up to the database (driver)
     * to detect whether a row is already present or not.
     * Please note: since some dbrms fire a delete && insert, make sure to pass ALL columns and values,
     * otherwise data might be lost. And: params are sent to the datebase unescaped.
     *
     * @param $strTable
     * @param $arrColumns
     * @param $arrValues
     *
     * @param $arrPrimaryColumns
     *
     * @return bool
     */
    public function insertOrUpdate($strTable, $arrColumns, $arrValues, $arrPrimaryColumns)
    {
        $bitReturn = $this->objDbDriver->insertOrUpdate($strTable, $arrColumns, $arrValues, $arrPrimaryColumns);
        if (!$bitReturn) {
            $this->getError("", array());
        }

        return $bitReturn;
    }

    /**
     * Sending a query to the database
     *
     * @param string $strQuery
     *
     * @return bool
     * @deprecated
     */
    public function _query($strQuery)
    {
        return $this->_pQuery($strQuery, array());
    }

    /**
     * Sending a prepared statement to the database
     *
     * @param string $strQuery
     * @param array $arrParams
     * @param array $arrEscapes An array of booleans for each param, used to block the escaping of html-special chars.
     *                          If not passed, all params will be cleaned.
     *
     * @return bool
     * @since 3.4
     */
    public function _pQuery($strQuery, $arrParams, $arrEscapes = array())
    {
        if (!$this->bitConnected) {
            $this->dbconnect();
        }

        $bitReturn = false;

        $strQuery = $this->processQuery($strQuery);

        if ($this->loggingEnabled) {
            $queryId = generateSystemid();
            Logger::getInstance(Logger::QUERIES)->info($queryId." ".$this->prettifyQuery($strQuery, $arrParams));
        }

        //Increasing the counter
        $this->intNumber++;

        if ($this->objDbDriver != null) {
            $bitReturn = $this->objDbDriver->_pQuery($strQuery, $this->dbsafeParams($arrParams, $arrEscapes));
        }

        if (!$bitReturn) {
            $this->getError($strQuery, $arrParams);
        }

        if ($this->loggingEnabled) {
            Logger::getInstance(Logger::QUERIES)->info($queryId." "."Query finished");
        }

        return $bitReturn;
    }


    /**
     * Returns one row from a result-set
     *
     * @param string $strQuery
     * @param int $intNr
     * @param bool $bitCache
     *
     * @return array
     * @deprecated use getPRow() instead
     */
    public function getRow($strQuery, $intNr = 0, $bitCache = true)
    {
        return $this->getPRow($strQuery, array(), $intNr, $bitCache);
    }

    /**
     * Returns the number of affected rows from the last _pQuery call
     *
     * @return integer
     */
    public function getIntAffectedRows()
    {
        return $this->objDbDriver->getIntAffectedRows();
    }

    /**
     * Returns one row from a result-set.
     * Makes use of prepared statements.
     *
     * @param string $strQuery
     * @param array $arrParams
     * @param int $intNr
     * @param bool $bitCache
     * @param array $arrEscapes
     *
     * @return array
     */
    public function getPRow($strQuery, $arrParams, $intNr = 0, $bitCache = true, array $arrEscapes = [])
    {
        if ($intNr !== 0) {
            trigger_error(E_USER_DEPRECATED, "The intNr parameter is deprecated");
        }

        $arrTemp = $this->getPArray($strQuery, $arrParams, null, null, $bitCache, $arrEscapes);

        if (isset($arrTemp[$intNr])) {
            return $arrTemp[$intNr];
        } else {
            return [];
        }
    }

    /**
     * Method to get an array of rows for a given query from the database
     *
     * @param string $strQuery
     * @param bool $bitCache
     *
     * @return array
     * @deprecated use getPArray() instead
     */
    public function getArray($strQuery, $bitCache = true)
    {
        Logger::getInstance(Logger::DBLOG)->warning("deprecated getArray call: ".$strQuery);
        return $this->getPArray($strQuery, array(), null, null, $bitCache);
    }


    /**
     * Method to get an array of rows for a given query from the database.
     * Makes use of prepared statements.
     *
     * @param string $strQuery
     * @param array $arrParams
     * @param int|null $intStart
     * @param int|null $intEnd
     * @param bool $bitCache
     * @param array $arrEscapes
     *
     * @return array
     * @since 3.4
     */
    public function getPArray($strQuery, $arrParams, $intStart = null, $intEnd = null, $bitCache = true, array $arrEscapes = [])
    {
        if (!$this->bitConnected) {
            $this->dbconnect();
        }

        //param validation
        if ((int)$intStart < 0) {
            $intStart = null;
        }

        if ((int)$intEnd < 0) {
            $intEnd = null;
        }


        $strQuery = $this->processQuery($strQuery);
        //Increasing global counter
        $this->intNumber++;

        $strQueryMd5 = null;
        if ($bitCache) {
            $strQueryMd5 = md5($strQuery.implode(",", $arrParams).$intStart.$intEnd);
            if (isset($this->arrQueryCache[$strQueryMd5])) {
                //Increasing Cache counter
                $this->intNumberCache++;
                return $this->arrQueryCache[$strQueryMd5];
            }
        }

        $arrReturn = array();

        if ($this->loggingEnabled) {
            $queryId = generateSystemid();
            Logger::getInstance(Logger::QUERIES)->info($queryId." ".$this->prettifyQuery($strQuery, $arrParams));
        }

        if ($this->objDbDriver != null) {
            if ($intStart !== null && $intEnd !== null && $intStart !== false && $intEnd !== false) {
                $arrReturn = $this->objDbDriver->getPArraySection($strQuery, $this->dbsafeParams($arrParams, $arrEscapes), $intStart, $intEnd);
            } else {
                $arrReturn = $this->objDbDriver->getPArray($strQuery, $this->dbsafeParams($arrParams, $arrEscapes));
            }

            if ($this->loggingEnabled) {
                Logger::getInstance(Logger::QUERIES)->info($queryId." "."Query finished");
            }

            if ($arrReturn === false) {
                $this->getError($strQuery, $arrParams);
                return array();
            }
            if ($bitCache) {
                $this->arrQueryCache[$strQueryMd5] = $arrReturn;
            }
        }
        return $arrReturn;
    }

    /**
     * Returns a generator which can be used to iterate over a section of the query without loading the complete data
     * into the memory. This can be used to query big result sets i.e. on installation update.
     * Make sure to have an ORDER BY in the statement, otherwise the chunks may use duplicate entries depending on the RDBMS.
     *
     * NOTE if the loop which consumes the generator reduces the result set i.e. you delete for each result set all
     * entries then you need to set paging to false. In this mode we always query the first 0 to chunk size rows, since
     * the loop reduces the result set we dont need to move the start and end values forward. NOTE if you set $paging to
     * false and dont modify the result set you will get an endless loop, so you must get sure that in the end the
     * result set will be empty.
     *
     * @param string $query
     * @param array $params
     * @param int $chunkSize
     * @param bool $paging
     * @return \Generator
     */
    public function getGenerator($query, array $params = [], $chunkSize = 2048, $paging = true)
    {
        $start = 0;
        $end = $chunkSize;

        do {
            $result = $this->getPArray($query, $params, $start, $end - 1, false);

            if (!empty($result)) {
                yield $result;
            }

            if ($paging) {
                $start += $chunkSize;
                $end += $chunkSize;
            }

            $this->flushQueryCache();
        } while (!empty($result));
    }

    /**
     * Returns just a part of a recordset, defined by the start- and the end-rows,
     * defined by the params.
     * <b>Note:</b> Use array-like counters, so the first row is startRow 0 whereas
     * the n-th row is the (n-1)th key!!!
     *
     * @param string $strQuery
     * @param int $intStart
     * @param int $intEnd
     * @param bool $bitCache
     *
     * @return array
     * @deprecated use getPArray() instead
     */
    public function getArraySection($strQuery, $intStart, $intEnd, $bitCache = true)
    {
        Logger::getInstance(Logger::DBLOG)->warning("deprecated getArraySection call: ".$strQuery);
        return $this->getPArray($strQuery, array(), $intStart, $intEnd, $bitCache);
    }


    /**
     * Returns just a part of a recordset, defined by the start- and the end-rows,
     * defined by the params. Makes use of prepared statements
     * <b>Note:</b> Use array-like counters, so the first row is startRow 0 whereas
     * the n-th row is the (n-1)th key!!!
     *
     * @param string $strQuery
     * @param array $arrParams
     * @param int $intStart
     * @param int $intEnd
     * @param bool $bitCache
     *
     * @return array
     * @deprecated use getPArray() instead
     */
    public function getPArraySection($strQuery, $arrParams, $intStart, $intEnd, $bitCache = true)
    {
        Logger::getInstance(Logger::DBLOG)->warning("deprecated getPArraySection call: ".$strQuery);
        return $this->getPArray($strQuery, $arrParams, $intStart, $intEnd, $bitCache);
    }

    /**
     * Writes the last DB-Error to the screen
     *
     * @param string $strQuery
     *
     * @throws Exception
     * @return void
     */
    private function getError($strQuery, $arrParams)
    {
        if (!$this->bitConnected) {
            $this->dbconnect();
        }

        $strError = "";
        if ($this->objDbDriver != null) {
            $strError = $this->objDbDriver->getError();
        }

        //reprocess query
        $strQuery = str_ireplace(
            array(" from ", " where ", " and ", " group by ", " order by "),
            array("\nFROM ", "\nWHERE ", "\n\tAND ", "\nGROUP BY ", "\nORDER BY "),
            $strQuery
        );

        $strQuery = $this->prettifyQuery($strQuery, $arrParams);

        $strErrorCode = "";
        $strErrorCode .= "Error in query\n\n";
        $strErrorCode .= "Error:\n";
        $strErrorCode .= $strError."\n\n";
        $strErrorCode .= "Query:\n";
        $strErrorCode .= $strQuery."\n";
        $strErrorCode .= "\n\n";
        $strErrorCode .= "Params: ".implode(", ", $arrParams)."\n";
        $strErrorCode .= "Callstack:\n";
        if (function_exists("debug_backtrace")) {
            $arrStack = debug_backtrace();

            foreach ($arrStack as $intPos => $arrValue) {
                $strErrorCode .= (isset($arrValue["file"]) ? $arrValue["file"] : "n.a.")."\n\t Row ".(isset($arrValue["line"]) ? $arrValue["line"] : "n.a.").", function ".$arrStack[$intPos]["function"]."\n";
            }
        }
        //send a warning to the logger
        Logger::getInstance(Logger::DBLOG)->warning($strErrorCode);

        if (Config::getInstance()->getDebug("debuglevel") > 0) {
            throw new Exception($strErrorCode, Exception::$level_ERROR);
        }

    }


    /**
     * Starts a transaction
     *
     * @return void
     */
    public function transactionBegin()
    {
        if (!$this->bitConnected) {
            $this->dbconnect();
        }

        if ($this->objDbDriver != null) {
            //just start a new tx, if no other tx is open
            if ($this->intNumberOfOpenTransactions == 0) {
                $this->objDbDriver->transactionBegin();
            }

            //increase tx-counter
            $this->intNumberOfOpenTransactions++;

        }
    }

    /**
     * Ends a tx successfully
     *
     * @return void
     */
    public function transactionCommit()
    {
        if (!$this->bitConnected) {
            $this->dbconnect();
        }

        if ($this->objDbDriver != null) {
            //check, if the current tx is allowed to be commited
            if ($this->intNumberOfOpenTransactions == 1) {
                //so, this is the last remaining tx. Commit or rollback?
                if (!$this->bitCurrentTxIsDirty) {
                    $this->objDbDriver->transactionCommit();
                } else {
                    $this->objDbDriver->transactionRollback();
                    $this->bitCurrentTxIsDirty = false;
                }

                //decrement counter
                $this->intNumberOfOpenTransactions--;
            } else {
                $this->intNumberOfOpenTransactions--;
            }

        }
    }

    /**
     * Rollback of the current tx
     *
     * @return void
     */
    public function transactionRollback()
    {
        if (!$this->bitConnected) {
            $this->dbconnect();
        }

        if ($this->objDbDriver != null) {
            if ($this->intNumberOfOpenTransactions == 1) {
                //so, this is the last remaining tx. rollback anyway
                $this->objDbDriver->transactionRollback();
                $this->bitCurrentTxIsDirty = false;
                //decrement counter
                $this->intNumberOfOpenTransactions--;
            } else {
                //mark the current tx session a dirty
                $this->bitCurrentTxIsDirty = true;
                //decrement the number of open tx
                $this->intNumberOfOpenTransactions--;
            }

        }
    }

    /**
     * Returns all tables used by the project
     *
     * @param string|null $prefix - only used internally to migrate old databases with non agp_ prefix
     * @return array
     */
    public function getTables($prefix = null)
    {
        if ($prefix === null) {
            $prefix = "agp_";
        }

        if (!$this->bitConnected) {
            $this->dbconnect();
        }

        if (isset($this->arrTablesCache[$prefix])) {
            return $this->arrTablesCache[$prefix];
        }

        $this->arrTablesCache[$prefix] = [];

        if ($this->objDbDriver != null) {
            // increase global counter
            $this->intNumber++;
            $arrTemp = $this->objDbDriver->getTables();

            foreach ($arrTemp as $arrTable) {
                if (StringUtil::startsWith($arrTable["name"], $prefix)) {
                    $this->arrTablesCache[$prefix][] = $arrTable["name"];
                }
            }
        }

        return $this->arrTablesCache[$prefix];
    }

    /**
     * Looks up the columns of the given table.
     * Should return an array for each row consisting of:
     * array ("columnName", "columnType")
     *
     * @param string $strTableName
     * @deprecated
     *
     * @return array
     */
    public function getColumnsOfTable($strTableName)
    {
        if (!$this->bitConnected) {
            $this->dbconnect();
        }

        $table = $this->objDbDriver->getTableInformation($strTableName);

        $return = [];
        foreach ($table->getColumns() as $column) {
            $return[$column->getName()] = [
                "columnName" => $column->getName(),
                "columnType" => $column->getInternalType()
            ];
        }

        return $return;
    }

    /**
     * Fetches extensive information per database table
     * @param $tableName
     * @return Table
     */
    public function getTableInformation($tableName): Table
    {
        if (!$this->bitConnected) {
            $this->dbconnect();
        }

        return $this->objDbDriver->getTableInformation($tableName);
    }

    /**
     * Returns the db-specific datatype for the kajona internal datatype.
     * Currently, this are
     *
     * @param string $strType
     *
     * @see Db_datatypes
     *
     * @return string
     */
    public function getDatatype($strType)
    {
        return $this->objDbDriver->getDatatype($strType);
    }

    /**
     * Used to send a create table statement to the database
     * By passing the query through this method, the driver can
     * add db-specific commands.
     * The array of fields should have the following structure
     * $array[string columnName] = array(string data-type, boolean isNull [, default (only if not null)])
     * whereas data-type is one of the following:
     *         int
     *      long
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
     * @param array $arrIndices array of additional indices
     *
     * @return bool
     * @throws Exception
     * @see DbDatatypes
     *
     */
    public function createTable($strName, $arrFields, $arrKeys, $arrIndices = array())
    {
        if (!$this->bitConnected) {
            $this->dbconnect();
        }

        // check whether table already exists
        $arrTables = $this->objDbDriver->getTables();
        foreach ($arrTables as $arrTable) {
            if ($arrTable["name"] == $strName) {
                return true;
            }
        }

        // create table
        $bitReturn = $this->objDbDriver->createTable($strName, $arrFields, $arrKeys);
        if (!$bitReturn) {
            $this->getError("", array());
        }

        // create index
        if ($bitReturn && count($arrIndices) > 0) {
            foreach ($arrIndices as $strOneIndex) {
                if (is_array($strOneIndex)) {
                    $bitReturn = $bitReturn && $this->createIndex($strName, "ix_".generateSystemid(), $strOneIndex);
                } else {
                    $bitReturn = $bitReturn && $this->createIndex($strName, "ix_".generateSystemid(), [$strOneIndex]);
                }
            }
        }

        $this->flushTablesCache();

        return $bitReturn;
    }

    /**
     * Creates a new index on the provided table over the given columns. If unique is true we create a unique index
     * where each index can only occur once in the table
     *
     * @param string $strTable
     * @param string $strName
     * @param array $arrColumns
     * @param bool $bitUnique
     * @return bool
     * @throws Exception
     */
    public function createIndex($strTable, $strName, array $arrColumns, $bitUnique = false)
    {
        if (!$this->bitConnected) {
            $this->dbconnect();
        }

        if ($this->objDbDriver->hasIndex($strTable, $strName)) {
            return true;
        }
        $bitReturn = $this->objDbDriver->createIndex($strTable, $strName, $arrColumns, $bitUnique);
        if (!$bitReturn) {
            $this->getError("", array());
        }

        return $bitReturn;
    }

    /**
     * Removes an index from the database / table
     * @param string $table
     * @param string $index
     * @return bool
     */
    public function deleteIndex(string $table, string $index): bool
    {
        if (!$this->bitConnected) {
            $this->dbconnect();
        }

        return $this->objDbDriver->deleteIndex($table, $index);
    }

    /**
     * Adds an index to a table based on the import / export format
     * @param string $table
     * @param TableIndex $index
     * @return bool
     * @internal
     */
    public function addIndex(string $table, TableIndex $index)
    {
        if (!$this->bitConnected) {
            $this->dbconnect();
        }

        return $this->objDbDriver->addIndex($table, $index);
    }

    /**
     * Checks whether the table has an index with the provided name
     *
     * @param string $strTable
     * @param string $strName
     * @return bool
     */
    public function hasIndex($strTable, $strName): bool
    {
        if (!$this->bitConnected) {
            $this->dbconnect();
        }

        return $this->objDbDriver->hasIndex($strTable, $strName);
    }

    /**
     * Renames a table
     *
     * @param $strOldName
     * @param $strNewName
     *
     * @return bool
     */
    public function renameTable($strOldName, $strNewName)
    {
        if (!$this->bitConnected) {
            $this->dbconnect();
        }

        $this->flushTablesCache();
        return $this->objDbDriver->renameTable($strOldName, $strNewName);
    }

    /**
     * Changes a single column, e.g. the datatype. Note in case you only change the column type you should be aware that
     * not all database engines support changing the type freely. Most engines disallow changing the type in case you
     * would loose data i.e. on oracle it is not possible to change from longtext to char(10) since then the db engine
     * would may need to truncate some rows
     *
     * @param $strTable
     * @param $strOldColumnName
     * @param $strNewColumnName
     * @param $strNewDatatype
     *
     * @return bool
     */
    public function changeColumn($strTable, $strOldColumnName, $strNewColumnName, $strNewDatatype)
    {
        if (!$this->bitConnected) {
            $this->dbconnect();
        }
        $this->flushTablesCache();
        return $this->objDbDriver->changeColumn($strTable, $strOldColumnName, $strNewColumnName, $strNewDatatype);
    }

    /**
     * Adds a column to a table
     *
     * @param $strTable
     * @param $strColumn
     * @param $strDatatype
     * @param null $bitNull
     * @param null $strDefault
     *
     * @return bool
     */
    public function addColumn($strTable, $strColumn, $strDatatype, $bitNull = null, $strDefault = null)
    {
        if (!$this->bitConnected) {
            $this->dbconnect();
        }

        $this->flushTablesCache();
        if ($this->hasColumn($strTable, $strColumn)) {
            return true;
        }
        return $this->objDbDriver->addColumn($strTable, $strColumn, $strDatatype, $bitNull, $strDefault);
    }

    /**
     * Removes a column from a table
     *
     * @param $strTable
     * @param $strColumn
     *
     * @return bool
     */
    public function removeColumn($strTable, $strColumn)
    {
        if (!$this->bitConnected) {
            $this->dbconnect();
        }

        $this->flushTablesCache();
        return $this->objDbDriver->removeColumn($strTable, $strColumn);
    }

    /**
     * Checks whether a table has a specific column
     *
     * @param string $strTable
     * @param string $strColumn
     * @return bool
     */
    public function hasColumn($strTable, $strColumn)
    {
        $arrColumns = $this->getColumnsOfTable($strTable);
        foreach ($arrColumns as $arrColumn) {
            if (strtolower($arrColumn["columnName"]) == strtolower($strColumn)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks whether the provided table exists
     *
     * @param string $strTable
     * @return bool
     */
    public function hasTable($strTable)
    {
        return in_array($strTable, $this->getTables());
    }

    /**
     * Dumps the current db
     * Takes care of holding just the defined number of dumps in the filesystem, defined by _system_dbdump_amount_
     *
     * @param array $arrTablesToExclude specify a set of tables not to be included in the dump
     *
     * @param bool $bitPrintDebug
     * @param string $strDumpFilename pass a string based variable in order to fetch the filename of the dump created
     * @return bool
     */
    public function dumpDb($arrTablesToExclude = array(), $bitPrintDebug = false, &$strDumpFilename = "")
    {
        if (!$this->bitConnected) {
            $this->dbconnect();
        }

        // Check, how many dumps to keep
        $objFilesystem = new Filesystem();
        $arrFiles = $objFilesystem->getFilelist(_projectpath_."/dbdumps/", array(".sql", ".gz", ".zip"));

        while (count($arrFiles) >= SystemSetting::getConfigValue("_system_dbdump_amount_")) {
            $strFile = array_shift($arrFiles);
            if (!$objFilesystem->fileDelete(_projectpath_."/dbdumps/".$strFile)) {
                Logger::getInstance(Logger::DBLOG)->warning("Error deleting old db-dumps");
                return false;
            }
            $arrFiles = $objFilesystem->getFilelist(_projectpath_."/dbdumps/", array(".sql", ".gz", ".zip"));
        }

        $strTargetFilename = _projectpath_."/dbdumps/dbdump_".time().".sql";

        $arrTables = $this->getTables();
        $arrTablesFinal = array();

        if (count($arrTablesToExclude) > 0) {
            foreach ($arrTables as $strOneTable) {
                if (!in_array($strOneTable, $arrTablesToExclude)) {
                    $arrTablesFinal[] = $strOneTable;
                }
            }
        } else {
            $arrTablesFinal = $arrTables;
        }

        $objPackages = new PackagemanagerManager();
        if (Config::getInstance()->getConfig("dbexport") == "internal" && $objPackages->getPackage("dbdump") !== null) {
            $objDump = new DbExport($this, $arrTablesToExclude, $bitPrintDebug);
            try {
                $bitDump = $objDump->createExport($strTargetFilename);
            } catch (Exception $objEx) {
                $bitDump = false;
                Logger::getInstance(Logger::DBLOG)->error("Failed to create dbdump: ".$objEx->getMessage());
            }
        } else {
            $bitDump = $this->objDbDriver->dbExport($strTargetFilename, $arrTablesFinal);
            if ($bitDump == true && !$this->objDbDriver->handlesDumpCompression()) {
                $objGzip = new Gzip();
                try {
                    if (!$objGzip->compressFile($strTargetFilename, true)) {
                        Logger::getInstance(Logger::DBLOG)->warning("Failed to compress (gzip) the file " . basename($strTargetFilename) . "");
                    }
                } catch (Exception $objExc) {
                    $objExc->processException();
                }
            }
        }

        if ($bitDump) {
            Logger::getInstance(Logger::DBLOG)->info("DB-Dump ".basename($strTargetFilename)." created");
            $strDumpFilename = basename($strTargetFilename);
        } else {
            Logger::getInstance(Logger::DBLOG)->error("Error creating ".basename($strTargetFilename));
        }
        return $bitDump;
    }

    /**
     * Imports the given dump
     *
     * @param string $strFilename
     *
     * @return bool
     */
    public function importDb($strFilename, $bitPrintDebug = false)
    {
        if (!$this->bitConnected) {
            $this->dbconnect();
        }

        $bitImport = null;

        if (substr($strFilename, -4) == ".zip") {
            //switch import based on filetype
            $objPackages = new PackagemanagerManager();
            if ($objPackages->getPackage("dbdump") !== null) {
                $objImport = new DbImport($this, $bitPrintDebug);
                if ($objImport->validateFile(_projectpath_ . "/dbdumps/".$strFilename)) {
                    try {
                        $bitImport = $objImport->importFile(_projectpath_ . "/dbdumps/".$strFilename);
                    } catch (Exception $objEx) {
                        $bitImport = false;
                        Logger::getInstance(Logger::DBLOG)->error("Failed to import dbdump: ".$objEx->getMessage());
                    }
                }
            }

        }

        if ($bitImport === null) {
            //db-driver based import required
            //gz file?
            $bitGzip = false;
            if (!$this->objDbDriver->handlesDumpCompression() && substr($strFilename, -3) == ".gz") {
                $bitGzip = true;
                //try to decompress
                $objGzip = new Gzip();
                try {
                    if ($objGzip->decompressFile(_projectpath_ . "/dbdumps/" . $strFilename)) {
                        $strFilename = substr($strFilename, 0, strlen($strFilename) - 3);
                    } else {
                        Logger::getInstance(Logger::DBLOG)->warning("Failed to decompress (gzip) the file " . basename($strFilename) . "");
                        return false;
                    }
                } catch (Exception $objExc) {
                    $objExc->processException();
                    return false;
                }
            }

            $bitImport = $this->objDbDriver->dbImport(_projectpath_ . "/dbdumps/" . $strFilename);
            //Delete source unzipped file?
            if ($bitGzip) {
                $objFilesystem = new Filesystem();
                $objFilesystem->fileDelete(_projectpath_ . "/dbdumps/" . $strFilename);
            }
        }
        if ($bitImport) {
            Logger::getInstance(Logger::DBLOG)->warning("DB-DUMP ".$strFilename." was restored");
        } else {
            Logger::getInstance(Logger::DBLOG)->error("Error restoring DB-DUMP ".$strFilename);
        }
        return $bitImport;
    }

    /**
     * Parses a query to eliminate unnecessary characters such as whitespaces
     *
     * @param string $strQuery
     *
     * @return string
     */
    private function processQuery($strQuery)
    {

        $strQuery = trim($strQuery);
        $arrSearch = array(
            "\r\n",
            "\n",
            "\r",
            "\t",
            "    ",
            "   ",
            "  "
        );
        $arrReplace = array(
            "",
            "",
            "",
            " ",
            " ",
            " ",
            " "
        );

        $strQuery = str_replace($arrSearch, $arrReplace, $strQuery);

        return $strQuery;
    }

    /**
     * Queries the current db-driver about common information
     *
     * @return mixed|string
     */
    public function getDbInfo()
    {
        if (!$this->bitConnected) {
            $this->dbconnect();
        }

        if ($this->objDbDriver != null) {
            return $this->objDbDriver->getDbInfo();
        }

        return "";
    }


    /**
     * Returns the number of queries sent to the database
     * including those solved by the cache
     *
     * @return int
     */
    public function getNumber()
    {
        return $this->intNumber;
    }

    /**
     * Returns the number of queries solved by the cache
     *
     * @return int
     */
    public function getNumberCache()
    {
        return $this->intNumberCache;
    }

    /**
     * Returns the number of items currently in the query-cache
     *
     * @return  int
     */
    public function getCacheSize()
    {
        return count($this->arrQueryCache);
    }

    /**
     * Internal wrapper to dbsafeString, used to process a complete array of parameters
     * as used by prepared statements.
     *
     * @param array $arrParams
     * @param array $arrEscapes An array of boolean for each param, used to block the escaping of html-special chars.
     *                          If not passed, all params will be cleaned.
     *
     * @return array
     * @since 3.4
     * @see Db::dbsafeString($strString, $bitHtmlSpecialChars = true)
     */
    private function dbsafeParams($arrParams, $arrEscapes = array())
    {
        $replace = [];
        foreach ($arrParams as $intKey => $strParam) {
            if (isset($arrEscapes[$intKey])) {
                $strParam = $this->dbsafeString($strParam, $arrEscapes[$intKey], false);
            } else {
                $strParam = $this->dbsafeString($strParam, true, false);
            }
            $replace[$intKey] = $strParam;
        }
        return $replace;
    }

    /**
     * Makes a string db-safe
     *
     * @param string $strString
     * @param bool $bitHtmlSpecialChars
     * @param bool $bitAddSlashes
     *
     * @return string
     * @deprecated we need to get rid of this
     */
    public function dbsafeString($strString, $bitHtmlSpecialChars = true, $bitAddSlashes = true)
    {
        //skip for numeric values to avoid php type juggling/autoboxing
        if (is_float($strString)) {
            return $strString;
        } elseif (is_int($strString)) {
            return $strString;
        }

        if ($strString === null) {
            return null;
        }

        if (!self::$bitDbSafeStringEnabled) {
            return $strString;
        }

        //escape special chars
        if ($bitHtmlSpecialChars) {
            $strString = html_entity_decode($strString, ENT_COMPAT, "UTF-8");
            $strString = htmlspecialchars($strString, ENT_COMPAT, "UTF-8");
        }

        //already escaped by php?
        if (get_magic_quotes_gpc() == 1) {
            $strString = stripslashes($strString);
        }

        if ($bitAddSlashes) {
            $strString = addslashes($strString);
        }

        return $strString;
    }

    /**
     * Method to flush the query-cache
     *
     * @return void
     */
    public function flushQueryCache()
    {
        //Logger::getInstance(Logger::DBLOG)->addLogRow("Flushing query cache", Logger::$levelInfo);
        $this->arrQueryCache = array();
        Objectfactory::getInstance()->flushCache();
    }

    /**
     * Method to flush the table-cache.
     * Since the tables won't change during regular operations,
     * flushing the tables cache is only required during package updates / installations
     *
     * @return void
     */
    public function flushTablesCache()
    {
        $this->arrTablesCache = [];
    }

    /**
     * Helper to flush the precompiled queries stored at the db-driver.
     * Use this method with great care!
     *
     * @return void
     */
    public function flushPreparedStatementsCache()
    {
        $this->objDbDriver->flushQueryCache();
    }

    /**
     * Allows the db-driver to add database-specific surroundings to column-names.
     * E.g. needed by the mysql-drivers
     *
     * @param string $strColumn
     *
     * @return string
     */
    public function encloseColumnName($strColumn)
    {
        return $this->objDbDriver->encloseColumnName($strColumn);
    }

    /**
     * Allows the db-driver to add database-specific surroundings to table-names.
     * E.g. needed by the mysql-drivers
     *
     * @param string $strTable
     *
     * @return string
     */
    public function encloseTableName($strTable)
    {
        return $this->objDbDriver->encloseTableName($strTable);
    }


    /**
     * Tries to validate the passed connection data.
     * May be used by other classes in order to test some credentials,
     * e.g. the installer.
     * The connection established will be closed directly and is not usable by other modules.
     *
     * @param string $strDriver
     * @param DbConnectionParams $objCfg
     *
     * @return bool
     */
    public function validateDbCxData($strDriver, DbConnectionParams $objCfg)
    {

        /** @var $objDbDriver DbDriverInterface */
        $objDbDriver = null;

        $strPath = Resourceloader::getInstance()->getPathForFile("/system/db/Db".ucfirst($strDriver).".php");
        if ($strPath != null) {
            $objDbDriver = Classloader::getInstance()->getInstanceFromFilename($strPath);
        } else {
            return false;
        }

        try {
            if ($objDbDriver != null && $objDbDriver->dbconnect($objCfg)) {
                $objDbDriver->dbclose();
                return true;
            }
        } catch (Exception $objEx) {
            return false;
        }

        return false;
    }

    /**
     * @return boolean
     */
    public function getBitConnected()
    {
        return $this->bitConnected;
    }

    /**
     * For some database vendors we need to escape the backslash character even if we are using prepared statements. This
     * method unifies the behaviour. In order to select a column which contains a backslash you need to escape the value
     * with this method
     *
     * @param string $strValue
     *
     * @return mixed
     */
    public function escape($strValue)
    {
        return $this->objDbDriver->escape($strValue);
    }

    /**
     * Helper to replace all param-placeholder with the matching value, only to be used
     * to render a debuggable-statement.
     *
     * @param $strQuery
     * @param $arrParams
     *
     * @return string
     */
    public function prettifyQuery($strQuery, $arrParams)
    {
        foreach ($arrParams as $strOneParam) {
            if (!is_numeric($strOneParam) && $strOneParam !== null) {
                $strOneParam = "'{$strOneParam}'";
            }
            if ($strOneParam === null) {
                $strOneParam = 'null';
            }

            $intPos = StringUtil::indexOf($strQuery, '?');
            if ($intPos !== false) {
                $strQuery = substr_replace($strQuery, $strOneParam, $intPos, 1);
            }
        }

        return $strQuery;
    }

    /**
     * Appends a limit expression to the provided query
     *
     * @param string $strQuery
     * @param int $intStart
     * @param int $intEnd
     *
     * @return string
     */
    public function appendLimitExpression($strQuery, $intStart, $intEnd)
    {
        return $this->objDbDriver->appendLimitExpression($strQuery, $intStart, $intEnd);
    }

    /**
     * @return string
     */
    public function getConcatExpression(array $parts)
    {
        return $this->objDbDriver->getConcatExpression($parts);
    }
}
