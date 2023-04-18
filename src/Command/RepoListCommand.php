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

    $result = $this->cpanelGetRepositories();
    if (!empty($result)) {
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
      foreach ($result as $repo) {
        $row = [
          new TableSeparator(),
          $repo['name'],
          $repo['branch'],
          $repo['repository_root'],
          preg_replace('#/[^/]*@#', '/', $repo['source_repository']['url']),
          $repo['deployable'],
          $branches[$repo['branch']] ?? '[use --git to check]',
          '',
          $repo['last_update']['identifier'],
          $this->date($repo['last_update']['date']),
          trim($repo['last_update']['message']),
          '',
          $repo['last_deployment']['repository_state']['identifier'],
          $this->date($repo['last_deployment']['timestamps']['active']),
          $this->date($repo['last_deployment']['timestamps']['succeeded']),
          $repo['last_deployment']['log_path'],
          new TableSeparator(),
        ];

        $io->horizontalTable($header, [$row]);
      }
    }

    return Command::SUCCESS;
  }

  /**
   * Format given time to date.
   *
   * @param int|string $time
   *   The time to convert into date.
   */
  private function date($time): string {
    return date('Y-m-d H:i:s', intval($time));
  }

}
