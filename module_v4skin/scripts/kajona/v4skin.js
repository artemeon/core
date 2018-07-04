
define(['jquery', 'bootstrap', 'jquery-ui', 'workingIndicator', 'tooltip', 'statusDisplay', 'messaging', 'ajax', 'util', 'folderview', 'breadcrumb', 'lang'], function ($, bootstrap, jqueryui, workingIndicator, tooltip, statusDisplay, messaging, ajax, util, folderview, breadcrumb, lang) {



    var msg = {

        properties: null,

        pollMessages : function() {

            messaging.getRecentMessages(function (objResponse) {
                messaging.updateCountInfo(objResponse.messageCount);

                $('#messagingShortlist').empty();
                $.each(objResponse.messages, function (index, item) {
                    if (item.unread == 0)
                        $('#messagingShortlist').append("<li><a href='" + item.details + "'><i class='fa fa-envelope'></i> <b>" + item.title + "</b></a></li>");
                    else
                        $('#messagingShortlist').append("<li><a href='" + item.details + "'><i class='fa fa-envelope'></i> " + item.title + "</a></li>");
                });
                $('#messagingShortlist').append("<li class='divider'></li><li><a href='#/messaging'><i class='fa fa-envelope'></i> " + msg.properties.show_all + "</a></li>");

            });
        }


    };

    $.widget('custom.catcomplete', $.ui.autocomplete, {
        _create: function() {
            this._super();
            this.widget().menu( "option", "items", "> :not(.ui-autocomplete-category)" );
        },
        _renderMenu: function(ul, items) {
            var self = this;
            var currentCategory = '';

            $.each(items, function(index, item) {
                if (item.module != currentCategory) {
                    ul.append('<li class="ui-autocomplete-category"><h3>' + item.module + '</h3></li>');
                    currentCategory = item.module;
                }
                self._renderItemData(ul, item);
            });

            ul.append('<li class="detailedResults"><div href="#">'+searchExtendText+'</div></li>');
            ul.addClass('dropdown-menu');
            ul.addClass('search-dropdown-menu');

            ul.find('.detailedResults a').click(function () {
                $('.navbar-search').submit();
            });
        },
        _renderItemData: function (ul, item) {
            return $('<li></li>')
                .data('ui-autocomplete-item', item)
                .append('<div>' + item.icon + item.description + '</div>')
                .appendTo(ul);
        }
    });

    $('#globalSearchInput').catcomplete({

        minLength: 2,
        delay: 500,

        source: function(request, response) {
            $.ajax({
                url: KAJONA_WEBPATH+'/xml.php?admin=1',
                type: 'POST',
                dataType: 'json',
                data: {
                    search_query: request.term,
                    module: 'search',
                    action: 'searchXml',
                    asJson: '1'
                },
                success: response
            });
        },
        select: function (event, ui) {
            if(ui.item) {
                document.location = ui.item.link;
            }
        },
        messages: {
            noResults: '',
            results: function() {}
        },
        search: function(event, ui) {
            $(this).parent().find('.input-group-addon').html('<i class="fa fa-spinner fa-spin"></i></span>');
            workingIndicator.start();
        },
        response: function(event, ui) {
            //$(this).css("background-image", "none");
            $(this).parent().find('.input-group-addon').html('<i class="fa fa-search"></i></span>');
            workingIndicator.stop();
        }
    });


    // init popovers & tooltips
    $('#content a[rel=popover]').popover();
    tooltip.initTooltip();

    kajonaScroll = null;
    $(window).scroll(function() {
        var scroll = $(this).scrollTop();
        if(scroll > 10 && kajonaScroll != 'top') {
            $("ul.breadcrumb").addClass("breadcrumbTop");
            $("#quickhelp").addClass("quickhelpTop");
            $(".pathNaviContainer").addClass("pathNaviContainerTop");
            kajonaScroll = "top";
        }
        else if(scroll <= 10 && kajonaScroll != 'margin') {
            $("ul.breadcrumb").removeClass("breadcrumbTop");
            $("#quickhelp").removeClass("quickhelpTop");
            $(".pathNaviContainer").removeClass("pathNaviContainerTop");
            kajonaScroll = "fixed";
        }


    });

    breadcrumb.updateEllipsis();
    $(window).on("resize", function() {
        breadcrumb.updateEllipsis();
    });

    //register desktop notifications for messaging
    //util.desktopNotification.grantPermissions();

    //init offacanvas menu
    $('[data-toggle="offcanvas"]').click(function () {
        $('.row-offcanvas').toggleClass('active')
    });

    //enable the top navigation
    if (!util.isStackedDialog()) {
        $('div.navbar-fixed-top .navbar-topbar').removeClass('hidden');
        $('div.pathNaviContainer').removeClass('hidden');
    }


    return {

        properties : {
            messaging : {},
            tags : {}
        },

        defaultAutoComplete : function() {
            this.minLength = 0;

            this.delay = 500;

            this.messages = {
                noResults: '',
                results: function() {return ''}
            };

            this.search = function(event, ui) {
                //If input field changes -> reset hidden id field
                var $objCur = $(this);
                if(!$objCur.is('[readonly]')) {
                    if($('#'+$objCur.attr('id')+'_id')) {
                        $( '#'+$objCur.attr('id')+'_id' ).val( "" ).trigger('change');
                    }
                }

                //Formentry must have at least 2 characters to trigger search.
                if(event.target.value.length < 2) {
                    event.stopPropagation();
                    return false;
                }
                $objCur.parent().find('.loading-feedback').html('<i class="fa fa-spinner fa-spin"></i>');
                workingIndicator.getInstance().start();
            };

            this.response = function(event, ui) {
                $(this).parent().find('.loading-feedback').html('');
                workingIndicator.getInstance().stop();
            };

            this.focus = function(event, ui) {
                return false;
            };

            this.select = function( event, ui ) {
                if(ui.item) {
                    var $objCur = $(this);
                    $objCur.val( ui.item.title );
                    if($('#'+$objCur.attr('id')+'_id')) {
                        $( '#'+$objCur.attr('id')+'_id' ).val( ui.item.systemid).trigger('change');
                    }
                    $objCur.trigger('change');
                }
            };

            this.create = function( event, ui ) {
                var $objCur = $(this);
                $objCur.closest('.form-group').addClass('has-feedback');
                $objCur.after("<span class='form-control-feedback loading-feedback'><i class='fa fa-keyboard-o'></i></span>");
            }
        },

        messaging: msg,

        breadcrumb: breadcrumb,

        initTagMenu: function(){
            var me = this;
            ajax.genericAjaxCall("tags", "getFavoriteTags", "", function(data, status, jqXHR) {
                if(status == 'success') {
                    $('#tagsSubemenu').empty();
                    $.each($.parseJSON(data), function(index, item) {
                        $('#tagsSubemenu').append("<li><a href='"+item.url+"'><i class='fa fa-tag'></i> "+item.name+"</a></li>");
                    });
                    $('#tagsSubemenu').append("<li class='divider'></li><li><a href='#/tags'><i class='fa fa-tag'></i> <span data-lang-property='tags:action_show_all'></span></a></li>");
                    lang.initializeProperties('#tagsSubemenu');
                }
            });
        },

        /**
         * Removes an object list row from the list
         *
         * @param el
         */
        removeObjectListItem: function(el){
            // remove all active tooltips
            tooltip.removeTooltip(el);

            // trigger table changed event
            var table = $(el).closest(".form-group").find(".table").first();

            // remove element
            $(el).parent().parent().fadeOut(0, function(){
                $(this).remove();
            });

            table.trigger('updated');
        },

        removeAllObjectListItems: function(strTableId) {
            $('#'+strTableId).find(".removeLink").trigger('click');
        },

        /**
         * Gets all items containd in the object list
         *
         * @param strElementName
         * @returns {Array}
         */
        getObjectListItems: function(strElementName){
            var table = util.getElementFromOpener(strElementName);

            var arrItems = [];

            var tbody = table.find('tbody');
            if(tbody.length > 0) {
                // remove only elements which are in the arrAvailableIds array
                tbody.children().each(function(){
                    var strId = $(this).find('input[type="hidden"]').val();
                    arrItems.push(strId);
                });
            }

            return arrItems;
        },

        /**
         * Use folderview.setObjectListItems
         *
         * @deprecated
         * @param strElementName
         * @param arrItems
         * @param arrAvailableIds
         * @param strDeleteButton
         */
        setObjectListItems: function(strElementName, arrItems, arrAvailableIds, strDeleteButton){
            console.log('v4skin.setObjectListItems is deprecated please use folderview.setObjectListItems instead');
            folderview.setObjectListItems(strElementName, arrItems, arrAvailableIds, strDeleteButton);
        },

        /**
         * Use folderview.setCheckboxArrayObjectListItems
         *
         * @deprecated
         * @param strElementName
         * @param arrItems
         */
        setCheckboxArrayObjectListItems : function(strElementName, arrItems){
            console.log('v4skin.setCheckboxArrayObjectListItems is deprecated please use folderview.setCheckboxArrayObjectListItems instead');
            folderview.setCheckboxArrayObjectListItems(strElementName, arrItems);
        },

        /**
         * We get the current tree selection from the iframe element and set the selection in the object list
         *
         * @param objIframeEl
         * @param strElementName
         */
        updateCheckboxTreeSelection: function(objIframeEl, strElementName, strDeleteButton){
            if(objIframeEl && objIframeEl.contentWindow) {
                var jstree = objIframeEl.contentWindow.$('.jstree');
                if(jstree.length > 0) {
                    // we modify only the ids which are visible for the user all other ids stay untouched
                    var arrAvailableIds = [];
                    jstree.find('li').each(function(){
                        arrAvailableIds.push($(this).attr('systemid'));
                    });

                    var arrEls = jstree.jstree('get_checked');
                    var arrItems = [];
                    for(var i = 0; i < arrEls.length; i++) {
                        var el = $(arrEls[i]);
                        var strSystemId = el.attr('id');
                        var strDisplayName = el.text().trim();
                        var strIcon = el.find('[rel="tooltip"]').html();

                        arrItems.push({
                            strSystemId: strSystemId,
                            strDisplayName: strDisplayName,
                            strIcon: strIcon
                        });
                    }

                    require('folderview').setObjectListItems(strElementName, arrItems, arrAvailableIds, strDeleteButton);

                    jsDialog_1.hide();
                }
            }
        },

        /**
         * Returns all systemids which are available in the object list. The name of the object list element name must be
         * available as GET parameter "element_name"
         *
         * @returns array
         */
        getCheckboxTreeSelectionFromParent: function() {
            if($('.jstree').length > 0) {
                // the query parameter contains the name of the form element where we insert the selected elements
                var strElementName = require('v4skin').getQueryParameter("element_name");
                var table = parent.$('#' + strElementName);
                var arrSystemIds = [];
                if(table.length > 0) {
                    table.find('input[type="hidden"]').each(function(){
                        arrSystemIds.push($(this).val());
                    });
                }

                return arrSystemIds;
            }
        }

    };

});
