import hotkeys from 'hotkeys-js'
import { keymaps, KeymapInterface } from './Keymaps'
/**
 * Class to register all the user keymaps
 */
class KeymapsController {
    public static init () : void {
        keymaps.forEach((keymap: KeymapInterface) => {
            this.registerEventListener(keymap.keys, keymap.eventName)
        })
    }
    public static registerEventListener (keys : string, eventName : string) : void {
        hotkeys(keys, (e) => {
            e.preventDefault()
            this.triggerEvent(eventName)
        })
    }
    public static triggerEvent (eventName : string) : void {
        const event = new Event(eventName)
        document.body.dispatchEvent(event)
    }
}

export default KeymapsController
