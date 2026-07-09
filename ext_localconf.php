<?php

declare(strict_types=1);

defined('TYPO3') or die();

$existingTextExt = (string)($GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'] ?? '');
$textExtParts = array_filter(array_map('trim', explode(',', $existingTextExt)));
if (!in_array('csv', $textExtParts, true)) {
    $textExtParts[] = 'csv';
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'] = implode(',', $textExtParts);
}
