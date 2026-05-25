<?php

namespace PhpCP\Command;

use PhpCP\AbstractCpanelCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Run a deployment.
 *
 * @see https://api.docs.cpanel.net/specifications/cpanel.openapi/deployment-settings
 */
class RepoDeployCommand extends AbstractCpanelCommand {

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setName('repo:deploy')
      ->setDescription('Deploy a CPanel repository')
      ->setAliases(['deploy'])
      ->addArgument(
        'repo',
        InputArgument::REQUIRED,
        'Filter a specific repository.'
      );
    parent::configure();
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $io = new SymfonyStyle($input, $output);
    $repo = $input->getArgument('repo');
    $results = $this->cpanelGetRepositories($repo);
    if (empty($results)) {
      $io->error("Could not find repository: $repo");
      return Command::FAILURE;
    }
    $repository = $results[0];

    $io->writeln("Using repo $repo with repository root {$repository['repository_root']}");
    if ($io->confirm('Do you want to proceed?')) {
      $root = urlencode($repository['repository_root']);
      $url = "/execute/VersionControlDeployment/create?repository_root=$root";
      $this->cpanelApiCall($url);
      $io->success('OK');
    }
    return Command::SUCCESS;
  }

}
