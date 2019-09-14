import { storiesOf } from '@storybook/vue'
import Autocomplete from './Autocomplete.vue'
import { action } from '@storybook/addon-actions'
import { withKnobs, text, boolean, object } from '@storybook/addon-knobs'

import 'jquery-ui.custom'

const stories = storiesOf('Autocomplete', module)
stories.addDecorator(withKnobs)
stories.add('Autocomplete Component with actions and props', () => {
    return {
        components: { Autocomplete },
        template: `
        <Autocomplete :label="label" :data="data" :loading="loading" :tooltip="tooltip" @select="onSelect"
        @delete="onDelete"
        @input="onInput"/>
        `,
        props: {
            label: { default: text('label', 'Label') },
            tooltip: {
                default: text('tooltip', 'Delete')
            },
            data: {
                default: object('data', [

                    { label: 'User 1',
                        title: 'User 1',
                        value: '1'
                    },
                    {
                        label: 'User 2',
                        title: 'User 2',
                        value: '1'
                    },
                    {
                        label: 'User 2',
                        title: 'User 2',
                        value: '2'
                    }
                ])
            },
            loading: { default: boolean('loading', false) }
        },
        methods: {
            onDelete: action('Emit delete'),
            onSelect: action('Emit select'),
            onInput: action('Emit input')
        }

    }
}, {
    notes: `
Please implement the AutocompleteInterface in each container-component using the Autocomplete Component. The parsedAutoCompleteData method should return an Array
from type AutocompleteItem :
AutocompleteItem {
    title : string,
    label : string,
    value : string | Number
}

    `
})
