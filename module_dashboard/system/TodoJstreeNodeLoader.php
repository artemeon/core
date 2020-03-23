<?php
/*"******************************************************************************************************
*   (c) 2015-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\Dashboard\System;

use Kajona\System\System\Carrier;
use Kajona\System\System\JStreeNodeLoaderBaseClass;
use Kajona\System\System\SystemJSTreeNode;

/**
 * @package module_prozessverwaltung
 * @author christoph.kappestein@artemeon.de
 */
class TodoJstreeNodeLoader extends JStreeNodeLoaderBaseClass
{
    private $objToolkit = null;

    /**
     * TodoJstreeNodeLoader constructor.
     */
    public function __construct()
    {
        $this->objToolkit = Carrier::getInstance()->getObjToolkit("admin");
    }

    /**
     * @inheritdoc
     */
    public function getChildNodes($strSystemId)
    {
        $arrProvider = array();
        $arrCategories = TodoRepository::getAllCategories();
        foreach($arrCategories as $strProviderName => $arrTaskCategories) {
            foreach($arrTaskCategories as $strKey => $strCategoryName) {
                if(!isset($arrProvider[$strProviderName])) {
                    $arrProvider[$strProviderName] = array();
                }

                $arrProvider[$strProviderName][$strKey] = $strCategoryName;
            }
        }

        $arrProviderNodes = array();
        foreach($arrProvider as $strProviderName => $arrCats) {

            $arrCategoryNodes = array();
            foreach($arrCats as $strKey => $strCategoryName) {
                $strJsonKey = json_encode($strKey);

                $objNode = new SystemJSTreeNode();
                $objNode->setStrId(generateSystemid());
                $objNode->setStrText($strCategoryName);
                $objNode->setArrChildren(false);
                $objNode->setStrType("provider");
                $objNode->addAAttrAttr(
                    SystemJSTreeNode::STR_NODE_AATTR_HREF,
                    "#/dashboard/todo"
                );
                $objNode->addAAttrAttr(
                    "onclick",
                    "require('dashboard').todo.loadCategory($strJsonKey,'');return false;"
                );
                $objNode->addStateAttr(
                    SystemJSTreeNode::STR_NODE_STATE_OPENED,
                    true
                );
                $arrCategoryNodes[] = $objNode;
            }

            $strKeys = implode(",", array_keys($arrCats));
            $strKeysJson = json_encode($strKeys);

            $objNode = new SystemJSTreeNode();
            $objNode->setStrId(generateSystemid());
            $objNode->setStrText('<i class="fa fa-folder-o"></i>&nbsp;'.$strProviderName);
            $objNode->setArrChildren($arrCategoryNodes);
            $objNode->setStrType("category");
            $objNode->addAAttrAttr(
                SystemJSTreeNode::STR_NODE_AATTR_HREF,
                "#/dashboard/todo"
            );
            $objNode->addAAttrAttr(
                "onclick",
                "require('dashboard').todo.loadCategory($strKeysJson,'');return false;"
            );
            $objNode->addStateAttr(
                SystemJSTreeNode::STR_NODE_STATE_OPENED,
                true
            );


            $arrProviderNodes[] = $objNode;
        }

        return $arrProviderNodes;
    }

    /**
     * @inheritdoc
     */
    public function getNode($strSystemId)
    {
        $objNode = new SystemJSTreeNode();
        $objNode->setStrId(generateSystemid());
        $objNode->setStrText(Carrier::getInstance()->getObjLang()->getLang("todo_provider_category", "dashboard"));
        $objNode->setStrType("navigationpoint");
        $objNode->addAAttrAttr(
            SystemJSTreeNode::STR_NODE_AATTR_HREF,
            "#"
        );
        $objNode->addAAttrAttr(
            "onclick",
            "require('dashboard').todo.loadCategory('','')"
        );

        return $objNode;

    }
}
