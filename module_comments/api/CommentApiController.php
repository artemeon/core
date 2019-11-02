<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\Comments\Api;

use Kajona\Api\System\ApiControllerInterface;
use Kajona\Comments\System\CommentComment;
use Kajona\System\System\Carrier;
use Kajona\System\System\Exception;
use Kajona\System\System\Lifecycle\ServiceLifeCycleFactory;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\OrmComparatorEnum;
use Kajona\System\System\OrmObjectlist;
use Kajona\System\System\OrmPropertyCondition;
use PSX\Http\Environment\HttpContext;
use PSX\Http\Environment\HttpResponse;
use Kajona\Api\System\Http\JsonResponse;

/**
 * Class CommentApiController
 * @package Kajona\Comments\Api
 *
 * @author rym.rjab@artemeon.de
 * @since 7.1
 */
class CommentApiController implements ApiControllerInterface
{
    /**
     * @inject system_object_factory
     * @var Objectfactory
     */
    protected $objectFactory;

    /**
     * @inject system_life_cycle_factory
     * @var ServiceLifeCycleFactory
     */
    protected $lifeCycleFactory;

    /**
     * returns available comments for a system_id
     *
     * @param HttpContext $context
     * @return HttpResponse
     * @api
     * @method GET
     * @path /v1/comments/{id}
     * @authorization usertoken
     */
    public function listComments(HttpContext $context): HttpResponse
    {
        $strId = $context->getUriFragment('id');
        $ormObj = new OrmObjectlist();
        $fields = $ormObj->getObjectList(CommentComment::class,$strId);
        $results = $this->createCommentsResult($fields);
        return new JsonResponse(['comments' => $results], 200);
    }

    /**
     * add a new comment to  system_id
     *
     * @param array body
     * @param HttpContext $context
     * @return HttpResponse
     * @throws Exception
     * @api
     * @method POST
     * @path /v1/comments/{id}
     * @authorization usertoken
     */
    public function addComment(array $body, HttpContext $context): HttpResponse
    {
        $language = Carrier::getInstance()->getObjLang();
        $comment = new CommentComment();
        $commentText = $body['text'];
        $commentFieldId = $body['fieldId'];
        $commentPred = $body['pred'];
        $commentEndDate = 123123123;
        $commentDone = $body['done'];
        $commentAssignee = $body['assignee'];
        $comment->setAssignee($commentAssignee);
        $comment->setCommentDone($commentDone);
        $comment->setCommentText($commentText);
        $comment->setFieldId($commentFieldId);
        $comment->setPrevId($commentPred);
        $comment->setObjEndDateComment($commentEndDate);
        $this->lifeCycleFactory->factory(\get_class($comment))->update($comment,$context->getUriFragment('id'));
//        Carrier::getInstance()->getObjDB()->flushQueryCache();
        return new JsonResponse(['message' => 'success test test'], 200);
    }

    /**
     * Update comment
     *
     * @param array body
     * @param HttpContext $context
     * @return HttpResponse
     * @throws Exception
     * @api
     * @method PUT
     * @path /v1/comments/{id}
     * @authorization usertoken
     */
    public function updateComment($body, HttpContext $context): HttpResponse
    {

    }

    /**
     * add a new comment to  system_id
     *
     * @param array body
     * @param HttpContext $context
     * @return HttpResponse
     * @throws Exception
     * @api
     * @method DELETE
     * @path /v1/comments/{id}
     * @authorization usertoken
     */
    public function deleteComment($body, HttpContext $context): HttpResponse
    {

    }

    /**
     * Parses comments objects into json
     * @param array $comments
     * @return array
     */
    private function createCommentsResult(array $comments): array
    {
        $items = array();
        foreach ($comments as $oneComment){
            $item = array();
            $item['id'] = $oneComment->getStrSystemid();
            $item['text'] = $oneComment->getCommentText();
            $items[] = $item;
        }
        return $items;
    }
}
