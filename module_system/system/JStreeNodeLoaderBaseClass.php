<?php
/*"******************************************************************************************************
*   (c) 2010-2018 ARTEMEON                                                                              *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                         *
********************************************************************************************************/

namespace Kajona\System\System;

/**
 * Base class for loading tree nodes.
 * Class provides basic implementation for getNodesByPath.
 *
 * @package module_system
 * @author andrii.konoval@artemeon.de
 *
 */
abstract class JStreeNodeLoaderBaseClass implements JStreeNodeLoaderInterface
{

    /**
     * @inheritdoc
     */
    public function getNodesByPath($arrSystemIdPath)
    {
        if(empty($arrSystemIdPath)) {
            return true;
        }

        $strSystemId = array_shift($arrSystemIdPath);
        $arrChildren = $this->getChildNodes($strSystemId);

        $strSubId = array_key_exists(0, $arrSystemIdPath) ? $arrSystemIdPath[0] : null;
        foreach($arrChildren as $objChildNode) {

            if($strSubId !== null && $objChildNode->getStrId() == $strSubId) {
                $objChildNode->addStateAttr(SystemJSTreeNode::STR_NODE_STATE_OPENED, true);

                $arrSubchildNodes = $this->getNodesByPath($arrSystemIdPath);
                $objChildNode->setArrChildren($arrSubchildNodes);
            }
        }

        return $arrChildren;
    }

    /**
     * Esacpes the current tree-nodes' title to avoid script tags
     * @param ModelInterface|Root $sourceObject
     * @param string|null $templateTitle
     * @return string
     */
    protected function escapeTitle(ModelInterface $sourceObject, string $templateTitle = null): string
    {
        $templateTitle = $templateTitle ?? $sourceObject->getStrDisplayName();
        $templateTitle = (html_entity_decode($templateTitle,  ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $templateTitle = strip_tags(($templateTitle));
        if ($sourceObject->getIntRecordDeleted() == 1) {
            $templateTitle = '<span style="text-decoration: line-through;">'.$templateTitle.'</span>';
        }
        return $templateTitle;
    }
}