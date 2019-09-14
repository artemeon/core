/// <reference path="catcomplete.d.ts" />
/// <reference path="jstree.ts" />
/// <reference path="tageditor.d.ts" />

declare module 'jquery' {
    export = jQuery;
}

declare module 'chartjs' {
    export = Chart;
}

interface Admin {
    folderview: any;
    lang: any;
    forms: any;
}

interface Kajona {
    util: any;
    portal: any;
    admin: any;
}

declare var KAJONA_WEBPATH: string
declare var KAJONA_DEBUG: number
declare var KAJONA_LANGUAGE: string
declare var KAJONA_BROWSER_CACHEBUSTER: number
declare var KAJONA_PHARMAP: Array<string>
declare var KAJONA_ACCESS_TOKEN: string

declare var routie: any

/** @deprecated */
declare var KAJONA: Kajona

/** @deprecated */
// eslint-disable-next-line camelcase
declare var jsDialog_0: any
/** @deprecated */
// eslint-disable-next-line camelcase
declare var jsDialog_1: any
/** @deprecated */
// eslint-disable-next-line camelcase
declare var jsDialog_2: any
/** @deprecated */
// eslint-disable-next-line camelcase
declare var jsDialog_3: any
