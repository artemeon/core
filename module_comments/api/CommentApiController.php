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
use Kajona\System\System\Database;
use Kajona\System\System\Exception;
use Kajona\System\System\Lifecycle\ServiceLifeCycleFactory;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\OrmCondition;
use Kajona\System\System\OrmObjectlist;
use PSX\Http\Environment\HttpContext;
use PSX\Http\Environment\HttpResponse;
use Kajona\Api\System\Http\JsonResponse;
use Kajona\System\System\Date;
use Kajona\System\System\UserSourcefactory;
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
     * returns available comments for a system_id and field_id
     *
     * @param HttpContext $context
     * @return HttpResponse
     * @api
     * @method GET
     * @path /v1/comments/{id}/{field}
     * @authorization usertoken
     */
    public function listComments(HttpContext $context): HttpResponse
    {
        $strSystemId = $context->getUriFragment('id');
        $strFieldId = $context->getUriFragment('field');
        $ormObj = new OrmObjectlist();
//        $ormObj->addWhereRestriction(new OrmPropertyCondition('commentSystemId', OrmComparatorEnum::Like(), $strId));
        $ormObj->addWhereRestriction(new OrmCondition('comment_system_id = ? AND comment_field_id = ?', array($strSystemId, $strFieldId)));
        $fields = $ormObj->getObjectList(CommentComment::class);
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
        $commentEndDate = new Date($body['endDate']);
        $commentDone = (bool)$body['done'];
        $commentSystemId = $context->getUriFragment('id');
        $commentAssignee = $body['assignee'];
        $comment->setAssignee($commentAssignee);
        $comment->setCommentDone($commentDone);
        $comment->setCommentText($commentText);
        $comment->setFieldId($commentFieldId);
        $comment->setCommentPrevId($commentPred);
        $comment->setCommentSystemId($commentSystemId);
        $comment->setEndDate($commentEndDate);
        $this->lifeCycleFactory->factory(\get_class($comment))->update($comment);
//        Carrier::getInstance()->getObjDB()->flushQueryCache();
        return new JsonResponse(['message' => 'success test test'], 200);
    }

    /**
     * @param $context
     * @return HttpResponse
     * @throws Exception
     * @api
     * @method GET
     * @QueryParam(name="query", type="string", description="the search query")
     * @path /v1/comments/users
     * @authorization usertoken
     */
    public function listAllUsers(HttpContext $context ): HttpResponse{
       $query = $context->getParameter('query');
//       $search = new OrmCondition('LIKE ?%',$query);
      $userFactory = new UserSourcefactory();
//     $results = $userFactory->getUserlistByUserquery($query);
        $results = $this->test($query,20);
        $items = array();
        foreach ($results as $result){
            $item = array();

            $userId = $result->getStrSystemid();
            $user = $this->objectFactory->getObject($userId)->getStrUsername();

            $item['name'] = $user;

            $items[] = $item;
        }


       return new JsonResponse(['users' =>$items], 200);
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

           $userId = $oneComment->getStrOwner($oneComment->getStrSystemid());
            $user = $this->objectFactory->getObject($userId);
            $user = $user->getStrUsername();
            $item['id'] = $oneComment->getStrSystemid();
            $item['text'] = $oneComment->getCommentText();
            $item['fieldId'] = $oneComment->getFieldId();
            $item['author'] = $user;
            $item['assignee'] = $oneComment->getAssignee();
            $item['done'] = $oneComment->isCommentDone();
            $item['endDate'] = $oneComment->getEndDate();
            $item['prevId'] = $oneComment->getCommentPrevId();
            $items[] = $item;
        }
        return $items;
    }


    private function test($query,$intMax){
        $connection = Database::getInstance();

        $strQuery = "SELECT user_tbl.user_id
                      FROM agp_system, agp_user AS user_tbl
                      LEFT JOIN agp_user_kajona AS user_kajona ON user_tbl.user_id = user_kajona.user_id
                      WHERE
                          (
                          user_tbl.user_username LIKE ? 
                          OR user_kajona.user_forename LIKE ? 
                          OR user_kajona.user_name LIKE ? 
                          OR ".$connection->getConcatExpression(['user_kajona.user_forename', '\' \'', 'user_kajona.user_name'])." LIKE ?
                          OR ".$connection->getConcatExpression(['user_kajona.user_name', '\' \'', 'user_kajona.user_forename'])." LIKE ?
                          OR ".$connection->getConcatExpression(['user_kajona.user_name', '\', \'', 'user_kajona.user_forename'])." LIKE ?                  
                          )
                          AND user_tbl.user_id = system_id
                          AND (system_deleted = 0 OR system_deleted IS NULL)
                      ORDER BY user_tbl.user_username, user_tbl.user_subsystem ASC";

        $arrParams = array($query."%", $query."%", $query."%", query."%", $query."%", $query."%");

        $arrIds = Carrier::getInstance()->getObjDB()->getPArray($strQuery, $arrParams, 0, $intMax);

        $arrReturn = array();
        foreach ($arrIds as $arrOneId) {
            $arrReturn[] = Objectfactory::getInstance()->getObject($arrOneId["user_id"]);
        }

        return $arrReturn;
    }
}
