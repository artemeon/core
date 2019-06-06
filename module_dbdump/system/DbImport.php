<?php
/*"******************************************************************************************************
*   (c) 2018 ARTEMEON                                                                                   *
*       Published under the GNU LGPL v2.1                                                               *
********************************************************************************************************/
declare(strict_types=1);

namespace Kajona\Dbdump\System;

use Kajona\System\System\Database;
use Kajona\System\System\Db\Schema\TableIndex;
use Kajona\System\System\Exception;
use Kajona\System\System\Filesystem;
use Kajona\System\System\StringUtil;
use Kajona\System\System\Zip;

/**
 * Imports a database dump created by the DbExport class.
 * The schema itself must be present, tables are truncated pre importing the data
 * @see DbExport
 *
 * @author sidler@mulchprod.de
 * @since 7.0
 */
class DbImport
{

    /**
     * @var Database
     */
    private $objDB;

    private $bitPrintDebug;

    /**
     * DbImport constructor.
     * @param Database $objDB
     * @param bool $bitPrintDebug
     */
    public function __construct(Database $objDB, $bitPrintDebug = false)
    {
        $this->objDB = $objDB;
        $this->bitPrintDebug = $bitPrintDebug;
    }


    /**
     * Imports a dump file into the current database.
     * The file must have been created by the matching exporter and needs to match the same schema version.
     * @param $strFile
     * @return bool
     * @throws Exception
     */
    public function importFile(string $strFile): bool
    {
        if (!$this->validateFile($strFile)) {
            throw new \InvalidArgumentException("File ".$strFile." is no valid dump");
        }

        $strTargetDir = "/project/temp/dbimport_".generateSystemid();
        $objZip = new Zip();
        if (!$objZip->extractArchive($strFile, $strTargetDir)) {
            return false;
        }

        $bitReturn = true;
        $objFilesystem = new Filesystem();
        $arrFiles = $objFilesystem->getFilelist($strTargetDir, [".ser"]);
        $arrSchema = $objFilesystem->getFilelist($strTargetDir, [".schema"]);

        if ($this->validateTargetSchema($arrSchema, $strTargetDir)) {
            foreach ($arrFiles as $strOneFile) {
                if (!$this->importSingleTableFile($strTargetDir."/".$strOneFile)) {
                    $bitReturn = false;
                    break;
                }
            }
        }

        $objFilesystem->folderDeleteRecursive($strTargetDir);
        return $bitReturn;
    }

    /**
     * Imports a single file of the dump, so a single table
     * @param string $strFile
     * @return bool
     */
    private function importSingleTableFile(string $strFile): bool
    {
        $strTableName = StringUtil::substring(basename($strFile), 0, -4);

        $objFileystem = new Filesystem();
        $objFileystem->openFilePointer($strFile, 'r');


        if ($this->bitPrintDebug) {
            echo "Importing into table ".str_pad($strTableName, 25)."  rows: ";
            ob_flush();
            flush();
        }


        $this->objDB->_pQuery("DELETE FROM ".$strTableName." WHERE 1=1", []);


        $arrRows = [];
        $intCount = 0;
        $intPrint = 0;
        while ($strRawData = $objFileystem->readLineByCustomDelimiterFromFile(DbExport::LINE_SEPARATOR)) {
            $arrRows[] = unserialize($strRawData);
            $intCount++;

            if (count($arrRows) > 100) {
                $this->importRows($arrRows, $strTableName);
                $arrRows = [];

                if ($this->bitPrintDebug) {
                    echo str_pad($intCount."", 10, " ");

                    if ($intPrint++ > 15) {
                        echo PHP_EOL.str_pad("", 54);
                        $intPrint = 0;
                    }
                    ob_flush();
                    flush();
                }

            }
        }

        $this->importRows($arrRows, $strTableName);

        if ($this->bitPrintDebug) {
            echo "{$intCount}".PHP_EOL;
            ob_flush();
            flush();
        }

        return true;
    }

    /**
     * Imports a set of rows into a target table
     * @param array $arrRows
     * @param string $strTable
     * @return bool
     */
    private function importRows(array $arrRows, string $strTable): bool
    {
        if (count($arrRows) == 0) {
            return true;
        }

        $arrCols = array_keys($arrRows[0]);
        Database::$bitDbSafeStringEnabled = false;
        $bitReturn = $this->objDB->multiInsert($strTable, $arrCols, $arrRows);
        Database::$bitDbSafeStringEnabled = true;
        return $bitReturn;
    }

    /**
     * Creates a missing table based on the import file
     * @param array $schema
     * @return bool
     */
    private function createTableFromSchemaFile(array $schema)
    {

        if ($this->bitPrintDebug) {
            echo "Creating table {$schema['name']}".PHP_EOL;
            ob_flush();
            flush();
        }

        $colDef = [];
        foreach ($schema['columns'] as $col) {
            $colDef[$col['name']] = [$col['internalType'], $col['nullable']];
        }

        $keyDef = [];
        foreach ($schema['keys'] as $key) {
            $keyDef[] = $key['name'];
        }

        if ($this->objDB->createTable($schema['name'], $colDef, $keyDef)) {
            $this->syncIndexes($schema);
            return true;
        }

        return false;
    }

    /**
     * Syncs the indexes of the linked table
     * @param array $schema
     */
    private function syncIndexes(array $schema)
    {
        foreach ($schema['indexes'] as $indexDef) {
            if (!$this->objDB->hasIndex($schema['name'], $indexDef['name'])) {
                $this->objDB->addIndex($schema['name'], (new TableIndex($indexDef['name']))->setDescription($indexDef['description']));
                if ($this->bitPrintDebug) {
                    echo "Added index {$schema['name']}.{$indexDef['name']}".PHP_EOL;
                    ob_flush();
                    flush();
                }
            }
        }
    }

    /**
     * Syncs a table definition between import file and databsae
     * @param array $schema
     * @throws Exception
     */
    private function syncTableDefinition(array $schema)
    {
        //load details from db
        $details = $this->objDB->getTableInformation($schema['name']);

        foreach ($schema['columns'] as $colDefintion) {
            $colFound = false;
            //search for the column in the current schema
            foreach ($details->getColumns() as $dbColumn) {
                if (StringUtil::toLowerCase($dbColumn->getName()) == StringUtil::toLowerCase($colDefintion['name'])) {
                    $colFound = true;

                    //compare the column data types
                    if ($colDefintion['internalType'] != $dbColumn->getInternalType()) {
                        if ($this->bitPrintDebug) {
                            echo "Changing column {$schema['name']}.{$dbColumn->getName()} datatype from {$dbColumn->getInternalType()} to  {$colDefintion['internalType']}".PHP_EOL;
                            ob_flush();
                            flush();
                        }
                        if (!$this->objDB->changeColumn($schema['name'], $dbColumn->getName(), $dbColumn->getName(), $colDefintion['internalType'])) {
                            throw new Exception("Failed to change column {$schema['name']}.{$dbColumn->getName()}", Exception::$level_ERROR);
                        }
                    }
                }
            }

            if (!$colFound) {
                //add the column
                if ($this->bitPrintDebug) {
                    echo "Adding column {$colDefintion['name']} to table {$schema['name']}".PHP_EOL;
                    ob_flush();
                    flush();
                }
                if (!$this->objDB->addColumn($schema['name'], $colDefintion['name'], $colDefintion['internalType'], $colDefintion['nullable'])) {
                    throw new Exception("Failed to add column {$schema['name']}.{$dbColumn->getName()}", Exception::$level_ERROR);
                }
            }

            $this->syncIndexes($schema);
        }
    }


    /**
     * Compares the dump files (columns) and the currently available schema
     * @param array $arrSchema
     * @param string $strBaseDir
     * @return bool
     * @throws Exception
     */
    private function validateTargetSchema(array $arrSchema, string $strBaseDir): bool
    {
        $arrTables = $this->objDB->getTables();

        foreach ($arrSchema as $strFile) {
            $schemaDefinition = json_decode(file_get_contents(_realpath_.$strBaseDir."/".$strFile), true);

            //var_dump($schemaDefinition);
            $tableName = $schemaDefinition['name'];

            //load the details from the current db
            if (!in_array($tableName, $arrTables)) {
                $tableCreated = $this->createTableFromSchemaFile($schemaDefinition);
                if ($this->bitPrintDebug && !$tableCreated) {
                    echo "Failed to create table {$tableName}".PHP_EOL;
                    ob_flush();
                    flush();

                    throw new Exception("Failed to create table {$tableName}", Exception::$level_ERROR);
                }
            } else {
                //sync column definitions
                $this->syncTableDefinition($schemaDefinition);
            }
        }
        return true;
    }


    /**
     * Validates the current file, e.g. if a marker-file is present
     * @param string $strFilename
     * @return bool
     * @throws Exception
     */
    public function validateFile(string $strFilename): bool
    {
        $objZip = new Zip();
        if ($objZip->isZipFile($strFilename) && $objZip->getFileFromArchive($strFilename, DbExport::MARKER_FILE) !== false) {
            $arrFile = json_decode($objZip->getFileFromArchive($strFilename, DbExport::MARKER_FILE));
            if ($arrFile != null) {
                return true;
            }
        }

        return false;
    }
}
