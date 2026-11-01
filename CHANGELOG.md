# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).
Before 1.0.0, a minor version can have breaking changes.

## [Unreleased]

## [0.3.0] - 2026-09-01

### Changed

- **Breaking:** the CPanel authentication uses an
  [API token](README.md#authentication), sent as
  `Authorization: cpanel USERNAME:APITOKEN`, instead of HTTP Basic
  authentication. Recent CPanel versions do not accept Basic authentication by
  default. Create a token in `Security > Manage API Tokens`, the old
  username/password based value of `--cp-token` does not work anymore.
- **Breaking:** the CPanel username is required, with the new configuration
  `phpcp.cpanel.user`, the environment variable `PHPCP_CPANEL_USER` and the
  option `--cp-user`.
- The CPanel base url does not need the temporary `/cpsessXXXXXXXXXX` segment,
  it is ignored if present.
- The API calls use `symfony/http-client` instead of the `curl` binary. The
  dependencies `ext-curl` and `symfony/process` are removed, and the token is no
  longer visible in the process list.
- The command `config` masks the secret values, use `--show-secrets` to print
  them.
- `repo:pull` and `repo:deploy` share the same flow. The exit code is `1` when
  the confirmation is declined.
- The CPanel API errors have clearer messages, including the login page (HTML)
  that CPanel returns when the request is not authorized.
- The docker-compose.yml uses the smaller image `composer:2`, the service is now
  `phpcp`.

### Added

- Option `--yes` (`-y`) in `repo:pull` and `repo:deploy` to skip the
  confirmation.
- `repo:deploy` follows the deployment task with the option `--wait`, using only
  the CPanel API, with the options `--timeout` and `--interval`. It fails when
  the deployment fails or is canceled, with the log file, and when it times out.
  After a successful deployment it checks that the last deployed commit is the
  HEAD commit.
- Option `--if-changed` in `repo:deploy` to skip the deployment when the HEAD
  commit is the last deployed commit.
- `repo:pull` and `repo:deploy` print the HEAD commit and the last deployed
  commit. `repo:pull` prints if the HEAD commit changed, and if it is not
  deployed yet. `repo:deploy` prints the deployment id and the log file.
- `repo:deploy` stops with an error when the repository is not deployable.
- `repo:list` exits with an error when the repository in `--repo` is not found.
- Unit tests with PHPUnit, and the tests in the GitHub Actions workflow for PHP
  8.2, 8.3 and 8.4.
- README section about [using phpcp in a pipeline](README.md#usage-in-a-pipeline).

### Fixed

- The configuration was parsed again in every call.
- The interactive questions for the missing credentials were never asked when the
  environment variable was not set.
- `repo:list` no longer fails when a repository has no source url, and prints a
  dash instead of `1970-01-01` for a deployment that did not succeed.
- `repo:list --git` reads all the pages of branches from GitHub, not only the
  first 30.
- `file:download` reports a missing or not writable output directory, and a
  response without content.
- The application version is the installed package version instead of `0.0.1`.

## [0.2.1] - 2026-05-25

### Changed

- **Breaking:** the argument of `repo:pull` and `repo:deploy` is the repository
  name, as shown by `repo:list`, instead of the branch name.

### Added

- Option `--repo` in `repo:list` to show a single repository.
- Error message when the repository is not found.

## [0.2.0] - 2026-05-25

### Changed

- **Breaking:** requires PHP 8.2 or higher and Symfony 7.

## [0.1.3] - 2026-05-24

### Fixed

- `repo:list` checks if the last deployment and the last update exist before
  printing them.

## [0.1.2] - 2023-10-30

### Changed

- Tagged on the same commit as 0.1.1.

## [0.1.1] - 2023-10-30

### Fixed

- The `phpcp` binary finds the composer autoloader when it is installed as a
  dependency of another project.

## [0.1.0] - 2023-04-19

### Added

- Initial release with the commands `config`, `file:download`, `repo:deploy`,
  `repo:list` and `repo:pull`.

[Unreleased]: https://github.com/joaocsilva/phpcp/compare/0.3.0...HEAD
[0.3.0]: https://github.com/joaocsilva/phpcp/compare/0.2.1...0.3.0
[0.2.1]: https://github.com/joaocsilva/phpcp/compare/0.2.0...0.2.1
[0.2.0]: https://github.com/joaocsilva/phpcp/compare/0.1.3...0.2.0
[0.1.3]: https://github.com/joaocsilva/phpcp/compare/0.1.2...0.1.3
[0.1.2]: https://github.com/joaocsilva/phpcp/compare/0.1.1...0.1.2
[0.1.1]: https://github.com/joaocsilva/phpcp/compare/0.1.0...0.1.1
[0.1.0]: https://github.com/joaocsilva/phpcp/releases/tag/0.1.0
