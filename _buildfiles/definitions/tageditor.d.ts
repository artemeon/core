/// <reference types="jquery"/>

interface TagEditorOptions {
    initialTags: Array<string>,
    forceLowercase: boolean,
    sortable: boolean,
    autocomplete: JQueryUI.AutocompleteOptions,
    onChange: (field: any, editor: TagEditorOptions, tags: Array<string>) => void;
    beforeTagSave: (field: any, editor: TagEditorOptions, tags: Array<string>, tag: string, val: string) => boolean;
    beforeTagDelete: (field: any, editor: TagEditorOptions, tags: Array<string>, val: string) => void;
}

interface JQuery {
    tagEditor(options: TagEditorOptions): JQuery;
}
