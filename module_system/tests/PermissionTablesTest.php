<?php
/*"******************************************************************************************************
*   (c) 2015-2017 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\Tests;

use Kajona\System\System\Database;
use Kajona\System\System\Lifecycle\ServiceLifeCycleFactory;
use Kajona\System\System\Permissions\AddPermissionToGroup;
use Kajona\System\System\Permissions\PermissionActionProcessor;
use Kajona\System\System\Permissions\RemoveAllGroups;
use Kajona\System\System\Permissions\RemoveGroup;
use Kajona\System\System\Permissions\RemovePermissionFromGroup;
use Kajona\System\System\Rights;
use Kajona\System\System\SystemAspect;
use Kajona\System\System\SystemSetting;
use Kajona\System\System\UserGroup;

/**
 * @author sidler@mulchprod.de
 */
class PermissionTablesTest extends Testbase
{

    public function testPermissionTables()
    {
        $record = new SystemAspect();
        ServiceLifeCycleFactory::getLifeCycle($record)->update($record);

        $processor = new PermissionActionProcessor(Rights::getInstance());
        $systemid = $record->getSystemid();
        $processor->addAction(new RemoveAllGroups($systemid));
        $processor->applyActions();

        //only admins should be assigned right now
        $rows = Database::getInstance()->getPArray("SELECT * FROM agp_permissions_view where view_id = ?", [$systemid]);
        $this->assertEquals(count($rows), 1);
        $this->assertEquals($rows[0]["view_id"], $systemid);
        $this->assertEquals($rows[0]["view_shortgroup"], UserGroup::getShortIdForGroupId(SystemSetting::getConfigValue("_admins_group_id_")));
        $rows = Database::getInstance()->getPArray("SELECT * FROM agp_permissions_right2 where right2_id = ?", [$systemid]);
        $this->assertEquals(count($rows), 1);
        $this->assertEquals($rows[0]["right2_id"], $systemid);
        $this->assertEquals($rows[0]["right2_shortgroup"], UserGroup::getShortIdForGroupId(SystemSetting::getConfigValue("_admins_group_id_")));

        $group = new UserGroup();
        $group->setStrName(generateSystemid());
        ServiceLifeCycleFactory::getLifeCycle($group)->update($group);

        $processor = new PermissionActionProcessor(Rights::getInstance());
        $processor->addAction(new AddPermissionToGroup($record->getSystemid(), $group->getSystemid(), Rights::$STR_RIGHT_VIEW));
        $processor->applyActions();
        $rows = Database::getInstance()->getPArray("SELECT * FROM agp_permissions_view where view_id = ?", [$systemid]);
        $this->assertEquals(count($rows), 2);
        $rows = Database::getInstance()->getPArray("SELECT * FROM agp_permissions_right2 where right2_id = ?", [$systemid]);
        $this->assertEquals(count($rows), 1);

        $processor->addAction(new AddPermissionToGroup($record->getSystemid(), $group->getSystemid(), Rights::$STR_RIGHT_RIGHT2));
        $processor->addAction(new AddPermissionToGroup($record->getSystemid(), $group->getSystemid(), Rights::$STR_RIGHT_RIGHT3));
        $processor->applyActions();
        $rows = Database::getInstance()->getPArray("SELECT * FROM agp_permissions_view where view_id = ? ORDER BY view_shortgroup ASC", [$systemid]);
        $this->assertEquals(count($rows), 2);
        $this->assertEquals($rows[0]["view_id"], $systemid);
        $this->assertEquals($rows[0]["view_shortgroup"], UserGroup::getShortIdForGroupId(SystemSetting::getConfigValue("_admins_group_id_")));
        $this->assertEquals($rows[1]["view_shortgroup"], UserGroup::getShortIdForGroupId($group->getSystemid()));

        $rows = Database::getInstance()->getPArray("SELECT * FROM agp_permissions_right2 where right2_id = ? ORDER BY right2_shortgroup ASC", [$systemid]);
        $this->assertEquals(count($rows), 2);
        $this->assertEquals($rows[0]["right2_id"], $systemid);
        $this->assertEquals($rows[0]["right2_shortgroup"], UserGroup::getShortIdForGroupId(SystemSetting::getConfigValue("_admins_group_id_")));
        $this->assertEquals($rows[1]["right2_shortgroup"], UserGroup::getShortIdForGroupId($group->getSystemid()));


        $processor = new PermissionActionProcessor(Rights::getInstance());
        $processor->addAction(new RemovePermissionFromGroup($record->getSystemid(), $group->getSystemid(), Rights::$STR_RIGHT_VIEW));
        $processor->applyActions();
        $rows = Database::getInstance()->getPArray("SELECT * FROM agp_permissions_view where view_id = ? ORDER BY view_shortgroup ASC", [$systemid]);
        $this->assertEquals(count($rows), 1);
        $this->assertEquals($rows[0]["view_id"], $systemid);
        $this->assertEquals($rows[0]["view_shortgroup"], UserGroup::getShortIdForGroupId(SystemSetting::getConfigValue("_admins_group_id_")));

        $rows = Database::getInstance()->getPArray("SELECT * FROM agp_permissions_right2 where right2_id = ? ORDER BY right2_shortgroup ASC", [$systemid]);
        $this->assertEquals(count($rows), 2);
        $this->assertEquals($rows[0]["right2_id"], $systemid);
        $this->assertEquals($rows[0]["right2_shortgroup"], UserGroup::getShortIdForGroupId(SystemSetting::getConfigValue("_admins_group_id_")));
        $this->assertEquals($rows[1]["right2_shortgroup"], UserGroup::getShortIdForGroupId($group->getSystemid()));

        ServiceLifeCycleFactory::getLifeCycle($record)->deleteObjectFromDatabase($record);
        $this->assertEquals(Database::getInstance()->getPRow("SELECT COUNT(*) as anz FROM agp_permissions_view where view_id = ?", [$systemid])["anz"], 0);
        $this->assertEquals(Database::getInstance()->getPRow("SELECT COUNT(*) as anz FROM agp_permissions_right2 where right2_id = ?", [$systemid])["anz"], 0);

    }


}
