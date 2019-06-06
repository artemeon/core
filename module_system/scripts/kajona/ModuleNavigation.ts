import $ from 'jquery'
import Ajax from './Ajax'

class ModuleNavigation {
    public static combinedActive () {
        $('#moduleNavigation')
            .find('.panel .linkcontainer')
            .removeClass('collapsed')
    }

    public static combinedInactive () {
        $('#moduleNavigation')
            .find('.panel .linkcontainer')
            .addClass('collapsed')
    }

    /**
     * @deprecated
     * @param strModule
     */
    public static setModuleActive (strModule: string) {
        var $moduleNavigation = $('#moduleNavigation')
        $moduleNavigation.find('a.active').removeClass('active')
        $moduleNavigation.find('.linkcontainer.active').removeClass('active')

        if (
            $(
                '.panel-combined .collapse[data-kajona-module="' +
                    strModule +
                    '"]'
            ).length !== 0
        ) {
            // is combined
            $moduleNavigation.find('.panel .linkcontainer').addClass('active')
        } else {
            // default: not combined
            $("a[data-kajona-module='" + strModule + "']").addClass('active')

            // see if the aspect needs to be switched
            var $objAspect = $(
                '[data-kajona-module="' + strModule + '"]'
            ).closest('.aspect-container')
            if ($objAspect.hasClass('hidden')) {
                this.switchAspect($objAspect.data('kajona-aspectid'))
            }
        }
    }

    public static loadNavigation (strAspect: string) {
        if (!strAspect) {
            strAspect = ''
        }
        Ajax.loadUrlToElement(
            '#moduleNavigation',
            '/xml.php?admin=1&module=v4skin&action=getBackendNavi&aspect=' +
                (strAspect || '')
        )
    }

    /**
     * @deprecated
     */
    public static switchAspect (strTargetId: string) {
        $('.mainnavi-container .aspect-container').addClass('hidden')
        $(
            '.mainnavi-container .aspect-container[data-kajona-aspectid=' +
                strTargetId +
                ']'
        ).removeClass('hidden')
    }
}
;(<any>window).ModuleNavigation = ModuleNavigation
export default ModuleNavigation
