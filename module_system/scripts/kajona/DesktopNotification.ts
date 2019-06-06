/**
 * Wrapper for desktop notifications.
 *
 * @see https://developer.mozilla.org/de/docs/Web/API/Notifications_API
 */
class DesktopNotification {
    private static bitGranted: boolean = false

    /**
     * Sends a message to the client. Asks for permissions if not yet given.
     *
     * @param strTitle
     * @param strBody
     * @param {callback} onClick
     */
    public static showMessage (
        strTitle: string,
        strBody?: string,
        onClick?: any
    ) {
        if (!this.hasNotification()) {
            return
        }

        this.grantPermissions()

        if (this.bitGranted) {
            let notification = new Notification(strTitle, { body: strBody })
            if (onClick) {
                notification.onclick = onClick
            }
        }
    }

    /**
     * Requests notification rights
     */
    public static grantPermissions () {
        if (!this.hasNotification()) {
            return
        }

        if (Notification.permission === 'denied') {
            DesktopNotification.bitGranted = false
        } else if (Notification.permission === 'granted') {
            DesktopNotification.bitGranted = true
        } else if (Notification.permission === 'default') {
            Notification.requestPermission()
                .then(function () {
                    DesktopNotification.bitGranted = true
                })
                .catch(function () {
                    DesktopNotification.bitGranted = false
                })
        } else {
            DesktopNotification.bitGranted = false
        }
    }

    private static hasNotification (): boolean {
        return typeof Notification !== 'undefined'
    }
}
;(<any>window).DesktopNotification = DesktopNotification
export default DesktopNotification
