
define(['jquery', 'bootstrap', 'router'], function ($, bootstrap, router) {

    return function (strDialogId, intDialogType, bitDragging, bitResizing) {
        this.dialog = null;
        this.containerId = strDialogId;
        this.iframeId = null;
        this.iframeURL = null;
        this.bitLarge = false;

        /** Set this variable to false if you don't want to remove actions on click */
        this.unbindOnClick = true;

        this.setTitle = function (strTitle) {
            if(strTitle == "")
                strTitle = "&nbsp;";
            $('#' + this.containerId + '_title').html(strTitle);
        };

        this.setBitLarge = function (bitLarge) {
            this.bitLarge = bitLarge
        };

        this.setContent = function (strContent, strConfirmButton, strLinkHref, blockHide) {

            if (intDialogType == 1) {
                this.unbindEvents();

                $('#' + this.containerId + '_content').html(strContent);
                var self = this;

                var $confirmButton = $('#' + this.containerId + '_confirmButton');
                $confirmButton.html(strConfirmButton);

                var bitUnbind = this.unbindOnClick;

                if(jQuery.isFunction(strLinkHref)) {

                    $confirmButton.click(function() {
                        var objReturn = strLinkHref();

                        if(!blockHide) {
                            self.hide();
                        }

                        if(bitUnbind) {
                            $confirmButton.unbind();
                            $confirmButton.click(function() {
                                return false;
                            });
                        }

                        return objReturn != undefined ? objReturn : false;
                    });
                }
                else {
                    $confirmButton.click(function() {
                        window.location = strLinkHref;

                        if(!blockHide) {
                            self.hide();
                        }

                        if(bitUnbind) {
                            $confirmButton.unbind();
                            $confirmButton.click(function() {
                                return false;
                            });
                        }

                        return false;
                    });
                }
            }
        };

        this.setContentRaw = function(strContent) {
            $('#' + this.containerId + '_content').html(strContent);
        };

        this.setContentIFrame = function(strUrl) {
            this.iframeId = this.containerId + '_iframe';
            strUrl = router.generateUrl(strUrl);
            strUrl = KAJONA_WEBPATH+strUrl.url+"&combinedLoad=1";
            this.iframeURL = strUrl;
        };

        this.init = function(intWidth, intHeight) {

            var $modal = $('#' + this.containerId).modal({
                backdrop: true,
                keyboard: false,
                show: false
            });

            if(!intHeight) {
                if($('#' + this.containerId+" .modal-dialog").hasClass("modal-lg")) {
                    intHeight = $(window).height() * 0.6;
                }
                else
                    intHeight = '';
            }

            var isStackedDialog = !!(window.frameElement && window.frameElement.nodeName && window.frameElement.nodeName.toLowerCase() == 'iframe');



            if (isStackedDialog) {
                if(this.iframeURL != null) {
                    //open the iframe in a regular popup
                    //workaround for stacked dialogs. if a modal is already opened, the second iframe is loaded in a popup window.
                    //stacked modals still face issues with dimensions and scrolling. (see http://trace.kajona.de/view.php?id=724)
                    if(!intWidth) {
                        intWidth = 500;
                    }

                    if(!intHeight) {
                        intHeight = 500;
                    }

                    window.open(this.iframeURL, $('#' + this.containerId + '_title').text(), 'scrollbars=yes,resizable=yes,width=' + (intWidth) + ',height=' + (intHeight));
                    return;
                }
            }

            if(this.iframeURL != null) {
                $("#folderviewDialog_loading").css('display', 'block');
                $('#' + this.containerId + '_content').html('<iframe src="' + this.iframeURL + '" width="100%" height="'+(intHeight)+'" name="' + this.iframeId + '" id="' + this.iframeId + '" class="seamless" seamless></iframe>');
                this.iframeURL = null;

                var id = this.iframeId;
                $("#"+this.iframeId).on('load', function() {
                    $("#folderviewDialog_loading").css('display', 'none');
                    $('#'+id).contents().find("body").addClass('dialogBody')

                });
            }


            if(!isStackedDialog && this.bitLarge) {
                $('#' + this.containerId+" .modal-dialog").addClass("modal-lg-lg");

                $('#' + this.containerId).on('hidden.bs.modal', function (e) {
                    console.log('hidden');
                    $(this).find(".modal-dialog").removeClass("modal-lg-lg");
                });

                this.bitLarge = false;
            }

            //finally show the modal
            $('#' + this.containerId).modal('show');

            if (bitDragging) {
                this.enableDragging();
            }
            if (bitResizing) {
                this.enableResizing();
            }
        };

        this.hide = function() {
            $('#' + this.containerId).modal('hide');
            this.unbindEvents();
        };

        this.enableDragging = function() {};

        this.enableResizing = function() {
            //$('#' + this.containerId).resizable();
            $('#' + this.containerId +" .modal-content").resizable().on("resize", function(event, ui) {
                ui.element.css("height", ui.size.height + $('.modal-footer').outerHeight() );

                $(ui.element).find(".modal-body").each(function() {
                    $(this).css("max-height", ui.size.height - $('.modal-header').outerHeight() - $('.modal-footer').outerHeight() );

                    $(ui.element).find("iframe.seamless").each(function() {
                        //-12 = resizable handle, -15 = padding
                        $(this).css("height", ui.size.height - $('.modal-header').outerHeight() - $('.modal-footer').outerHeight() - 12 -15 );
                    });
                });


            });
        };

        this.unbindEvents = function() {
            if(intDialogType == 1) {
                $('#' + this.containerId + '_cancelButton').unbind();
                $('#' + this.containerId + '_confirmButton').unbind();
                this.unbindOnClick = true;
                this.bitLarge = false;
            }
        };

        this.resetDialog = function(){

            //clone the template
            var clone = $("#template_" +this.id).clone();

            //remove "template_" from all id's of the clone
            clone.find("*[id]").andSelf().each(function() {
                $(this).attr("id", $(this).attr("id").substring(9));
            });

            //replace the current dialog with the clone
            $('#' + this.id).replaceWith(clone);


            //set hidden event again (needed as when replacing the events are not set anymore)
            $('#' + this.id).on('hidden', function (e) {
                require('dialog').resetDialog.call(this);
            })
        };

        //register event to reset the dialog with default settings (only if the dialog has template dialog)
        if($("#template_" +this.containerId).length > 0) {
            $('#' + this.containerId).on('hidden', function (e) {
                require('dialog').resetDialog.call(this);
            })
        }
    };

});
