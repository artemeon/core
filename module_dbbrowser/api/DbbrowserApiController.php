<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\Dbbrowser\Api;

use Kajona\Api\System\ApiControllerInterface;
use Kajona\System\System\Database;
use Kajona\System\System\Lang;
use PSX\Http\Environment\HttpContext;
use PSX\Http\Environment\HttpResponse;

/**
 * DbbrowserApiController
 *
 * @author christoph.kappestein@artemeon.de
 * @author dhafer.harrathi@artemeon.de
 * @since 7.1
 */
class DbbrowserApiController implements ApiControllerInterface
{
    /**
     * @inject system_db
     * @var Database
     */
    protected $connection;

    /**
     * @inject system_lang
     * @var Lang
     */
    protected $lang;

    /**
     * Returns a list of all tables
     *
     * @api
     * @method GET
     * @path /dbbrowser
     * @authorization filetoken
     */
    public function listTables()
    {
        return [
            "headline" => $this->lang->getLang("schema_tables", "dbbrowser"),
            "tables" => $this->connection->getTables(),
        ];
    }

    /**
     * The backend call to return table in json
     *
     * @api
     * @method GET
     * @path /dbbrowser/{table}
     * @authorization filetoken
     */
    public function detailTable(HttpContext $context)
    {
        $tableName = $context->getUriFragment('table');
        $details = $this->connection->getTableInformation($tableName);

        $result = [];
        $result["columns"] = [];
        $result["indexes"] = [];
        $result["keys"] = [];

        foreach ($details->getColumns() as $column) {
            $result["columns"][] = [
                "name" => $column->getName(),
                "type" => $column->getInternalType(),
                "dbtype" => $column->getDatabaseType(),
                "nullable" => $column->isNullable() === true ? "null" : "not null",
            ];
        }

        foreach ($details->getPrimaryKeys() as $key) {
            $result["keys"][] = $key->getName();
        }

        foreach ($details->getIndexes() as $index) {
            $result["indexes"][] = [
                "name" => $index->getName(),
                "description" => $index->getDescription(),
            ];
        }

        return $result;
    }

    /**
     * @api
     * @method POST
     * @path /dbbrowser/index
     * @authorization filetoken
     */
    public function addIndex($body)
    {
        $table = $body["table"] ?? null;
        $column = $body["column"] ?? null;

        return ["status" => $this->connection->createIndex($table, "ix_".generateSystemid(), [$column])];
    }

    /**
     * Deletes an index from the database
     *
     * @api
     * @method DELETE
     * @path /dbbrowser/index
     * @authorization filetoken
     */
    public function deleteIndex()
    {
        $table = $body["table"] ?? null;
        $index = $body["index"] ?? null;

        return ["status" => $this->connection->deleteIndex($table, $index)];
    }

    /**
     * Recreates an index
     *
     * @api
     * @method PUT
     * @path /dbbrowser/index
     * @authorization filetoken
     */
    public function recreateIndex()
    {
        $table = $body["table"] ?? null;
        $index = $body["index"] ?? null;

        //fetch the relevant metadata
        $tableDef = $this->connection->getTableInformation($table);
        foreach ($tableDef->getIndexes() as $indexDef) {
            if ($indexDef->getName() == $index) {
                $this->connection->deleteIndex($table, $index);
                return ["status" => $this->connection->addIndex($table, $indexDef)];
            }
        }

        return new HttpResponse(400, [], ["status" => "index not found"]);
    }
}
