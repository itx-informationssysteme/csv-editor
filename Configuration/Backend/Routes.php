<?php

declare(strict_types=1);

use Itx\CsvEditor\Controller\EditCsvController;

return [
    'csv_editor_edit' => [
        'path' => '/typo3/csv/edit',
        'target' => EditCsvController::class . '::handleRequest',
    ],
];
