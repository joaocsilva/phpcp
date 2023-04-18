<?php

namespace PhpCP\Command;

use PhpCP\AbstractCpanelCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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
        'branch',
        InputArgument::REQUIRED,
        'The branch identifies the repository to deploy'
      );
    parent::configure();
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $io = new SymfonyStyle($input, $output);
    $branch = $input->getArgument('branch');
    $results = $this->cpanelGetRepositories();
    $branches = $this->cpanelGetBranches($results, $branch);

    $io->writeln("Deploying branch $branch with repository root $branches[$branch]");
    if ($io->confirm('Do you want to proceed?')) {
      $root = urlencode($branches[$branch]);
      $url = "/execute/VersionControlDeployment/create?repository_root=$root";
      $this->cpanelApiCall($url);
      $io->success('OK');
    }
    return Command::SUCCESS;
  }

}
