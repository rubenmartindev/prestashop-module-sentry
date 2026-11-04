<?php

$dbPrefix = _DB_PREFIX_;
$moduleName = 'extsentry';
$sql = [];

foreach ($sql as $query) {
    if (!Db::getInstance()->execute($query)) {
        return false;
    }
}

return true;
