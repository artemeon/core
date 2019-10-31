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
 * @targetTable agp_comment_comment.comment_comment_id
 *
 * @module comments
 * @moduleId _comments_module_id_
 */
class CommentComment extends Model implements ModelInterface
{
    /**
     * @var Date
     * @tableColumn agp_comment_comment.comment_comment_time_limit
     * @tableColumnDatatype long
     */
    private $timeLimit = null;

    /**
     * @var string
     * @tableColumn agp_comment_comment.comment_comment_text
     * @tableColumnDatatype text
     */
    private $commentText = null;

    /**
     * @var bool
     * @tableColumn agp_comment_comment.comment_comment_done
     */
    private $bitCommentDone;
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
