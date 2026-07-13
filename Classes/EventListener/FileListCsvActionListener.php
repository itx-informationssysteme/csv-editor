<?php

declare(strict_types=1);

namespace Itx\CsvEditor\EventListener;

use Itx\CsvEditor\Service\CsvEditorTargetResolver;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Filelist\Event\ProcessFileListActionsEvent;

class FileListCsvActionListener
{
    public function __construct(
        private readonly UriBuilder $uriBuilder,
        private readonly IconFactory $iconFactory,
        private readonly CsvEditorTargetResolver $targetResolver
    ) {}

    public function __invoke(ProcessFileListActionsEvent $event): void
    {
        if (!$event->isFile()) {
            return;
        }

        $resource = $event->getResource();
        if (!$resource instanceof File) {
            return;
        }

        if (!$this->targetResolver->isAllowedFile($resource)) {
            return;
        }

        if (!$resource->checkActionPermission('write')) {
            return;
        }

        $returnUrl = (string)$this->uriBuilder->buildUriFromRoute('file_FilelistList', [
            'id' => $resource->getParentFolder()->getCombinedIdentifier(),
        ]);
        $editUrl = (string)$this->uriBuilder->buildUriFromRoute('csv_editor_edit', [
            'target' => $resource->getCombinedIdentifier(),
            'returnUrl' => $returnUrl,
        ]);

        $actionItems = $event->getActionItems();
        $actionItems['edit'] = sprintf(
            '<a class="btn btn-default" href="%s" title="%s">%s</a>',
            htmlspecialchars($editUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($this->trans('tooltip.editAsTable'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $this->iconFactory->getIcon('actions-page-open', Icon::SIZE_SMALL)->render()
        );

        $event->setActionItems($actionItems);
    }

    private function trans(string $key): string
    {
        return $this->getLanguageService()->sL('LLL:EXT:csv_editor/Resources/Private/Language/locallang.xlf:' . $key) ?: $key;
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
