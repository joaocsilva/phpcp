<?php

namespace PhpCP\Command;

use PhpCP\AbstractCpanelCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RepoPullCommand extends AbstractCpanelCommand {

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setName('repo:pull')
      ->setDescription('Pull a CPanel repository')
      ->setAliases(['pull'])
      ->addArgument(
        'branch',
        InputArgument::REQUIRED,
        'The branch identifies the repository to pull.'
      )
      ->setHelp('This command triggers the CPanel action - Update from Remote for a repository');
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

    $io->writeln("Using branch $branch with repository root $branches[$branch]");
    if ($io->confirm('Do you want to proceed?')) {
      $root = urlencode($branches[$branch]);
      $url = "/execute/VersionControl/update?repository_root=$root&branch=$branch";
      $this->cpanelApiCall($url);
      $io->success('OK');
    }
    return Command::SUCCESS;
  }

}
