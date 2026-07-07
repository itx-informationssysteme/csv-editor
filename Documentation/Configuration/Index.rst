.. include:: ../Includes.rst.txt

Configuration
=============

Backend route
-------------

The backend route is registered in:

- ``Configuration/Backend/Routes.php``
- Route identifier: ``csv_editor_edit``
- Path: ``/typo3/csv/edit``

CSV text extension support
--------------------------

The extension appends ``csv`` to TYPO3 setting ``SYS.textfile_ext`` in
``ext_localconf.php`` if it is missing.

Permissions
-----------

Editors need write permissions for the target storage and folder in TYPO3 Filelist.

