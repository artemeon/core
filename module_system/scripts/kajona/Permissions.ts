import $ from 'jquery'
import Ajax from './Ajax'

interface Response {
    bitInherited: boolean
    arrConfigs: Array<string>
}

/**
 * Little helper function for the system permissions matrix
 */
class Permissions {
    public static submitForm () {
        var objResponse: Response = {
            bitInherited: $('#inherit').is(':checked'),
            arrConfigs: []
        }

        $('.core-component-rights tbody > tr')
            .find('input:checked')
            .each(function () {
                objResponse.arrConfigs.push($(this).attr('id'))
            })

        // disable submit button
        $('.savechanges').addClass('processing')
        $('.savechanges').prop('disabled', true)

        $.ajax({
            url:
                KAJONA_WEBPATH +
                '/xml.php?admin=1&module=right&action=saveRights&systemid=' +
                $('#systemid').val(),
            type: 'POST',
            data: JSON.stringify(objResponse),
            contentType: 'application/json',
            dataType: 'json'
        }).done(function (data) {
            // enable submit button
            $('.savechanges').removeClass('processing')
            $('.savechanges').prop('disabled', false)

            // load rights
            Permissions.loadRights()
        })

        return false
    }

    public static toggleInherit () {
        $('.core-component-rights')
            .find("input[type='checkbox']")
            .each(function () {
                $(this).prop('disabled', Permissions.isInherited())
                $('#group_add').prop('disabled', Permissions.isInherited())

                $('.core-component-rights')
                    .find('.fa-trash-o')
                    .each(function () {
                        if (Permissions.isInherited()) {
                            $(this).addClass('text-muted')
                        } else {
                            $(this).removeClass('text-muted')
                        }
                    })
            })
    }

    public static isInherited () {
        return $('#inherit').prop('checked')
    }

    public static addGroup (groupId: string) {
        if (!groupId) {
            return
        }

        if (this.isInherited()) {
            return
        }

        if (this.hasGroup(groupId)) {
            return
        }

        var groupName = $('#group_add').val()

        var row = ''
        row += "<tr data-groupid='" + groupId + "'>"
        row +=
            '<td><a href="#" onclick="Permissions.removeGroup(\'' +
            groupId +
            '\',this);return false;"><i class="kj-icon fa fa-trash-o" ></i></a></td>'
        row += '<td>' + groupName + '</td>'

        $('.core-component-rights thead > tr > th').each(function () {
            var right = $(this).data('right')
            if (right) {
                row +=
                    '<td><input rel="tooltip" type="checkbox" name="' +
                    right +
                    ',' +
                    groupId +
                    '" id="' +
                    right +
                    ',' +
                    groupId +
                    '" value="1"></td>'
            }
        })

        row += '</tr>'
        $('.core-component-rights')
            .find('tbody')
            .append(row)

        setTimeout(function () {
            $('#group_add').val('')
            $('#group_add_id').val('')
        }, 100)
    }

    public static removeGroup (groupId: string, el: string) {
        if (this.isInherited()) {
            return
        }

        $(el)
            .closest('tr')
            .remove()
    }

    public static hasGroup (groupId: string) {
        return (
            $(
                ".core-component-rights tbody > tr[data-groupid='" +
                    groupId +
                    "']"
            ).length > 0
        )
    }

    public static loadRights () {
        Ajax.loadUrlToElement(
            '#rightsContainer',
            KAJONA_WEBPATH +
                '/xml.php?module=right&action=loadRights&systemid=' +
                $('#systemid').val() +
                '&folderview=1',
            '',
            true,
            'GET',
            function () {
                Permissions.toggleInherit()
            }
        )
    }
}
;(<any>window).Permissions = Permissions
export default Permissions
