"use strict";

/**
 * require statements
 */
var SeleniumUtil = require('../util/SeleniumUtil.js');
var BasePage = require('../pageobject/base/BasePage.js');
var SeleniumWaitHelper = require('../util/SeleniumWaitHelper.js');

/**
 *
 */
class TopMenu extends BasePage {

    constructor() {
        super();

        //constants
        this._TOPMENU_SEARCHBOX_INPUT = "//*[@id='globalSearchInput']";
        this._TOPMENU_SEARCHBOX_LNK_SEARCHRESULTS = "//*[@class='detailedResults ui-menu-item']/a";

        this._TOPMENU_USERMENU = "//*[@class='dropdown userNotificationsDropdown']";
        this._TOPMENU_USERMENU_MESSAGES = this._TOPMENU_USERMENU + "/ul/li[1]/a";
        this._TOPMENU_USERMENU_TAGS = this._TOPMENU_USERMENU + "/ul/li[2]/a";
        this._TOPMENU_USERMENU_HELP = this._TOPMENU_USERMENU + "/ul/li[3]/a";
        this._TOPMENU_USERMENU_MESSAGES_SUBMENU = this._TOPMENU_USERMENU + "//*[@id='messagingShortlist']";
        this._TOPMENU_USERMENU_MESSAGES_LNK_SHOWALLMESAGES = this._TOPMENU_USERMENU_MESSAGES_SUBMENU + "/li[last()]/a";
        this._TOPMENU_USERMENU_LOGOUT_LNK = this._TOPMENU_USERMENU + "/ul/li[last()]/a";

        this._TOPMENU_ASPECT_SELECTBOX = "//*[@class='navbar navbar-fixed-top']/div[1]/div/div/div[2]/select";


        //properties
        /** @type {!webdriver.WebElement} */
        this.element_searchBox = this.webDriver.findElement(By.xpath(this._TOPMENU_SEARCHBOX_INPUT));
        /** @type {!webdriver.WebElement} */
        this.element_lnkUserMenu = this.webDriver.findElement(By.xpath(this._TOPMENU_USERMENU));
        /** @type {!webdriver.WebElement} */
        this.element_lnkUserMenuLogOut = this.webDriver.findElement(By.xpath(this._TOPMENU_USERMENU_LOGOUT_LNK));
    }

    /**
     *
     * @param {string} strSearchTerm
     */
    search(strSearchTerm) {
        this.element_searchBox.sendKeys(strSearchTerm);
        // SeleniumWaitHelper.waitForElementUntilPresent(driver, By.xpath(Constants.TOPMENU_SEARCHBOX_LNK_SEARCHRESULTS), 10);
        // lnkShowAllSearchResults.click();
    };

    /**
     * Logs out.
     *
     * @returns {Promise<void>}
     */
    logout() {
        var context = this;

        return this.showUserMenu()
            .then(function () {
                return context.element_lnkUserMenuLogOut
            })
            .then(function (el) {
                return el.click();
            });
    }

    /**
     * Displays the user menu.
     *
     * @returns {webdriver.promise.Promise.<void>}
     */
    showUserMenu() {
        return this.element_lnkUserMenu.then(function (element) {
            return SeleniumUtil.moveToElement(element);
        });
    }
}

/** @type {TopMenu} */
module.exports = TopMenu;
