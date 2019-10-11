
 class EventDispatcher {
    /**
     *
     * @param name : Event name that will be created
     */
    public static createEvent (name: string): any {
        let event: any
        if (typeof (Event) === 'function') {
            event = new Event(name)
        } else {
            event = document.createEvent('Event')
            event.initEvent(name, true, true)
        }
        return event
    }
}
;(<any>window).EventDispatcher = EventDispatcher
export default EventDispatcher
