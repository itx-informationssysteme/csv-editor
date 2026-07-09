<?php

/* @phpstan-ignore-next-line */
$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CSV Editor',
    'version' => '2.0.0',
    'description' => 'Backend CSV editor',
    'constraints' => [
        'depends' => [
            'typo3' => '14.3.0-14.3.99',
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'Itx\\CsvEditor\\' => 'Classes/',
        ],
    ],
];
