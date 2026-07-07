.. include:: ../Includes.rst.txt

Known Problems
==============

- The backend route path is currently configured as ``/typo3/csv/edit``.
  Depending on backend base path setup, this may lead to confusing URLs.
- CSV parsing assumes semicolon-separated values.
  Files using comma separators are not auto-detected.
- Empty rows are filtered during save (except header row).
  This is intentional but can surprise users expecting strict row preservation.

