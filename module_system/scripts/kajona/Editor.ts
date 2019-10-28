import pell from 'pell'
import Lang from './Lang'

class Editor {
    public static init(name: string) {
        // Initialize pell on an HTMLElement
        let bold: string
        let italic: string
        let underline: string
        let strikeThrough: string
        let heading1: string
        let heading2: string
        let orderedList: string
        let unorderedList: string
        let horizontalLine: string

        const queryCommandState = (command) => document.queryCommandState(command)

        Lang.fetchSingleProperty(
            'system',
            'wysiwyg_bold',
            (value: string) => {
                bold = value
            },
        )
        Lang.fetchSingleProperty(
            'system',
            'wysiwyg_italic',
            (value: string) => {
                italic = value
            },
        )
        Lang.fetchSingleProperty(
            'system',
            'wysiwyg_underline',
            (value: string) => {
                underline = value
            },
        )
        Lang.fetchSingleProperty(
            'system',
            'wysiwyg_strike_through',
            (value: string) => {
                strikeThrough = value
            },
        )
        Lang.fetchSingleProperty(
            'system',
            'wysiwyg_heading1',
            (value: string) => {
                heading1 = value
            },
        )
        Lang.fetchSingleProperty(
            'system',
            'wysiwyg_heading2',
            (value: string) => {
                heading2 = value
            },
        )
        Lang.fetchSingleProperty(
            'system',
            'wysiwyg_ordered_list',
            (value: string) => {
                orderedList = value
            },
        )
        Lang.fetchSingleProperty(
            'system',
            'wysiwyg_unordered_list',
            (value: string) => {
                unorderedList = value
            },
        )
        Lang.fetchSingleProperty(
            'system',
            'wysiwyg_horizontal_line',
            (value: string) => {
                horizontalLine = value
            },
        )

        pell.init({
            element: document.getElementById(`${name}_pell`),
            onChange: (html) => {
                (<HTMLInputElement>document.getElementById(name)).innerHTML = html
            },
            defaultParagraphSeparator: 'br',
            actions: [
                {
                    icon: '<b>B</b>',
                    title: bold,
                    state: () => queryCommandState('bold'),
                    result: () => pell.exec('bold'),
                },
                {
                    icon: '<i>I</i>',
                    title: italic,
                    state: () => queryCommandState('italic'),
                    result: () => pell.exec('italic'),
                },
                {
                    icon: '<u>U</u>',
                    title: underline,
                    state: () => queryCommandState('underline'),
                    result: () => pell.exec('underline'),
                },
                {
                    icon: '<strike>S</strike>',
                    title: strikeThrough,
                    state: () => queryCommandState('strikeThrough'),
                    result: () => pell.exec('strikeThrough'),
                },
                {
                    icon: '<b>H<sub>1</sub></b>',
                    title: heading1,
                    result: () => pell.exec('formatBlock', '<h1>'),
                },
                {
                    icon: '<b>H<sub>2</sub></b>',
                    title: heading2,
                    result: () => pell.exec('formatBlock', '<h2>'),
                },
                {
                    icon: '&#35;',
                    title: orderedList,
                    state: () => queryCommandState('insertOrderedList'),
                    result: () => pell.exec('insertOrderedList'),
                },
                {
                    icon: '&#8226;',
                    title: unorderedList,
                    state: () => queryCommandState('insertUnorderedList'),
                    result: () => pell.exec('insertUnorderedList'),
                },
                {
                    icon: '&#8213;',
                    title: horizontalLine,
                    state: () => queryCommandState('insertHorizontalRule'),
                    result: () => pell.exec('insertHorizontalRule'),
                },
            ],

            classes: {
                actionbar: 'pell-actionbar',
                button: 'pell-button',
                content: 'pell-content',
                selected: 'pell-button-selected',
            },
        })
    }

    public static setContent(name, content) {
        document.getElementById(`${name}_pell`).addEventListener('paste', (e) => {
            e.stopPropagation()
            e.preventDefault()
            let clipText
            if (e.clipboardData !== undefined) {
                clipText = e.clipboardData.getData('text/plain')
                pell.exec('insertText', clipText)
            } else {
                clipText = (window as any).clipboardData.getData('text')
                pell.exec('paste', clipText)
            }
        })

        const decodeHTML = function (html) {
            const txt = document.createElement('textarea')
            txt.innerHTML = html
            return txt.value
        }
        const decoded = decodeHTML(content);
        (<HTMLInputElement>document.getElementById(`${name}_pell`).getElementsByClassName('pell-content')[0]).innerHTML = decoded;
        (<HTMLInputElement>document.getElementById(name)).innerHTML = content

        document.getElementById(`${name}_pell`).getElementsByClassName('pell-content')[0].classList.add('form-control')
        const $objInput = $(`#${name}`)
        $objInput.on('kajona.forms.mandatoryAdded', () => {
            document.getElementById(`${name}_pell`).getElementsByClassName('pell-content')[0].classList.add('mandatoryFormElement')
        })
    }
}
(<any>window).Editor = Editor
export default Editor
