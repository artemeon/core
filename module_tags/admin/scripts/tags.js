//   (c) 2004-2006 by MulchProductions, www.mulchprod.de
//   (c) 2007-2012 by Kajona, www.kajona.de
//       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt
//       $Id$

if (typeof KAJONA == "undefined") {
    alert('load kajona.js before!');
}



/**
 * Tags-handling
 */
KAJONA.admin.tags = {};

KAJONA.admin.tags.saveTag = function(strTagname, strSystemid, strAttribute) {
    KAJONA.admin.ajax.genericAjaxCall("tags", "saveTag", strSystemid+"&tagname="+strTagname+"&attribute="+strAttribute, function(data, status, jqXHR) {
        if(status == 'success') {
            KAJONA.admin.tags.reloadTagList(strSystemid, strAttribute);
            document.getElementById('tagname').value='';
        }
        else {
            KAJONA.admin.statusDisplay.messageError("<b>Request failed!</b><br />" + data);
        }
    });
};

KAJONA.admin.tags.reloadTagList = function(strSystemid, strAttribute) {

    $("#tagsWrapper_"+strSystemid).addClass("loadingContainer");

    KAJONA.admin.ajax.genericAjaxCall("tags", "tagList", strSystemid+"&attribute="+strAttribute, function(data, status, jqXHR) {
        if(status == 'success') {
            var intStart = data.indexOf("<tags>")+6;
            var strContent = data.substr(intStart, data.indexOf("</tags>")-intStart);
            $("#tagsWrapper_"+strSystemid).removeClass("loadingContainer");
            document.getElementById("tagsWrapper_"+strSystemid).innerHTML = strContent;
        }
        else {
            KAJONA.admin.statusDisplay.messageError("<b>Request failed!</b><br />" + data);
            $("#tagsWrapper_"+strSystemid).removeClass("loadingContainer");
        }
    });
};

KAJONA.admin.tags.removeTag = function(strTagId, strTargetSystemid, strAttribute) {
    KAJONA.admin.ajax.genericAjaxCall("tags", "removeTag", strTagId+"&targetid="+strTargetSystemid+"&attribute="+strAttribute, function(data, status, jqXHR) {
        if(status == 'success') {
            KAJONA.admin.tags.reloadTagList(strTargetSystemid, strAttribute);
            document.getElementById('tagname').value='';
        }
        else {
            KAJONA.admin.statusDisplay.messageError("<b>Request failed!</b><br />" + data);
        }
    });
};
