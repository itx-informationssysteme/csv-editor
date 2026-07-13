<?php

/* @phpstan-ignore-next-line */
$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CSV Editor',
    'version' => '1.1.0',
    'description' => 'Backend CSV editor',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-14.99.99',
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'Itx\\CsvEditor\\' => 'Classes/',
        ],
    ],
];
