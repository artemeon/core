/********************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$											*
********************************************************************************************************/

This skin-file is used for the Kajona v4 admin skin and can be used as a sample file to create
your own cool skin. Just modify the sections you'd like to. Don't forget the css file and the basic
templates!


---------------------------------------------------------------------------------------------------------
-- GRID ELEMENTS ----------------------------------------------------------------------------------------

<grid_header>
<div class="grid" data-kajona-pagenum="%%curPage%%" data-kajona-elementsperpage="%%elementsPerPage%%">
    <ul class="thumbnails gallery %%sortable%%">
</grid_header>

<grid_footer>
    </ul>
</div>
<script type="text/javascript">
   $(function() {
        $('.grid > ul.sortable').sortable( {
            items: 'li[data-systemid!=""]',
            handle: 'div.thumbnail',
            cursor: 'move',
            start: function(event, ui) {
                oldPos = ui.item.index()
            },
            stop: function(event, ui) {
                if(oldPos != ui.item.index()) {

                    //calc the page-offset
                    var intCurPage = $(this).parent(".grid").attr("data-kajona-pagenum");
                    var intElementsPerPage = $(this).parent(".grid").attr("data-kajona-elementsperpage");

                    var intPagingOffset = 0;
                    if(intCurPage > 1 && intElementsPerPage > 0)
                        intPagingOffset = (intCurPage*intElementsPerPage)-intElementsPerPage;

                    Ajax.setAbsolutePosition(ui.item.data('systemid'), ui.item.index()+1+intPagingOffset);
                }
                oldPos = 0;
            },
            delay: Util.isTouchDevice() ? 500 : 0
        });
        $('.grid > ul.sortable > li[data-systemid!=""] > div.thumbnail ').css("cursor", "move");
    });
</script>
</grid_footer>

<grid_entry>
<li class="col-md-3 col-xs-3 %%cssaddon%%" data-systemid="%%systemid%%" >
    <div class="thumbnail" %%clickaction%% >
        <h5 >%%title%%</h5>
        <div class="contentWrapper" style="background: url(%%image%%) center no-repeat; background-size: cover;">
            <div class="metainfo">
                <div>%%info%%</div>
                <div>%%subtitle%%</div>
            </div>
        </div>
        <div class="actions">
            %%actions%%
        </div>
    </div>
</li>
</grid_entry>

---------------------------------------------------------------------------------------------------------
-- LIST ELEMENTS ----------------------------------------------------------------------------------------

Optional Element to start a list
<list_header>
<table class="table admintable table-striped-tbody">
</list_header>

Header to use when creating drag n dropable lists. places an id an loads the needed js-scripts in the
background using the ajaxHelper.
Loads the script-helper and adds the table to the drag-n-dropable tables getting parsed later
<dragable_list_header>
<script type="text/javascript">
ListSortable.init('%%listid%%', '%%targetModule%%', %%bitMoveToTree%%);
</script>
<div id='%%listid%%_prev' class='alert alert-info divPageTarget'>[lang,commons_list_sort_prev,system]</div>
<table id="%%listid%%" class="table admintable table-striped-tbody" data-kajona-pagenum="%%curPage%%" data-kajona-elementsperpage="%%elementsPerPage%%">

</dragable_list_header>

Optional Element to close a list
<list_footer>
</table>
<script type="text/javascript"> if (%%clickable%%) {  Lists.initRowClick() }</script>
</list_footer>

<dragable_list_footer>
</table>
<div id='%%listid%%_next' class='alert alert-info divPageTarget'>[lang,commons_list_sort_next,system]</div>
<script type="text/javascript"> if (%%clickable%%) { Lists.initRowClick()  }</script>
</dragable_list_footer>


The general list will replace all other list types in the future.
It is responsible for rendering the different admin-lists.
Currently, there are two modes: with and without a description.

<generallist_checkbox>
    <input type="checkbox" name="kj_cb_%%systemid%%" id="kj_cb_%%systemid%%" onchange="Lists.updateToolbar();">
</generallist_checkbox>

<generallist>
    <tbody class="%%cssaddon%%">
        <tr data-systemid="%%listitemid%%" data-deleted="%%deleted%%">
            <td class="treedrag"></td>
            <td class="listsorthandle"></td>
            <td class="listcheckbox">%%checkbox%%</td>
            <td class="listimage">%%image%%</td>
            <td class="title">%%title%%</td>
            <td class="center">%%center%%</td>
            <td class="actions">%%actions%%</td>
        </tr>
    </tbody>
</generallist>


<generallist_desc>
    <tbody class="generalListSet %%cssaddon%%">
        <tr data-systemid="%%listitemid%%" data-deleted="%%deleted%%">
            <td class="treedrag"></td>
            <td class="listsorthandle"></td>
            <td class="listcheckbox">%%checkbox%%</td>
            <td class="listimage">%%image%%</td>
            <td class="title">%%title%%</td>
            <td class="center">%%center%%</td>
            <td class="actions">%%actions%%</td>
        </tr>
        <tr>
            <td colspan="4" class="description"></td>
            <td colspan="3" class="description">%%description%%</td>
        </tr>
    </tbody>
</generallist_desc>



<batchactions_wrapper>
<div class="batchActionsWrapper">
    %%entries%%
    <div class="batchActionsProgress" style="display: none;">
        <h5 class="progresstitle"></h5>
        <span class="batch_progressed">0</span> / <span class="total">0</span>
        <div class="progress">
            <div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;">0%</div>
        </div>
        <div class="batchaction_messages">
            <ul class="batchaction_messages_list"></ul>
        </div>
    </div>
</div>
<script type="text/javascript">
     $("#kj_cb_batchActionSwitch").on('click', function() { Lists.toggleAllFields(); Lists.updateToolbar(); });
        Lists.strConfirm = '[lang,commons_batchaction_confirm,pages]';
        Lists.strDialogTitle = '[lang,commons_batchaction_title,pages]';
        Lists.strDialogStart = '[lang,commons_start,pages]';
        Lists.updateToolbar();
</script>
</batchactions_wrapper>

<batchactions_entry>
    <a href="#" onclick="%%onclick%% return false;" title="%%title%%" rel="tooltip">%%icon%%</a>
</batchactions_entry>

Divider to split up a page in logical sections
<divider>
<hr />
</divider>





---------------------------------------------------------------------------------------------------------
-- ACTION ELEMENTS --------------------------------------------------------------------------------------

Element containing one button / action, multiple put together, e.g. to edit or delete a record.
To avoid side-effects, no line-break in this case -> not needed by default, but in classics-style!
<list_button><span class="listButton">%%content%%</span></list_button>

---------------------------------------------------------------------------------------------------------
-- FORM ELEMENTS ----------------------------------------------------------------------------------------

<form_start>
<form name="%%name%%" id="%%name%%" method="%%method%%" action="%%action%%" enctype="%%enctype%%" onsubmit="%%onsubmit%%" class="form-horizontal">
    <script type="text/javascript">
        //    $(function() {
                Forms.initForm('%%name%%', %%onchangedetection%%);
                Forms.changeLabel = '[lang,commons_form_entry_changed,system]';
                Forms.changeConfirmation = '[lang,commons_form_entry_changed_conf,system]';
                Forms.leaveUnsaved = '[lang,commons_form_unchanged,system]';
            // });
    </script>
</form_start>

<form_close>
</form>
</form_close>

Dropdown
<input_dropdown>
    <div class="form-group">
        <label for="%%name%%" class="col-sm-3 control-label">%%title%%</label>
        <div class="col-sm-6">
            <select data-placeholder="%%dataplaceholder%%" name="%%name%%" id="%%name%%" class="form-control %%class%%" %%disabled%% %%addons%% data-kajona-instantsave="%%instantEditor%%" >%%options%%</select>
        </div>
        <div class="col-sm-2 form-opener">
            %%opener%%
        </div>
    </div>
</input_dropdown>

<input_dropdown_row>
<option value="%%key%%">%%value%%</option>
</input_dropdown_row>

<input_dropdown_row_selected>
<option value="%%key%%" selected="selected">%%value%%</option>
</input_dropdown_row_selected>


Multiselect
<input_multiselect>
    <div class="form-group">
        <label for="%%name%%[]" class="col-sm-3 control-label">%%title%%</label>
        <div class="col-sm-6">
            <select size="7" name="%%name%%[]" id="%%name%%" class="form-control %%class%%" multiple="multiple" %%disabled%% %%addons%%>%%options%%</select>
        </div>
    </div>
</input_multiselect>

<input_multiselect_row>
    <option value="%%key%%">%%value%%</option>
</input_multiselect_row>

<input_multiselect_row_selected>
    <option value="%%key%%" selected="selected">%%value%%</option>
</input_multiselect_row_selected>

Date interval
<input_date_interval>
    <div class="form-group">
        <label for="%%name%%_unit" class="col-sm-3 control-label">%%title%%</label>
        <div class="col-sm-6">
            <div class="row">
                <div class="col-md-8">
                    <input type="number" name="%%name%%" id="%%name%%" value="%%value%%" class="form-control %%class%%" %%disabled%%>
                </div>
                <div class="col-md-4">
                    <select name="%%name%%_unit" id="%%name%%_unit" class="form-control %%class%%" %%disabled%% %%addons%%>%%units%%</select>
                </div>
            </div>
        </div>
    </div>
</input_date_interval>

Toggle Button-Bar
<input_toggle_buttonbar>
    <div class="form-group">
        <label for="%%name%%[]" class="col-sm-3 control-label">%%title%%</label>
        <div class="col-sm-6">
            <div class="btn-group buttonbar" data-toggle="buttons">
                %%options%%
            </div>
        </div>
    </div>
</input_toggle_buttonbar>

<input_toggle_buttonbar_button>
    <label class="btn btn-primary %%btnclass%%">
        <input type="%%type%%" name="%%name%%[]" value="%%key%%" %%disabled%% %%addons%%> %%value%%
    </label>
</input_toggle_buttonbar_button>

<input_toggle_buttonbar_button_selected>
    <label class="btn btn-primary active %%btnclass%%">
        <input type="%%type%%" name="%%name%%[]" value="%%key%%" checked="checked" %%disabled%% %%addons%%> %%value%%
    </label>
</input_toggle_buttonbar_button_selected>


Radiogroup
<input_radiogroup>
    <div class="form-group %%class%%">
        <label class="col-sm-3 control-label">%%title%%</label>
        <div class="col-sm-6">
            %%radios%%
        </div>
    </div>
</input_radiogroup>


<input_radiogroup_row>
    <div class="radio">
        <label>
            <input type="radio" name="%%name%%" id="%%name%%_%%key%%" value="%%key%%" class="%%class%%" %%checked%% %%disabled%%>
            %%value%%
        </label>
    </div>
</input_radiogroup_row>


Checkbox
<input_checkbox>
<div class="form-group">
    <label for="%%name%%" class="col-sm-3 control-label"></label>
    <div class="col-sm-6">
        <div class="checkbox">
            <label>
                <input type="checkbox" name="%%name%%" value="checked" id="%%name%%" class="%%class%%" %%checked%% %%readonly%%>
                %%title%%
            </label>
        </div>
    </div>
</div>
</input_checkbox>

Toggle_On_Off (using bootstrap-switch.org)
<input_on_off_switch>
    <script type="text/javascript">
             window.setTimeout(function() {
                var divId = '%%name%%';
                divId = '#' + divId.replace( /(:|\.|\[|\])/g, "\\$1" );
                $(divId).bootstrapSwitch();
                $(divId).on('switchChange.bootstrapSwitch', function (event, state) {
                    %%onSwitchJSCallback%%
                });

            }, 200);
    </script>

    <div class="form-group">
        <label class="col-sm-3 control-label" for="%%name%%">%%title%%</label>
        <div class="col-sm-6">
            <div id="div_%%name%%" class="" >
                <input type="checkbox" name="%%name%%" value="checked" id="%%name%%" class="%%class%%" %%checked%% %%readonly%% data-size="small" data-on-text="<i class='fa fa-check fa-white' ></i>" data-off-text="<i class='fa fa-times'></i>">
            </div>
        </div>
    </div>
</input_on_off_switch>

Regular Hidden-Field
<input_hidden>
	<input name="%%name%%" value="%%value%%" type="hidden" id="%%name%%">
</input_hidden>

Regular Text-Field
<input_text>
    <div class="form-group">
        <label for="%%name%%" class="col-sm-3 control-label">%%title%%</label>
        <div class="col-sm-6 %%class%%">
            <input type="text" id="%%name%%" name="%%name%%" value="%%value%%" class="form-control" %%readonly%% data-kajona-instantsave="%%instantEditor%%">
        </div>
        <div class="col-sm-2 form-opener">
            %%opener%%
        </div>
    </div>
</input_text>

Color Picker
<input_colorpicker>
    <div class="form-group colorpicker-component">
        <label for="%%name%%" class="col-sm-3 control-label">%%title%%</label>
        <div class="col-sm-6">
            <div class="input-group colorpicker-component" id="c_%%name%%">
                <div class="input-group-addon"><i></i></div>
                <input id="%%name%%" name="%%name%%" class="form-control" type="text" value="%%value%%" %%readonly%% data-kajona-instantsave="%%instantEditor%%">
            </div>
        </div>
        <script type="text/javascript">
     $('#c_%%name%%').colorpicker({component: '*'});

//            if($('#%%name%%').is(':focus')) {
//                $('#%%name%%').blur();
//                $('#%%name%%').focus();
//            }
        </script>
    </div>
</input_colorpicker>

Regular Password-Field
<input_password>
    <div class="form-group">
        <label for="%%name%%" class="col-sm-3 control-label">%%title%%</label>
        <div class="col-sm-6 %%class%%">
            <input type="password" autocomplete="off" id="%%name%%" name="%%name%%" value="%%value%%" class="form-control" %%readonly%%>
        </div>
    </div>
</input_password>

Upload-Field
<input_upload>
    <div class="form-group">
        <label for="%%name%%" class="col-sm-3 control-label">%%title%%</label>
        <div class="col-sm-6">
            <input type="file" name="%%name%%" id="%%name%%" class="form-control %%class%%">
            <span class="help-block">
                %%maxSize%%
                <a href="%%fileHref%%">%%fileName%%</a>
            </span>
        </div>
    </div>
</input_upload>

Download-Field
<input_upload_disabled>
    <div class="form-group">
        <label for="%%name%%" class="col-sm-3 control-label">%%title%%</label>
        <div class="col-sm-6">
            <div class="form-control %%class%%">
                <a href="%%fileHref%%" id="%%name%%">%%fileName%%</a>
            </div>
        </div>
    </div>
</input_upload_disabled>

Upload-Field for multiple files with progress bar
<input_upload_multiple>

    <div id="%%name%%" class="fileupload-wrapper">
            <div class="fileupload-buttonbar drop-zone">

                <span class="btn btn-default fileinput-button">
                    <i class="fa fa-plus-square"></i>
                    <span>[lang,mediamanager_upload,mediamanager]</span>
                    <input type="file" name="%%name%%" multiple>
                </span>

                <button type="submit" class="btn btn-default start" style="display: none;">
                    <i class="fa fa-upload"></i>
                    <span>[lang,upload_multiple_uploadFiles,mediamanager]</span>
                </button>

                <button type="reset" class="btn btn-default cancel" style="display: none;">
                    <i class="fa fa-ban"></i>
                    <span>[lang,upload_multiple_cancel,mediamanager]</span>
                </button>

                <span class="fileupload-process"></span>
                <div class="alert alert-info">
                    [lang,upload_dropArea,mediamanager]<br />
                     %%allowedExtensions%%
                </div>
            </div>

            <div class="fileupload-progress" style="display: none;">

                <div class="progress" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%;"></div>
                </div>

                <div class="progress-extended">&nbsp;</div>
            </div>

        <table class="table admintable table-striped-tbody files"></table>

        <div class="hidden fileupload-list-template">
            <table>
                <tbody class="template-upload fade">
                <tr>
                    <td><span class="preview"></span></td>
                    <td><p class="name"></p>
                    <div class="error"></div>
                    </td>
                    <td><p class="size"></p>
                        <div class="progress"><div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;"></div></div>
                    </td>
                <td>
                    <button class="btn start " disabled style="display: none;">Start</button>
                    <button class="btn cancel ">[lang,upload_multiple_cancel,mediamanager]</button>
                </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script type="text/javascript">
        (function () {
            var filesToUpload = 0;
            var uploader = Fileupload.initUploader({
                baseElement: $('#%%name%%'),
                paramName: '%%name%%',
                formData: [
                    {name: 'systemid', value: '%%mediamanagerRepoId%%'},
                    {name: 'inputElement', value : '%%name%%'}
                ],

                maxFileSize: %%maxFileSize%%,
                acceptFileTypes: %%acceptFileTypes%%,
                uploadTemplate: function (o) {
                var rows = $();
                $.each(o.files, function (index, file) {
                    var row = $('#%%name%% .fileupload-list-template .template-upload').clone();
                    row.find('.name').text(file.name);
                    row.find('.size').text(o.formatFileSize(file.size));
                    if (file.error) {
                        row.find('.error').text(file.error);
                    }
                    rows = rows.add(row);
                });
                return rows;
            }
        });

            uploader.getUploader()
                .bind('fileuploadadded', function (e) {
                    $(this).find('.fileupload-buttonbar button.start').css('display', '');
                    $(this).find('.fileupload-buttonbar button.cancel').css('display', '');
                    $(this).find('.fileupload-progress').css('display', '');
                    filesToUpload++;
                })
                .bind('fileuploadfail', function (e) {
                    filesToUpload--;
                    $(this).trigger('kajonahideelements');
                })
                .bind('fileuploaddone', function (e) {
                    filesToUpload--;
                    $(this).trigger('kajonahideelements');
                })
                .bind('fileuploadstop', function (e) {
                    $(this).trigger('kajonahideelements');
                    document.location.reload();
                })
                .bind('kajonahideelements', function() {
                    if(filesToUpload === 0) {
                        $(this).find('.fileupload-buttonbar button.start').css('display', 'none');
                        $(this).find('.fileupload-buttonbar button.cancel').css('display', 'none');
                        $(this).find('.fileupload-progress').css('display', 'none');
                    }
                });
        })();
    </script>
</input_upload_multiple>




Upload-Field for multiple files with progress bar
<input_upload_inline>
    <div id="%%name%%_upl" class="form-group fileupload-wrapper">
        <label for="%%name%%" class="col-sm-3 control-label">%%title%%</label>
        <div  class="col-sm-6 inputText ">

            <table class="table admintable table-striped-tbody files form-control drop-zone" id="%%name%%">

            </table>

            <div class="hidden fileupload-list-template">
                <table>
                <tbody class="template-upload fade" data-uploadid="">
                    <tr class="progress-row">
                        <td colspan="4">
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;"></div>
                            </div>
                        </td>
                    </tr>
                    <tr class="file-details">
                        <td><span class="preview"></span></td>
                        <td>
                            <span class="name"></span><div class="error"></div>
                        </td>
                        <td>
                            <span class="date"></span>
                        </td>
                        <td>
                            <span class="size"></span>
                        </td>
                        <td class="actions">
                            <span class="dl-link hidden"><a href=""><i class="kj-icon fa fa-download"></i></a></span>
                            <span class="del-button hidden"></span>
                        </td>
                    </tr>
                </tbody>
                </table>
            </div>

            <div class="archive-list "></div>
        </div>
        <div class="col-sm-2 form-opener">
            <span class="listButton">
                <span class=" fileinput-button">
                    <input type="file" name="%%name%%" multiple>
                    %%addButton%%
                </span>
            </span>
            %%helpButton%%
            %%moveButton%%
            %%archiveButton%%
        </div>
    </div>

    <script type="text/javascript">
        (function () {
            /**
             *
             * @type {UploadManager}
             */
            var uploader = Fileupload.initUploader({
                baseElement: $('#%%name%%_upl'),
                autoUpload: true,
                readOnly: %%readOnly%%,
                multiUpload: %%multiUpload%%,
                paramName: '%%name%%_upl',
                formData: [
                    {name: 'systemid', value: '%%mediamanagerRepoId%%'},
                    {name: 'inputElement', value : '%%name%%_upl'},
                    {name: 'folder', value : '%%folder%%'}
            ],
                maxFileSize: %%maxFileSize%%,
                acceptFileTypes: %%acceptFileTypes%%,
                downloadTemplate: function (o) {
                    var rows = $();
                    $.each(o.files, function (index, file) {
                        var row = $('#%%name%%_upl .fileupload-list-template .template-upload').clone();
                        //file.name = file.name.replace(/(.{60})/g,"$1 ");
                        row.find('.name').text(file.name);
                        row.find('.date').text(file.createDate);
                        row.find('.size').text(o.formatFileSize(file.size));
                        if (file.error) {
                            row.find('.error').text(file.error);
                        }
                        if (file.url) {
                            row.find(".dl-link a").attr('href', file.url);
                            row.find(".dl-link").removeClass('hidden');
                        }
                        if (file.deleteButton && !%%readOnly%%) {
                            row.find('.del-button').html(file.deleteButton).removeClass('hidden');
                        }
                        row.attr('data-uploadid', file.systemid);
                        rows = rows.add(row);
                    });
                return rows;
            },
            uploadTemplate: function (o) {
                var rows = $();
                $.each(o.files, function (index, file) {
                    var row = $('#%%name%%_upl .fileupload-list-template .template-upload').clone();
                    row.find('.name').text(file.name);
                    row.find('.size').text(o.formatFileSize(file.size));
                    if (file.error) {
                        row.find('.error').text(file.error);
                    }
                    rows = rows.add(row);
                });
                return rows;
            }
        });

            //load files from the backend
            Ajax.genericAjaxCall("mediamanager", "fileUploadList", "&systemid=%%mediamanagerRepoId%%&folder=%%folder%%", function(data) {
                uploader.getUploader().fileupload('option', 'done').call(uploader.getUploader(), $.Event('done'), {result: data});
            }, null, null, "post", "json");

            if ($('#version_%%name%%').length > 0) {
                $('#version_%%name%%').on('click', function() {
                    uploader.fileVersioning();
                });
            }

            if ($('#archive_%%name%%').length > 0) {
                $('#archive_%%name%%').on('click', function() {
                    uploader.fileArchiving("%%targetSystemId%%", "%%archiveTitle%%", "%%archiveBody%%", "%%archiveTitle%%");
                });
            }

            uploader.renderArchiveList();
        })();
    </script>
</input_upload_inline>

An easy date-selector
If you want to use the js-date-picker, leave %%calendarCommands%% at the end of the section
in addition, a container for the calendar is needed. Use %%calendarContainerId%% as an identifier.
<input_date_simple>
    <div class="form-group">
        <label for="%%name%%" class="col-sm-3 control-label">%%title%%</label>
        <div class="col-sm-6">
            <div class="input-group">
                <div class="input-group-addon"><i class="fa fa-calendar-o"></i></div>
                <input id="%%calendarId%%" name="%%calendarId%%" class="form-control %%class%%" size="16" type="text" value="%%valuePlain%%" %%readonly%% autocomplete="off">
            </div>
            <script>

                        $('#%%calendarId%%').datepicker({
                            format: Util.transformDateFormat('%%dateFormat%%', "bootstrap-datepicker"),
                            weekStart: 1,
                            autoclose: true,
                            language: '%%calendarLang%%',
                            todayHighlight: true,
                            container: '#content',
                            todayBtn: "linked",
                            daysOfWeekHighlighted: "0,6",
                            calendarWeeks: true
                        });

                        if($('#%%calendarId%%').is(':focus')) {
                            $('#%%calendarId%%').blur();
                            $('#%%calendarId%%').focus();
                        }

            </script>
        </div>
    </div>

</input_date_simple>

<input_datetime_simple>

    <div class="form-group">
        <label for="%%name%%" class="col-sm-3 control-label">%%title%%</label>
        <div class="col-sm-2">
            <div class="input-group">
                <div class="input-group-addon"><i class="fa fa-calendar-o"></i></div>
                <input id="%%calendarId%%" name="%%calendarId%%" class="form-control" size="16" type="text" value="%%valuePlain%%" %%readonly%% autocomplete="off">
            </div>
        </div>

        <div class="col-sm-2 form-inline">
            <div class="form-group">

                <div class="input-group">
                    <div class="input-group-addon"><i class="fa fa-clock-o"></i></div>
                    <input name="%%titleHour%%" id="%%titleHour%%" type="text" class="form-control %%class%%" size="2" maxlength="2" value="%%valueHour%%" />
                </div>
                <input name="%%titleMin%%" id="%%titleMin%%" type="text" class="form-control %%class%%" size="2" maxlength="2" value="%%valueMin%%" />
            </div>
        </div>
        <div class="col-sm-1">
        </div>
        <script>

                    $('#%%calendarId%%').datepicker({
                        format: Util.transformDateFormat('%%dateFormat%%', "bootstrap-datepicker"),
                        weekStart: 1,
                        autoclose: true,
                        language: '%%calendarLang%%',
                        todayHighlight: true,
                        container: '#content',
                        todayBtn: "linked",
                        daysOfWeekHighlighted: "0,6",
                        calendarWeeks: true
                    });

                    if($('#%%calendarId%%').is(':focus')) {
                        $('#%%calendarId%%').blur();
                        $('#%%calendarId%%').focus();
                    }

        </script>
    </div>
</input_datetime_simple>

<input_tageditor>
    <div class="form-group">
        <label for="%%name%%" class="col-sm-3 control-label">%%title%%</label>

        <div class="col-sm-6 inputText inputTagEditor">
            <input type="text" id="%%name%%" data-name="%%name%%" autocomplete="off"/>
        </div>
    </div>
    <script type="text/javascript">
         var onChange = %%onChange%%;
            var $objInput = $("#%%name%%");
            $objInput.TagEditor({
                initialTags: %%values%%,
                forceLowercase: false,
                delimiter: %%delimiter%%,
                maxLength: 250,
                onChange: onChange
            });
            $objInput.on('kajona.forms.mandatoryAdded', function() {
                TagEditor.updateMandatoryRendering($(this));
            });
            TagEditor.updateMandatoryRendering($objInput);
            onChange("#%%name%%", $objInput.TagEditor('getTags')[0].editor, %%values%%);
    </script>
</input_tageditor>

<input_objecttags>
    <div class="form-group">
        <label for="%%name%%" class="col-sm-3 control-label">%%title%%</label>

        <div class="col-sm-6 inputText inputTagEditor" id="tageditor_%%name%%">
            <input type="text" id="%%name%%" data-name="%%name%%" class="form-control" autocomplete="off" data-kajona-block-initval="true"/>
            <div id="%%name%%-list">%%data%%</div>
        </div>

        <div class="col-sm-2 form-opener">
            %%opener%%
        </div>
    </div>
    <script type="text/javascript">
        TagEditor.init('%%name%%', '%%source%%', %%values%%, %%onChange%%);
    </script>
</input_objecttags>

<input_container>
    <div class="form-group">
        <label for="%%name%%" class="col-sm-3 control-label">%%title%%</label>

        <div class="col-sm-6 inputText">
            <div id="%%name%%" class="inputContainer %%class%%">
                %%elements%%
            </div>
        </div>

        <div class="col-sm-2 form-opener">
            %%opener%%
        </div>
    </div>
</input_container>

<input_container_row>
    <div class="inputContainerPanel">%%element%%</div>
</input_container_row>

A page-selector.
If you want to use ajax to load a list of proposals on entering a char,
place ajaxScript before the closing input_pageselector-tag and make sure, that you
have a surrounding div with class "ac_container" and a div with id "%%name%%_container" and class
"ac_results" inside the "ac_container", to generate a resultlist
<input_pageselector>
    <div class="form-group">
        <label for="%%name%%" class="col-sm-3 control-label">%%title%%</label>

        <div class="col-sm-6">
            <input type="text" id="%%name%%" name="%%name%%" value="%%value%%" class="form-control %%class%%" %%readonly%% data-kajona-instantsave="%%instantEditor%%" >
        </div>
        <div class="col-sm-2 form-opener">
            %%opener%%
            %%ajaxScript%%
        </div>
    </div>
</input_pageselector>

<input_userselector>
<div class="form-group">
    <label for="%%name%%" class="col-sm-3 control-label">%%title%%</label>

    <div class="col-sm-6">
        <input type="text" id="%%name%%" name="%%name%%" value="%%value%%" class="form-control %%class%%" %%readonly%% autocomplete="off">
        <input type="hidden" id="%%name%%_id" name="%%name%%_id" value="%%value_id%%" />
    </div>
    <div class="col-sm-2 form-opener">
        %%opener%%
        %%ajaxScript%%
    </div>
</div>
</input_userselector>


A list of checkbox for object elements
<input_checkboxarrayobjectlist>
    <div class="form-group form-checkboxarraylist form-list">
        <label for="%%name%%" class="col-sm-3 control-label">%%title%%</label>

        <div class="col-sm-6 inputText">
            <div id="%%name%%" class="inputContainer">
                %%elements%%
            </div>
        </div>
        <div class="col-sm-2 form-opener">
            %%addLink%%
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-3 control-label"></label>
        <div class="col-sm-6">
            <div class="checkbox">
                <label>
                    <input type="checkbox" name="checkAll_%%name%%" id="checkAll_%%name%%" %%readonly%%>
                    [lang,commons_select_all,system]
                </label>
            </div>
        </div>
    </div>

    <script type='text/javascript'>
          $("input:checkbox[name='checkAll_%%name%%']").on('change', function() {
                var checkBoxes = $("input:checkbox[name^='%%name%%']").not("[disabled]");
                checkBoxes.prop('checked', $("input:checkbox[name='checkAll_%%name%%']").prop('checked')).trigger('change');
            });
    </script>
</input_checkboxarrayobjectlist>

<input_checkboxarrayobjectlist_row>
    <tbody>
        <tr data-systemid="%%systemid%%">
            <td class="listcheckbox"><input type="checkbox" name="%%name%%[%%systemid%%]" data-systemid="%%systemid%%" %%checked%% %%readonly%%></td>
            <td class="listimage">%%icon%%</td>
            <td class="title">
                <div class="small text-muted">%%path%%</div>
                %%title%%
            </td>
        </tr>
    </tbody>
</input_checkboxarrayobjectlist_row>

---------------------------------------------------------------------------------------------------------
-- MISC ELEMENTS ----------------------------------------------------------------------------------------
Used to fold elements / hide/unhide elements
<layout_folder>
<div id="%%id%%" class="contentFolder %%display%%">%%content%%</div>
</layout_folder>

A precent-beam to illustrate proportions
<percent_beam>
    <div class="progress">
        <div class="progress-bar %%animationClass%% active" role="progressbar" aria-valuenow="%%percent%%" aria-valuemin="0" aria-valuemax="100" style="width: %%percent%%%;">%%percent%%%</div>
    </div>
</percent_beam>

A fieldset to structure logical sections
<misc_fieldset>
<fieldset class="%%class%%" data-systemid="%%systemid%%"><legend>%%title%%</legend><div>%%content%%</div></fieldset>
</misc_fieldset>

<graph_container>
<div class="graphBox">%%imgsrc%%</div>
</graph_container>


<iframe_container>
    <div id="%%iframeid%%_loading" class="loadingContainer loadingContainerBackground"></div>
    <iframe src="%%iframesrc%%" id="%%iframeid%%" class="seamless" width="100%" height="100%" frameborder="0" seamless ></iframe>

    <script type='text/javascript'>
           $(document).ready(function(){
                var frame = $('iframe#%%iframeid%%');
                frame.load(function() {
                    $('.tab-content.fullHeight iframe').each(function() {

                        var frame = document.getElementById('%%iframeid%%');
                        innerDoc = (frame.contentDocument) ?
                            frame.contentDocument : frame.contentWindow.document;

                        var intHeight = (innerDoc.body.scrollHeight + 10);

                        if($(this).height() < intHeight) {
                            $(this).height(intHeight);
                        }
                    });
                });

            });
    </script>
</iframe_container>


---------------------------------------------------------------------------------------------------------
-- SPECIAL SECTIONS -------------------------------------------------------------------------------------

The login-Form is being displayed, when the user has to log in.
Needed Elements: %%error%%, %%form%%
<login_form>
<div class="alert alert-danger" id="loginError">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    <p>%%error%%</p>
</div>
%%form%%
<script type="text/javascript">
	if (navigator.cookieEnabled == false) {
	    document.getElementById("loginError").innerHTML = "%%loginCookiesInfo%%";
	}
        if ($('#loginError > p').html() == "") {
            $('#loginError').remove();
        }
</script>
<noscript><div class="alert alert-danger">%%loginJsInfo%%</div></noscript>
</login_form>

Part to display the login status, user is logged in
<logout_form>
<div class="dropdown userNotificationsDropdown">
    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
        <i class="fa fa-user" id="icon-user"><span class="badge badge-info" id="userNotificationsCount">-</span></i> <span class="username">%%name%%</span>
    </a>
    <ul class="dropdown-menu generalContextMenu" role="menu">
        <li class="dropdown-submenu">
            <a tabindex="-1" href="#" onclick="return false;"><i class='fa fa-envelope'></i> [lang,modul_titel,messaging]</a>
            <ul class="dropdown-menu sub-menu" id="messagingShortlist">
                <li><a>Loading...</a></li>
                <li class="divider"></li>
                <li><a href='#/messaging'><i class='fa fa-envelope'></i> [lang,action_show_all,messaging]</a></li>
            </ul>
        </li>

        <!-- messages will be inserted here -->
        <li class="divider"></li>
        <li class="dropdown-submenu">
            <a tabindex="-1" href="#" onclick="return false;"><i class='fa fa-tag'></i> [lang,modul_titel,tags]</a>
            <ul class="dropdown-menu sub-menu" id="tagsSubemenu">
                <li><a>Loading...</a></li>
                <li class="divider"></li>
                <li><a href='#/tags'><i class='fa fa-tag'></i> [lang,action_show_all,tags]</a></li>
            </ul>
        </li>
        <li class="divider"></li>
        <li><a href="%%dashboard%%"><i class='fa fa-home'></i> %%dashboardTitle%%</a></li>
        <li class="divider"></li>
        <li><a href="#" onclick="window.print();"><i class='fa fa-print'></i> %%printTitle%%</a></li>
        <li class="divider"></li>
        <li><a href="%%profile%%"><i class='fa fa-user'></i> %%profileTitle%%</a></li>
        <li class="divider"></li>
        <li><a href="%%logout%%"><i class="fa fa-power-off"></i> %%logoutTitle%%</a></li>
    </ul>
</div>
<script type="text/javascript">
      if(%%renderMessages%%) {
            $(function() {
                V4skin.messaging.properties = {
                    notification_title : '[lang,messaging_notification_title,messaging]',
                    notification_body : '[lang,messaging_notification_body,messaging]',
                    show_all : '[lang,action_show_all,messaging]'
                };

                $('#messagingShortlist').parent().on('mouseover', function(e) {
                    V4skin.messaging.pollMessages();
                    $('#messagingShortlist').parent().unbind('mouseover');

                });

                window.setTimeout(function() { Messaging.setPollingEnabled(true); }, 1000);
            });
        }
        else {
            $('#messagingShortlist').closest("li").hide();
        }

        if(%%renderTags%%) {
            $(function() {
                V4skin.properties.tags.show_all = '[lang,action_show_all,tags]';

                $('#tagsSubemenu').parent().on('mouseover', function(e) {
                    V4skin.initTagMenu();
                    $('#tagsSubemenu').parent().unbind('mouseover');
                });
            });
        }
        else {
            $('#tagsSubemenu').closest("li").hide();
        }
</script>
</logout_form>

Shown, wherever the attention of the user is needed
<warning_box>
    <div class="alert %%class%%">
        <a class="close" data-dismiss="alert" href="#">&times;</a>
        %%content%%
    </div>
</warning_box>

Used to print plain text
<text_row>
<p class="%%class%%">%%text%%</p>
</text_row>

Used to print plaintext in a form
<text_row_form>
    <div class="form-group">
        <div class="col-sm-3"></div>
        <div class="col-sm-6">
            <span class="help-block %%class%%">%%text%%</span>
        </div>
    </div>
</text_row_form>


---------------------------------------------------------------------------------------------------------
-- RIGHTS MANAGEMENT ------------------------------------------------------------------------------------

The following sections specify the layout of the rights-mgmt

<rights_form_header>
    <div>
        %%desc%% %%record%% <br />
        <a href="javascript:Permissions.toggleEmtpyRows('[lang,permissions_toggle_visible,system]', '[lang,permissions_toggle_hidden,system]', '#rightsForm tr');" id="rowToggleLink" class="rowsVisible">[lang,permissions_toggle_visible,system]</a><br /><br />
    </div>
</rights_form_header>

<rights_form_form>
    <table class="table admintable table-striped kajona-data-table">
        <thead>
        <tr class="">
            <th>&nbsp;</th>
            <th>%%title0%%</th>
            <th>%%title1%%</th>
            <th>%%title2%%</th>
            <th>%%title3%%</th>
            <th>%%title4%%</th>
            <th>%%title5%%</th>
            <th>%%title6%%</th>
            <th>%%title7%%</th>
            <th>%%title8%%</th>
            <th>%%title9%%</th>
        </tr>
        </thead>
        %%rows%%
    </table>
    <script type="text/javascript">
          $('table.kajona-data-table').floatThead({
                scrollingTop: $("body.dialogBody").length > 0 ? 0 : 70,
                useAbsolutePositioning: true
            });
    </script>
    %%inherit%%
</rights_form_form>

<rights_form_row>
	<tr>
		<td>%%group%%</td>
		<td>%%box0%%</td>
		<td>%%box1%%</td>
		<td>%%box2%%</td>
		<td>%%box3%%</td>
		<td>%%box4%%</td>
		<td>%%box5%%</td>
		<td>%%box6%%</td>
		<td>%%box7%%</td>
		<td>%%box8%%</td>
		<td>%%box9%%</td>
	</tr>
</rights_form_row>


<rights_form_inherit>
<div class="form-group">
    <label class="col-sm-3 control-label" for="%%name%%"></label>
    <div class="col-sm-6">
        <div class="checkbox">
            <label>
                    <input name="%%name%%" type="checkbox" id="%%name%%" value="1" onclick="this.blur();" onchange="Permissions.checkRightMatrix();" %%checked%% />
                %%title%%
            </label>
        </div>
    </div>
</div>
</rights_form_inherit>


---------------------------------------------------------------------------------------------------------
-- PATH NAVIGATION --------------------------------------------------------------------------------------

The following sections are used to display the path-navigations, e.g. used by the navigation module

<path_entry>
  <script type="text/javascript">Breadcrumb.appendLinkToPathNavigation(%%pathlink%%) ; </script>
</path_entry>

---------------------------------------------------------------------------------------------------------
-- CONTENT TOOLBARS -------------------------------------------------------------------------------------

Toolbar, prominent in the layout. Rendered to switch between action.
<contentToolbar_wrapper>
    <script type="text/javascript">  %%entries%% ; </script>
</contentToolbar_wrapper>

<contentToolbar_entry>
    ContentToolbar.registerContentToolbarEntry(new ContentToolbar.Entry('%%entry%%', '%%identifier%%', %%active%%));
</contentToolbar_entry>


Toolbar for the current record, rendered to quick-access the actions of the current record.
<contentActionToolbar_wrapper>
<div class="hidden toolbarContentContainer">%%content%%</div>
<script type="text/javascript">
ContentToolbar.registerRecordActions($('.toolbarContentContainer'));
</script>
</contentActionToolbar_wrapper>

---------------------------------------------------------------------------------------------------------
-- ERROR HANDLING ---------------------------------------------------------------------------------------

<error_container>
    <div class="alert %%errorclass%%">
        <a class="close" data-dismiss="alert" href="#">&times;</a>
        <h4 class="alert-heading">%%errorintro%%</h4>
        <ul>
            %%errorrows%%
        </ul>
    </div>
</error_container>

<error_row>
    <li>%%field_errortext%%</li>
</error_row>

---------------------------------------------------------------------------------------------------------
-- PREFORMATTED -----------------------------------------------------------------------------------------

Used to print pre-formatted text, e.g. log-file contents
<preformatted>
    <pre class="code pre-scrollable">%%pretext%%</pre>
</preformatted>

---------------------------------------------------------------------------------------------------------
-- PORTALEDITOR -----------------------------------------------------------------------------------------

The following section is the toolbar of the portaleditor, displayed at top of the page.
The following placeholders are provided by the system:
pe_status_page, pe_status_status, pe_status_autor, pe_status_time
pe_status_page_val, pe_status_status_val, pe_status_autor_val, pe_status_time_val
pe_iconbar, pe_disable
<pe_toolbar>

    <!-- KAJONA_BUILD_LESS_START -->
    <link href="_webpath_/[webpath,module_v4skin]/admin/skins/kajona_v4/less/bootstrap_pe.less?_system_browser_cachebuster_" rel="stylesheet/less">
    <script> less = { env:'development' }; </script>
    <script src="_webpath_/[webpath,module_v4skin]/admin/skins/kajona_v4/less/less.min.js"></script>
    <!-- KAJONA_BUILD_LESS_END -->


    <div class="kajona-pe-wrap">
        <div class="modal fade" id="peDialog">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div id="folderviewDialog_loading" class="peLoadingContainer loadingContainerBackground"></div>
                    <div class="modal-body" id="peDialog_content">
                        <!-- filled by js -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="kajona-pe-wrap">
        <div class="modal fade" id="delDialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h3 id="delDialog_title"><!-- filled by js --></h3>
                    </div>
                    <div class="modal-body" id="delDialog_content">
                        <!-- filled by js -->
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-default" data-dismiss="modal" id="delDialog_cancelButton">[lang,dialog_cancelButton,system]</a>
                        <a href="#" class="btn btn-default btn-primary" id="delDialog_confirmButton">confirm</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</pe_toolbar>

---------------------------------------------------------------------------------------------------------
-- QUICK HELP -------------------------------------------------------------------------------------------

<quickhelp>
    <script type="text/javascript">
        Quickhelp.setQuickhelp('%%title%%', '%%text%%');
    </script>
</quickhelp>

<quickhelp_button>
</quickhelp_button>

---------------------------------------------------------------------------------------------------------
-- PAGEVIEW ---------------------------------------------------------------------------------------------

<pageview_body>
    <div class="pager">
        <ul class="pagination">
            %%linkBackward%%
            %%pageList%%
            %%linkForward%%
            <li><span>%%nrOfElementsText%% %%nrOfElements%%</span></li>
        </ul>
    </div>
</pageview_body>

<pageview_link_forward>
<li>
    <a href="%%href%%">%%linkText%% &raquo;</a>
</li>
</pageview_link_forward>

<pageview_link_backward>
<li>
    <a href="%%href%%">&laquo; %%linkText%%</a>
</li>
</pageview_link_backward>

<pageview_page_list>
%%pageListItems%%
</pageview_page_list>

<pageview_list_item>
    <li data-kajona-pagenum="%%pageNr%%">
        <a href="%%href%%">%%pageNr%%</a>
    </li>
</pageview_list_item>

<pageview_list_item_active>
    <li data-kajona-pagenum="%%pageNr%%" class="active" >
        <a href="%%href%%" class="active">%%pageNr%%</a>
    </li>
</pageview_list_item_active>


---------------------------------------------------------------------------------------------------------
-- TREE VIEW --------------------------------------------------------------------------------------------

<tree>
    <div id="%%treeId%%" class="treeDiv"></div>
    <script type="text/javascript">

            Tree.toggleInitial('%%treeId%%');

            var jsTree = new Tree.jstree();
            jsTree.loadNodeDataUrl = "%%loadNodeDataUrl%%";
            jsTree.rootNodeSystemid = '%%rootNodeSystemid%%';
            jsTree.treeConfig = %%treeConfig%%;
            jsTree.treeId = '%%treeId%%';
            jsTree.treeviewExpanders = %%treeviewExpanders%%;
            jsTree.initiallySelectedNodes = %%initiallySelectedNodes%%;

            jsTree.initTree();
    </script>
</tree>

<treeview>
    <div class="row">
        <div class="col-md-4 treeViewColumn" data-kajona-treeid="%%treeId%%" >
            <div class="treeViewWrapper">
                %%treeContent%%
            </div>
        </div>
        <div class="col-md-8 treeViewContent" data-kajona-treeid="%%treeId%%">
            <div class=""><a href="#" id="%%treeId%%_toggle" onclick="" title="[lang,treeviewtoggle,system]" rel="tooltip"><i class="fa fa-bars"></i></a></div>
            %%sideContent%%
        </div>

        <script type='text/javascript'>
            $('#%%treeId%%_toggle').on('click', function(e) {
                Tree.toggleTreeView('%%treeId%%');
                e.preventDefault();
            })
        </script>
    </div>
</treeview>

The tag-wrapper is the section used to surround the list of tag.
Please make sure that the containers' id is named tagsWrapper_%%targetSystemid%%,
otherwise the JavaScript will fail!
<tags_wrapper>
    <div id="tagsLoading_%%targetSystemid%%" class="loadingContainer"></div>
    <div id="tagsWrapper_%%targetSystemid%%"></div>
    <script type="text/javascript">
            Tags.reloadTagList('%%targetSystemid%%', '%%attribute%%');
    </script>
</tags_wrapper>

<tags_tag>
    <span class="label label-default">%%tagname%%</span>
    <script type="text/javascript">
            Tooltip.addTooltip('#icon_%%strTagId%%');
    </script>
</tags_tag>

<tags_tag_delete>
    <span class="label label-default taglabel">%%tagname%% <a href="javascript:Tags.removeTag('%%strTagId%%', '%%strTargetSystemid%%', '%%strAttribute%%');"> %%strDelete%%</a> %%strFavorite%%</span>
    <script type="text/javascript">
            Tooltip.addTooltip($(".taglabel [rel='tooltip']"));
    </script>
</tags_tag_delete>


A tag-selector.
If you want to use ajax to load a list of proposals on entering a char,
place ajaxScript before the closing input_tagselector-tag.
<input_tagselector>

    <div class="form-group">
        <label for="%%name%%" class="col-sm-3 control-label">%%title%%</label>
        <div class="col-sm-6">
            <input type="text" id="%%name%%" name="%%name%%" value="%%value%%" class="form-control %%class%%">
            %%opener%%
        </div>
    </div>

%%ajaxScript%%
</input_tagselector>


<tooltip_text>
    <span title="%%tooltip%%" rel="tooltip">%%text%%</span>
</tooltip_text>


---------------------------------------------------------------------------------------------------------
-- MENU -------------------------------------------------------------------------------------------------
<contextmenu_wrapper>
    <div class="dropdown-menu generalContextMenu %%ddclass%%" role="menu">
        <ul>
            %%entries%%
        </ul>
    </div>
    <script type="text/javascript">
        $('.dropdown-menu .dropdown-submenu a').click(function (e) {
                e.stopPropagation();
            });
    </script>
</contextmenu_wrapper>

<contextmenu_entry>
    <li ><a href="%%elementLink%%" onclick="%%elementAction%%">%%elementName%%</a></li>
</contextmenu_entry>

<contextmenu_entry_full>
    <li >%%elementFullEntry%%</li>
</contextmenu_entry_full>

<contextmenu_divider_entry>
    <li class="divider"></li>
</contextmenu_divider_entry>

<contextmenu_submenucontainer_entry>
    <li class="dropdown-submenu" >
        <a href="%%elementLink%%" tabindex="-1">%%elementName%%</a>
        <ul class="dropdown-menu">
            %%entries%%
        </ul>
    </li>
</contextmenu_submenucontainer_entry>

<contextmenu_submenucontainer_entry_full>
    <li class="dropdown-submenu" >
        %%elementFullEntry%%
        <ul class="dropdown-menu">
            %%entries%%
        </ul>
    </li>
</contextmenu_submenucontainer_entry_full>


---------------------------------------------------------------------------------------------------------
-- BACKEND NAVIGATION -----------------------------------------------------------------------------------

<sitemap_wrapper>
      <div class="nav-header">
            %%aspectToggle%%
            [lang,commons_product_title,commons]
      </div>
    %%level%%
</sitemap_wrapper>


<sitemap_aspect_wrapper>
<div data-kajona-aspectid='%%aspectId%%' id="%%aspectId%%" class='%%class%% aspect-container panel-group'>
%%aspectContent%%
</div>

</sitemap_aspect_wrapper>

<sitemap_combined_entry_header>
    <a data-toggle="collapse" data-parent="#%%aspectId%%" href="#menu_%%systemid%%%%aspectId%%" rel="tooltip"
       title="%%moduleName%%" data-kajona-module="%%moduleTitle%%"
       onclick="ModuleNavigation.combinedActive();">
        <i class="fa %%faicon%%"></i>
    </a>
</sitemap_combined_entry_header>

<sitemap_combined_entry_body>
    <div id="menu_%%systemid%%%%aspectId%%" class="panel-collapse collapse" data-kajona-module="%%moduleTitle%%">
        <div class="panel-body">
            <ul>%%actions%%</ul>
        </div>
    </div>
</sitemap_combined_entry_body>


<sitemap_combined_entry_wrapper>
    <div class="panel panel-default panel-combined">
        <div class="panel-heading">
            <span class="linkcontainer collapsed">
                %%combined_header%%
            </span>
        </div>
        %%combined_body%%
    </div>
</sitemap_combined_entry_wrapper>


<sitemap_module_wrapper>
    <div class="panel panel-default">
        <div class="panel-heading">
            <a data-toggle="collapse" data-parent="#%%aspectId%%" href="#menu_%%systemid%%%%aspectId%%"
               data-kajona-module="%%moduleTitle%%" class="collapsed"
               onclick="ModuleNavigation.combinedInactive();">
                %%moduleName%%
            </a>
        </div>
        <div id="menu_%%systemid%%%%aspectId%%" class="panel-collapse collapse" data-kajona-module="%%moduleTitle%%">
            <div class="panel-body">
                <ul>%%actions%%</ul>
            </div>
        </div>
    </div>
</sitemap_module_wrapper>


<sitemap_action_entry>
    <li>%%action%%</li>
</sitemap_action_entry>

<sitemap_divider_entry>
<li class="divider"></li>
</sitemap_divider_entry>

<changelog_heatmap>
    <div class="chart-navigation pull-left"><a href="#" onclick="Changelog.loadPrevYear();return false;"><i class="kj-icon fa fa-arrow-left"></i></a></div>
    <div class="chart-navigation pull-right"><a href="#" onclick="Changelog.loadNextYear();return false;"><i class="kj-icon fa fa-arrow-right"></i></a></div>
    <div id='changelogTimeline' style='text-align:center;'></div>

    <script type="text/javascript">
            Changelog.lang = %%strLang%%;
            Changelog.systemId = "%%strSystemId%%";
            Changelog.format = Util.transformDateFormat('%%strDateFormat%%', "momentjs");
            Changelog.now = moment().endOf('day').toDate();
            Changelog.yearAgo = moment().startOf('day').subtract(1, 'year').toDate();
            Changelog.selectColumn("right");
            Changelog.loadChartData();

            Changelog.loadDate("%%strSystemId%%", "%%strLeftDate%%", "left", function(){
                Changelog.loadDate("%%strSystemId%%", "%%strRightDate%%", "right", Changelog.compareTable);
            });
    </script>
</changelog_heatmap>

<js_action_button>
<button type="button" class="btn" style="background-color:transparent; border: none" onclick="%%callback%%"><span style="margin-right: 5px">%%icon%%</span>%%label%%</button>
</js_action_button>
