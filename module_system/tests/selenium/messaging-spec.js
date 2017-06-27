"use strict";

const SeleniumUtil = requireHelper('/util/SeleniumUtil.js');
const SeleniumWaitHelper = requireHelper('/util/SeleniumWaitHelper.js');


describe('module_messaging', function() {

    it('test list', function() {
        SeleniumUtil.gotToUrl('index.php?admin=1&module=messaging&action=list');

        element.all(by.css('.actions')).last().$('a').click();

        SeleniumUtil.switchToModalDialog();

        // enter a new message to the form
        browser.driver.findElement(by.id('messaging_user')).sendKeys('test');

        // select user from autocomplete
        browser.driver.wait(protractor.until.elementLocated(by.css('.ui-autocomplete .ui-menu-item')), 5000);
        browser.driver.findElement(by.css('.ui-autocomplete .ui-menu-item')).click();

        browser.driver.findElement(by.id('messaging_title')).sendKeys('foo');
        browser.driver.findElement(by.id('messaging_body')).sendKeys('bar');
        browser.driver.findElement(by.css('button[type="submit"]')).click();

        expect(browser.driver.findElement(by.id('content')).getText()).toMatch('Die Nachricht wurde erfolgreich verschickt.');

        browser.driver.findElement(by.css('button[type="submit"]')).click();
        browser.driver.switchTo().defaultContent();
    });

    it('provides config page', function() {

        let mailConfigUrl = "index.php?admin=1&module=messaging&action=config";
        let enableInputLocator = By.id('Kajona-Packagemanager-System-Messageproviders-MessageproviderPackageupdate_enabled');
        let mailInputLocator = By.id('Kajona-Packagemanager-System-Messageproviders-MessageproviderPackageupdate_bymail');
        let mailButtonLocator = By.css('.bootstrap-switch-id-Kajona-Packagemanager-System-Messageproviders-MessageproviderPackageupdate_bymail');

        SeleniumUtil.gotToUrl(mailConfigUrl);

        // check the default values
        SeleniumWaitHelper.getElementWhenPresent(SeleniumUtil.getWebDriver(), mailInputLocator);
        expect(browser.driver.findElement(enableInputLocator).getAttribute('checked')).not.toBe(null);
        expect(browser.driver.findElement(mailInputLocator).getAttribute('checked')).toBe(null);

        browser.driver.wait(protractor.until.elementLocated(mailButtonLocator), 5000);

        // click the enable button
        browser.driver.findElement(mailButtonLocator).click();
        expect(browser.driver.findElement(mailInputLocator).getAttribute('checked')).not.toBe(null);

        // refresh
        SeleniumUtil.gotToUrl(mailConfigUrl);

        // and revalidate if the ajax request worked as specified
        SeleniumWaitHelper.getElementWhenPresent(SeleniumUtil.getWebDriver(), mailInputLocator);
        expect(browser.driver.findElement(enableInputLocator).getAttribute('checked')).not.toBe(null);
        expect(browser.driver.findElement(mailInputLocator).getAttribute('checked')).not.toBe(null);
    });

});
