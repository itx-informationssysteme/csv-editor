.. include:: ../Includes.rst.txt

Changelog
=========

1.0.1
-----

- Fix: Do not overwrite the TYPO3 default "Edit" action in the file list. The CSV editor now
  adds a separate action item under the key `csv_edit`, so the original Edit button (which
  allows replacing the whole file) remains available.

1.0.0
-----

Initial stable release.

- Adds a table-based CSV editor in TYPO3 backend Filelist.
- Adds CSV edit action integration via Filelist event listener.
- Adds multilingual button and label translations (en, de, es, fr).
- Adds extension documentation structure for TYPO3 docs rendering.

