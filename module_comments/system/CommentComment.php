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
     * @tableColumn agp_comment_comment.comment_field_id
     * @tableColumnDatatype char20
     */
    private $strFieldId = null;

    /**
     * @var string
     * @tableColumn agp_comment_comment.comment_prev_id
     * @tableColumnDatatype char20
     */
    private $strCommentPrevId = '';

    /**
     * @var Date
     * @tableColumn agp_comment_comment.comment_end_date
     * @tableColumnDatatype long
     */
    private $objEndDate = null;

    /**
     * @var string
     * @tableColumn agp_comment_comment.comment_text
     * @tableColumnDatatype text
     */
    private $strCommentText = null;

    /**
     * @var bool
     * @tableColumn agp_comment_comment.comment_done
     * @tableColumnDatatype int
     */
    private $bitCommentDone = 0;

    /**
     * @var string
     * @tableColumn agp_comment_comment.comment_assignee
     * @tableColumnDatatype char20
     */
    private $strAssignee = '';

    /**
     * Sets the field id
     *
     * @param string $fieldId
     */
    public function setFieldId(string $fieldId)
    {
        $this->strFieldId = $fieldId;
    }

    /**
     * returns the field_id
     *
     * @return string
     */
    public function getFieldId()
    {
        return $this->strFieldId;
    }

    /**
     * Sets the previous comment  id
     *
     * @param string $prevId
     */
    public function setPrevId(string $prevId)
    {
        $this->strCommentPrevId= $prevId;
    }

    /**
     * returns the comment_prev_id
     *
     * @return string
     */
    public function getCommentPrevId()
    {
        return $this->strCommentPrevId;
    }

    /**
     * @param Date $objEndDate
     */
    public function setObjEndDateComment($objEndDate)
    {
        $this->objEndDate = $objEndDate;
    }

    /**
     * @return Date
     */
    public function getEndDate()
    {
        return $this->objEndDate;
    }

    /**
     * @param string $commentText
     */
    public function setCommentText($commentText)
    {
        $this->strCommentText = $commentText;
    }

    /**
     * @return string
     */
    public function getCommentText()
    {
        return $this->strCommentText;
    }

    /**
     * @param bool $commentDone
     */
    public function setCommentDone($commentDone)
    {
        $this->bitCommentDone = $commentDone;
    }

    /**
     * @return bool
     */
    public function getCommentDone()
    {
        return $this->bitCommentDone;
    }

    /**
     * @param string assignee
     */
    public function setAssignee($assignee)
    {
        $this->strAssignee = $assignee;
    }

    /**
     * @return string
     */
    public function getAssignee()
    {
        return $this->strAssignee;
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
