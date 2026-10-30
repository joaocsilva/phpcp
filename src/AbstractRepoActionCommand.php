<?php

namespace PhpCP;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Base for the commands that run an action in a single CPanel repository.
 *
 * It finds the repository, asks for confirmation and runs the action. The exit
 * code is a failure when the repository is not found, is not valid for the
 * action, or when the confirmation is declined.
 */
abstract class AbstractRepoActionCommand extends AbstractCpanelCommand {

  /**
   * Run the action in the repository.
   *
   * @param array $repository
   *   The CPanel repository information.
   */
  abstract protected function repoAction(array $repository): void;

  /**
   * Check if the action can run in the repository.
   *
   * @param array $repository
   *   The CPanel repository information.
   *
   * @return string|null
   *   An error message when the action cannot run, otherwise NULL.
   */
  protected function repoValidate(array $repository): ?string {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->addArgument(
        'repo',
        InputArgument::REQUIRED,
        'The repository name, as shown by repo:list'
      )
      ->addOption(
        'yes',
        'y',
        InputOption::VALUE_NONE,
        'Do not ask for confirmation'
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

    $error = $this->repoValidate($repository);
    if ($error !== NULL) {
      $io->error($error);
      return Command::FAILURE;
    }

    $io->writeln("Using repo $repo with repository root {$repository['repository_root']}");
    if (!$input->getOption('yes') && !$io->confirm('Do you want to proceed?')) {
      $io->warning('Aborted, nothing was changed.');
      return Command::FAILURE;
    }

    $this->repoAction($repository);
    $io->success('OK');
    return Command::SUCCESS;
  }

}
