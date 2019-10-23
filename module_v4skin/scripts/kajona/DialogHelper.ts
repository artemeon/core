import Folderview from 'core/module_system/scripts/kajona/Folderview'
import Dialog from './Dialog'

class DialogHelper {
    private static loadingModal: Dialog = null

    /**
     * Creates a new confirmation dialog
     *
     * @param strTitle
     * @param strContent
     * @param strConfirmationLabel
     * @param strConfirmationHref
     * @returns {module:dialog}
     */
    public static showConfirmationDialog (
        strTitle: string,
        strContent: string,
        strConfirmationLabel: string,
        strConfirmationHref: string | Function
    ) {
        var dialogInstance = new Dialog(Dialog.ID_DIALOG_1, 1)
        dialogInstance.setTitle(strTitle)
        dialogInstance.setContent(
            strContent,
            strConfirmationLabel,
            strConfirmationHref
        )
        dialogInstance.init()
        return dialogInstance
    }

    /**
     * Opens an iframe based dialog to load other pages within a dialog. saves the dialog reference to folderview.dialog
     * in order to modify / access it later
     *
     * @param strUrl
     * @param strTitle
     * @returns {module:dialog}
     */
    public static showIframeDialog (strUrl: string, strTitle: string) {
        var dialogInstance = new Dialog('folderviewDialog', 0)
        dialogInstance.setContentIFrame(strUrl)
        dialogInstance.setTitle(strTitle)
        dialogInstance.init()

        // register the dialog
        Folderview.dialog = dialogInstance

        return dialogInstance
    }

    public static showIframeDialogStacked (strUrl: string, strTitle: string) {
        var dialogInstance = new Dialog('folderviewDialogStacked', 0)
        dialogInstance.setContentIFrame(strUrl)
        dialogInstance.setTitle(strTitle)
        dialogInstance.init()

        // register the dialog
        Folderview.dialog = dialogInstance

        return dialogInstance
    }

    /**
     * Registers and shows a loading modal
     * @returns {module:dialog}
     */
    public static showLoadingModal () {
        if (this.loadingModal === null) {
            this.loadingModal = new Dialog(Dialog.ID_DIALOG_3, 3)
        }

        this.loadingModal.init()
        return this.loadingModal
    }

    /**
     * Registers and shows a information modal
     * @returns {module:dialog}
     */
    public static showInfoModal (title: string, content: string) {
        var dialogInstance = new Dialog(Dialog.ID_DIALOG_0, 0)
        dialogInstance.setTitle(title)
        dialogInstance.setContentRaw(content)
        dialogInstance.init(300, 300)

        return dialogInstance
    }

    /**
     * Hides the currently open loading modal
     */
    public static hideLoadingModal () {
        if (this.loadingModal instanceof Dialog) {
            this.loadingModal.hide()
        }
    }
}
;(<any>window).DialogHelper = DialogHelper
export default DialogHelper
