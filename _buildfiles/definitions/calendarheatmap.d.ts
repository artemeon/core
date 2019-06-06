
declare module "calendarheatmap" {

    interface CalendarHeatmap extends Function {
        construct(nowDate? : Date, yearAgoDate? : Date) : void
        data(value : any) : CalendarHeatmap;
        selector(value : any) : CalendarHeatmap;
        colorRange(value : any) : CalendarHeatmap;
        width(value : any) : CalendarHeatmap;
        height(value : any) : CalendarHeatmap;
        padding(value : any) : CalendarHeatmap;
        months(value : any) : CalendarHeatmap;
        days(value : any) : CalendarHeatmap;
        tooltipEnabled(value : any) : CalendarHeatmap;
        tooltipHtml(value : any) : CalendarHeatmap;
        tooltipUnit(value : any) : CalendarHeatmap;
        tooltipUnitPlural(value : any) : CalendarHeatmap;
        tooltipDateFormat(value : any) : CalendarHeatmap;
        legendEnabled(value : any) : CalendarHeatmap;
        toggleDays(value : any) : CalendarHeatmap;
        onClick(value : any) : CalendarHeatmap;
    }

    var ch : CalendarHeatmap;

    export = ch;

}

