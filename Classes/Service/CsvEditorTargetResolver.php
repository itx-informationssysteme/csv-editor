<?php

declare(strict_types=1);

namespace Itx\CsvEditor\Service;

use TYPO3\CMS\Core\Resource\FileInterface;

class CsvEditorTargetResolver
{
    private const ALLOWED_IDENTIFIER_SUFFIX = '.csv';

    public function isAllowedFile(FileInterface $file): bool
    {
        if (strtolower($file->getExtension()) !== 'csv') {
            return false;
        }

        $identifier = '/' . ltrim($file->getIdentifier(), '/');
        return str_ends_with($identifier, self::ALLOWED_IDENTIFIER_SUFFIX);
    }

    public function getAllowedIdentifierSuffix(): string
    {
        return self::ALLOWED_IDENTIFIER_SUFFIX;
    }
}
