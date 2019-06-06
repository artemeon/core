import Ajax from 'core/module_system/scripts/kajona/Ajax'
import Vue from 'vue'
/**
 * Fronend controller for the dbbrowser module
 */
class Dbbrowser {
    /**
     * creates a new index for the column
     * @param tableName
     * @param column
     */
    public static initDbBrowser (element: any) {
        // eslint-disable-next-line no-new
        new Vue({ el: '#' + element })
    }
    public static addIndex (tableName: string, column: string) {
        Ajax.genericAjaxCall(
            'dbbrowser',
            'apiAddIndex',
            '&table=' + tableName + '&column=' + column,
            function (data: any, status: string) {
                if (status === 'success') {
                    Ajax.loadUrlToElement(
                        '.schemaDetails',
                        '/xml.php?module=dbbrowser&action=apiSystemSchema&table=' +
                            tableName
                    )
                }
            },
            null,
            null,
            'post',
            'json'
        )
    }

    /**
     * Deletes an index from the table
     * @param tableName
     * @param indexName
     */
    public static deleteIndex (tableName: string, indexName: string) {
        Ajax.genericAjaxCall(
            'dbbrowser',
            'apiDeleteIndex',
            '&table=' + tableName + '&index=' + indexName,
            function (data: any, status: string) {
                if (status === 'success') {
                    Ajax.loadUrlToElement(
                        '.schemaDetails',
                        '/xml.php?module=dbbrowser&action=apiSystemSchema&table=' +
                            tableName
                    )
                }
            },
            null,
            null,
            'post',
            'json'
        )
    }

    /**
     * Recreates an index
     * @param tableName
     * @param indexName
     */
    public static recreateIndex (tableName: string, indexName: string) {
        Ajax.genericAjaxCall(
            'dbbrowser',
            'apiRecreateIndex',
            '&table=' + tableName + '&index=' + indexName,
            function (data: any, status: string) {
                if (status === 'success') {
                    Ajax.loadUrlToElement(
                        '.schemaDetails',
                        '/xml.php?module=dbbrowser&action=apiSystemSchema&table=' +
                            tableName
                    )
                }
            },
            null,
            null,
            'post',
            'json'
        )
    }
}
;(<any>window).Dbbrowser = Dbbrowser
export default Dbbrowser
