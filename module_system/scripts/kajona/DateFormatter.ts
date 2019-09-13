/**
 * Helper Class to format the Agp dates
 */
class DateFormatter {
    /**
     * Formats an Agp Date and gives it in the given Format
     * @param date
     * @param wishedFormat
     */
    public static formatAgpDate (date : number, wishedFormat : string) : string {
        if ((wishedFormat !== 'mm/dd/yyyy' && (wishedFormat !== 'dd.mm.yyyy'))) {
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
        // set year
        for (let i = 0; i < 4; i++) {
            year += stringDate[i]
        }
        // set month
        for (let i = 4; i < 6; i++) {
            month += stringDate[i]
        }
        // set day
        for (let i = 6; i < 8; i++) {
            day += stringDate[i]
        }
        // format the date
        switch (wishedFormat) {
            case 'mm/dd/yyyy' : formattedDate = month + '/' + day + '/' + year; break
            case 'dd.mm.yyyy' : formattedDate = day + '.' + month + '.' + year; break
        }
        return formattedDate
    }
}

export default DateFormatter
