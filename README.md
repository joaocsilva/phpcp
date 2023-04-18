# About PHP CPanel

PHPCP is a
PHP [Symfony application](https://symfony.com/doc/5.4/components/console.html)
to list and interact with CPanel Git repositories through the
[API](https://api.docs.cpanel.net/cpanel/introduction).

Allows you to list available repositories in the CPanel account
A website hosted in a CPanel server using the Git Version Control,
this allows you to perform the manual operations for 'Pull or Deploy' in the
CPanel Git repositories interface.

## Features

* [List existing repositories information](#command-repolist)
* [Pull a git repository](#command-repopull)
* [Push a git repository](#command-repodeploy)
* [View or Download a file](#command-filedownload)

## Installation

```shell
$ composer require phpcp/phpcp
```

When the package is installed, the bin `phpcp` is added to the composer's
`vendor/bin` directory, you can run the application with `php vendor/bin/phpcp`.

```shell
$ php vendor/bin/phpcp
PHP CPanel cli 0.0.1

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
    token: '${env.PHPCP_CPANEL_TOKEN}'
    url: '${env.PHPCP_CPANEL_URL}'
```

### Using Environment variables

The provided configurations are looking for environment variables. See below
the used variables.

- **PHPCP_CPANEL_TOKEN** - The CPanel username:password in base64 format
- **PHPCP_CPANEL_URL** - The CPanel base url
  (i.e: https://cpanelXXX.dnscpanel.com:XXXX/cpsessXXXXXXXXXX)
- **PHPCP_GITHUB_TOKEN** - The GitHub API token (required when using option
  `--git` in command `repo:list`)
- **PHPCP_GITHUB_USER** - The GitHub username (i.e: joaocsilva) (required when
  using option `--git` in command `repo:list`)
- **PHPCP_GITHUB_REPO** - The GitHub repository (i.e: phpcp) (required when
  using option `--git` in command `repo:list`)

### Using configuration file

PHPCP will search and load the following configuration files `phpcp.yml.dist`
and `phpcp.yml`.

An example of configuration file to interact with CPanel only using static
values.

```yaml
phpcp:
  cpanel:
    token: 'aBCF'
    url: 'https://cpanelXXX.dnscpanel.com:XXXX/cpsessXXXXXXXXXX'
```

### Using command options as configuration

When using a command option, the value defined in the configuration file
is overridden.

**CPanel related options**

Used in commands: [repos](#command-repolist), [pull](#command-repopull),
[deploy](#command-repodeploy)

- `--cp-token` - The CPanel auth token
- `--cp-base-url` - The CPanel base url

**GitHub related options**

Use when option `--git` is used in commands: [repos](#command-repolist)

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
  repo:list --cp-token="aBcdef" --cp-base-url="https://cpanelXXX.dnscpanel.com:XXXX/cpsessXXXXXXXXXX"

Options:
  --git                        Check for branch HEAD commit hash in GitHub.
  --github-token=GITHUB-TOKEN  The GitHub API access token
  --github-user=GITHUB-USER    The GitHub username, i.e: joaocsilva
  --github-repo=GITHUB-REPO    The GitHub repository, i.e: phpcp
  --cp-token=CP-TOKEN          The CPanel auth token, usually a base64 encode
  --cp-base-url=CP-BASE-URL    The CPanel base url, i.e: https://cpanelXXX.dnscpanel.com:XXXX/cpsessXXXXXXXXXX
```

View a specific configuration.

```shell
$ php vendor/bin/phpcp config phpcp.cpanel.url
```

### Command `repo:list`

List the available repositories that the current user has access to.
It is the same list present in the CPanel interface for `Git™ Version Control`
tool.

```shell
$ php vendor/bin/phpcp repo:list
```

### Command `repo:pull`

Perform the action to pull a repository in the CPanel interface
`Git™ Version Control`>`Pull or Deploy`, button `Update from Remote`.
This will pull the changes from the remote.

The command has a required parameter for the branch name to identify the repo.

```shell
$ php vendor/bin/phpcp repo:pull master
```

### Command `repo:deploy`

Perform the action to deploy a repository in the CPanel interface
`Git™ Version Control`>`Pull or Deploy`, button `Deploy HEAD Commit`.
This will trigger the CPanel deployment and execute the `.cpanel` file.

The command has a required argument for the branch name to identify the repo.

```shell
$ php vendor/bin/phpcp repo:deploy master
```

### Command `file:download`

Output a file content or to a file with option `--output`.

The command has a required arguments dir and file to specify the directory where
the file is and the filename.

Print the composer.json content.

```shell
$ php vendor/bin/phpcp file:download /home/user composer.json
```

Download the composer.json file to a file named remote-composer.json.

```shell
$ php vendor/bin/phpcp file:download /home/user composer.json --output=remote-composer.json
```

## Local development

You can use docker to run the project locally.

The docker-compose.yml file contains the environment variables needs for the commands.

```yaml
PHPCP_GITHUB_TOKEN:
PHPCP_CPANEL_TOKEN:
PHPCP_CPANEL_URL:
```

You can map these variables to your own env variables with.

```yaml
PHPCP_GITHUB_TOKEN: ${GITHUB_TOKEN}
PHPCP_CPANEL_TOKEN: ${CPANEL_TOKEN}
PHPCP_CPANEL_URL: ${CPANEL_URL}
```

Usage.

```shell
# Start containers.
$ docker compose up -d
# Install composer dependencies..
$ docker compose exec web composer install
# Check if the phpcp is running.
$ docker compose exec web php phpcp
# List CPanel repositories.
$ docker compose exec web php phpcp repo:list
```
