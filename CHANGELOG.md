# Change Log

## Version 3.0.4 - 2016-08-04
- Feature: Added support for older SQLite versions as used by some Linux distributions like CentOS and RHEL (issue #25).

## Version 3.0.3 - 2016-08-02
- Feature: Adjustment to avoid warnings in PHP 7.1.x when using the CLI version.
- Fix: Added info about the required SQLite library version to the documentation.

## Version 3.0.2 - 2016-08-02
- Fix: Corrected the check for directory permissions, so that directories don't have to be writable if only used in read-only mode (issue #26).
- Fix: Corrected the parser for the case that the Browscap source file was generated on a Windows system and contains differend line-endings (issue #28).
- Fix: Fixed wrong example in documentation (issue #27).
- Fix: Adjusted some unit tests to no more use deprecated methods (which caused that the test result on Travis was marked as failed for PHP 5.6.x)

## Version 3.0.1 - 2016-04-23
- Fix: Fixed composer requirements (pull request #24).

## Version 3.0.0 - 2016-04-08
- Feature: Library optimized for PHP 7 (with strict types).
