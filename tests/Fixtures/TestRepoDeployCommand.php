<?php

namespace PhpCP\Tests\Fixtures;

use PhpCP\Command\RepoDeployCommand;

/**
 * The deploy command without real waits.
 */
class TestRepoDeployCommand extends RepoDeployCommand {

  /**
   * The seconds that the command waited, in order.
   *
   * @var int[]
   */
  public array $pauses = [];

  /**
   * {@inheritdoc}
   */
  protected function pause(int $seconds): void {
    $this->pauses[] = $seconds;
  }

}
