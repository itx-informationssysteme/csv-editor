<?php

/* @phpstan-ignore-next-line */
$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CSV Editor',
    'version' => '1.0.0',
    'description' => 'Backend CSV editor',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.4.99',
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'Itx\\CsvEditor\\' => 'Classes/',
        ],
    ],
];
