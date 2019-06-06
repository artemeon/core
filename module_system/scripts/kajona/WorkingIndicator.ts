import $ from 'jquery'

class WorkingIndicator {
    private static intWorkingCount = 0

    public static start () {
        if (this.intWorkingCount === 0) {
            $('#status-indicator').addClass('active')
        }
        this.intWorkingCount++
    }

    public static stop () {
        this.intWorkingCount--

        if (this.intWorkingCount === 0) {
            $('#status-indicator').removeClass('active')
        }
    }

    public static getInstance () {
        return WorkingIndicator
    }
}
;(<any>window).WorkingIndicator = WorkingIndicator
export default WorkingIndicator
