<?php

namespace Kajona\Debugging\Debug;


use Kajona\System\System\Database;
use Kajona\System\System\IdGenerator;

Database::getInstance()->transactionBegin();

sleep(10);

$generator = new IdGenerator();

echo $generator::generateNextId('abcd');

sleep(10);

Database::getInstance()->transactionCommit();