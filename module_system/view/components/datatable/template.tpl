

data list header. Used to open a table to print data. use css fencing
<datalist_header>
    <table class="core-component-datatable table table-striped table-condensed kajona-data-table %%cssaddon%%">
</datalist_header>

<datalist_header_tbody>
    <table class="core-component-datatable table table-striped-tbody table-condensed kajona-data-table %%cssaddon%%">
</datalist_header_tbody>

data list footer. at the bottom of the datatable
<datalist_footer>
    </table>
    <script type="text/javascript">
           $('table.core-component-datatable:not(.kajona-data-table-ignore-floatthread)').floatThead({
                scrollingTop: $("body.dialogBody").length > 0 ? 0 : 70,
                useAbsolutePositioning: true
            });
    </script>
</datalist_footer>

One Column in a row (header record) - the header, the content, the footer
<datalist_column_head_header>
    <thead><tr>
</datalist_column_head_header>

<datalist_column_head>
    <th class="%%class%%" %%addons%%>%%value%%</th>
</datalist_column_head>

<datalist_column_head_footer>
    </tr></thead>
</datalist_column_head_footer>

One Column in a row (data record) - the header, the content, the footer, providing the option of two styles
<datalist_column_header>
    <tr data-systemid="%%systemid%%">
</datalist_column_header>

<datalist_column_header_tbody>
    <tbody>
    <tr data-systemid="%%systemid%%">
</datalist_column_header_tbody>

<datalist_column>
    <td class="%%class%%">%%value%%</td>
</datalist_column>

<datalist_column_footer>
    </tr>
</datalist_column_footer>

<datalist_column_footer_tbody>
    </tr>
    </tbody>
</datalist_column_footer_tbody>
