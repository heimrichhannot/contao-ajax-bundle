# Changelog
All notable changes to this project will be documented in this file.

## [1.1.0] - 2021-08-30

- Added: php8 support

## [1.0.7] - 2019-03-27

### Changed
- removed symfony/framework-bundle as dependency for better compatibility

## [1.0.6] - 2018-04-19

### Fixed
- set `$GLOBALS['AJAX']` only if it's not already set (caused error when using heimrichhannot/contao-ajax and heimrichhannot/contao-ajax-bundle simultanious)

## [1.0.5] - 2018-04-03

### Fixed
- composer.json

## [1.0.4] - 2018-03-22

### Fixed
- including exit function

## [1.0.3] - 2018-03-21

### Fixed
- fixed token manager from `contao.csrf` to `security.csrf`

## [1.0.2] - 2018-03-16

### Fixed
- fixed isset csrf_protection and tests

## [1.0.1] - 2018-03-14

### Fixed
- fixed namespace in test
- replaced deprecated functions call
