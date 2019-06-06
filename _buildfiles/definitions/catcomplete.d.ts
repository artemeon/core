/// <reference types="jquery"/>

interface Messages {
    noResults: string;
    results: Function;
}

interface CatCompleteOptions extends JQueryUI.AutocompleteOptions {
    messages: Messages;
}

interface JQuery {
    catcomplete(options: CatCompleteOptions): JQuery;
}
