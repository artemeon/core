import { AxiosResponse } from 'axios'
import HttpClient from 'core/module_system/scripts/kajona/HttpClient'

/**
 * makes all the necessary api calls of the commentsModule
 */
class CommentsServices{
    public static async listComments(payload): Promise<[Error, AxiosResponse]> {
        const [err, res] = await HttpClient.get('api.php/v1/comments/'+payload)
        return [err, res]
    }
    public static async addComment(payload): Promise<[Error, AxiosResponse]> {
        console.log(payload)
        const [err, res] = await HttpClient.post('api.php/v1/comments/'+payload.systemId, {},{
            text: payload.text,
            fieldId: payload.fieldId,
            pred: payload.pred,
            endDate: payload.date,
            done:payload.done,
            assignee: payload.assignee
        })
        return [err, res]
    }
}
export default CommentsServices
