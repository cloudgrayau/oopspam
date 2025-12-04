# OOPSpam Changelog

Release notes for the OOPSpam Craft CMS plugin.

## 1.5.1 - 2025-12-02
### Changed
- Fixed possible error that can occur on the dashboard widget
- Updated settings to be able to be viewed in environments where `allowAdminChanges` is disabled

## 1.5.0 - 2025-12-01
### Added
- Added optional submission rate limiting (to reduce excessive spam calls)

### Changed
- Fixed `FreeForm` integration (will now correctly mark as spam)
- Updated widget title to be customisable

## 1.4.2 - 2025-11-27
### Added
- Added dashboard widget to show recent logs

## 1.4.1 - 2025-11-21
### Changed
- Fixed compatibility with the `Contact Form Extensions` plugin

## 1.4.0 - 2025-11-20
### Added
- Added `Test Suite` for testing API calls

### Changed
- Refactored elements / log results
- Fixed `Formie` integration Craft 5 field namespace mapping

## 1.3.3 - 2025-11-12
### Changed
- Fixed compatibility with both MySQL and PostgreSQL
- Fixed the inability to use env vars for the api key

## 1.3.2 - 2025-09-23
### Changed
- Improved `spamScore` range field

## 1.3.1 - 2025-09-16
### Added
- Added domain reputation checker

### Changed
- Improved API error handling
- Improved contextual logs

## 1.3.0 - 2025-09-13
### Added
- Added support for Craft Commerce
- Added contextual spam detection (experimental feature)

### Changed
- Improved `verbb/comments` integration
- Improved settings

## 1.2.1 - 2025-06-16
### Added
- Added new setting `blockContentSpam` for blocking spam content based on an AI algorithm
- Added new setting `blockVPN` for blocking VPN, Proxy and TOR IPs
- Added new setting `blockDC` for blocking Data Center IPs

### Changed
- Fixed light-switch field styling

## 1.1.1 - 2024-11-30
### Changed
- Updated limits to be saved in the database

## 1.0.2 - 2024-11-13
### Changed
- `Spam Score` setting is now a `range` field

## 1.0.0 - 2024-10-29
- Initial release