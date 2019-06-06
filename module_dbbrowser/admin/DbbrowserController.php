<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/


declare(strict_types = 1);

namespace Kajona\Dbbrowser\Admin;

use Kajona\System\Admin\AdminEvensimpler;
use Kajona\System\System\AdminskinHelper;
use Kajona\System\System\Carrier;
use Kajona\System\System\Db\Schema\TableKey;
use Kajona\System\System\Exception;
use Kajona\System\System\HttpStatuscodes;
use Kajona\System\System\Link;
use Kajona\System\System\ResponseObject;
use Kajona\System\View\Components\Dtable\DTableComponent;
use Kajona\System\View\Components\Dtable\Model\DCell;
use Kajona\System\View\Components\Dtable\Model\DRow;
use Kajona\System\View\Components\Dtable\Model\DTable;
use Kajona\System\View\Components\Grid\Grid;


/**
 *
 * @author stefan.idler@artemeonde
 *
 * @module dbbrowser
 * @moduleId _dbbrowser_module_id_
 */
class DbbrowserController extends AdminEvensimpler
{

    /**
     * @return array
     */
    public function getOutputModuleNavi()
    {
        $arrReturn = array();
        $arrReturn[] = array("view", Link::getLinkAdmin($this->getArrModule("modul"), "list", "", $this->getLang("action_list")));
        return $arrReturn;
    }


    /**
     * Creates the main view of the dbbrowser
     *
     * @return string "" in case of success
     * @autoTestable
     * @permissions view
     * @throws Exception
     */
    protected function actionList()
    {
        $listId = generateSystemid();
        $list = "<div id='list{$listId}'>";
        $list.= "<dbbrowser-list></dbbrowser-list>";
        $list.= "</div>";
        $list.= "<script>Dbbrowser.initDbBrowser('list{$listId}')</script>";

        $detailUrl = Link::getLinkAdminXml($this->getArrModule("module"), "apiSystemSchema", ["table" => Carrier::getInstance()->getObjDB()->getTables()[0]]);
        $detail = "<div class='schemaDetails'></div><script>Ajax.loadUrlToElement('.schemaDetails', '{$detailUrl}')</script>";

        $grid = new Grid([3, 9]);
        $grid->setBitLimitHeight(true);
        $grid->addRow([$list, $detail]);
        return $grid->renderComponent();

    }

    /**
     * The backend call to render a single table
     *
     * @return string "" in case of success
     * @permissions view
     * @throws Exception
     * @responseType html
     */
    protected function actionApiSystemSchema()
    {
        $tableName = $this->getParam("table");
        $details = Carrier::getInstance()->getObjDB()->getTableInformation($tableName);


        $arrIndexPlain = array_map(function (TableKey $key) {
            return $key->getName();
        }, $details->getPrimaryKeys());

        $tableColumns = new DTable();
        $tableColumns->addHeader(new DRow([
            "",
            "",
            $this->getLang("schema_header_name"),
            $this->getLang("schema_header_type_int"),
            $this->getLang("schema_header_type_db"),
            $this->getLang("schema_header_type_null"),
            ""
        ]));
        foreach ($details->getColumns() as $column) {
            $tableColumns->addRow(
                new DRow([
                    (new DCell(AdminskinHelper::getAdminImage("icon_column")))->setClassAddon("width-20-px"),
                    (new DCell(in_array($column->getName(), $arrIndexPlain) ? AdminskinHelper::getAdminImage("icon_key") : ""))->setClassAddon("width-20-px"),
                    $column->getName(),
                    $column->getInternalType(),
                    $column->getDatabaseType(),
                    $column->isNullable() === true ? "null" : "not null",
                    (new DCell($this->objToolkit->listConfirmationButton($this->getLang("create_index_question", [$column->getName()]), "javascript:Dbbrowser.addIndex(\'{$tableName}\', \'{$column->getName()}\');", "icon_index", $this->getLang("action_index_create"), $this->getLang("action_index_create"))))->setClassAddon("align-right")
                ])
            );
        }

        $tableKeys = new DTable();
        $tableKeys->addHeader(new DRow([
            "",
            $this->getLang("schema_header_name"),
        ]));
        foreach ($details->getPrimaryKeys() as $key) {
            $tableKeys->addRow(
                new DRow([
                    (new DCell(AdminskinHelper::getAdminImage("icon_key")))->setClassAddon("width-20-px"),
                    $key->getName()
                ])
            );
        }

        $tableIndex = new DTable();
        $tableIndex->addHeader(new DRow([
            "",
            $this->getLang("schema_header_name"),
            $this->getLang("schema_header_type_def"),
            ""
        ]));
        foreach ($details->getIndexes() as $index) {
            $tableIndex->addRow(
                new DRow([
                    (new DCell(AdminskinHelper::getAdminImage("icon_index")))->setClassAddon("width-20-px"),
                    $index->getName(),
                    $index->getDescription(),
                    (new DCell(
                        $this->objToolkit->listDeleteButton($index->getName(), $this->getLang("index_delete_question"), "javascript:Dbbrowser.deleteIndex(\'{$tableName}\', \'{$index->getName()}\');").
                        $this->objToolkit->listConfirmationButton($this->getLang("recreate_index_question", [$index->getName()]), "javascript:Dbbrowser.recreateIndex(\'{$tableName}\', \'{$index->getName()}\');", "icon_sync", $this->getLang("action_index_recreate"), $this->getLang("action_index_recreate")))
                    )->setClassAddon("align-right")
                ])
            );
        }

        $return = "";
        $return .= $this->objToolkit->formHeadline($tableName);
        $return .= $this->objToolkit->getFieldset($this->getLang("schema_columns"), (new DTableComponent($tableColumns))->renderComponent());
        $return .= $this->objToolkit->getFieldset($this->getLang("schema_keys"), (new DTableComponent($tableKeys))->renderComponent());
        $return .= $this->objToolkit->getFieldset($this->getLang("schema_indexes"), (new DTableComponent($tableIndex))->renderComponent());

        return $return;
    }


    /**
     * Adds a new index for a given column
     * @permissions edit
     * @responseType json
     */
    protected function actionApiAddIndex()
    {
        $table = $this->getParam("table");
        $column = $this->getParam("column");

        return ["status" => Carrier::getInstance()->getObjDB()->createIndex($table, "ix_".generateSystemid(), [$column])];
    }

    /**
     * Deletes an index from the database
     * @permissions delete
     * @responseType json
     */
    protected function actionApiDeleteIndex()
    {
        $table = $this->getParam("table");
        $index = $this->getParam("index");

        return ["status" => Carrier::getInstance()->getObjDB()->deleteIndex($table, $index)];
    }

    /**
     * Recreates an index
     * @permissions edit
     * @responseType json
     */
    protected function actionApiRecreateIndex()
    {
        $table = $this->getParam("table");
        $index = $this->getParam("index");

        //fetch the relevant metadata
        $tableDef = Carrier::getInstance()->getObjDB()->getTableInformation($table);
        foreach ($tableDef->getIndexes() as $indexDef) {
            if ($indexDef->getName() == $index) {
                Carrier::getInstance()->getObjDB()->deleteIndex($table, $index);
                return ["status" => Carrier::getInstance()->getObjDB()->addIndex($table, $indexDef)];
            }
        }

        ResponseObject::getInstance()->setStrStatusCode(HttpStatuscodes::SC_BADREQUEST);
        return ["status" => "index not found"];
    }

    /**
     * Recreates an index
     * @permissions view
     * @responseType json
     */
    protected function actionApiListTables()
    {
        return [
            "headline" => $this->getLang("schema_tables"),
            "tables" => Carrier::getInstance()->getObjDB()->getTables(),
        ];
    }

}
