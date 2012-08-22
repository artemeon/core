//   (c) 2004-2006 by MulchProductions, www.mulchprod.de
//   (c) 2007-2012 by Kajona, www.kajona.de
//       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt
//       $Id$

if (typeof KAJONA == "undefined") {
	var KAJONA = {
		util: {},
		portal: {
			lang: {}
		},
		admin: {
			lang: {}
		}
	};
}


/*
 * -------------------------------------------------------------------------
 * Global functions
 * -------------------------------------------------------------------------
 */

/**
 * Function to evaluate the script-tags in a passed string, e.g. loaded by an ajax-request
 *
 * @param {String} scripts
 * @see http://wiki.ajax-community.de/know-how:nachladen-von-javascript
 **/
KAJONA.util.evalScript = function (scripts) {
	try {
        if(scripts != '')	{
            var script = "";
			scripts = scripts.replace(/<script[^>]*>([\s\S]*?)<\/script>/gi, function() {
                 if (scripts !== null)
                         script += arguments[1] + '\n';
                return '';
            });
			if(script)
                (window.execScript) ? window.execScript(script) : window.setTimeout(script, 0);
		}
		return false;
	}
	catch(e) {
        alert(e);
	}
};

KAJONA.util.isTouchDevice = function() {
    return !!('ontouchstart' in window) ? 1 : 0;
};


/**
 * Checks if the given array contains the given string
 *
 * @param {String} strNeedle
 * @param {String[]} arrHaystack
 */
KAJONA.util.inArray = function (strNeedle, arrHaystack) {
    for (var i = 0; i < arrHaystack.length; i++) {
        if (arrHaystack[i] == strNeedle) {
            return true;
        }
    }
    return false;
};

/**
 * Used to show/hide an html element
 *
 * @param {String} strElementId
 * @param {Function} objCallbackVisible
 * @param {Function} objCallbackInvisible
 */
KAJONA.util.fold = function (strElementId, objCallbackVisible, objCallbackInvisible) {
	var element = document.getElementById(strElementId);
	if (element.style.display == 'none') 	{
		element.style.display = 'block';
		if ($.isFunction(objCallbackVisible)) {
			objCallbackVisible();
		}
    }
    else {
    	element.style.display = 'none';
		if ($.isFunction(objCallbackInvisible)) {
			objCallbackInvisible();
		}
    }
};

/**
 * Used to show/hide an html element and switch an image (e.g. a button)
 *
 * @param {String} strElementId
 * @param {String} strImageId
 * @param {String} strImageVisible
 * @param {String} strImageHidden
 */
KAJONA.util.foldImage = function (strElementId, strImageId, strImageVisible, strImageHidden) {
	var element = document.getElementById(strElementId);
	var image = document.getElementById(strImageId);
	if (element.style.display == 'none') 	{
		element.style.display = 'block';
		image.src = strImageVisible;
    }
    else {
    	element.style.display = 'none';
    	image.src = strImageHidden;
    }
};

KAJONA.util.setBrowserFocus = function (strElementId) {
	$(function() {
		try {
		    focusElement = $("#"+strElementId);
		    if (focusElement.hasClass("inputWysiwyg")) {
		    	CKEDITOR.config.startupFocus = true;
		    } else {
		        focusElement.focus();
		    }
		} catch (e) {}
	});
};

/**
 * some functions to track the mouse position and move an element
 * @deprecated will be removed with Kajona 3.4 or 3.5, use YUI Panel instead
 */
KAJONA.util.mover = (function() {
	var currentMouseXPos;
	var currentMouseYPos;
	var objToMove = null;
	var objDiffX = 0;
	var objDiffY = 0;

	function checkMousePosition(e) {
		if (document.all) {
			currentMouseXPos = event.clientX + document.body.scrollLeft;
			currentMouseYPos = event.clientY + document.body.scrollTop;
		} else {
			currentMouseXPos = e.pageX;
			currentMouseYPos = e.pageY;
		}

		if (objToMove != null) {
			objToMove.style.left = currentMouseXPos - objDiffX + "px";
			objToMove.style.top = currentMouseYPos - objDiffY + "px";
		}
	}

	function setMousePressed(obj) {
		objToMove = obj;
		objDiffX = currentMouseXPos - objToMove.offsetLeft;
		objDiffY = currentMouseYPos - objToMove.offsetTop;
	}

	function unsetMousePressed() {
		objToMove = null;
	}


	//public variables and methods
	return {
		checkMousePosition : checkMousePosition,
		setMousePressed : setMousePressed,
		unsetMousePressed : unsetMousePressed
	}
}());

/**
 * Loader for dynamically loading additional js and css files after the onDOMReady event
 * Please only use the specific instances KAJONA.portal.loader or KAJONA.admin.loader
 *
 * @param {String} strScriptBase
 * @see specific instances KAJONA.portal.loader or KAJONA.admin.loader
 *
 */
KAJONA.util.Loader = function () {

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
                        //console.log('requirement '+arrCallbacks[i].requiredModules[j]+' not given, no callback');
						bitCallback = false;
                        break;
					}
				}

				//execute callback and delete it so it won't get called again
				if (bitCallback) {
                    console.log('requirements all given, triggering callback. loaded: '+arrCallbacks[i].requiredModules);
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
            //console.log("skipped loading files, all already loaded");
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

        if(!bitPreventPathAdding)
            strPath = KAJONA_WEBPATH + strPath;

        strPath = strPath+"?"+KAJONA_BROWSER_CACHEBUSTER;

        return strPath;
    }


    function loadCss(strPath, strOriginalPath) {
        //console.log("loading css: "+strPath);

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
        //console.log("loading js: "+strPath);

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
                arrFilesLoaded.push(strOriginalPath);
                checkCallbacks();

            })
            .fail(function(jqxhr, settings, exception) {
                console.warn('loading file '+strPath+' failed: '+exception);
            });
    }

};


/*
 * -------------------------------------------------------------------------
 * Admin-specific functions
 * -------------------------------------------------------------------------
 */

/**
 * Loader for dynamically loading additional js and css files after the onDOMReady event
 */
KAJONA.admin.loader = new KAJONA.util.Loader();


/**
 * Tooltips
 *
 * originally based on Bubble Tooltips by Alessandro Fulciniti (http://pro.html.it - http://web-graphics.com)
 */
KAJONA.admin.tooltip = (function() {
	var container;
	var lastMouseX = 0;
	var lastMouseY = 0;

	function locate(e) {
		var posx = 0, posy = 0, c;
		if (e == null) {
			e = window.event;
		}
		if (e.pageX || e.pageY) {
			posx = e.pageX;
			posy = e.pageY;
		} else if (e.clientX || e.clientY) {
			if (document.documentElement.scrollTop) {
				posx = e.clientX + document.documentElement.scrollLeft;
				posy = e.clientY + document.documentElement.scrollTop;
			} else {
				posx = e.clientX + document.body.scrollLeft;
				posy = e.clientY + document.body.scrollTop;
			}
		}

		//save current x and y pos (needed to show tooltip at right position if it's added by onclick)
		if (posx == 0 && posy == 0) {
			posx = lastMouseX;
			posy = lastMouseY;
		} else {
			lastMouseX = posx;
			lastMouseY = posy;
		}

		c = container;
		var left = (posx - c.offsetWidth);
		if (left - c.offsetWidth < 0) {
			left += c.offsetWidth;
		}
		c.style.top = (posy + 10) + "px";
		c.style.left = left + "px";
	}

	function add(objElement, strHtmlContent, bitOpacity) {
		var tooltip;

		if (strHtmlContent == null || strHtmlContent.length == 0) {
			try {
				strHtmlContent = objElement.getAttribute("title");
			} catch (e) {}
		}
		if (strHtmlContent == null || strHtmlContent.length == 0) {
			return;
		}

		//try to remove title
		try {
			objElement.removeAttribute("title");
		} catch (e) {}

		tooltip = document.createElement("span");
		tooltip.className = "kajonaAdminTooltip";
		tooltip.style.display = "block";
		tooltip.innerHTML = strHtmlContent;

		if (bitOpacity != false) {
			tooltip.style.filter = "alpha(opacity:85)";
			tooltip.style.KHTMLOpacity = "0.85";
			tooltip.style.MozOpacity = "0.85";
			tooltip.style.opacity = "0.85";
		}

		//create tooltip container and save reference
		if (container == null) {
			var h = document.createElement("span");
			h.id = "kajonaTooltipContainer";
			h.setAttribute("id", "kajonaTooltipContainer");
			h.style.position = "absolute";
			h.style.zIndex = "2000";
			document.getElementsByTagName("body")[0].appendChild(h);
			container = h;
		}

		objElement.tooltip = tooltip;
		objElement.onmouseover = show;
		objElement.onmouseout = hide;
		objElement.onmousemove = locate;
		objElement.onmouseover(objElement);
	}

	function show(objEvent) {
		hide();
		container.appendChild(this.tooltip);
		locate(objEvent);
	}

	function hide() {
		try {
			var c = container;
			if (c.childNodes.length > 0) {
				c.removeChild(c.firstChild);
			}
		} catch (e) {}
	}

    function init() {
    }

	//public variables and methods
	return {
		add : add,
		show : show,
		hide : hide,
        init : init
	}
}());

/**
 * Folderview functions
 */
KAJONA.admin.folderview = {
	/**
	 * holds a reference to the ModalDialog
	 */
	dialog: undefined,

	/**
	 * holds CKEditors CKEditorFuncNum parameter to read it again in KAJONA.admin.folderview.fillFormFields()
	 * so we don't have to pass through the param with all requests
	 */
	selectCallbackCKEditorFuncNum: 0,

	/**
	 * To be called when the user selects an page/folder/file out of a folderview dialog/popup
	 * Detects if the folderview is embedded in a dialog or popup to find the right context
     *
     * @param {Array} arrTargetsValues
     * @param {function} objCallback
	 */
	selectCallback: function (arrTargetsValues, objCallback) {
		if (window.opener) {
			window.opener.KAJONA.admin.folderview.fillFormFields(arrTargetsValues);
		} else if (parent) {
			parent.KAJONA.admin.folderview.fillFormFields(arrTargetsValues);
		}

        if ($.isFunction(objCallback)) {
			objCallback();
		}

        this.close();

	},

	/**
	 * fills the form fields with the selected values
	 */
	fillFormFields: function (arrTargetsValues) {
		for (var i in arrTargetsValues) {
	    	if (arrTargetsValues[i][0] == "ckeditor") {
	    		CKEDITOR.tools.callFunction(this.selectCallbackCKEditorFuncNum, arrTargetsValues[i][1]);
	    	} else {
	    		var formField = $("#"+arrTargetsValues[i][0]).get(0);

                if (formField != null) {
                	formField.value = arrTargetsValues[i][1];

                	//fire the onchange event on the form field
                    if (document.createEvent) { //Firefox
                        var evt = document.createEvent("Events");
                        evt.initEvent('change', true, true, window, 0, 0, 0, 0, 0, false, false, false, false, 0, null);
                        formField.dispatchEvent(evt);
                    } else if (document.createEventObject) { //IE
                        var evt = document.createEventObject();
                        formField.fireEvent('onchange', evt);
                    }

                }
	    	}
		}
	},

	/**
	 * fills the form fields with the selected values
	 */
	close: function () {
		if (window.opener) {
			window.close();
		} else if (parent) {
			var context = parent.KAJONA.admin.folderview;
			context.dialog.hide();
			context.dialog.setContentRaw("");
		}
	}
};



/**
 * switches the edited language in admin
 */
KAJONA.admin.switchLanguage = function(strLanguageToLoad) {
	var url = window.location.href;
	url = url.replace(/(\?|&)language=([a-z]+)/, "");
	if (url.indexOf('?') == -1) {
		window.location.replace(url + '?language=' + strLanguageToLoad);
	} else {
		window.location.replace(url + '&language=' + strLanguageToLoad);
	}
};

/**
 * little helper function for the system right matrix
 */
KAJONA.admin.checkRightMatrix = function() {
	// mode 1: inheritance
	if (document.getElementById('inherit').checked) {
		// loop over all checkboxes to disable them
		for (var intI = 0; intI < document.forms['rightsForm'].elements.length; intI++) {
			var objCurElement = document.forms['rightsForm'].elements[intI];
			if (objCurElement.type == 'checkbox') {
				if (objCurElement.id != 'inherit') {
					objCurElement.disabled = true;
					objCurElement.checked = false;
					var strCurId = "inherit," + objCurElement.id;
					if (document.getElementById(strCurId) != null) {
						if (document.getElementById(strCurId).value == '1') {
							objCurElement.checked = true;
						}
					}
				}
			}
		}
	} else {
		// mode 2: no inheritance, make all checkboxes editable
		for (intI = 0; intI < document.forms['rightsForm'].elements.length; intI++) {
			var objCurElement = document.forms['rightsForm'].elements[intI];
			if (objCurElement.type == 'checkbox') {
				if (objCurElement.id != 'inherit') {
					objCurElement.disabled = false;
				}
			}
		}
	}
};

/**
 * General way to display a status message.
 * Therefore, the html-page should provide the following elements as noted as instance-vars:
 * - div,   id: jsStatusBox    				the box to be animated
 * 		 class: jsStatusBoxMessage			class in case of an informal message
 * 		 class: jsStatusBoxError		    class in case of an error message
 * - div,   id: jsStatusBoxContent			the box to place the message-content into
 *
 * Pass a xml-response from a Kajona server to displayXMLMessage() to start the logic
 * or use messageOK() / messageError() passing a regular string
 */
KAJONA.admin.statusDisplay = {
	idOfMessageBox : "jsStatusBox",
	idOfContentBox : "jsStatusBoxContent",
	classOfMessageBox : "jsStatusBoxMessage",
	classOfErrorBox : "jsStatusBoxError",
	timeToFadeOutMessage : 4000,
	timeToFadeOutError : 10000,
	timeToFadeOut : null,
	animObject : null,

	/**
	 * General entrance point. Use this method to pass an xml-response from the kajona server.
	 * Tries to find a message- or an error-tag an invokes the corresponding methods
	 *
	 * @param {String} message
	 */
	displayXMLMessage : function(message) {
		//decide, whether to show an error or a message, message only in debug mode
		if(message.indexOf("<message>") != -1 && KAJONA_DEBUG > 0 && message.indexOf("<error>") == -1) {
			var intStart = message.indexOf("<message>")+9;
			var responseText = message.substr(intStart, message.indexOf("</message>")-intStart);
			this.messageOK(responseText);
		}

		if(message.indexOf("<error>") != -1) {
			var intStart = message.indexOf("<error>")+7;
			var responseText = message.substr(intStart, message.indexOf("</error>")-intStart);
			this.messageError(responseText);
		}
	},

	/**
	 * Creates a informal message box contaning the passed content
	 *
	 * @param {String} strMessage
	 */
    messageOK : function(strMessage) {
		$("#"+this.idOfMessageBox).removeClass(this.classOfMessageBox).removeClass(this.classOfErrorBox).addClass(this.classOfMessageBox);
		this.timeToFadeOut = this.timeToFadeOutMessage;
		this.startFadeIn(strMessage);
    },

	/**
	 * Creates an error message box containg the passed content
	 *
	 * @param {String} strMessage
	 */
    messageError : function(strMessage) {
        $("#"+this.idOfMessageBox).removeClass(this.classOfMessageBox).removeClass(this.classOfErrorBox).addClass(this.classOfErrorBox);
		this.timeToFadeOut = this.timeToFadeOutError;
		this.startFadeIn(strMessage);
    },

	startFadeIn : function(strMessage) {
		//currently animated?
		if(this.animObject != null && this.animObject.isAnimated()) {
			this.animObject.stop(true);
			this.animObject.onComplete.unsubscribeAll();
		}
		var statusBox = $("#"+this.idOfMessageBox);
		var contentBox = $("#"+this.idOfContentBox);
		contentBox.html(strMessage);
		statusBox.css("display", "").css("opacity", 0.0);

		//place the element at the top of the page
		var screenWidth = $(window).width()
		var divWidth = statusBox.width();
		var newX = screenWidth/2 - divWidth/2;
		var newY = $(window).scrollTop() -2;
        statusBox.css('top', newY);
        statusBox.css('left', newX);

		//start fade-in handler

        KAJONA.admin.statusDisplay.fadeIn();

	},

	fadeIn : function () {
        $("#"+this.idOfMessageBox).animate({opacity: 0.8}, 1000, function() {  window.setTimeout("KAJONA.admin.statusDisplay.startFadeOut()", this.timeToFadeOut); });
	},

	startFadeOut : function() {

        $("#"+this.idOfMessageBox).animate(
            { top: -200 },
            1000,
            function() {
                $("#"+this.idOfMessageBox).css("display", "none");
            }
        );

	}
};


/**
 * Functions to execute system tasks
 */
KAJONA.admin.systemtask = {
    executeTask : function(strTaskname, strAdditionalParam, bitNoContentReset) {
        if(bitNoContentReset == null || bitNoContentReset == undefined) {

            if(document.getElementById('taskParamForm') != null) {
                document.getElementById('taskParamForm').style.display = "none";
            }

            jsDialog_0.setTitle(KAJONA_SYSTEMTASK_TITLE);
            jsDialog_0.setContentRaw(kajonaSystemtaskDialogContent);
            document.getElementById(jsDialog_0.containerId).style.width = "550px";
            document.getElementById('systemtaskCancelButton').onclick = this.cancelExecution;
            jsDialog_0.init();
        }

        KAJONA.admin.ajax.genericAjaxCall("system", "executeSystemTask", "&task="+strTaskname+strAdditionalParam, function(data, status, jqXHR) {
            if(status == 'success') {
                var strResponseText = data;

                //parse the response and check if it's valid
                if(strResponseText.indexOf("<error>") != -1) {
                    KAJONA.admin.statusDisplay.displayXMLMessage(strResponseText);
                }
                else if(strResponseText.indexOf("<statusinfo>") == -1) {
                	KAJONA.admin.statusDisplay.messageError("<b>Request failed!</b><br />"+strResponseText);
                }
                else {
                    var intStart = strResponseText.indexOf("<statusinfo>")+12;
                    var strStatusInfo = strResponseText.substr(intStart, strResponseText.indexOf("</statusinfo>")-intStart);

                    //parse text to decide if a reload is necessary
                    var strReload = "";
                    if(strResponseText.indexOf("<reloadurl>") != -1) {
                        intStart = strResponseText.indexOf("<reloadurl>")+11;
                        strReload = strResponseText.substr(intStart, strResponseText.indexOf("</reloadurl>")-intStart);
                    }

                    //show status info
                    document.getElementById('systemtaskStatusDiv').innerHTML = strStatusInfo;

                    if(strReload == "") {
                    	jsDialog_0.setTitle(KAJONA_SYSTEMTASK_TITLE_DONE);
                    	document.getElementById('systemtaskLoadingDiv').style.display = "none";
                    	document.getElementById('systemtaskCancelButton').value = KAJONA_SYSTEMTASK_CLOSE;
                    }
                    else {
                    	KAJONA.admin.systemtask.executeTask(strTaskname, strReload, true);
                    }
                }
            }

            else {
                jsDialog_0.hide();
                KAJONA.admin.statusDisplay.messageError("<b>Request failed!</b><br />"+data);
            }
        });
    },

    cancelExecution : function() {
        jsDialog_0.hide();
    },

    setName : function(strName) {
    	document.getElementById('systemtaskNameDiv').innerHTML = strName;
    }
};

/**
 * AJAX functions for connecting to the server
 */
KAJONA.admin.ajax = {

    getDataObjectFromString: function(strData, bitFirstIsSystemid) {
        //strip other params, backwards compatibility
        var arrElements = strData.split("&");
        var data = { };

        if(bitFirstIsSystemid)
            data["systemid"] = arrElements[0];

        //first one is the systemid
        if(arrElements.length > 1) {
            $.each(arrElements, function(index, strValue) {
                if(!bitFirstIsSystemid || index > 0) {
                    var arrSingleParams = strValue.split("=");
                    data[arrSingleParams[0]] = arrSingleParams[1];
                }
            });
        }
        return data;
    },

    regularCallback: function(data, status, jqXHR) {
		if(status == 'success') {
			KAJONA.admin.statusDisplay.displayXMLMessage(data)
		}
		else {
			KAJONA.admin.statusDisplay.messageError("<b>Request failed!</b>")
		}
	},


	genericAjaxCall : function(module, action, systemid, objCallback) {
		var postTarget = KAJONA_WEBPATH + '/xml.php?admin=1&module='+module+'&action='+action;
        var data = this.getDataObjectFromString(systemid, true);

        $.ajax({
            type: 'POST',
            url: postTarget,
            data: data,
            success: objCallback,
            dataType: 'text'
        });

	},

    setAbsolutePosition : function(systemIdToMove, intNewPos, strIdOfList, objCallback, strTargetModule) {
        if(strTargetModule == null || strTargetModule == "")
            strTargetModule = "system";

        if(typeof objCallback == 'undefined' || objCallback == null)
            objCallback = KAJONA.admin.ajax.regularCallback;


        KAJONA.admin.ajax.genericAjaxCall(strTargetModule, "setAbsolutePosition", systemIdToMove + "&listPos=" + intNewPos, objCallback);
	},

	setSystemStatus : function(strSystemIdToSet, bitReload) {
        var objCallback = function(data, status, jqXHR) {
            if(status == 'success') {
				KAJONA.admin.statusDisplay.displayXMLMessage(data);

                if(bitReload !== null && bitReload === true)
                    location.reload();

				if (data.indexOf('<error>') == -1 && data.indexOf('<html>') == -1) {
					var image = document.getElementById('statusImage_' + strSystemIdToSet);
					var link = document.getElementById('statusLink_' + strSystemIdToSet);

					if (image.src.indexOf('icon_enabled.gif') != -1) {
						image.src = strInActiveImageSrc;
						image.setAttribute('alt', strInActiveText);
						link.setAttribute('title', strInActiveText);
					} else {
						image.src = strActiveImageSrc;
						image.setAttribute('alt', strActiveText);
						link.setAttribute('title', strActiveText);
					}

					KAJONA.admin.tooltip.add(link);
				}
        	}
            else{
        		KAJONA.admin.statusDisplay.messageError(data);
        	}
        };

        KAJONA.admin.ajax.genericAjaxCall("system", "setStatus", strSystemIdToSet, objCallback);
	}

};


/**
 * Form management
 */
KAJONA.admin.forms = {};
KAJONA.admin.forms.renderMandatoryFields = function(arrFields) {

    for(var i=0; i<arrFields.length; i++) {
        var arrElement = arrFields[i];
        if(arrElement.length == 2) {
            if(arrElement[1] == 'date') {
               $("#"+arrElement[0]+"_day").addClass("mandatoryFormElement");
               $("#"+arrElement[0]+"_month").addClass("mandatoryFormElement");
               $("#"+arrElement[0]+"_year").addClass("mandatoryFormElement");
            }
            else
                $("#"+arrElement[0]).addClass("mandatoryFormElement");
        }
    }
};

/**
 * Dashboard calendar functions
 */
KAJONA.admin.dashboardCalendar = {};
KAJONA.admin.dashboardCalendar.eventMouseOver = function(strSourceId) {
    if(strSourceId == "")
        return;

    var sourceArray = eval("kj_cal_"+strSourceId);
    if(typeof sourceArray != undefined) {
        for(var i=0; i< sourceArray.length; i++) {
            $("#event_"+sourceArray[i]).addClass("mouseOver");
        }
    }
};

KAJONA.admin.dashboardCalendar.eventMouseOut = function(strSourceId) {
    if(strSourceId == "")
        return;

    var sourceArray = eval("kj_cal_"+strSourceId);
    if(typeof sourceArray != undefined) {
        for(var i=0; i< sourceArray.length; i++) {
            $("#event_"+sourceArray[i]).removeClass("mouseOver");
        }
    }
};

/**
 * Context menus
 */
KAJONA.admin.contextMenu = {
    menus: {},

    addElements: function (strIdentifier, arrElements) {
		this.menus[strIdentifier] = arrElements;
	},

	showElementMenu: function (strIdentifier, objAttach) {
        KAJONA.admin.tooltip.hide();
        if(typeof $(objAttach).attr('id') != 'string')
            $(objAttach).attr('id', new Date().getTime()+strIdentifier);

        KAJONA.admin.loader.loadFile(["/core/module_system/admin/scripts/jquery/jquery.contextMenu.js", "/core/module_system/admin/scripts/jquery/jquery.contextMenu.css"], function() {
            var items = {};
            var bitElementsFound = false;
            $.each(KAJONA.admin.contextMenu.menus[strIdentifier], function(index, element) {
                //element.name = element.elementName;
                items[index] = {
                    name : element.elementName
                };
                bitElementsFound = true;
            });
            if(bitElementsFound) {
                $.contextMenu({
                    selector: '#'+$(objAttach).attr('id'),
                    build: function($trigger, e) {
                        return {
                            callback: function(key, options) {
                                var objElement = KAJONA.admin.contextMenu.menus[strIdentifier][key];
                                eval(objElement.elementAction);
                            },
                            items: items
                        }
                    }
                });
                $('#'+$(objAttach).attr('id')).contextMenu();
            }
        });
	}
};


KAJONA.admin.openPrintView = function(strUrlToLoad) {
    var intWidth = $(window).width() * 0.8;
    var intHeight = $(window).height() * 0.9;

    if(strUrlToLoad == null)
        strUrlToLoad = location.href;

    strUrlToLoad = strUrlToLoad.replace(/#/g, '')+"&printView=1";

    if(strUrlToLoad.indexOf('html&')) {
        strUrlToLoad = strUrlToLoad.replace(/html&/g, 'html?');
    }

    KAJONA.admin.folderview.dialog.setContentIFrame(strUrlToLoad);

    KAJONA.admin.folderview.dialog.init(intWidth+"px", intHeight+"px"); return false;
};

/**
 * Subsystem for all messaging related tasks. Queries the backend for the number of unread messages, ...
 * @type {Object}
 */
KAJONA.admin.messaging = {

    /**
     * Gets the number of unread messages for the current user.
     * Expects a callback-function whereas the number is passed as a param.
     *
     * @param objCallback
     */
    getUnreadCount : function(objCallback) {

        KAJONA.admin.ajax.genericAjaxCall("messaging", "getUnreadMessagesCount", "", function(data, status, jqXHR) {
            if(status == 'success') {
                var objResponse = $($.parseXML(data));
                KAJONA.admin.messaging.intCount = objResponse.find("messageCount").text();
                objCallback(objResponse.find("messageCount").text());

            }
        });
    },

    /**
     * Loads the list of recent messages for the current user.
     * The callback is passed the json-object as a param.
     * @param objCallback
     */
    getRecentMessages : function(objCallback) {
        KAJONA.admin.ajax.genericAjaxCall("messaging", "getRecentMessages", "", function(data, status, jqXHR) {
            if(status == 'success') {
                var objResponse = $.parseJSON(data);
                objCallback(objResponse);
            }
        });
    }
};