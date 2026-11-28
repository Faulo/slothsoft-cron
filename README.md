Cron Module
===========
[![Packagist Version](https://img.shields.io/packagist/v/slothsoft/cron)](https://packagist.org/packages/slothsoft/cron)
[![PHP Version Support](https://img.shields.io/packagist/php-v/slothsoft/cron)](https://www.php.net/)
[![Documentation](https://img.shields.io/badge/docs-reference-blue.svg)](https://faulo.github.io/slothsoft-cron/)
[![Test Status](https://github.com/Faulo/slothsoft-cron/actions/workflows/ci-tests.yml/badge.svg)](https://github.com/Faulo/slothsoft-cron/actions/workflows/ci-tests.yml)
[![license badge](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

A Farah module that handles cronjobs.

## Work scheduler

Extend `Slothsoft\Cron\Work\AbstractWorkThread` and implement `work()` to define a job.
Queue classes and their options with `Slothsoft\Cron\Work\WorkManager::thenDo()`.
Each `thenWait()` ends a phase. The next phase starts after all jobs and recursively queued follow-ups in the current phase finish.
Consume `toChunks()` to stream job logs.

Cron uses native workers on PHP CLI with Zend Thread Safety and `ext-parallel` enabled.
Other PHP configurations execute jobs serially and stream the same log messages.
Use scalar values and arrays for portable work options. Use `writeBatch()` and `readBatch()` to pass large row batches by ID.
Every worker in the run can open these files, and Cron removes remaining batches when the stream closes.

### Development and Jenkins

`ddev start` builds a PHP 8.2 ZTS CLI with `ext-parallel`. The regular DDEV Composer, PHPUnit, and PHPStan commands use that CLI. DDEV's managed PHP-FPM service handles HTTP requests, which use the serial scheduler.

The [main Jenkinsfile](.jenkins/Jenkinsfile.groovy) checks the native scheduler on Linux and Windows with PHP 8.2 through 8.5. The [fallback Jenkinsfile](.jenkins/Jenkinsfile-nts.groovy) uses the same PHP version matrix with NTS builds and also checks a Linux PHP 8.2 ZTS CLI that has no `ext-parallel` installed.

### Native backend comparison

The table reports medians from three runs on each platform. In every run, workers read shards of the same 60,000 synthetic card rows and produced the same checksum. Linux reports peak resident memory and Windows reports peak working set.

| Platform | Workers | `ext-parallel`, copied rows | `ext-parallel`, shared file | `ext-pmmpthread`, shared array |
| --- | ---: | ---: | ---: | ---: |
| Linux, PHP 8.2.36 ZTS | 1 | 307 ms / 179 MiB | 430 ms / 78 MiB | 290 ms / 139 MiB |
| Linux, PHP 8.2.36 ZTS | 4 | 345 ms / 401 MiB | 344 ms / 81 MiB | 725 ms / 301 MiB |
| Windows, PHP 8.2.25 ZTS | 1 | 301 ms / 176 MiB | 522 ms / 75 MiB | 315 ms / 136 MiB |
| Windows, PHP 8.2.25 ZTS | 4 | 283 ms / 342 MiB | 412 ms / 79 MiB | 481 ms / 303 MiB |

The benchmark used `ext-parallel` 1.2.16, `ext-pmmpthread` 6.0.12 on Linux, and `ext-pmmpthread` 6.1.0 on Windows.
