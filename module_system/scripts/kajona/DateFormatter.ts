/**
 * Helper Class to format the Agp dates
 */
class DateFormatter {
    /**
     * Formats a Date into RFC3339 timestamp
     * @param date javascript Date
     */
    public static rfc3339(date: Date) {
        function pad(n: number) {
            return n < 10 ? "0" + n : n
        }

        function timezoneOffset(offset: number) {
            let sign
            if (offset === 0) {
                return "+00:00"
            }
            sign = (offset > 0) ? "-" : "+"
            offset = Math.abs(offset)
            return sign + pad(Math.floor(offset / 60)) + ":" + pad(offset % 60)
        }

        return date.getFullYear() + "-" +
            pad(date.getMonth() + 1) + "-" +
            pad(date.getDate()) + "T" +
            pad(date.getHours()) + ":" +
            pad(date.getMinutes()) + ":" +
            pad(date.getSeconds()) +
            timezoneOffset(date.getTimezoneOffset())
    }
}

export default DateFormatter

