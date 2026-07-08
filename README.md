# CSV Editor for TYPO3

This extension provides a backend CSV editor integrated into the Filelist module. It allows editors to open CSV files from the Filelist in a spreadsheet-like editor (semicolon separated) and save the results back to CSV (optional with UTF-8 BOM).

Status
------
- Initial semantic version: **1.0.0**
- Tested with: **TYPO3 11.5.50**

Features
--------
- Replaces the default edit action in the Filelist for target CSV files with a CSV editor.
- Spreadsheet-like editing interface for semicolon-separated CSV files.
- Save back to CSV with optional UTF-8 BOM.

Requirements
------------
- PHP and TYPO3 versions supported: TYPO3 11.5.x (tested on 11.5.50)
- Composer for installation
- The user needs write permissions for the target storage/folder to save CSV files.

Installation
------------
Install via Composer in your TYPO3 project root:

```bash
composer require itx/csv-editor:^1.0
```

After installation, clear the TYPO3 caches and check the extension list in the backend.

Usage
-----
1. Open the Filelist in the TYPO3 backend.
2. For CSV files the regular edit button is replaced by the CSV Editor action.
3. Edit the file in the grid and save. The file will be written back as CSV (semicolon separated).

Configuration
-------------
- By default the editor works on all files with a `.csv` extension.

Composer constraints
--------------------
The package requires TYPO3 packages at least from 11.5.50 and below 12.0:

Development / Contributing
--------------------------
Contributions are welcome. Please open an issue or a pull request on the repository. When contributing:

- Follow PSR-12 coding style where possible.
- Add tests for new functionality if applicable.
- Update `CHANGELOG` and `ext_emconf.php` version when creating releases.

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
