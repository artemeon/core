<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                *
********************************************************************************************************/


namespace Kajona\System\System;

/**
 * Interface for loading tree nodes
 *
 * @package module_system
 * @author stefan.meyer1@yahoo.de
 *
 * @module system
 * @moduleId _system_modul_id_
 *
 *
 */
interface JStreeNodeLoaderInterface
{
    /**
     * @param array $arrSystemIdPath
     * @return mixed
     */
    public function getNodesByPath($arrSystemIdPath);

    /**
     * Returns all child nodes for the given system id.
     *
     * @param $strSystemId
     *
     * @return SystemJSTreeNode[]
     */
    public function getChildNodes($strSystemId);


    /**
     * Returns a node for the tree.
     *
     * @param $strSystemId
     *
     * @return SystemJSTreeNode
     */
    public function getNode($strSystemId);
}
