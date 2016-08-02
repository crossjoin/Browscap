# Change Log

## Version 3.0.2 - 2016-08-02
- Corrected the check for directory permissions, so that directories don't have to be writable if 
  only used in read-only mode (issue #26).
- Corrected the parser for the case that the Browscap source file was generated on a Windows 
  system and contains differend line-endings (issue #28).
- Fixed wrong example in documentation (issue #27).
- Adjusted some unit tests to no more use deprecated methods (which caused that the test result on 
  Travis was marked as failed for PHP 5.6.x)

## Version 3.0.1 - 2016-04-23
- Fixed composer requirements (pull request #24).

## Version 3.0.0 - 2016-04-08
- Feature: Library optimized for PHP 7 (with strict types).
