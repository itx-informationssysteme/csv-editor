# CSV Editor for TYPO3

This extension provides a backend CSV editor integrated into the Filelist module. It allows editors to open CSV files from the Filelist in a spreadsheet-like editor (semicolon separated) and save the results back to CSV (optional with UTF-8 BOM).

Features
--------
- Replaces the default edit action in the Filelist for target CSV files with a CSV editor.
- Spreadsheet-like editing interface for semicolon-separated CSV files.
- Save back to CSV with optional UTF-8 BOM.

Requirements
------------
- Composer for installation
- The user needs write permissions for the target storage/folder to save CSV files.

Installation
------------
Install via Composer in your TYPO3 project root:

```bash
composer require itx/csv-editor
```

After installation, clear the TYPO3 caches and check the extension list in the backend.

Usage
-----
1. Open the Filelist in the TYPO3 backend.
2. For CSV files, in the more options menu, a new option "Edit CSV as table" is available
3. Edit the file in the grid and save. The file will be written back as CSV (semicolon separated).

Configuration
-------------
- By default the editor works on all files with a `.csv` extension.

Development / Contributing
--------------------------
Contributions are welcome. Please open an issue or a pull request on the repository. When contributing:

- Follow PSR-12 coding style where possible.
- Add tests for new functionality if applicable.
- Update `CHANGELOG` when creating releases.

Support
-------
If you encounter problems or bugs, please open an issue on the repository.

License
-------
This extension is licensed under the GPL-3.0-or-later. See `LICENCE.txt` for details.

Changelog
---------
See the `Changelog/` directory for release notes and history.

Notes
-----
- The extension installs as a Composer package and will be placed in `vendor/` by Composer. If you require installation into `typo3conf/ext/`, configure installer paths accordingly.
