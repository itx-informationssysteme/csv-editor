# csv-editor

Eigenständige TYPO3-Extension für die direkte Bearbeitung von CSV-Dateien
innerhalb der Filelist.

## Verhalten

- ersetzt in der Filelist fuer die Ziel-Datei den normalen Edit-Button
- öffnet einen tabellarischen CSV-Editor (Semikolon-getrennt)
- speichert wieder als CSV (optional mit UTF-8 BOM)

## Installation

1. Composer-Abhängigkeit in der Projektwurzel:

```bash
composer require itx/csv-editor:^1.0
```

2. TYPO3 Cache leeren.

## Hinweise

- Zugriff wird auf CSV-Dateien beschränkt.
- Der Benutzer benötigt Schreibrechte im entsprechenden Storage/Ordner.

