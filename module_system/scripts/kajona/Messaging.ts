import $ from 'jquery'
import DialogHelper from 'core/module_v4skin/scripts/kajona/DialogHelper'
import Dialog from 'core/module_v4skin/scripts/kajona/Dialog'
import * as toastr from 'toastr'
import Ajax from './Ajax'
import Util from './Util'
import Router from './Router'


interface Accept {
    type: string;
}

interface RedirectAction extends Accept {
    target: string;
}

interface AjaxAction extends Accept {
    module: string;
    action: string;
    systemid: string;
}

interface UpdateStatusAction extends Accept {
    systemid: string;
    icon: string;
}

interface Alert {
    type: string;
    systemid: string;
    title: string;
    body: string;
    onAccept: Accept;
    confirmLabel?: string;
}

/**
 * Subsystem for all messaging related tasks. Queries the backend for the number of unread messages, ...
 */
class Messaging {
    private static pollInterval: number = 30000

    private static timeout: number = null

    private static intCount: number = 0

    private static dialog: Dialog

    private static properties: boolean = null

    private static bitFirstLoad: boolean = true

    /**
     * Forces a polling of unread messages and alert, but only once
     */
    public static pollMessages(): void {
        Messaging.getUnreadCount((intCount: number): void => {
            Messaging.updateCountInfo(intCount)
        })
    }

    /**
     * Gets the number of unread messages for the current user.
     * Expects a callback-function whereas the number is passed as a param.
     *
     * @param objCallback
     */
    public static getUnreadCount(objCallback: Function): void{
        // in case we are on the login page dont poll
        if ($('#loginContainer').length > 0) {
            return
        }

        Ajax.genericAjaxCall(
            'messaging',
            'getUnreadMessagesCount',
            '',
            (data: any, status: string): void => {
                if (status === 'success') {
                    const $objResult = $.parseJSON(data)
                    objCallback($objResult.count)

                    if ($objResult.alert) {
                        Messaging.renderAlert($objResult.alert)
                    }
                } else if (data.status === 401
                    && $('#loginContainer').length === 0
                    && !$('body').hasClass('anonymous')) {
                    // in case the API returns a 401 the user has logged out so reload the page to show the login page
                    document.location.reload()
                }
            },
        )
    }

    /**
     * Loads the list of recent messages for the current user.
     * The callback is passed the json-object as a param.
     * @param objCallback
     */
    public static getRecentMessages(objCallback: Function): void {
        Ajax.genericAjaxCall('messaging', 'getRecentMessages', '', (
            data: any,
            status: string,
        ): void => {
            if (status === 'success') {
                const objResponse = $.parseJSON(data)
                objCallback(objResponse)
            }
        })
    }

    /**
     * Enables or disables the polling of message counts / alerts
     * @param bitEnabled
     */
    public static setPollingEnabled(bitEnabled: boolean): void {
        let enabled = bitEnabled
        if (Util.isStackedDialog() || $('body').hasClass('anonymous')) {
            enabled = false
        }

        if (enabled) {
            // start timeout only if we have not already a timeout
            if (!Messaging.timeout) {
                Messaging.pollMessageCount()
            }
        } else {
            // if we have a timeout clear
            if (Messaging.timeout) {
                window.clearTimeout(Messaging.timeout)
            }
            Messaging.timeout = null
        }
    }

    /**
     * Updates the count info of the current unread messages
     * @param intCount
     */
    public static updateCountInfo(intCount: number): void {
        const $userNotificationsCount = $('#userNotificationsCount')
        const oldCount = Number($userNotificationsCount.text())
        $userNotificationsCount.text(intCount)
        if (intCount > 0) {
            $userNotificationsCount.show()
            if (oldCount !== intCount) {
                if (document.title.match(/\(\d+\)/)) {
                    document.title = document.title.replace(
                        /\(\d+\)/,
                        `(${intCount})`,
                    )
                } else {
                    document.title = `(${intCount}) ${document.title}`
                }
            }
        } else {
            $userNotificationsCount.hide()
        }
    }

    private static registerListener(): void {
        // listen to browser events to enable/disable notification polling if window is not active
        $(window).focus((): void => {
            Messaging.setPollingEnabled(true)
        })

        $(window).blur((): void => {
            Messaging.setPollingEnabled(false)
        })
    }

    /**
     * Triggers the polling of unread messages from the backend
     */
    private static pollMessageCount(): void {
        Messaging.getUnreadCount((intCount: number): void => {
            Messaging.updateCountInfo(intCount)
        })

        Messaging.timeout = window.setTimeout(
            Messaging.pollMessageCount,
            Messaging.pollInterval,
        )
    }

    /**
     * Renders an alert generated on the backend
     * @param $objAlert
     */
    private static renderAlert($objAlert: Alert): void {
        if (
            $objAlert.type === 'Kajona\\System\\System\\MessagingNotification'
        ) {
            const options = {
                onclick(): void {
                    const callback = Messaging.getActionCallback(
                        $objAlert.onAccept,
                    )
                    callback()
                },
            }

            toastr.info($objAlert.title, $objAlert.body, options)
        } else if (
            !Messaging.dialog
                || (Messaging.dialog && !Messaging.dialog.isVisible())
        ) {
            Messaging.dialog = DialogHelper.showConfirmationDialog(
                $objAlert.title,
                $objAlert.body,
                $objAlert.confirmLabel,
                Messaging.getActionCallback($objAlert.onAccept),
            )
        }
        Ajax.genericAjaxCall('messaging', 'deleteAlert', $objAlert.systemid)
    }

    /**
     * Internal helper to built a real callback based on the action provided by the backend
     * @param $onAccept
     * @returns {Function}
     */
    private static getActionCallback($onAccept: Accept): Function {
        if ($onAccept && $onAccept.type === 'redirect') {
            const data = $onAccept as RedirectAction
            return (): void => {
                Router.registerLoadCallback('alert_redirect', (): void => {
                    $('.modal-backdrop.fade.in').remove()
                    Messaging.pollMessages()
                })

                if (Messaging.dialog) {
                    Messaging.dialog.hide()
                }
                Router.loadUrl(data.target)
            }
        } if ($onAccept && $onAccept.type === 'ajax') {
            const data = $onAccept as AjaxAction
            return (): void => {
                Ajax.genericAjaxCall(
                    data.module,
                    data.action,
                    data.systemid,
                    (resp: any): void => {
                        // on ok we trigger the getUnreadCount again since the ajax call could have created
                        // other alert messages
                        Messaging.pollMessages() // check whether the ajax call returns actions which we should execute
                        const respData = JSON.parse(resp)
                        if (respData.actions) {
                            respData.actions.forEach((action: Accept): void => {
                                const callback = Messaging.getActionCallback(
                                    action,
                                )
                                callback()
                            })
                        }
                    },
                )
            }
        } if ($onAccept && $onAccept.type === 'update_status') {
            const data = $onAccept as UpdateStatusAction

            return (): void => {
                // search for the specific status flag and update
                $('.flow-status-icon').each((): void => {
                    const el = $(this).find('.navbar-link')
                    if ($(this).data('systemid') === data.systemid) {
                        el.html(data.icon)
                    }
                })
            }
        }

        return (): void => {}
    }
}
(window as any).Messaging = Messaging
export default Messaging
