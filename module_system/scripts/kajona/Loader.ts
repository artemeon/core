import $ from 'jquery'
import Util from './Util'

interface Callback {
    requiredModules: Array<string>
    callback: Function
}

class Loader {
    private static arrCallbacks: Array<Callback> = []
    private static arrFilesLoaded: Array<string> = []
    private static arrFilesInProgress: Array<string> = []

    public static checkCallbacks () {
        // check if we're ready to call some registered callbacks
        for (var i = 0; i < this.arrCallbacks.length; i++) {
            if (this.arrCallbacks[i]) {
                var bitCallback = true
                for (
                    var j = 0;
                    j < this.arrCallbacks[i].requiredModules.length;
                    j++
                ) {
                    if (
                        $.inArray(
                            this.arrCallbacks[i].requiredModules[j],
                            this.arrFilesLoaded
                        ) === -1
                    ) {
                        bitCallback = false
                        break
                    }
                }

                // execute callback and delete it so it won't get called again
                if (bitCallback) {
                    this.arrCallbacks[i].callback()
                    delete this.arrCallbacks[i]
                }
            }
        }
    }

    public static loadFile (
        arrInputFiles: Array<string>,
        objCallback?: Function,
        bitPreventPathAdding?: boolean
    ) {
        var arrFilesToLoad: Array<string> = []

        if (!$.isArray(arrInputFiles)) arrInputFiles = [arrInputFiles]

        // add suffixes
        $.each(arrInputFiles, function (index, strOneFile) {
            if ($.inArray(strOneFile, Loader.arrFilesLoaded) === -1) { arrFilesToLoad.push(strOneFile) }
        })

        if (arrFilesToLoad.length === 0) {
            // all files already loaded, call callback
            if ($.isFunction(objCallback)) objCallback()
        } else {
            // start loader-processing
            var bitCallbackAdded = false
            $.each(arrFilesToLoad, function (index, strOneFileToLoad) {
                // check what loader to take - js or css
                var fileType =
                    strOneFileToLoad.substr(strOneFileToLoad.length - 2, 2) ===
                    'js'
                        ? 'js'
                        : 'css'

                if (!bitCallbackAdded && $.isFunction(objCallback)) {
                    Loader.arrCallbacks.push({
                        callback: function () {
                            setTimeout(objCallback, 100)
                        },
                        requiredModules: arrFilesToLoad
                    })
                    bitCallbackAdded = true
                }

                if (
                    $.inArray(strOneFileToLoad, Loader.arrFilesInProgress) === -1
                ) {
                    Loader.arrFilesInProgress.push(strOneFileToLoad)

                    // start loading process
                    if (fileType === 'css') {
                        Loader.loadCss(
                            Loader.createFinalLoadPath(
                                strOneFileToLoad,
                                bitPreventPathAdding
                            ),
                            strOneFileToLoad
                        )
                    }

                    if (fileType === 'js') {
                        Loader.loadJs(
                            Loader.createFinalLoadPath(
                                strOneFileToLoad,
                                bitPreventPathAdding
                            ),
                            strOneFileToLoad
                        )
                    }
                }
            })
        }
    }

    public static createFinalLoadPath (
        strPath: string,
        bitPreventPathAdding: boolean
    ) {
        // see if the path has to be changed according to a phar-extracted content
        if (KAJONA_PHARMAP && !bitPreventPathAdding) {
            var arrMatches = strPath.match(
                /(core(.*))\/((module_|element_)([a-zA-Z0-9_])*)/i
            )
            if (
                strPath.indexOf('files/extract') === -1 &&
                arrMatches &&
                Util.inArray(arrMatches[3], KAJONA_PHARMAP)
            ) {
                strPath = '/files/extract' + strPath
            }
        }

        if (!bitPreventPathAdding) strPath = KAJONA_WEBPATH + strPath

        strPath = strPath + '?' + KAJONA_BROWSER_CACHEBUSTER

        return strPath
    }

    public static loadCss (strPath: string, strOriginalPath: string) {
        $(
            '<link rel="stylesheet" type="text/css" href="' + strPath + '" />'
        ).appendTo('head')

        this.arrFilesLoaded.push(strOriginalPath)
        this.checkCallbacks()
    }

    /**
     * @deprecated
     */
    public static loadJs (strPath: string, strOriginalPath: string) {
        console.info(
            'Loading JS through loader.loadJs() is deprecated use require instead (' +
                strOriginalPath +
                ')'
        )

        //        console. debug('loading '+strOriginalPath);

        // enable caching, cache flushing is done by the cachebuster
        var options = {
            dataType: 'script',
            cache: true,
            url: strPath
        }

        // Use $.ajax() since it is more flexible than $.getScript
        // Return the jqXHR object so we can chain callbacks
        $.ajax(options)
            .done(function (script, textStatus) {
                //                console. debug('loaded '+strOriginalPath);
                Loader.arrFilesLoaded.push(strOriginalPath)
                Loader.checkCallbacks()
            })
            .fail(function (jqxhr, settings, exception) {
                //                console. error('loading file '+strPath+' failed: '+exception);
            })
    }
}
;(<any>window).Loader = Loader
export default Loader
