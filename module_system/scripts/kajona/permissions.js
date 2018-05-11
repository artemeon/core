/**
 * (c) 2013-2017 by Kajona, www.kajona.de
 * Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt
 */

/**
 * Little helper function for the system permissions matrix
 *
 * @module permissions
 */
define("permissions", ["jquery", "forms"], function($, forms){

    /** @exports permissions */
    var perms = {
        checkRightMatrix : function () {
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
        },

        toggleMode : null,
        toggleEmtpyRows : function (strVisibleName, strHiddenName, parentSelector) {

            var $rowToggleLink = $('#rowToggleLink');
            perms.toggleMode = $rowToggleLink.hasClass("rowsVisible")  ? "hide" : "show";

            $(parentSelector).each(function() {

                if($(this).find("input").length > 0 && $(this).find("input:checked").length == 0 && $(this).find("th").length == 0) {

                    if(perms.toggleMode == "show") {
                        $(this).removeClass("hidden");
                    }
                    else {
                        $(this).addClass("hidden");
                    }
                }
                else if(perms.toggleMode == "show") {
                    $(this).removeClass("hidden");
                }
            });


            if($rowToggleLink.hasClass("rowsVisible")) {
                $rowToggleLink.html(strVisibleName);
                $rowToggleLink.removeClass("rowsVisible");
            }
            else {
                $rowToggleLink.html(strHiddenName);
                $rowToggleLink.addClass("rowsVisible")
            }
        },

        submitForm : function(objForm) {
            var objResponse = {
                bitInherited : $("#inherit").is(":checked"),
                arrConfigs : []
            };

            $('#rightsForm table tr input:checked').each(function () {
                if ($(this).find("input:checked").length == 0) {
                    objResponse.arrConfigs.push($(this).attr('id'));
                }
            });

            forms.animateSubmitStart(objForm);

            $.ajax({
                url: KAJONA_WEBPATH + '/xml.php?admin=1&module=right&action=saveRights&systemid=' + $('#systemid').val(),
                type: 'POST',
                data: {json: JSON.stringify(objResponse)},
                dataType: 'json'
            }).done(function (data) {
                if (!data.error) {
                    require('statusDisplay').messageSuccess(data.message);
                } else {
                    require('statusDisplay').messageError(data.message);
                }
            }).always(function () {
                forms.animateSubmitStop(objForm);
            });


            return false;
        },

        /**
         * Filters the rows of the permission matrix based on the value of the input element
         * @param evt
         * @returns {boolean}
         */
        filterMatrix: function (evt) {

            // If it's the propertychange event, make sure it's the value that changed.
            if (window.event && event.type == "propertychange" && event.propertyName != "value")
                return false;


            var strFilter = $('#filter').val().toLowerCase();
            if (strFilter.length < 3 && strFilter.length > 0)
                return false;

            // Clear any previously set timer before setting a fresh one, default delay are 500ms
            window.clearTimeout($(this).data("timeout"));
            $(this).data("timeout", setTimeout(function () {
                // Do your thing here
                var strFilter = $('#filter').val().toLowerCase();


                $('#rightsForm table tr').each(function () {
                    var $tr = $(this);

                    if (strFilter.length > 0 && $tr.find("td:first-child").text().toLowerCase().indexOf(strFilter) === -1) {
                        $tr.addClass("hidden")
                    }
                    else {
                        $tr.removeClass("hidden");
                    }

                });

            }, 500));


            return false;
        }
    };

    return perms;

});