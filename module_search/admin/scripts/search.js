//   (c) 2013-2015 by Kajona, www.kajona.de
//       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt
//       $Id$

if (!KAJONA) {
    alert('load kajona.js before!');
}


KAJONA.admin.search = {

    /**
     * Enables or disables the field "search_formfiltermodules" depending on the field "search_filter_all".
     *
     */
    switchFilterAllModules : function() {
        var checkBox = $($('#search_filter_all')[0]);
        if(checkBox.is(':checked')) {
            $("#search_formfiltermodules").prop("disabled", true)
        }
        else {
            $("#search_formfiltermodules").prop("disabled", false);
        }
    }
}