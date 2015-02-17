<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2015 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id: interface_xml.php 6322 2014-01-02 08:31:49Z sidler $                                         *
********************************************************************************************************/

/**
 * List of events managed by the search module.
 * Please take care to not referencing this class directly! There may be scenarios where
 * this class is not available (e.g. if module search is not installed).
 *
 * @package module_search
 * @since 4.5
 */
interface class_search_eventidentifier {


    /**
     * Name of the event thrown as soon as record is indexed.
     *
     * Use this listener-identifier to add additional content to
     * a search-document.
     * The params-array contains two entries:
     *
     * @param class_model $objInstance the record to be indexed
     * @param class_module_search_document $objSearchDocument the matching search document which may be extended
     *
     * @since 4.5
     *
     */
    const EVENT_SEARCH_OBJECTINDEXED = "core.search.objectindexed";



}
