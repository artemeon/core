//   (c) 2004-2006 by MulchProductions, www.mulchprod.de
//   (c) 2007-2013 by Kajona, www.kajona.de
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



/*
 * -------------------------------------------------------------------------
 * Portaleditor-specific functions
 * -------------------------------------------------------------------------
 */


KAJONA.admin.portaleditor = {
	objPlaceholderWithElements: {},

    initPortaleditor : function() {
        CKEDITOR_BASEPATH = KAJONA_WEBPATH+"/core/module_system/admin/scripts/ckeditor/";

        KAJONA.admin.loader.loadFile([
            "/core/module_system/admin/scripts/ckeditor/ckeditor.js"

        ], function() {
            //console.debug('ckeditor js loaded');
            //span and a tags are officially not support, nevertheless working...
            CKEDITOR.dtd.$editable.span = 1;
            CKEDITOR.dtd.$editable.a = 1;
            CKEDITOR.disableAutoInline = true;
            KAJONA.admin.portaleditor.RTE.init();
        });

    },

	showActions: function (elementSysId) {
	    $('#container_'+elementSysId).attr('class', 'peContainerHover');
	    $('#menu_'+elementSysId).attr('class', 'menuHover');
	},

	hideActions: function (elementSysId) {
		$('#container_'+elementSysId).attr('class', 'peContainerOut');
		$('#menu_'+elementSysId).attr('class', 'menuOut');
	},

	switchEnabled: function (bitStatus) {
	    var strStatus = bitStatus == true ? 'true' : 'false';
		var url = window.location.href;
		var anchorPos = url.indexOf('#');
		if (anchorPos != -1) {
	    	url = url.substring(0, anchorPos);
		}

	    url = url.replace('&pe=false', '');
	    url = url.replace('&pe=true', '');
	    url = url.replace('?pe=false', '');
	    url = url.replace('?pe=true', '');

	    if(url.indexOf('?') == -1) {
	        window.location.replace(url+'?pe='+strStatus);
	    } else {
	        window.location.replace(url+'&pe='+strStatus);
	    }
	},

	openDialog: function (strUrl) {
		peDialog.setContentIFrame(strUrl);
		peDialog.init();
	},

	closeDialog: function () {
	    var bitClose = confirm(KAJONA.admin.lang["pe_dialog_close_warning"]);
	    if(bitClose) {
	    	peDialog.hide();
	    	//reset iframe
	    	peDialog.setContentRaw("");
	    }
	},

	addNewElements: function (strPlaceholder, strPlaceholderName, arrElements) {
		this.objPlaceholderWithElements[strPlaceholder] = {
			placeholderName: strPlaceholderName,
			elements: arrElements
		};
	}
};

KAJONA.admin.portaleditor.RTE = {};
KAJONA.admin.portaleditor.RTE.config = {};
KAJONA.admin.portaleditor.RTE.modifiedFields = {};

KAJONA.admin.portaleditor.RTE.savePage = function () {

    //console.group('savePage');
    $.each(KAJONA.admin.portaleditor.RTE.modifiedFields, function (key, value) {
        var keySplitted = key.split('#');

        var data = {
            systemid: keySplitted[0],
            property: keySplitted[1],
            value: value
        };

        $.post(KAJONA_WEBPATH + '/xml.php?admin=1&module=pages_content&action=updateObjectProperty', data, function () {
            //console.warn('server response');
            //console.log(this.responseText);
        });
    });
    //console.groupEnd('savePage');
    $('#savePageLink > img').attr('src', $('#savePageLink > img').attr('src').replace(".png", "Disabled.png"));
    KAJONA.admin.portaleditor.RTE.modifiedFields = {};
};


KAJONA.admin.portaleditor.RTE.init = function () {

    $('*[data-kajona-editable]').each(function () {
        //console.debug('editor init');

        var editable = $(this);
        var keySplitted = editable.attr('data-kajona-editable').split('#');
        var isPlaintext = (keySplitted[2] && keySplitted[2] == 'plain') ? true : false;
        var ckeditorConfig = KAJONA.admin.portaleditor.RTE.config;
        ckeditorConfig.customConfig = 'config_kajona_standard.js';
        ckeditorConfig.toolbar = isPlaintext ? 'pe_lite' : 'pe_full';
        ckeditorConfig.forcePasteAsPlainText = true;
        ckeditorConfig.on = {
            blur: function( event ) {
                var data = event.editor.getData();
                var attr = $(event.editor.element).attr('data-kajona-editable');

                $('#savePageLink > img').attr('src', $('#savePageLink > img').attr('src').replace("Disabled", ""));
                KAJONA.admin.portaleditor.RTE.modifiedFields[attr] = data;
                console.log('modified field', attr, data);
            }
        };

        CKEDITOR.inline(editable.get(0), ckeditorConfig);

        editable.bind('drop drag', function () {
            return false;
        });
        editable.attr("contenteditable", "true");
    });

    // warn user if there are unsaved changes when leaving the page
    $(window).on('beforeunload', function () {
        // check if there are unsaved changes
        var unsavedChanges = false;
        $.each(KAJONA.admin.portaleditor.RTE.modifiedFields, function () {
            unsavedChanges = true;
            return false;
        });

        if (unsavedChanges) {
            return KAJONA.admin.lang.pe_rte_unsavedChanges;
        }
    });
};



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
 * Loader for dynamically loading additional js and css files after the onDOMReady event
 *
 */
KAJONA.admin.loader = new KAJONA.util.Loader();


KAJONA.admin.tooltip = {
    initTooltip : function() {
        KAJONA.admin.loader.loadFile(['/core/module_system/admin/scripts/qtip2/jquery.qtip.min.js', '/core/module_system/admin/scripts/qtip2/jquery.qtip.min.css'], function() {

            $('*[rel=tooltip]').qtip({
                position: {
                    viewport: $(window)
                },
                style: {
                    classes: 'qtip-youtube qtip-shadow'
                }
            });
        });
    },

    addTooltip : function(objElement, strText) {
        KAJONA.admin.loader.loadFile(['/core/module_system/admin/scripts/qtip2/jquery.qtip.min.js', '/core/module_system/admin/scripts/qtip2/jquery.qtip.min.css'], function() {

            if(strText) {
                $(objElement).qtip({
                    position: {
                        viewport: $(window)
                    },
                    style: {
                        classes: 'qtip-youtube qtip-shadow'
                    },
                    content : {
                        text: strText
                    }
                });
            }
            else {
                $(objElement).qtip({
                    position: {
                        viewport: $(window)
                    },
                    style: {
                        classes: 'qtip-youtube qtip-shadow'
                    }
                });
            }
        });
    }

};

KAJONA.admin.tooltip.initTooltip();