<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\Flow\System\Flow\Condition;

use Kajona\Flow\System\FlowConditionAbstract;
use Kajona\Flow\System\FlowConditionResult;
use Kajona\Flow\System\FlowTransition;
use Kajona\System\Admin\AdminFormgenerator;
use Kajona\System\System\Model;
use Kajona\System\System\Session;
use Kajona\System\System\SystemChangelog;

/**
 * User is not allowed to execute two status changes in a row
 * is used for principle of dual control - e.g. check and review check
 *
 * @author bernhard.grabietz@artemeon.de
 * @module flow
 * @since 7.1
 */
class NextTransitionForSameUserForbiddenCondition extends FlowConditionAbstract
{
    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        return $this->getLang('flow_condition_user_forbidden_title', 'flow');
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return $this->getLang('flow_condition_user_forbidden_description', 'flow');
    }

    /**
     * validates if a user executing a flow transition is not the same user who did the last flow status change
     *
     * @param Model $flowObject
     * @param FlowTransition $FlowTransition
     * @return FlowConditionResult
     * @throws \Kajona\System\System\Exception
     */
    public function validateCondition(Model $flowObject, FlowTransition $FlowTransition)
    {
        if (Session::getInstance()->isSuperAdmin()) {
            return new FlowConditionResult(true);
        }
        $changeLog = SystemChangelog::getSpecificEntries($flowObject->getStrSystemid(), 'actionEdit', 'intRecordStatus', null, $flowObject->getIntRecordStatus());
        $changeLogLastEntry = array_shift($changeLog);
        $lastRecordStatusChangeUserId = $changeLogLastEntry ? $changeLogLastEntry->getStrUserId() : '';
        $activeUserId = Session::getInstance()->getUserID();
        if ($activeUserId === $lastRecordStatusChangeUserId) {
            return new FlowConditionResult(false, [$this->getLang('flow_condition_user_forbidden_error', 'flow')]);
        }

        return new FlowConditionResult(true);
    }

    /**
     * @inheritdoc
     */
    public function configureForm(AdminFormgenerator $objForm)
    {
    }
}
