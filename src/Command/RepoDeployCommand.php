<?php

namespace PhpCP\Command;

use PhpCP\AbstractRepoActionCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Run a deployment.
 *
 * @see https://api.docs.cpanel.net/specifications/cpanel.openapi/deployment-settings
 */
class RepoDeployCommand extends AbstractRepoActionCommand {

  /**
   * The default seconds to wait for the deployment.
   */
  private const DEFAULT_TIMEOUT = 300;

  /**
   * The default seconds between the checks of the deployment.
   */
  private const DEFAULT_INTERVAL = 3;

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setName('repo:deploy')
      ->setDescription('Deploy a CPanel repository')
      ->setAliases(['deploy'])
      ->addOption(
        'if-changed',
        NULL,
        InputOption::VALUE_NONE,
        'Skip the deployment when the HEAD commit is the last deployed commit.'
      )
      ->addOption(
        'wait',
        NULL,
        InputOption::VALUE_NONE,
        'Wait until the deployment finishes, it fails when the deployment fails or on timeout.'
      )
      ->addOption(
        'timeout',
        NULL,
        InputOption::VALUE_REQUIRED,
        'The seconds to wait for the deployment with --wait.',
        self::DEFAULT_TIMEOUT
      )
      ->addOption(
        'interval',
        NULL,
        InputOption::VALUE_REQUIRED,
        'The seconds between each check of the deployment with --wait.',
        self::DEFAULT_INTERVAL
      )
      ->addUsage('my-site')
      ->addUsage('my-site --yes')
      ->addUsage('my-site --yes --wait')
      ->addUsage('my-site --yes --wait --if-changed')
      ->setHelp('This command triggers the CPanel action - Deploy HEAD Commit for a repository');
    parent::configure();
  }

  /**
   * {@inheritdoc}
   */
  protected function repoValidateInput(InputInterface $input): ?string {
    if (!$input->getOption('wait')) {
      return NULL;
    }
    foreach (['timeout', 'interval'] as $name) {
      $value = (string) $input->getOption($name);
      if (!ctype_digit($value) || (int) $value < 1) {
        return "The option --$name must be a number of seconds higher than 0.";
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function repoValidate(array $repository): ?string {
    if (empty($repository['deployable'])) {
      return sprintf(
        'The repository %s is not deployable. CPanel requires a .cpanel.yml file in the repository root'
        . ' and a clean working tree.',
        $repository['name'] ?? ''
      );
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   *
   * With the option --if-changed there is nothing to deploy when the last
   * deployed commit is the HEAD commit.
   */
  protected function repoSkipReason(InputInterface $input, array $repository): ?string {
    if (!$input->getOption('if-changed')) {
      return NULL;
    }
    $head = self::repoHead($repository);
    if ($head !== '' && $head === self::repoDeployed($repository)) {
      return sprintf('Nothing to deploy, the HEAD commit %s is already deployed.', self::shortCommit($head));
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function repoAction(array $repository): array {
    return (array) $this->cpanelApiCall('/execute/VersionControlDeployment/create', [
      'repository_root' => $repository['repository_root'],
    ]);
  }

  /**
   * {@inheritdoc}
   *
   * The deployment is a task in the CPanel queue, the action only adds it.
   */
  protected function repoFinish(InputInterface $input, SymfonyStyle $io, array $repository, array $result): int {
    $deployId = (string) ($result['deploy_id'] ?? '');
    $logPath = (string) ($result['log_path'] ?? '');
    $io->writeln(sprintf(
      'Deployment %s queued%s',
      $deployId === '' ? '(unknown id)' : $deployId,
      $logPath === '' ? '' : ", log file: $logPath"
    ));
    if (!$input->getOption('wait')) {
      return Command::SUCCESS;
    }
    if ($deployId === '') {
      $io->error('CPanel did not return the deployment id, it is not possible to wait for it.');
      return Command::FAILURE;
    }
    return $this->waitDeployment($input, $io, $repository, $deployId, $logPath);
  }

  /**
   * Waits the seconds between the checks.
   */
  protected function pause(int $seconds): void {
    sleep($seconds);
  }

  /**
   * Waits until the deployment succeeds, fails, is canceled or the timeout.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The command input.
   * @param \Symfony\Component\Console\Style\SymfonyStyle $io
   *   The input and output style.
   * @param array $repository
   *   The CPanel repository information.
   * @param string $deployId
   *   The deployment id returned by CPanel.
   * @param string $logPath
   *   The deployment log file returned by CPanel, it can be empty.
   *
   * @return int
   *   The command exit code.
   */
  private function waitDeployment(
    InputInterface $input,
    SymfonyStyle $io,
    array $repository,
    string $deployId,
    string $logPath
  ): int {
    $timeout = (int) $input->getOption('timeout');
    $interval = (int) $input->getOption('interval');
    $waited = 0;
    $state = '';
    while (TRUE) {
      $deployment = $this->findDeployment($deployId);
      $logPath = (string) ($deployment['log_path'] ?? $logPath);
      $newState = $this->deploymentState($deployment);
      if ($newState !== $state) {
        $state = $newState;
        $io->writeln("Deployment $deployId: $state");
      }

      if ($state === 'succeeded') {
        return $this->verifyDeployment($io, $repository);
      }
      if ($state === 'failed' || $state === 'canceled') {
        $io->error(sprintf(
          'The deployment %s %s.%s',
          $deployId,
          $state,
          $logPath === '' ? '' : " Check the log file: $logPath"
        ));
        return Command::FAILURE;
      }
      if ($waited >= $timeout) {
        $io->error(sprintf(
          'Timeout after %d seconds, the deployment %s is still %s.%s',
          $waited,
          $deployId,
          $state,
          $logPath === '' ? '' : " Log file: $logPath"
        ));
        return Command::FAILURE;
      }
      $this->pause($interval);
      $waited += $interval;
    }
  }

  /**
   * Checks that the last deployed commit is the HEAD commit.
   *
   * A different commit is a warning, because the deployment finished, the HEAD
   * commit can be changed after it, i.e: by a pull.
   */
  private function verifyDeployment(SymfonyStyle $io, array $repository): int {
    $current = $this->repoCurrent($repository['name']);
    $head = self::repoHead($current);
    $deployed = self::repoDeployed($current);
    if ($head !== '' && $head === $deployed) {
      $io->writeln(sprintf('The HEAD commit %s is deployed.', self::shortCommit($head)));
    }
    else {
      $io->warning(sprintf(
        'The deployment succeeded, but CPanel reports the last deployed commit %s and the HEAD commit %s.',
        self::shortCommit($deployed),
        self::shortCommit($head)
      ));
    }
    return Command::SUCCESS;
  }

  /**
   * Finds a deployment in the deployment tasks.
   *
   * @return array|null
   *   The deployment, NULL if CPanel does not list it yet.
   */
  private function findDeployment(string $deployId): ?array {
    $deployments = $this->cpanelApiCall('/execute/VersionControlDeployment/retrieve');
    foreach (is_array($deployments) ? $deployments : [] as $deployment) {
      if (is_array($deployment) && (string) ($deployment['deploy_id'] ?? '') === $deployId) {
        return $deployment;
      }
    }
    return NULL;
  }

  /**
   * Returns the deployment state, from the timestamps of the task.
   *
   * @param array|null $deployment
   *   The deployment, NULL if CPanel does not list it yet.
   *
   * @return string
   *   One of: queued, running, succeeded, failed or canceled.
   */
  private function deploymentState(?array $deployment): string {
    $timestamps = $deployment['timestamps'] ?? [];
    foreach (['succeeded', 'failed', 'canceled'] as $state) {
      if (!empty($timestamps[$state])) {
        return $state;
      }
    }
    return empty($timestamps['active']) ? 'queued' : 'running';
  }

}
