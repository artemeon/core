import { AxiosResponse } from 'axios'
import HttpClient from 'core/module_system/scripts/kajona/HttpClient'

/**
 * makes all the necessary api calls of the commentsModule
 */
class CommentsServices{
    public static async addComment(payload): Promise<[Error, AxiosResponse]> {
        const [err, res] = await HttpClient.post('api.php/v1/comments/09790b35dbb1287229a8', {
            text: 'test test test lorem ipsum test',
            fieldId: '362b9e155667',
            pred: '0',
            endDate: Date.now(),
            done:0,
            assignee: 'dha'
        })
        return [err, res]
    }
}
export default CommentsServices
