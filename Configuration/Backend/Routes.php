<?php

declare(strict_types=1);

use Itx\CsvEditor\Controller\EditCsvController;

return [
    'csv_editor_edit' => [
        'path' => '/csv-editor/edit',
        'target' => EditCsvController::class . '::handleRequest',
    ],
];
