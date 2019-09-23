import pell from 'pell'
import Lang from './Lang'

class Editor {

    public static init(name: string, content) {
        // Initialize pell on an HTMLElement
        let bold: string;
        let italic: string;
        let underline:string;
        let strike_through: string;
        let heading1: string;
        let heading2: string;
        let ordered_list: string;
        let unordered_list: string;
        let horizontal_line: string;

        var $objInput = $('#' + name);
        $objInput.on('kajona.forms.mandatoryAdded', function() {
            document.getElementById(name + "_pell").getElementsByClassName( 'pell-content' )[0].classList.add("mandatoryFormElement");
        });

        const queryCommandState = command => document.queryCommandState(command);

        Lang.fetchSingleProperty(
            'system',
            'wysiwyg_bold',
            function (value: string) {
                bold  = value
            });
        Lang.fetchSingleProperty(
            'system',
            'wysiwyg_italic',
            function (value: string) {
                italic  = value
            });
        Lang.fetchSingleProperty(
            'system',
            'wysiwyg_underline',
            function (value: string) {
                underline  = value
            });
        Lang.fetchSingleProperty(
            'system',
            'wysiwyg_strike_through',
            function (value: string) {
                strike_through  = value
            });
        Lang.fetchSingleProperty(
            'system',
            'wysiwyg_heading1',
            function (value: string) {
                heading1  = value
            });
        Lang.fetchSingleProperty(
            'system',
            'wysiwyg_heading2',
            function (value: string) {
                italic  = value
            });
        Lang.fetchSingleProperty(
            'system',
            'wysiwyg_ordered_list',
            function (value: string) {
                ordered_list  = value
            });
        Lang.fetchSingleProperty(
            'system',
            'wysiwyg_unordered_list',
            function (value: string) {
                unordered_list  = value
            });
        Lang.fetchSingleProperty(
            'system',
            'wysiwyg_horizontal_line',
            function (value: string) {
                horizontal_line  = value
            })

        pell.init({
            element: document.getElementById(name + "_pell"),
            onChange: html => {
                (<HTMLInputElement>document.getElementById(name)).innerHTML = html;
            },
            defaultParagraphSeparator: 'br',
            actions: [
               {
                    icon: '<b>B</b>',
                    title: bold,
                    state: () => queryCommandState('bold'),
                    result: () => pell.exec('bold')
                },
                {
                    icon: '<i>I</i>',
                    title: italic,
                    state: () => queryCommandState('italic'),
                    result: () => pell.exec('italic')
                },
                {
                    icon: '<u>U</u>',
                    title: underline,
                    state: () => queryCommandState('underline'),
                    result: () => pell.exec('underline')
                },
                {
                    icon: '<strike>S</strike>',
                    title: strike_through,
                    state: () => queryCommandState('strikeThrough'),
                    result: () => pell.exec('strikeThrough')
                },
                {
                    icon: '<b>H<sub>1</sub></b>',
                    title: heading1,
                    result: () => pell.exec('formatBlock', '<h1>')
                },
                {
                    icon: '<b>H<sub>2</sub></b>',
                    title: heading2,
                    result: () => pell.exec('formatBlock', '<h2>')
                },
                {
                    icon: '&#35;',
                    title: ordered_list,
                    state: () => queryCommandState('insertOrderedList'),
                    result: () => pell.exec('insertOrderedList')
                },
                {
                    icon: '&#8226;',
                    title: unordered_list,
                    state: () => queryCommandState('insertUnorderedList'),
                    result: () => pell.exec('insertUnorderedList')
                },
                {
                    icon: '&#8213;',
                    title: horizontal_line,
                    state: () => queryCommandState('insertHorizontalRule'),
                    result: () => pell.exec('insertHorizontalRule')
                },
            ],

            classes: {
                actionbar: 'pell-actionbar',
                button: 'pell-button',
                content: 'pell-content',
                selected: 'pell-button-selected'
            }
        })
    }
    public  static setContent(name, content) {
        var decodeHTML = function (html) {
            var txt = document.createElement('textarea');
            txt.innerHTML = html;
            return txt.value;
        };
        var decoded = decodeHTML(content);
        (<HTMLInputElement>document.getElementById(name + "_pell").getElementsByClassName( 'pell-content' )[0]).innerHTML = decoded;
        (<HTMLInputElement>document.getElementById(name)).innerHTML = content;
    }

}
;(<any>window).Editor = Editor
export default Editor