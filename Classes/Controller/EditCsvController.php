<?php

declare(strict_types=1);

namespace Itx\CsvEditor\Controller;

use Itx\CsvEditor\Service\CsvEditorTargetResolver;
use TYPO3\CMS\Core\Localization\LanguageService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class EditCsvController
{
    public function __construct(
        private readonly ResourceFactory $resourceFactory,
        private readonly UriBuilder $uriBuilder,
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly IconFactory $iconFactory,
        private readonly CsvEditorTargetResolver $targetResolver
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($request);

        $queryParams = $request->getQueryParams();
        $parsedBody = (array)$request->getParsedBody();
        $target = (string)($parsedBody['target'] ?? $queryParams['target'] ?? '');

        $returnUrl = (string)($parsedBody['returnUrl'] ?? $queryParams['returnUrl'] ?? '');
        $returnUrl = GeneralUtility::sanitizeLocalUrl($returnUrl);
        if ($returnUrl === '') {
            $returnUrl = (string)$this->uriBuilder->buildUriFromRoute('file_FilelistList');
        }

        $file = $this->resolveFile($target);
        if (!$file instanceof File) {
            return new RedirectResponse($returnUrl, 303);
        }

        if (!$this->targetResolver->isAllowedFile($file)) {
            $moduleTemplate->setTitle($this->trans('title.editor'));
            $moduleTemplate->setContent('<div class="alert alert-danger">Diese Datei ist im CSV-Editor nicht freigegeben.</div>');
            return new HtmlResponse($moduleTemplate->renderContent(), 403);
        }

        if (!$file->checkActionPermission('write')) {
            $moduleTemplate->setTitle($this->trans('title.editor'));
            $moduleTemplate->setContent('<div class="alert alert-danger">Keine Schreibrechte auf diese Datei.</div>');
            return new HtmlResponse($moduleTemplate->renderContent(), 403);
        }

        $message = '';
        $isError = false;

        if (strtoupper($request->getMethod()) === 'POST' && isset($parsedBody['_save'])) {
            $cells = is_array($parsedBody['cells'] ?? null) ? $parsedBody['cells'] : [];
            $columnCount = max(1, (int)($parsedBody['columnCount'] ?? 1));
            $hasBom = (string)($parsedBody['hasBom'] ?? '0') === '1';

            $rows = $this->normalizeRows($cells, $columnCount);
            if ($rows === []) {
                $message = $this->trans('message.minOneRow');
                $isError = true;
            } else {
                try {
                    $this->writeCsv($file, $rows, $hasBom);
                    $message = $this->trans('message.saved');
                } catch (RuntimeException $exception) {
                    $message = $exception->getMessage();
                    $isError = true;
                }
            }
        }

        try {
            $csvData = $this->readCsv($file);
        } catch (RuntimeException $exception) {
            $moduleTemplate->setTitle($this->trans('title.editor'));
            $moduleTemplate->setContent('<div class="alert alert-danger">' . $this->escape($exception->getMessage()) . '</div>');
            return new HtmlResponse($moduleTemplate->renderContent(), 500);
        }

        $formAction = (string)$this->uriBuilder->buildUriFromRoute('csv_editor_edit', [
            'target' => $file->getCombinedIdentifier(),
            'returnUrl' => $returnUrl,
        ]);

        $this->addButtons($moduleTemplate, $returnUrl);
        $moduleTemplate->setTitle($this->trans('title.editor'));
        $moduleTemplate->setContent(
            $this->renderEditor(
                $csvData['rows'],
                $csvData['hasBom'],
                $formAction,
                $returnUrl,
                $message,
                $isError,
                $file->getCombinedIdentifier()
            )
        );

        return new HtmlResponse($moduleTemplate->renderContent());
    }

    private function addButtons($moduleTemplate, string $returnUrl): void
    {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();

        $saveButton = $buttonBar->makeInputButton()
            ->setName('_save')
            ->setValue('1')
            ->setForm('csv-editor-form')
            ->setShowLabelText(true)
            ->setTitle($this->trans('button.save'))
            ->setIcon($this->iconFactory->getIcon('actions-document-save', Icon::SIZE_SMALL));
        $buttonBar->addButton($saveButton, ButtonBar::BUTTON_POSITION_LEFT, 20);

        $cancelButton = $buttonBar->makeLinkButton()
            ->setShowLabelText(true)
            ->setHref($returnUrl)
            ->setTitle($this->trans('button.back'))
            ->setIcon($this->iconFactory->getIcon('actions-close', Icon::SIZE_SMALL));
        $buttonBar->addButton($cancelButton, ButtonBar::BUTTON_POSITION_LEFT, 10);
    }

    private function resolveFile(string $target): ?File
    {
        if ($target === '') {
            return null;
        }

        try {
            $resource = $this->resourceFactory->retrieveFileOrFolderObject($target);
        } catch (\Throwable) {
            return null;
        }

        return $resource instanceof File ? $resource : null;
    }

    /**
     * @return array{rows: array<int, array<int, string>>, hasBom: bool}
     */
    private function readCsv(File $file): array
    {
        $localPath = $file->getForLocalProcessing(false);
        if (!is_file($localPath)) {
            throw new RuntimeException('CSV-Datei konnte lokal nicht gelesen werden.');
        }

        $contents = file_get_contents($localPath);
        if ($contents === false) {
            throw new RuntimeException('CSV-Datei konnte nicht gelesen werden.');
        }

        $hasBom = str_starts_with($contents, "\xEF\xBB\xBF");
        $handle = fopen($localPath, 'rb');
        if ($handle === false) {
            throw new RuntimeException('CSV-Datei konnte nicht geoeffnet werden.');
        }

        $rows = [];
        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $normalizedRow = [];
            foreach ($row as $index => $cell) {
                $value = (string)($cell ?? '');
                if ($index === 0) {
                    $value = preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
                }
                $normalizedRow[] = $value;
            }
            $rows[] = $normalizedRow;
        }
        fclose($handle);

        if ($rows === []) {
            $rows = [['']];
        }

        return [
            'rows' => $this->normalizeRows($rows, 1),
            'hasBom' => $hasBom,
        ];
    }

    /**
     * @param array<int, array<int, string>> $rows
     */
    private function writeCsv(File $file, array $rows, bool $hasBom): void
    {
        $localPath = $file->getForLocalProcessing(false);
        $directory = dirname($localPath);

        if (!is_dir($directory) || !is_writable($directory)) {
            throw new RuntimeException('CSV-Verzeichnis ist nicht beschreibbar.');
        }

        if (file_exists($localPath) && !is_writable($localPath)) {
            throw new RuntimeException('CSV-Datei ist nicht beschreibbar.');
        }

        $tmpPath = $localPath . '.tmp';
        $handle = fopen($tmpPath, 'wb');
        if ($handle === false) {
            throw new RuntimeException('Temporaere Datei konnte nicht erstellt werden.');
        }

        if ($hasBom) {
            fwrite($handle, "\xEF\xBB\xBF");
        }

        foreach ($rows as $row) {
            if (fputcsv($handle, $row, ';') === false) {
                fclose($handle);
                @unlink($tmpPath);
                throw new RuntimeException('CSV-Datei konnte nicht geschrieben werden.');
            }
        }

        fclose($handle);

        if (!rename($tmpPath, $localPath)) {
            @unlink($tmpPath);
            throw new RuntimeException('CSV-Datei konnte nicht gespeichert werden.');
        }
    }

    /**
     * @param array<int, array<int, string|null>> $rows
     * @return array<int, array<int, string>>
     */
    private function normalizeRows(array $rows, int $fallbackColumns): array
    {
        if ($rows === []) {
            return [];
        }

        $width = max($fallbackColumns, 1);
        foreach ($rows as $row) {
            $width = max($width, count($row));
        }

        $result = [];
        foreach ($rows as $index => $row) {
            $normalized = [];
            for ($column = 0; $column < $width; $column++) {
                $normalized[] = trim((string)($row[$column] ?? ''));
            }
            if ($index === 0 || $this->rowHasContent($normalized)) {
                $result[] = $normalized;
            }
        }

        return $result;
    }

    /**
     * @param array<int, string> $row
     */
    private function rowHasContent(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, array<int, string>> $rows
     */
    private function renderEditor(
        array $rows,
        bool $hasBom,
        string $formAction,
        string $returnUrl,
        string $message,
        bool $isError,
        string $combinedIdentifier
    ): string {
        if ($rows === []) {
            $rows = [['']];
        }

        $columnCount = max(1, ...array_map('count', $rows));
        $headerRowLabel = $this->trans('label.headerRow');
        $columnLabel = $this->trans('label.column');
        $actionLabel = $this->trans('label.action');
        $fileLabel = $this->trans('label.file');
        $addRowLabel = $this->trans('button.addRow');
        $addColumnLabel = $this->trans('button.addColumn');
        $removeRowLabel = $this->trans('button.removeRow');
        $keepBomLabel = $this->trans('label.keepBom');

        $rowsHtml = '';
        $headerValues = $rows[0] ?? [];
        foreach ($rows as $rowIndex => $row) {
            $rowsHtml .= '<tr>';
            $rowsHtml .= '<th scope="row" class="csv-editor__row-label">' . ($rowIndex === 0 ? $this->escape($headerRowLabel) : (string)$rowIndex) . '</th>';
            for ($column = 0; $column < $columnCount; $column++) {
                $value = $this->escape($row[$column] ?? '');
                if ($rowIndex === 0) {
                    $rowsHtml .= '<td><span class="form-control-plaintext">' . $value . '</span><input class="csv-editor__input csv-editor__header-value" type="hidden" name="cells[' . $rowIndex . '][' . $column . ']" value="' . $value . '"></td>';
                } else {
                    $rowsHtml .= '<td><input class="form-control csv-editor__input" type="text" name="cells[' . $rowIndex . '][' . $column . ']" value="' . $value . '" style="min-width: 320px;"></td>';
                }
            }
            if ($rowIndex === 0) {
                $rowsHtml .= '<td></td>';
            } else {
                $rowsHtml .= '<td><button class="btn btn-default csv-editor__remove-row" type="button">' . $this->escape($removeRowLabel) . '</button></td>';
            }
            $rowsHtml .= '</tr>';
        }

        $headings = '';
        for ($column = 1; $column <= $columnCount; $column++) {
            $headerValue = trim((string)($headerValues[$column - 1] ?? ''));
            $heading = $headerValue !== '' ? $headerValue : ($columnLabel . ' ' . $column);
            $headings .= '<th scope="col">' . $this->escape($heading) . '</th>';
        }

        $flash = '';
        if ($message !== '') {
            $class = $isError ? 'alert-danger' : 'alert-success';
            $flash = '<div class="alert ' . $class . '">' . $this->escape($message) . '</div>';
        }

        $checkedBom = $hasBom ? ' checked' : '';

        return '<div class="csv-editor">'
            . $flash
            . '<p><strong>' . $this->escape($fileLabel) . ':</strong> <code>' . $this->escape($combinedIdentifier) . '</code></p>'
            . '<form id="csv-editor-form" method="post" action="' . $this->escape($formAction) . '">'
            . '<input type="hidden" name="target" value="' . $this->escape($combinedIdentifier) . '">'
            . '<input type="hidden" name="returnUrl" value="' . $this->escape($returnUrl) . '">'
            . '<input type="hidden" id="csv-editor-column-count" name="columnCount" value="' . $columnCount . '">'
            . '<div class="mb-3 d-flex" style="gap:.5rem; flex-wrap: wrap;">'
            . '<button class="btn btn-default" id="csv-editor-add-row" type="button">' . $this->escape($addRowLabel) . '</button>'
            . '<button class="btn btn-default" id="csv-editor-add-column" type="button">' . $this->escape($addColumnLabel) . '</button>'
            . '<label class="form-check-label" style="display:flex;align-items:center;gap:.5rem;">'
            . '<input class="form-check-input" type="checkbox" name="hasBom" value="1"' . $checkedBom . '>'
            . $this->escape($keepBomLabel) . '</label>'
            . '</div>'
            . '<div class="table-fit"><table class="table table-striped" id="csv-editor-table">'
            . '<thead><tr><th scope="col">#</th>' . $headings . '<th scope="col">' . $this->escape($actionLabel) . '</th></tr></thead>'
            . '<tbody>' . $rowsHtml . '</tbody>'
            . '</table></div>'
            . '</form>'
            . $this->renderScript($headerRowLabel, $removeRowLabel, $columnLabel)
            . '</div>';
    }

    private function renderScript(string $headerRowLabel, string $removeRowLabel, string $columnLabel): string
    {
        $headerRowJs = $this->jsString($headerRowLabel);
        $removeRowJs = $this->jsString($removeRowLabel);
        $columnJs = $this->jsString($columnLabel);

        return '<script>(function(){'
            . 'const table=document.getElementById("csv-editor-table");if(!table){return;}'
            . 'const tbody=table.querySelector("tbody");'
            . 'const form=document.getElementById("csv-editor-form");'
            . 'const colInput=document.getElementById("csv-editor-column-count");'
            . 'const addRowBtn=document.getElementById("csv-editor-add-row");'
            . 'const addColBtn=document.getElementById("csv-editor-add-column");'
            . 'const headerRowLabel=' . $headerRowJs . ';'
            . 'const removeRowLabel=' . $removeRowJs . ';'
            . 'const columnLabel=' . $columnJs . ';'
            . 'const getHeaderRow=function(){return tbody.querySelector("tr");};'
            . 'const updateColumnHeadings=function(){const headerRow=getHeaderRow();if(!headerRow){return;}const headers=table.querySelectorAll("thead th");const headerValues=headerRow.querySelectorAll("input.csv-editor__header-value");headerValues.forEach((input,index)=>{const th=headers[index+1];if(!th){return;}const value=(input.value||"").trim();th.textContent=value!==""?value:(columnLabel+" "+(index+1));});};'
            . 'const updateLabels=function(){tbody.querySelectorAll("tr").forEach((row,index)=>{const label=row.querySelector(".csv-editor__row-label");if(label){label.textContent=index===0?headerRowLabel:String(index);}});};'
            . 'const reindex=function(){tbody.querySelectorAll("tr").forEach((row,r)=>{row.querySelectorAll("input.csv-editor__input").forEach((input,c)=>{input.name=`cells[${r}][${c}]`;});});};'
            . 'const wireRemove=function(scope){scope.querySelectorAll(".csv-editor__remove-row").forEach((btn)=>{btn.onclick=function(){if(tbody.querySelectorAll("tr").length<=1){return;}btn.closest("tr").remove();reindex();updateLabels();};});};'
            . 'addRowBtn.onclick=function(){const rowIndex=tbody.querySelectorAll("tr").length;const colCount=parseInt(colInput.value,10);const tr=document.createElement("tr");'
            . 'const head=document.createElement("th");head.scope="row";head.className="csv-editor__row-label";head.textContent=String(rowIndex);tr.appendChild(head);'
            . 'for(let c=0;c<colCount;c++){const td=document.createElement("td");const input=document.createElement("input");input.type="text";input.className="form-control csv-editor__input";input.name=`cells[${rowIndex}][${c}]`;td.appendChild(input);tr.appendChild(td);}'
            . 'const action=document.createElement("td");const remove=document.createElement("button");remove.type="button";remove.className="btn btn-default csv-editor__remove-row";remove.textContent=removeRowLabel;action.appendChild(remove);tr.appendChild(action);tbody.appendChild(tr);wireRemove(tr);reindex();updateLabels();};'
            . 'addColBtn.onclick=function(){const current=parseInt(colInput.value,10);colInput.value=String(current+1);'
            . 'const headRow=table.querySelector("thead tr");const actionHead=headRow.lastElementChild;const th=document.createElement("th");th.scope="col";th.textContent=columnLabel+" "+(current+1);headRow.insertBefore(th,actionHead);'
            . 'tbody.querySelectorAll("tr").forEach((row,rowIndex)=>{const action=row.lastElementChild;const td=document.createElement("td");if(rowIndex===0){const label=document.createElement("span");label.className="form-control-plaintext";const input=document.createElement("input");input.type="hidden";input.className="csv-editor__input csv-editor__header-value";input.name=`cells[${rowIndex}][${current}]`;input.value="";td.appendChild(label);td.appendChild(input);}else{const input=document.createElement("input");input.type="text";input.className="form-control csv-editor__input";input.name=`cells[${rowIndex}][${current}]`;td.appendChild(input);}row.insertBefore(td,action);});reindex();updateColumnHeadings();};'
            . 'form.addEventListener("submit",function(){reindex();});'
            . 'wireRemove(document);updateLabels();updateColumnHeadings();})();</script>';
    }

    private function trans(string $key): string
    {
        return $this->getLanguageService()->sL('LLL:EXT:csv_editor/Resources/Private/Language/locallang.xlf:' . $key) ?: $key;
    }

    private function jsString(string $value): string
    {
        return json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?: '""';
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}


