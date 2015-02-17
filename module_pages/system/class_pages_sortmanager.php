<?php
/*"******************************************************************************************************
*   (c) 2007-2015 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

/**
 * A sort-manager for pages & folders
 */
class class_pages_sortmanager extends class_common_sortmanager {

    public function setAbsolutePosition($intNewPosition, $arrRestrictionModules = false) {
        parent::setAbsolutePosition($intNewPosition, array(_pages_modul_id_, _pages_folder_id_));
    }

    function fixSortOnDelete($arrRestrictionModules = false) {
        parent::fixSortOnDelete(array(_pages_modul_id_, _pages_folder_id_));
    }

    public function fixSortOnPrevIdChange($strOldPrevid, $strNewPrevid, $arrRestrictionModules = false) {
        parent::fixSortOnPrevIdChange($strOldPrevid, $strNewPrevid, array(_pages_modul_id_, _pages_folder_id_));
    }

}
