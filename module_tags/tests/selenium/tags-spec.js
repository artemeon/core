"use strict";

const SeleniumUtil = requireHelper('/util/SeleniumUtil.js');

describe('module_tags', function() {

    it('test list', async function() {
        await SeleniumUtil.gotToUrl('index.php?admin=1&module=tags&action=list');
    });

});
