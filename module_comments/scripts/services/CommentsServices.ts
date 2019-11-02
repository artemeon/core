import { AxiosResponse } from 'axios'
import HttpClient from 'core/module_system/scripts/kajona/HttpClient'

/**
 * makes all the necessary api calls of the commentsModule
 */
class CommentsServices{
    public static async listComments(payload): Promise<[Error, AxiosResponse]> {
        const [err, res] = await HttpClient.get('api.php/v1/comments/55d45f65dbc6ea24a4d3')
        return [err, res]
    }
    public static async addComment(payload): Promise<[Error, AxiosResponse]> {
        const [err, res] = await HttpClient.post('api.php/v1/comments/55d45f65dbc6ea24a4d3', {},{
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
