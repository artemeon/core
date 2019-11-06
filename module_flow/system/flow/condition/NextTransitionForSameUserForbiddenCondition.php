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
use \Kajona\System\System\Exception;

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
     * @var Session
     */
    private $session;

    public function __construct($strSystemid = '')
    {
        parent::__construct($strSystemid);
        $this->session = Session::getInstance();
    }

    public function getTitle(): string
    {
        return $this->getLang('flow_condition_user_forbidden_title', 'flow');
    }

    public function getDescription(): string
    {
        return $this->getLang('flow_condition_user_forbidden_description', 'flow');
    }

    /**
     * validates if a user executing a flow transition is not the same user who did the last flow status change
     *
     * @param Model $flowObject
     * @param FlowTransition $FlowTransition
     * @return FlowConditionResult
     * @throws Exception
     */
    public function validateCondition(Model $flowObject, FlowTransition $FlowTransition)
    {
        if ($this->session->isSuperAdmin()) {
            return new FlowConditionResult(true);
        }
        $changeLog = SystemChangelog::getSpecificEntries($flowObject->getStrSystemid(), 'actionEdit', 'intRecordStatus', null, $flowObject->getIntRecordStatus());
        $changeLogLastEntry = array_shift($changeLog);
        if (
            !empty($changeLogLastEntry)
            && $this->session->getUserID() === $changeLogLastEntry->getStrUserId()
        ) {
            return new FlowConditionResult(false, [$this->getLang('flow_condition_user_forbidden_error', 'flow')]);
        }

        return new FlowConditionResult(true);
    }

    public function configureForm(AdminFormgenerator $objForm)
    {
    }
}
