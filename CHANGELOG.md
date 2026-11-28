# Changelog

## [Unreleased]

Planned for 2.0.0.

### Added

- Provide an extensible scheduler under `Slothsoft\Cron\Work`. Custom jobs extend `AbstractWorkThread` and use `WorkManager` to queue work in phases and stream logs.
- Run jobs in a bounded native worker pool on PHP CLI with Zend Thread Safety and `ext-parallel`. Run jobs serially on other PHP configurations while streaming the same log messages.
- Let jobs pass large batches by ID through files that any worker in the run can read. Clean up remaining batch files when the work stream closes.
- Use PHP 8.2 ZTS with `ext-parallel` for DDEV CLI development and PHPStan analysis. Test PHP 8.2 through 8.5 with lowest and stable Composer dependencies on Linux and Windows. Run serial fallback checks through a separate Jenkinsfile.

### Changed

- Raise the minimum versions to PHP 8.2, Farah 1.30, and Slothsoft Core 1.16.2. Declare the DOM extension in Composer.
- Use Cron’s scheduler for `/run`, which accepts the `ref` parameter and streams a `work.txt` result as `text/plain`. Migrate custom jobs that import Farah’s `WorkManager` or `AbstractWorkThread` to the corresponding classes in `Slothsoft\Cron\Work`.
- Declare Farah manifest version 1.1 and point the sample PHP job at `phpinfo.local` in the working directory.

### Fixed

- Keep jobs queued before the first `thenWait()` in the same phase. Drain the queue and all recursive follow-ups before starting the next phase, including with a single worker.
- Stream job failures and continue running queued work. Release the workers and temporary batches when the log stream closes early.
- Reject an instructions document without a root element and skip jobs with no effective `active` flag. Handle failed downloads and empty XPath results in scraper jobs.

### Removed

- Remove the `ext-pthreads` integration and `krakjoe/pthreads-polyfill` dependency.
- Stop tracking `composer.lock` for the library.

## [1.0.7] - 2025-07-04

### Changed

- Generate and publish API documentation with phpDocumentor and GitHub Pages. Remove the generated HTML from the repository.
- Update the locked Slothsoft Core and Farah versions to 1.13.0 and 1.14.2. The refreshed lock file drops `krakjoe/pthreads-polyfill`.

## [1.0.6] - 2024-10-05

### Changed

- Add `.env` for the PHP 7.4 development runtime and rename the phpDocumentor configuration to `phpdoc.xml`.
- Update the locked Slothsoft Core and Farah versions to 1.11.6 and 1.12.2.

### Fixed

- Restore Composer’s autoloader as the PHPUnit bootstrap.

## [1.0.5] - 2024-09-29

### Changed

- Update the locked Slothsoft Core and Farah versions to 1.11.5 and 1.12.1.
- Update the MIT license copyright year.

## [1.0.4] - 2024-09-23

### Changed

- Point Composer’s `1.0.x-dev` branch alias at `dev-main`.
- Update the locked Slothsoft Core and Farah versions to 1.11.4 and 1.11.2.

## [1.0.3] - 2024-04-01

### Changed

- Regenerate the API documentation and exclude `.github/` from Git archives.
- Update the locked Slothsoft Core and Farah versions to 1.11.1.

## [1.0.2] - 2024-04-01

### Changed

- Accept a Farah asset reference through the `ref` parameter on `/run`. Replace the bundled instruction sets under `/instructions` with a single `/example-cron` asset.
- Adopt the MIT license and raise the minimum Slothsoft Core and Farah versions to 1.10 and 1.9.

## [1.0.1] - 2022-01-21

### Changed

- Set the supported PHP versions to 7.4 and 8.0. Raise the minimum Slothsoft Core and Farah versions to 1.5 and 1.6.
- Switch from PSR-0 to PSR-4 autoloading, add the module homepage to Composer metadata, and derive package versions from Git tags.
- Upgrade the test suite to PHPUnit 8.5 and add GitHub Actions testing for both supported PHP versions.

## [1.0.0] - 2018-08-10

### Added

- First tagged release of `slothsoft/cron`, a Farah module for PHP 7.2 with an XML instructions schema and a `/run` asset that streams job logs.
- Include workers for file downloads, PHP commands, comics and manga, and RSS and podcast feeds.

[Unreleased]: https://github.com/Faulo/slothsoft-cron/compare/1.0.7...HEAD
[1.0.7]: https://github.com/Faulo/slothsoft-cron/compare/1.0.6...1.0.7
[1.0.6]: https://github.com/Faulo/slothsoft-cron/compare/1.0.5...1.0.6
[1.0.5]: https://github.com/Faulo/slothsoft-cron/compare/1.0.4...1.0.5
[1.0.4]: https://github.com/Faulo/slothsoft-cron/compare/1.0.3...1.0.4
[1.0.3]: https://github.com/Faulo/slothsoft-cron/compare/1.0.2...1.0.3
[1.0.2]: https://github.com/Faulo/slothsoft-cron/compare/1.0.1...1.0.2
[1.0.1]: https://github.com/Faulo/slothsoft-cron/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/Faulo/slothsoft-cron/tree/1.0.0
