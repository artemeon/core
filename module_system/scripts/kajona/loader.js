/**
 * (c) 2013-2017 by Kajona, www.kajona.de
 * Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt
 */

/**
 * Loader to load css files. For js use require
 *
 * @module loader
 */
define("loader", ["jquery", "util"], function ($, util) {

    /** @exports loader */
    var loader = function () {

        var arrCallbacks = [];
        var arrFilesLoaded = [];
        var arrFilesInProgress = [];

        function checkCallbacks() {
            //check if we're ready to call some registered callbacks
            for (var i = 0; i < arrCallbacks.length; i++) {
                if (arrCallbacks[i]) {
                    var bitCallback = true;
                    for (var j = 0; j < arrCallbacks[i].requiredModules.length; j++) {
                        if ($.inArray(arrCallbacks[i].requiredModules[j], arrFilesLoaded) == -1) {
                            bitCallback = false;
                            break;
                        }
                    }

                    //execute callback and delete it so it won't get called again
                    if (bitCallback) {
                        arrCallbacks[i].callback();
                        delete arrCallbacks[i];
                    }
                }
            }
        }


        this.loadFile = function(arrInputFiles, objCallback, bitPreventPathAdding) {
            var arrFilesToLoad = [];

            if(!$.isArray(arrInputFiles))
                arrInputFiles = [ arrInputFiles ];

            //add suffixes
            $.each(arrInputFiles, function(index, strOneFile) {
                if($.inArray(strOneFile, arrFilesLoaded) == -1 )
                    arrFilesToLoad.push(strOneFile);
            });

            if(arrFilesToLoad.length == 0) {
                //all files already loaded, call callback
                if($.isFunction(objCallback))
                    objCallback();
            }
            else {
                //start loader-processing
                var bitCallbackAdded = false;
                $.each(arrFilesToLoad, function(index, strOneFileToLoad) {
                    //check what loader to take - js or css
                    var fileType = strOneFileToLoad.substr(strOneFileToLoad.length-2, 2) == 'js' ? 'js' : 'css';

                    if(!bitCallbackAdded && $.isFunction(objCallback)) {
                        arrCallbacks.push({
                            'callback' : function() { setTimeout( objCallback, 100); },
                            'requiredModules' : arrFilesToLoad
                        });
                        bitCallbackAdded = true;
                    }

                    if( $.inArray(strOneFileToLoad, arrFilesInProgress) == -1 ) {
                        arrFilesInProgress.push(strOneFileToLoad);

                        //start loading process
                        if(fileType == 'css') {
                            loadCss(createFinalLoadPath(strOneFileToLoad, bitPreventPathAdding), strOneFileToLoad);
                        }

                        if(fileType == 'js') {
                            loadJs(createFinalLoadPath(strOneFileToLoad, bitPreventPathAdding), strOneFileToLoad);
                        }
                    }
                });
            }
        };

        function createFinalLoadPath(strPath, bitPreventPathAdding) {

            //see if the path has to be changed according to a phar-extracted content
            if(KAJONA_PHARMAP && !bitPreventPathAdding) {
                var arrMatches = strPath.match(/(core(.*))\/((module_|element_)([a-zA-Z0-9_])*)/i);
                if (strPath.indexOf("files/extract") === -1 && arrMatches && util.inArray(arrMatches[3], KAJONA_PHARMAP)) {
                    strPath = '/files/extract'+strPath
                }
            }

            if(!bitPreventPathAdding)
                strPath = KAJONA_WEBPATH + strPath;

            strPath = strPath+"?"+KAJONA_BROWSER_CACHEBUSTER;

            return strPath;
        }


        function loadCss(strPath, strOriginalPath) {

            if (document.createStyleSheet) {
                document.createStyleSheet(strPath);
            }
            else {
                $('<link rel="stylesheet" type="text/css" href="' + strPath + '" />').appendTo('head');
            }

            arrFilesLoaded.push(strOriginalPath);
            checkCallbacks();
        }

        function loadJs(strPath, strOriginalPath) {

            console.info("Loading JS through loader.loadJs() is deprecated use require instead (" + strOriginalPath + ")");

//        console. debug('loading '+strOriginalPath);

            //enable caching, cache flushing is done by the cachebuster
            var options =  {
                dataType: "script",
                cache: true,
                url: strPath
            };

            // Use $.ajax() since it is more flexible than $.getScript
            // Return the jqXHR object so we can chain callbacks
            $.ajax(options)
                .done(function(script, textStatus) {
//                console. debug('loaded '+strOriginalPath);
                    arrFilesLoaded.push(strOriginalPath);
                    checkCallbacks();

                })
                .fail(function(jqxhr, settings, exception) {
//                console. error('loading file '+strPath+' failed: '+exception);
                });
        }

    };

    return new loader();

});

