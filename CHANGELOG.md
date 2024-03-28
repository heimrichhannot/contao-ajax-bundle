# Changelog
All notable changes to this project will be documented in this file.

## [1.5.0] - 2024-03-28
- Changed: improved support for contao 5
- Changed: improved PHP 8 compatibility
- Removed: PHP 7.4 support

## [1.4.5] - 2024-01-30
- Fixed: check for getCurrentRequest() first

## [1.4.4] - 2024-01-18
- Fixed: incompatibility with contao 5 form ajax submit

## [1.4.3] - 2023-12-16
- Fixed: empty url type issue

## [1.4.2] - 2023-11-09
- Fixed: UitlsBundle api change

## [1.4.1] - 2023-11-07
- Fixed: possible exception

## [1.4.0] - 2023-10-31
- Added: support for contao 5
- Added: support for utils bundle version 3
- Added: license file
- Changed: dropped support for contao 4.9
- Changed: some small code adjustments
- Fixed: Symfony 6 compatibility
- Removed: outdated test setup

## [1.3.0] - 2023-03-19
- Changed: removed remaining request bunde usages
- Deprecated: huh.ajax service alias

## [1.2.0] - 2023-02-10
- Changed: requires at least php 7.4
- Changed: requires at least contao 4.9
- Changed: some refactoring
- Changed: lowered dependency on RequestBundle
- Fixed: deprecation warning due missing return value in contao manager plugin class

## [1.1.3] - 2022-08-22
- Fixed: invalid composer.json file

## [1.1.2] - 2022-08-22
- Fixed: array index issue

## [1.1.1] - 2022-06-02
- Fixed: session issue

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
