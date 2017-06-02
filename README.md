# Change Log
This is the Maleficarum Database component. It carries classes used to persist data in storage.

## [2.0.3] - 2017-06-02
### Fixed
- Merge data only if query returns an array on update

## [2.0.2] - 2017-04-19
### Fixed
- Set database charset only if is defined

## [2.0.1] - 2017-04-10
### Fixed
- Set MSSQL specific connection options to handle inserts

## [2.0.0] - 2017-04-05
### Added
- Add MSSQL connection handler
- Add model & collection classes for MSSQL
- Update initializer

## [1.1.2] - 2017-03-23
### Changed
- Default initializer now has an option to skip loading default builder functions.

## [1.1.1] - 2017-03-23
### Changed
- Default package initializer now properly returns it's name instead of null.

## [1.1.0] - 2017-03-22
### Added
- Added IOC definitions specific to this package.

## [1.0.0] - 2017-03-21
### Added
- This is an initial release of the component - based on the code written by me and included inside the maleficarum API repository.
