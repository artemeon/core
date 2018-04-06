<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                  *
********************************************************************************************************/

namespace Kajona\Search\System;

use Kajona\System\System\Carrier;
use Kajona\System\System\LanguagesLanguage;

/**
 * This class contains a few methods used by the search as little helpers
 *
 * @package module_search
 * @author sidler@mulchprod.de
 * @module search
 * @moduleId _search_module_id_
 */
class SearchLog extends \Kajona\System\System\Model implements \Kajona\System\System\ModelInterface {

    /**
     * Returns the name to be used when rendering the current object, e.g. in admin-lists.
     *
     * @return string
     */
    public function getStrDisplayName() {
        return "";
    }


    /**
     * Generates a new entry in the log-table
     *
     * @param string $strSeachterm
     *
     * @return bool
     * @static
     */
    public static function generateLogEntry($strSeachterm) {

        $objLanguage = new LanguagesLanguage();
        $strLanguage = $objLanguage->getStrPortalLanguage();

        $strQuery = "INSERT INTO agp_search_log 
                    (search_log_id, search_log_date, search_log_query, search_log_language) VALUES
                    (?, ?, ?, ? )";

        return Carrier::getInstance()->getObjDB()->_pQuery($strQuery, array(generateSystemid(), (int)time(), $strSeachterm, $strLanguage));
    }

    /**
     * Loads a list of logbook-entries
     *
     * @return array
     */
    public function getLogBookEntries() {
        return $this->objDB->getPArray(
            "SELECT search_log_date, search_log_query
               FROM ".$this->arrModule["table"]."
           GROUP BY search_log_date
           ORDER BY search_log_date DESC",
            array()
        );
    }

}
