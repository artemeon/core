<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\Comments\System;

use Kajona\System\System\AdminListableInterface;
use Kajona\System\System\Date;
use Kajona\System\System\Model;
use Kajona\System\System\ModelInterface;
use Kajona\System\System\SystemModule;

/**
 * Model-Class for comment queries.
 *
 * @package module_comments
 * @author rym.rjab@artemeon.de
 * @since 3.4
 * @targetTable agp_comment_comment.comment_id
 *
 * @module comments
 * @moduleId _comments_module_id_
 */
class CommentComment extends Model implements ModelInterface
{
    /**
     * @var string
     * @tableColumn agp_comment_comment.comment_system_id
     * @tableColumnDatatype char20
     */
    private $commentSystemId = null;

    /**
     * @var string
     * @tableColumn agp_comment_comment.comment_field_id
     * @tableColumnDatatype char20
     */
    private $fieldId = null;

    /**
     * @var string
     * @tableColumn agp_comment_comment.comment_prev_id
     * @tableColumnDatatype char20
     */
    private $commentPrevId = '';

    /**
     * @var Date
     * @tableColumn agp_comment_comment.comment_end_date
     * @tableColumnDatatype long
     */
    private $endDate = null;

    /**
     * @var string
     * @tableColumn agp_comment_comment.comment_text
     * @tableColumnDatatype text
     */
    private $commentText = null;

    /**
     * @var bool
     * @tableColumn agp_comment_comment.comment_done
     * @tableColumnDatatype int
     */
    private $commentDone;

    /**
     * @var string
     * @tableColumn agp_comment_comment.comment_assignee
     * @tableColumnDatatype char20
     */
    private $assignee = '';

    /**
     * @return string
     */
    public function getCommentSystemId(): ?string
    {
        return $this->commentSystemId;
    }

    /**
     * @param string $commentSystemId
     */
    public function setCommentSystemId(?string $commentSystemId): void
    {
        $this->commentSystemId = $commentSystemId;
    }

    /**
     * @return string
     */
    public function getFieldId(): ?string
    {
        return $this->fieldId;
    }

    /**
     * @param string $fieldId
     */
    public function setFieldId(?string $fieldId): void
    {
        $this->fieldId = $fieldId;
    }

    /**
     * @return string
     */
    public function getCommentPrevId(): ?string
    {
        return $this->commentPrevId;
    }

    /**
     * @param string $commentPrevId
     */
    public function setCommentPrevId(?string $commentPrevId): void
    {
        $this->commentPrevId = $commentPrevId;
    }

    /**
     * @return Date
     */
    public function getEndDate(): ?Date
    {
        return $this->endDate;
    }

    /**
     * @param Date $endDate
     */
    public function setEndDate(?Date $endDate): void
    {
        $this->endDate = $endDate;
    }

    /**
     * @return string
     */
    public function getCommentText(): ?string
    {
        return $this->commentText;
    }

    /**
     * @param string $commentText
     */
    public function setCommentText(?string $commentText): void
    {
        $this->commentText = $commentText;
    }

    /**
     * @return bool
     */
    public function isCommentDone(): ?bool
    {
        return $this->commentDone;
    }

    /**
     * @param bool $commentDone
     */
    public function setCommentDone(?bool $commentDone): void
    {
        $this->commentDone = $commentDone;
    }

    /**
     * @return string
     */
    public function getAssignee(): ?string
    {
        return $this->assignee;
    }

    /**
     * @param string $assignee
     */
    public function setAssignee(?string $assignee): void
    {
        $this->assignee = $assignee;
    }



    /*
     * Returns the name to be used when rendering the current object, e.g. in admin-lists.
     *
     * @return string
     * @todo move this to \Kajona\System\System\Model, making this interface obsolete
     */
    public function getStrDisplayName()
    {
        // TODO: Implement getStrDisplayName() method.
    }
}
