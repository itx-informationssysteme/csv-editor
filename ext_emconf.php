<?php

/* @phpstan-ignore-next-line */
$EM_CONF[$_EXTKEY] = [
    'title' => 'csv_editor',
    'version' => '1.0.0',
    'description' => 'Backend CSV editor for the bwregiobus source file',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-11.5.99',
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'Itx\\CsvEditor\\' => 'Classes/',
        ],
    ],
];

