# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.3.1] - 2023-04-18

### Changed

- Improved permission resolver

## [1.3.0] - 2023-03-10

### Changed

- Completely refactored code.

### Added

- Automatically assign package classes to database tables.
- Edit button for each database table.

## [1.2.0] - 2023-02-23

### Added

- Compatibility with MODX 3.
- Use <package>.core_path setting to get the table class name.

## [1.1.3] - 2017-06-30

### Added

- Added the horizontal scrolling for a table window.
- Added output type - var_export or print_r.
- Added syntax highlighting for sql.
- Now, instead of the "query error", a full description of the error is displayed.

## [1.1.2] - 2016-03-17

### Added

- Changed file name format of database backup to "database_date_time.sql". For example, "modx_20160317_090155.sql".

## [1.1.1] - 2016-02-05

### Added

- Add support of PHP 5.3.

### Fixed

- Fixed some bugs.

## [1.1.0] - 2015-09-06

### Added

- Added the ability to edit the selected cell in tables with a class or remove entire row.
- Auto-update the list of tables.
- Added Magic button to define a class for table.
- Added 'Select from' button to the tables grid that adds 'Select from' script for selected table to the SQL query editor.

## [1.0.1] - 2015-08-21

### Added

- Added autocreation a folder for export operation.
- Added the table access_namespace for MODX 2.4.0.

### Fixed

- Fixed the error of checking update (wrong package name).

## [1.0.0] - 2015-08-17

### Added

- Auto synchronization instead of the button.
- Added a "need update" marker (by experiment).

### Fixed

- Fixed the error of renaming table.

## [1.0.0-beta] - 2015-08-13

### Added

- Initial release
