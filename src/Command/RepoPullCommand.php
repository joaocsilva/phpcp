<?php

namespace PhpCP\Command;

use PhpCP\AbstractRepoActionCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Pull a repository.
 *
 * @see https://api.docs.cpanel.net/specifications/cpanel.openapi/repository-management/versioncontrol::update
 */
class RepoPullCommand extends AbstractRepoActionCommand {

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setName('repo:pull')
      ->setDescription('Pull a CPanel repository')
      ->setAliases(['pull'])
      ->addUsage('my-site')
      ->addUsage('my-site --yes')
      ->setHelp(
        'This command triggers the CPanel action - Update from Remote for a repository,'
        . ' then prints the HEAD commit before and after, and the last deployed commit.'
      );
    parent::configure();
  }

  /**
   * {@inheritdoc}
   */
  protected function repoAction(array $repository): array {
    return (array) $this->cpanelApiCall('/execute/VersionControl/update', [
      'repository_root' => $repository['repository_root'],
      'branch' => $repository['branch'],
    ]);
  }

  /**
   * {@inheritdoc}
   *
   * Prints if the pull changed the HEAD commit, and if it is not deployed yet.
   */
  protected function repoFinish(InputInterface $input, SymfonyStyle $io, array $repository, array $result): int {
    $branch = $repository['branch'] ?? '';
    $current = $this->repoCurrent($repository['name']);
    $before = self::repoHead($repository);
    $head = self::repoHead($current);
    if ($head === '') {
      $io->writeln('The HEAD commit is not available.');
      return Command::SUCCESS;
    }

    if ($head === $before) {
      $io->writeln(sprintf('Already up to date, %s is at the commit %s.', $branch, self::shortCommit($head)));
    }
    else {
      $io->writeln(sprintf(
        'Updated %s from the commit %s to %s.',
        $branch,
        self::shortCommit($before),
        self::shortCommit($head)
      ));
    }

    $deployed = self::repoDeployed($current);
    if ($deployed !== $head) {
      $io->writeln(sprintf(
        'The HEAD commit is not deployed yet, the last deployed commit is %s.',
        self::shortCommit($deployed)
      ));
    }
    return Command::SUCCESS;
  }

}
