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
        const GERMAN_FORMAT : string = 'dd.mm.yyyy'
        const ENGLISH_FORMAT : string = 'mm/dd/yyyy'
        if ((dateFormat !== ENGLISH_FORMAT && (dateFormat !== GERMAN_FORMAT))) {
            throw new Error('Unknown given date format')
        }
        if (typeof date !== 'number') {
            throw new Error('Date should be an int')
        }
        if (date.toString().length !== 14) {
            throw new Error('Wrong length of date given')
        }
        let year : string = ''
        let month : string = ''
        let day : string = ''
        let formattedDate : string = ''
        let stringDate : string = date.toString()

        year = stringDate.substr(0, 4)
        month = stringDate.substr(4, 2)
        day = stringDate.substr(6, 2)
        // format the date
        switch (dateFormat) {
            case ENGLISH_FORMAT : formattedDate = month + '/' + day + '/' + year; break
            case GERMAN_FORMAT : formattedDate = day + '.' + month + '.' + year; break
        }
        return formattedDate
    }
}

export default DateFormatter
