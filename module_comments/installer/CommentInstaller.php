<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                         *
********************************************************************************************************/

namespace Kajona\Comments\Installer;

use Kajona\Comments\System\CommentComment;
use Kajona\System\System\Carrier;
use Kajona\System\System\DbDatatypes;
use Kajona\System\System\InstallerBase;
use Kajona\System\System\InstallerRemovableInterface;
use Kajona\System\System\Lifecycle\ServiceLifeCycleFactory;
use Kajona\System\System\OrmDeletedhandlingEnum;
use Kajona\System\System\OrmObjectlist;
use Kajona\System\System\OrmSchemamanager;
use Kajona\System\System\SystemModule;
use Kajona\System\System\SystemSetting;

/**
 * Class providing the installer of the comments-module
 *
 * @package module_comments
 * @moduleId _comments_module_id_
 */
class InstallerComment extends InstallerBase implements InstallerRemovableInterface
{
    private $bitIndexTablesUpToDate = false;

    public function install()
    {
    }

    /**
     * Validates whether the current module/element is removable or not.
     * This is the place to trigger special validations and consistency checks going
     * beyond the common metadata-dependencies.
     *
     * @return bool
     */
    public function isRemovable()
    {
        return true;
    }

    /**
     * Does the hard work: updates the module to the current version
     *
     */
    public function update()
    {
        // TODO: Implement update() method.
    }

    /**
     * Removes the elements / modules handled by the current installer.
     * Use the reference param to add a human readable logging.
     *
     * @param string &$strReturn
     *
     * @return bool
     */
    public function remove(&$strReturn)
    {
        // TODO: Implement remove() method.
    }
}
