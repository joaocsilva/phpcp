<?php

namespace PhpCP\Command;

use PhpCP\AbstractCpanelCommand;
use PhpCP\Traits\GitHubApiTrait;
use PhpCP\Traits\GitHubCommandTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * List repositories.
 *
 * @see https://api.docs.cpanel.net/specifications/cpanel.openapi/repository-management/versioncontrol::retrieve
 */
class RepoListCommand extends AbstractCpanelCommand {

  use GitHubCommandTrait;
  use GitHubApiTrait;

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setName('repo:list')
      ->setDescription('List CPanel repositories')
      ->setAliases(['repos'])
      ->addOption(
        'git',
        NULL,
        InputOption::VALUE_NONE,
        'Check for branch HEAD commit hash in GitHub.'
      )
      ->addOption(
        'repo',
        NULL,
        InputOption::VALUE_REQUIRED,
        'Filter a specific repository.'
      )
      ->githubAddOptions()
      ->addUsage('--git');
    parent::configure();
  }

  /**
   * {@inheritdoc}
   */
  protected function initialize(InputInterface $input, OutputInterface $output): void {
    parent::initialize($input, $output);
    $this->githubInitialize($input, $output);
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output): void {
    parent::interact($input, $output);
    if (!empty($input->getOption('git'))) {
      $this->githubInteract($input, $output);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $this->cpanelValidate();
    $io = new SymfonyStyle($input, $output);
    if ($git = !empty($input->getOption('git'))) {
      $this->githubValidate();
    }
    $filter = $input->hasOption('repo') && !empty($input->getOption('repo'))
      ? $input->getOption('repo')
      : NULL;
    $repositories = $this->cpanelGetRepositories($filter);
    if (empty($repositories)) {
      $io->warning($filter ? "Could not find repository: $filter" : 'No repositories found.');
      return $filter ? Command::FAILURE : Command::SUCCESS;
    }

    $branches = $git ? $this->getGithubBranches() : [];
    $header = [
      new TableSeparator(),
      'Name',
      'Branch',
      'Path',
      'Source repo',
      'Deployable',
      'GitHub commit',
      '',
      'HEAD commit',
      'HEAD date',
      'HEAD message',
      '',
      'Deployed commit',
      'Deployed start',
      'Deployed succeeded',
      'Deployed log file',
      new TableSeparator(),
    ];
    foreach ($repositories as $repository) {
      $update = $repository['last_update'] ?? [];
      $deployment = $repository['last_deployment'] ?? [];
      $branch = $repository['branch'] ?? '';
      if (!$git) {
        $githubCommit = '[use --git to check]';
      }
      else {
        $githubCommit = $branches[$branch] ?? '[branch not found in GitHub]';
      }
      $row = [
        new TableSeparator(),
        $repository['name'] ?? '-',
        $branch ?: '-',
        $repository['repository_root'] ?? '-',
        $this->sourceUrl($repository),
        !empty($repository['deployable']) ? 'yes' : 'no',
        $githubCommit,
        '',
        $update['identifier'] ?? '-',
        $this->date($update['date'] ?? NULL),
        trim($update['message'] ?? '') ?: '-',
        '',
        $deployment['repository_state']['identifier'] ?? '-',
        $this->date($deployment['timestamps']['active'] ?? NULL),
        $this->date($deployment['timestamps']['succeeded'] ?? NULL),
        $deployment['log_path'] ?? '-',
        new TableSeparator(),
      ];

      $io->horizontalTable($header, [$row]);
    }

    return Command::SUCCESS;
  }

  /**
   * Returns the source repository url without credentials.
   *
   * @param array $repository
   *   The CPanel repository information.
   */
  private function sourceUrl(array $repository): string {
    $url = $repository['source_repository']['url'] ?? '';
    return $url === '' ? '-' : preg_replace('#/[^/]*@#', '/', $url);
  }

  /**
   * Format given time to date.
   *
   * @param int|string|null $time
   *   The time to convert into date, empty values (i.e: a deployment that has
   *   not succeeded) are printed as a dash.
   */
  private function date($time): string {
    return empty($time) ? '-' : date('Y-m-d H:i:s', intval($time));
  }

}
