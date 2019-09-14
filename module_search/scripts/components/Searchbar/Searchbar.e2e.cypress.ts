/// <reference path="../../../../_buildfiles/node_modules/cypress/types/index.d.ts" />
/// <reference path="../../../../_buildfiles/definitions/kajona.d.ts" />
beforeEach(() => {
    cy.visit('http://localhost/agp-core-project')
    cy.get('input#name').type('dha')
    cy.get('input#passwort').type('admin0815')
    cy.get('button').click()
})
describe('Searchbar Component', function () {
    it('open the searchbar Modal', function () {
        cy.get('input.search-query:first').click()
    })
    it('close the searchbar Modal', function () {
        cy.get('input.search-query:first').click()
        cy.get('div.modal.fade.in').click()
    })
    it('Search and display search results', () => {
        cy.get('input.search-query:first').click()
        cy.get('input#searchbarInput').type('admin')
    })
    it('module filter', () => {
        cy.get('input.search-query:first').click()
        cy.get('input#searchbarInput').type('admin')
        cy.get('div#toggleFilters:first').click()
        cy.get('div.multiselect__select').click()
        cy.get('li.multiselect__element').eq(1).click()
        cy.get('li.multiselect__element').eq(2).click()
        cy.get('div.multiselect__select').click()
    })
})
