<?php

namespace PhpCP\Command;

use PhpCP\AbstractRepoActionCommand;

/**
 * Run a deployment.
 *
 * @see https://api.docs.cpanel.net/specifications/cpanel.openapi/deployment-settings
 */
class RepoDeployCommand extends AbstractRepoActionCommand {

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setName('repo:deploy')
      ->setDescription('Deploy a CPanel repository')
      ->setAliases(['deploy'])
      ->addUsage('my-site')
      ->addUsage('my-site --yes')
      ->setHelp('This command triggers the CPanel action - Deploy HEAD Commit for a repository');
    parent::configure();
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
   */
  protected function repoAction(array $repository): void {
    $this->cpanelApiCall('/execute/VersionControlDeployment/create', [
      'repository_root' => $repository['repository_root'],
    ]);
  }

}
