import * as toastr from 'toastr'

/**
 * General way to display a status message using toastr
 *
 * Pass a xml-response from a Kajona server to displayXMLMessage() to start the logic
 * or use messageOK() / messageError() passing a regular string
 */
class StatusDisplay {
    /**
     * General entrance point. Use this method to pass an xml-response from the kajona server.
     * Tries to find a message- or an error-tag an invokes the corresponding methods
     *
     * @param {String} message
     */
    public static displayXMLMessage (message: string) {
        // decide, whether to show an error or a message, message only in debug mode
        if (
            message.indexOf('<message>') !== -1 &&
            message.indexOf('<error>') === -1
        ) {
            let intStart = message.indexOf('<message>') + 9
            let responseText = message.substr(
                intStart,
                message.indexOf('</message>') - intStart
            )
            this.messageOK(responseText)
        }

        if (message.indexOf('<error>') !== -1) {
            let intStart = message.indexOf('<error>') + 7
            let responseText = message.substr(
                intStart,
                message.indexOf('</error>') - intStart
            )
            this.messageError(responseText)
        }
    }

    /**
     * Creates a success message box contaning the passed content
     *
     * @param {String} strMessage
     */
    public static messageSuccess (strMessage: string) {
        toastr.success(strMessage)
    }

    /**
     * Creates a informal message box contaning the passed content
     *
     * @param {String} strMessage
     */
    public static messageOK (strMessage: string) {
        toastr.info(strMessage)
    }

    /**
     * Creates an error message box containg the passed content
     *
     * @param {String} strMessage
     */
    public static messageError (strMessage: string) {
        toastr.error(strMessage)
    }
}
;(<any>window).StatusDisplay = StatusDisplay
export default StatusDisplay
