# PHP CPanel (PHPCP)

PHPCP is a PHP command line application, built with the
[Symfony Console](https://symfony.com/doc/current/components/console.html)
component, to list and interact with CPanel Git repositories through the CPanel
[UAPI](https://api.docs.cpanel.net/cpanel/introduction).

If a website is hosted in a CPanel server using `Git™ Version Control`, PHPCP
lets you run from a terminal or a CI pipeline the same operations you would do
in the CPanel interface: `Update from Remote` and `Deploy HEAD Commit`.

CPanel developer
[portal](https://api.docs.cpanel.net/specifications/cpanel.openapi/repository-management).

## Features

* [List existing repositories information](#command-repolist)
* [Pull a git repository](#command-repopull) (Update from Remote)
* [Deploy a git repository](#command-repodeploy) (Deploy HEAD Commit)
* [View or download a file](#command-filedownload)

## Requirements

* PHP 8.2 or higher
* A CPanel account with an [API token](#authentication)

## Installation

```shell
$ composer require phpcp/phpcp
```

When the package is installed, the bin `phpcp` is added to the composer's
`vendor/bin` directory, you can run the application with `php vendor/bin/phpcp`.

```shell
$ php vendor/bin/phpcp
PHP CPanel cli

Usage:
  command [options] [arguments]

Options:
  ...
  -h, --help            Display help for the given command. When no command is given display help for the list command
  -n, --no-interaction  Do not ask any interactive question

Available commands:
  ...
  config         Print configurations
 file
  file:download  [fetch] Download a CPanel file
 repo
  repo:deploy    [deploy] Deploy a CPanel repository
  repo:list      [repos] List CPanel repositories
  repo:pull      [pull] Pull a CPanel repository
```

## Authentication

PHPCP authenticates with a CPanel **API token**, sent in the header

```text
Authorization: cpanel USERNAME:APITOKEN
```

> **Note**
> HTTP Basic authentication (username and password) is no longer accepted by
> the CPanel interface by default, starting with CPanel 136, and it was
> used by PHPCP versions before 0.3.0. If your commands started failing with a
> login page (HTML) instead of JSON, switch to an API token.

### Create an API token

1. Log in to CPanel.
2. Go to **Security > Manage API Tokens**.
3. Click **Create**, enter a name and, optionally, an expiration date.
4. Copy the token right away, it is only displayed once.

A token has the same permissions as the account that created it, keep it secret
and revoke it in the same page when it is no longer needed.

### Base url

The base url is the CPanel host and port, without a path:

```text
https://cpanelXXX.dnscpanel.com:2083
```

The temporary session segment (`/cpsessXXXXXXXXXX`) used with the old Basic
authentication is not needed. If it is present in the configured url, it is
ignored.

### Test the credentials

You can check the token outside of PHPCP with curl.

```shell
$ curl -H "Authorization: cpanel USERNAME:APITOKEN" \
  "https://cpanelXXX.dnscpanel.com:2083/execute/VersionControl/retrieve"
```

## Configuration

The configurations are loaded using Yaml files and default values are gathered
from environment variables.

You can see the current configuration by running the command `config`.

```shell
$ php vendor/bin/phpcp config
phpcp:
  github:
    token: '${env.PHPCP_GITHUB_TOKEN}'
    user: '${env.PHPCP_GITHUB_USER}'
    repo: '${env.PHPCP_GITHUB_REPO}'
  cpanel:
    user: '${env.PHPCP_CPANEL_USER}'
    token: '${env.PHPCP_CPANEL_TOKEN}'
    url: '${env.PHPCP_CPANEL_URL}'
```

Secret values (tokens) are masked as `********`. Unresolved placeholders such
as `${env.PHPCP_CPANEL_TOKEN}` are shown as they are, they help to find a
missing environment variable. Use the option `--show-secrets` to print the real
values.

### Using environment variables

The provided configurations are looking for environment variables.

| Variable              | Description                                                                                                         |
|-----------------------|---------------------------------------------------------------------------------------------------------------------|
| `PHPCP_CPANEL_USER`   | The CPanel account username                                                                                         |
| `PHPCP_CPANEL_TOKEN`  | The CPanel API token                                                                                                |
| `PHPCP_CPANEL_URL`    | The CPanel base url, i.e: `https://cpanelXXX.dnscpanel.com:2083`                                                    |
| `PHPCP_GITHUB_TOKEN`  | The GitHub API token (required when using option `--git` in command `repo:list`)                                    |
| `PHPCP_GITHUB_USER`   | The GitHub username or organization, i.e: `joaocsilva` (required when using option `--git` in command `repo:list`) |
| `PHPCP_GITHUB_REPO`   | The GitHub repository, i.e: `phpcp` (required when using option `--git` in command `repo:list`)                     |

```shell
$ export PHPCP_CPANEL_USER="myuser"
$ export PHPCP_CPANEL_TOKEN="U7HMR63FHY282DQZ4H5BIH16JLYSO01M"
$ export PHPCP_CPANEL_URL="https://cpanelXXX.dnscpanel.com:2083"
$ php vendor/bin/phpcp repo:list
```

### Using a configuration file

PHPCP will search and load the following configuration files, in the current
directory, `phpcp.yml.dist` and `phpcp.yml`. Later files override earlier ones.

An example of configuration file to interact with CPanel only using static
values. Prefer environment variables for the token, and never commit it.

```yaml
phpcp:
  cpanel:
    user: 'myuser'
    token: 'aBCF'
    url: 'https://cpanelXXX.dnscpanel.com:2083'
```

### Using command options

When using a command option, the value defined in the configuration file
or in the environment is overridden.

**CPanel related options**

Used in commands: [repo:list](#command-repolist), [repo:pull](#command-repopull),
[repo:deploy](#command-repodeploy), [file:download](#command-filedownload)

- `--cp-user` - The CPanel account username
- `--cp-token` - The CPanel API token
- `--cp-base-url` - The CPanel base url

If a value is missing, the command asks for it, unless the option
`--no-interaction` is used.

**GitHub related options**

Used when option `--git` is used in command [repo:list](#command-repolist)

- `--github-token` - The GitHub API access token
- `--github-user` - The GitHub username
- `--github-repo` - The GitHub repository

## Commands list

### Command `config`

View all loaded configurations.

```shell
$ php vendor/bin/phpcp config
```

View a specific configuration.

```shell
$ php vendor/bin/phpcp config phpcp.cpanel.url
```

Print the real token instead of the mask.

```shell
$ php vendor/bin/phpcp config phpcp.cpanel.token --show-secrets
```

### Command `help`

View command information, options, arguments and usages.

```shell
$ php vendor/bin/phpcp help repo:list
```

```text
Description:
  List CPanel repositories

Usage:
  repo:list [options]
  repos
  repo:list --git
  repo:list --cp-user="user" --cp-token="aBcdef" --cp-base-url="https://cpanelXXX.dnscpanel.com:2083"

Options:
  --git                        Check for branch HEAD commit hash in GitHub.
      --repo=REPO              Filter a specific repository.
      --github-token=GITHUB-TOKEN  The GitHub API access token
      --github-user=GITHUB-USER    The GitHub username, i.e: joaocsilva
      --github-repo=GITHUB-REPO    The GitHub repository, i.e: phpcp
      --cp-user=CP-USER            The CPanel account username
      --cp-token=CP-TOKEN          The CPanel API token (Security > Manage API Tokens)
      --cp-base-url=CP-BASE-URL    The CPanel base url, i.e: https://cpanelXXX.dnscpanel.com:2083
```

### Command `repo:list`

List the available repositories that the current user has access to.
It is the same list present in the CPanel interface for `Git™ Version Control`
tool.

```shell
$ php vendor/bin/phpcp repo:list
```

Show only one repository, by name.

```shell
$ php vendor/bin/phpcp repo:list --repo=my-site
```

Compare the CPanel HEAD commit with the branch HEAD commit in GitHub.

```shell
$ php vendor/bin/phpcp repo:list --git
```

### Command `repo:pull`

Perform the action to pull a repository in the CPanel interface
`Git™ Version Control` > `Pull or Deploy`, button `Update from Remote`.
This will pull the changes from the remote.

The command has a required argument with the repository name, as shown in
`repo:list`.

```shell
$ php vendor/bin/phpcp repo:pull my-site
```

The command asks for confirmation before running. Use the option `--yes`
(`-y`) to skip the question, i.e: in a script.

```shell
$ php vendor/bin/phpcp repo:pull my-site --yes
```

The exit code is `0` on success and `1` when the repository is not found or the
confirmation is declined, so it can be used in pipelines.

### Command `repo:deploy`

Perform the action to deploy a repository in the CPanel interface
`Git™ Version Control` > `Pull or Deploy`, button `Deploy HEAD Commit`.
This will trigger the CPanel deployment and execute the `.cpanel.yml` file.

The command has a required argument with the repository name, as shown in
`repo:list`.

```shell
$ php vendor/bin/phpcp repo:deploy my-site
```

The repository must be deployable, the command stops with an error otherwise.
CPanel requires a `.cpanel.yml` file in the repository root and a clean working
tree, `repo:list` shows the value in the column `Deployable`.

Same as `repo:pull`, use `--yes` to skip the confirmation. The exit code is `0`
on success and `1` when the repository is not found, is not deployable or the
confirmation is declined.

```shell
$ php vendor/bin/phpcp repo:deploy my-site --yes
```

### Command `file:download`

Output a file content, or write it to a file with option `--output`.
It is meant for text files, the content is returned by the CPanel API as text.

When writing to a file the command prints the number of bytes saved. It exits
with `1` if the output directory does not exist or is not writable.

The command has the required arguments `dir` and `file` to specify the directory
where the file is and the filename.

Print the composer.json content.

```shell
$ php vendor/bin/phpcp file:download /home/user composer.json
```

Download the composer.json file to a file named remote-composer.json.

```shell
$ php vendor/bin/phpcp file:download /home/user composer.json --output=remote-composer.json
```

## Troubleshooting

| Symptom                                                    | Cause and fix                                                                                              |
|------------------------------------------------------------|------------------------------------------------------------------------------------------------------------|
| `Unexpected response from CPanel API (HTTP 200)`, expected JSON | The request was not authorized and CPanel returned the login page. Check user, token and url.         |
| `CPanel rejected the credentials (HTTP 401)`               | Wrong username or token, or the token was revoked or expired. Create a new one.                            |
| `Missing configuration key(s): phpcp.cpanel.user`          | `PHPCP_CPANEL_USER` is not set. This key is new in 0.3.0.                                                  |
| `Could not find repository`                                | The argument is the repository name shown by `repo:list`, not the branch name.                             |
| `The repository ... is not deployable`                     | Add a `.cpanel.yml` file to the repository root and make sure the working tree is clean, then pull again.  |

## Local development

You can use docker to run the project locally, the image `composer:2` has PHP
and composer.

The docker-compose.yml file contains the environment variables needed for the
commands.

```yaml
PHPCP_GITHUB_TOKEN:
PHPCP_GITHUB_USER:
PHPCP_GITHUB_REPO:
PHPCP_CPANEL_USER:
PHPCP_CPANEL_TOKEN:
PHPCP_CPANEL_URL:
```

You can map these variables to your own env variables with.

```yaml
PHPCP_CPANEL_USER: ${CPANEL_USER}
PHPCP_CPANEL_TOKEN: ${CPANEL_TOKEN}
PHPCP_CPANEL_URL: ${CPANEL_URL}
```

Usage.

```shell
# Start the container.
$ docker compose up -d
# Install composer dependencies.
$ docker compose exec phpcp composer install
# Check if the phpcp is running.
$ docker compose exec phpcp php phpcp
# List CPanel repositories.
$ docker compose exec phpcp php phpcp repo:list
```

### Tests and code style

The tests use [PHPUnit](https://phpunit.de) and mock the CPanel API, they do not
need a CPanel account.

```shell
$ docker compose exec phpcp composer test
```

Code style is checked with PHP_CodeSniffer.

```shell
$ docker compose exec phpcp composer cs
```

Both run in the GitHub Actions workflow, for PHP 8.2, 8.3 and 8.4.

## License

MIT, see [LICENSE](LICENSE).
