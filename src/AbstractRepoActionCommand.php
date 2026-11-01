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
 * It validates the input, finds the repository, asks for confirmation, runs the
 * action and then the finish step. The exit code is a failure when the input is
 * not valid, the repository is not found or is not valid for the action, the
 * confirmation is declined, or the finish step fails.
 */
abstract class AbstractRepoActionCommand extends AbstractCpanelCommand {

  /**
   * Run the action in the repository.
   *
   * @param array $repository
   *   The CPanel repository information.
   *
   * @return array
   *   The data returned by the CPanel API.
   */
  abstract protected function repoAction(array $repository): array;

  /**
   * Check the command input, before any request is sent.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The command input.
   *
   * @return string|null
   *   An error message when the input is not valid, otherwise NULL.
   */
  protected function repoValidateInput(InputInterface $input): ?string {
    return NULL;
  }

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
   * Check if there is nothing to do, so the action is skipped.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The command input.
   * @param array $repository
   *   The CPanel repository information.
   *
   * @return string|null
   *   The reason to skip the action, otherwise NULL.
   */
  protected function repoSkipReason(InputInterface $input, array $repository): ?string {
    return NULL;
  }

  /**
   * Runs after the action, i.e: to print the result or to wait for it.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The command input.
   * @param \Symfony\Component\Console\Style\SymfonyStyle $io
   *   The input and output style.
   * @param array $repository
   *   The CPanel repository information, before the action.
   * @param array $result
   *   The data returned by the action.
   *
   * @return int
   *   The command exit code.
   */
  protected function repoFinish(InputInterface $input, SymfonyStyle $io, array $repository, array $result): int {
    return Command::SUCCESS;
  }

  /**
   * Returns the current information of a repository.
   *
   * @param string $name
   *   The repository name.
   *
   * @return array
   *   The CPanel repository information, empty if it is not found.
   */
  protected function repoCurrent(string $name): array {
    return $this->cpanelGetRepositories($name)[0] ?? [];
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
    $error = $this->repoValidateInput($input);
    if ($error !== NULL) {
      $io->error($error);
      return Command::FAILURE;
    }

    $repo = $input->getArgument('repo');
    $repository = $this->repoCurrent($repo);
    if (empty($repository)) {
      $io->error("Could not find repository: $repo");
      return Command::FAILURE;
    }

    $error = $this->repoValidate($repository);
    if ($error !== NULL) {
      $io->error($error);
      return Command::FAILURE;
    }

    $io->writeln("Using repo $repo with repository root {$repository['repository_root']}");
    $io->writeln(sprintf(
      'HEAD commit: %s, last deployed commit: %s',
      self::shortCommit(self::repoHead($repository)),
      self::shortCommit(self::repoDeployed($repository))
    ));

    $skip = $this->repoSkipReason($input, $repository);
    if ($skip !== NULL) {
      $io->writeln($skip);
      return Command::SUCCESS;
    }

    if (!$input->getOption('yes') && !$io->confirm('Do you want to proceed?')) {
      $io->warning('Aborted, nothing was changed.');
      return Command::FAILURE;
    }

    $result = $this->repoAction($repository);
    $code = $this->repoFinish($input, $io, $repository, $result);
    if ($code === Command::SUCCESS) {
      $io->success('OK');
    }
    return $code;
  }

  /**
   * Returns the HEAD commit of a repository.
   *
   * @param array $repository
   *   The CPanel repository information.
   *
   * @return string
   *   The commit hash, empty if it is not available.
   */
  protected static function repoHead(array $repository): string {
    $update = $repository['last_update'] ?? NULL;
    return is_array($update) ? (string) ($update['identifier'] ?? '') : '';
  }

  /**
   * Returns the last deployed commit of a repository.
   *
   * @param array $repository
   *   The CPanel repository information.
   *
   * @return string
   *   The commit hash, empty if the repository was never deployed.
   */
  protected static function repoDeployed(array $repository): string {
    $deployment = $repository['last_deployment'] ?? NULL;
    $state = is_array($deployment) ? ($deployment['repository_state'] ?? NULL) : NULL;
    return is_array($state) ? (string) ($state['identifier'] ?? '') : '';
  }

  /**
   * Returns an abbreviated commit hash to print.
   */
  protected static function shortCommit(string $commit): string {
    return $commit === '' ? '(none)' : substr($commit, 0, 7);
  }

}
