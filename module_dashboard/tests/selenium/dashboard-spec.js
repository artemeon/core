"use strict";

const SeleniumUtil = requireHelper('/util/SeleniumUtil.js');

describe('module_dashboard', function() {

    it('test list', async function() {
        await SeleniumUtil.gotToUrl('index.php?admin=1&module=dashboard&action=list');
    });

});
