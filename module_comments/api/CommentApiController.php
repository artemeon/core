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
    public function addComment($body, HttpContext $context): HttpResponse
    {
        $language = Carrier::getInstance()->getObjLang();
        $comment = new CommentComment();

        $commentText = $body['text']??null;
        $commentFieldId = $body['fieldId']??'';
        $commentPred = $body['pred']??'';
        $commentEndDate = $body['endDate']??null;
        $commentDone = $body['done']??null;
        $commentAssignee = $body['assignee']??null;
        $comment->setAssignee($commentAssignee);
        $comment->setCommentDone($commentDone);
        $comment->setCommentText($commentText);
        $comment->setFieldId($commentFieldId);
        $comment->setPrevId($commentPred);
        $comment->setObjEndDate($commentEndDate);

        return new JsonResponse(['message' => 'success'], 200);
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

}
