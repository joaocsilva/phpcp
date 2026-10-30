<?php

namespace PhpCP\Command;

use PhpCP\AbstractRepoActionCommand;

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
      ->setHelp('This command triggers the CPanel action - Update from Remote for a repository');
    parent::configure();
  }

  /**
   * {@inheritdoc}
   */
  protected function repoAction(array $repository): void {
    $this->cpanelApiCall('/execute/VersionControl/update', [
      'repository_root' => $repository['repository_root'],
      'branch' => $repository['branch'],
    ]);
  }

}
