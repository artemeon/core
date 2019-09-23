/**
 * define all custom keymaps as an object
 * {
 *  keys : the keys combination ,
 *  eventName : the name of event you wish to trigger
 * }
 */
const keymaps = [
    {
        keys: 'ctrl+f',
        eventName: 'agp-search-open' // opens the searchbar
    }
]

interface KeymapInterface {
    keys : string,
    eventName : string
}

export { keymaps, KeymapInterface }
