/**
 * Helper Class to format the Agp dates
 */
class DateFormatter {
    /**
     * Formats an Agp Date and gives it in the given Format
     * @param date the longInt value of the agpDate
     * @param dateFormat the required date format
     */
    public static formatAgpDate (date : number, dateFormat : string) : string {
        const GERMAN_FORMAT : string = 'dd.mm.yyyy';
        const ENGLISH_FORMAT : string = 'mm/dd/yyyy';
        if ((dateFormat !== ENGLISH_FORMAT && (dateFormat !== GERMAN_FORMAT))) {
            throw new Error('Unknown given date format')
        }
        if (typeof date !== 'number') {
            throw new Error('Date should be an int')
        }
        if (date.toString().length !== 14) {
            throw new Error('Wrong length of date given')
        }
        let year : string = '';
        let month : string = '';
        let day : string = '';
        let formattedDate : string = '';
        let stringDate : string = date.toString();

        year = stringDate.substr(0, 4);
        month = stringDate.substr(4, 2);
        day = stringDate.substr(6, 2);
        // format the date
        switch (dateFormat) {
            case ENGLISH_FORMAT : formattedDate = month + '/' + day + '/' + year; break;
            case GERMAN_FORMAT : formattedDate = day + '.' + month + '.' + year; break
        }
        return formattedDate
    }

    /**
     * Formats a Date into RFC3339 timestamp
     * @param date javascript Date
     */
    public static rfc3339 (date: Date) {
        function pad(n: number) {
            return n < 10 ? "0" + n : n;
        }
        function timezoneOffset(offset: number) {
            let sign;
            if (offset === 0) {
                return "+00:00";
            }
            sign = (offset > 0) ? "-" : "+";
            offset = Math.abs(offset);
            return sign + pad(Math.floor(offset / 60)) + ":" + pad(offset % 60);
        }

        return date.getFullYear() + "-" +
            pad(date.getMonth() + 1) + "-" +
            pad(date.getDate()) + "T" +
            pad(date.getHours()) + ":" +
            pad(date.getMinutes()) + ":" +
            pad(date.getSeconds()) +
            timezoneOffset(date.getTimezoneOffset());
    }
}

export default DateFormatter
