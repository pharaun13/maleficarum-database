# Maleficarum Database [OBSOLETE]

This is the Maleficarum Database component. It carries classes used to persist data in storage.

CAUTION: This component is now obsolete - use Maleficarum Storage instead: https://github.com/pharaun13/maleficarum-storage

# Change Log

## [7.0.1] - 2018-11-28
### Changed
- Remove `__instance` key from pdo parameters

## [7.0.0] - 2018-09-12
### Changed
- Adjustment code to `maleficarum/ioc` component v 3.0

## [6.0.0] - 2018-07-13
### Changed
- Make use of new persistable collection provided by `maleficarum/data` component

## [5.0.0] - 2018-06-19
### Changed
- Make use of new persistable model provided by `maleficarum/data` component
- Removed maleficarum repositories

## [4.6.0] - 2018-10-01
### Changed
- PDO builder now has it's own skip option in the default initializer.

## [4.5.1] - 2017-09-19
### Fixed
- Build proper query with `distinct` for MSSQL collection

## [4.5.0] - 2018-01-24
### Changed
- `Pgsql\TransactionAware` advisory locks are now blocking in favor of non-blocking

## [4.4.0] - 2017-12-07
### Changed
- Being able to delete huge collections from MS SQL Server

## [4.3.0] - 2017-11-28
### Changed
- `Pgsql\TransactionAware` supporting `transaction` and `session` level locks

## [4.2.0] - 2017-09-29
### Changed
- All exceptions implement `\Maleficarum\Database\Exception\DatabaseExceptionInterface`
- `\Maleficarum\Database\Exception\EntityNotFoundException` introduced in `\Maleficarum\Database\Data\Model\AbstractModel::read`
- `\Maleficarum\Database\Errors\Pgsql\Errors` introduced
- `\Maleficarum\Database\Data\Transaction\Pgsql\TransactionAware` introduced
- PhpStorm type hinting improved

## [4.1.2] - 2017-09-20
### Changed
- `\Maleficarum\Database\Shard\Connection\AbstractConnection::prepareStatement` able to re-use prepared statements
- Collection re-uses prepared statement
### Fixed
- Model using prepared statements in a wrong way
- Reverting 4.1.1 as it was not a bug in the driver but in my brain

## [4.1.1] - 2017-09-19
### Fixed
- MSSQL Model not reusing prepared statements as driver has [a bug](https://github.com/Microsoft/msphpsql/issues/60)  

## [4.1.0] - 2017-08-25
### Changed
- Being able to insert huge collections from MS SQL Server

## [4.0.0] - 2017-08-25
> NOTICE!!!
> Please use >= 4.1.1 as this one has a serious bug
### Changed
- Being able to fetch huge collections from MS SQL Server
- NOTICE: `\Maleficarum\Database\Shard\Connection\AbstractConnection::prepare` has been deprecated
    and will throw an `\LogicException`. 
    `prepareStatement` should be used instead.
- Throw `\Maleficarum\Database\Exception\Exception` on connection failure, eg. due to missing driver
- Don't break `\PDO` constructor contract when using `\Maleficarum\Database\Initializer\Initializer`

## [3.1.1] - 2017-08-21
### Changed
- MSSQL connection does not cast all columns to string

## [3.1.0] - 2017-08-18
### Changed
- For MSSQL connection use the `sqlsrv` driver instead of obsolete `dblib`
- Charset setting removed. It's always UTF-8

## [3.0.0] - 2017-08-01
### Changed
- Make use of nullable types provided in PHP 7.1 (http://php.net/manual/en/migration71.new-features.php)

## [2.0.4] - 2017-07-19
### Fixed
- Merge data only if query returns an array on update

## [2.0.3] - 2017-06-02
### Fixed
- Merge data only if query returns an array on create

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
