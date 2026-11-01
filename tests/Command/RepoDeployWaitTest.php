<?php

namespace PhpCP\Tests\Command;

use PhpCP\Tests\CpanelTestCase;
use PhpCP\Tests\Fixtures\TestRepoDeployCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class RepoDeployWaitTest extends CpanelTestCase {

  private const LOG_PATH = '/home/user/.cpanel/logs/vc_1525289448.759720_git_deploy.log';

  /**
   * Tests that without --wait the deployment id and the log are printed.
   */
  public function testWithoutWait(): void {
    $command = new TestRepoDeployCommand();
    $tester = $this->deploy($command, [$this->repositories(self::COMMIT_B, self::COMMIT_A)], []);
    $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
    $this->assertStringContainsString('HEAD commit: f00dbab, last deployed commit: a1b2c3d', $tester->getDisplay());
    $this->assertStringContainsString('Deployment 12 queued, log file: ' . self::LOG_PATH, $tester->getDisplay());
    $this->assertFalse($this->requested('VersionControlDeployment/retrieve'));
  }

  /**
   * Tests that it follows the deployment until it succeeds.
   */
  public function testWaitUntilSucceeded(): void {
    $command = new TestRepoDeployCommand();
    $tester = $this->deploy($command, [
      $this->repositories(self::COMMIT_B, self::COMMIT_A),
      $this->repositories(self::COMMIT_B, self::COMMIT_B),
    ], [
      $this->deployments('queued'),
      $this->deployments('running'),
      $this->deployments('succeeded'),
    ], ['--wait' => TRUE]);
    $display = $tester->getDisplay();
    $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
    $this->assertSame([3, 3], $command->pauses);
    $this->assertStringContainsString('Deployment 12: queued', $display);
    $this->assertStringContainsString('Deployment 12: running', $display);
    $this->assertStringContainsString('Deployment 12: succeeded', $display);
    $this->assertStringContainsString('The HEAD commit f00dbab is deployed.', $display);
  }

  /**
   * Tests that a deployment of the commit that is already deployed is awaited.
   */
  public function testWaitsForRedeployOfTheSameCommit(): void {
    $command = new TestRepoDeployCommand();
    $tester = $this->deploy($command, [$this->repositories(self::COMMIT_A, self::COMMIT_A)], [
      $this->deployments('queued'),
      $this->deployments('succeeded'),
    ], ['--wait' => TRUE]);
    $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
    $this->assertSame([3], $command->pauses);
  }

  /**
   * Tests that a failed or canceled deployment fails with the log file.
   */
  public function testFailedAndCanceled(): void {
    foreach (['failed', 'canceled'] as $state) {
      $command = new TestRepoDeployCommand();
      $tester = $this->deploy($command, [$this->repositories(self::COMMIT_B, self::COMMIT_A)], [
        $this->deployments('running'),
        $this->deployments($state),
      ], ['--wait' => TRUE]);
      $this->assertSame(Command::FAILURE, $tester->getStatusCode());
      $this->assertSame([3], $command->pauses);
      $this->assertStringContainsString("Deployment 12: $state", $tester->getDisplay());
      $this->assertStringContainsString('Check the log file', $tester->getDisplay());
      $this->assertStringContainsString(self::LOG_PATH, $tester->getDisplay());
    }
  }

  /**
   * Tests that it fails when the deployment does not finish in the timeout.
   */
  public function testTimeout(): void {
    $command = new TestRepoDeployCommand();
    $tester = $this->deploy($command, [$this->repositories(self::COMMIT_B, self::COMMIT_A)], [
      $this->deployments('running'),
    ], ['--wait' => TRUE, '--timeout' => '6', '--interval' => '3']);
    $this->assertSame(Command::FAILURE, $tester->getStatusCode());
    $this->assertSame([3, 3], $command->pauses);
    $display = $tester->getDisplay();
    $this->assertStringContainsString('Timeout after 6 seconds, the deployment 12 is still running.', $display);
  }

  /**
   * Tests that a deployment that CPanel does not list yet is waited as queued.
   */
  public function testDeploymentNotListedYet(): void {
    $command = new TestRepoDeployCommand();
    $tester = $this->deploy($command, [
      $this->repositories(self::COMMIT_B, self::COMMIT_A),
      $this->repositories(self::COMMIT_B, self::COMMIT_B),
    ], [
      $this->apiOk([]),
      $this->deployments('succeeded'),
    ], ['--wait' => TRUE]);
    $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
    $this->assertSame([3], $command->pauses);
    $this->assertStringContainsString('Deployment 12: queued', $tester->getDisplay());
  }

  /**
   * Tests that a different last deployed commit is a warning, not a failure.
   */
  public function testSucceededButDifferentCommit(): void {
    $command = new TestRepoDeployCommand();
    $tester = $this->deploy($command, [
      $this->repositories(self::COMMIT_B, self::COMMIT_A),
      $this->repositories(self::COMMIT_B, self::COMMIT_A),
    ], [$this->deployments('succeeded')], ['--wait' => TRUE]);
    $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
    $display = $tester->getDisplay();
    $this->assertStringContainsString('The deployment succeeded, but CPanel reports the last deployed', $display);
  }

  /**
   * Tests that --if-changed skips the deployment of the deployed commit.
   */
  public function testIfChangedSkips(): void {
    $command = new TestRepoDeployCommand();
    $tester = $this->deploy($command, [$this->repositories(self::COMMIT_A, self::COMMIT_A)], [], [
      '--if-changed' => TRUE,
      '--wait' => TRUE,
    ]);
    $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
    $display = $tester->getDisplay();
    $this->assertStringContainsString('Nothing to deploy, the HEAD commit a1b2c3d is already deployed.', $display);
    $this->assertFalse($this->requested('VersionControlDeployment/create'));
  }

  /**
   * Tests that --if-changed deploys when the commits are different.
   */
  public function testIfChangedDeploys(): void {
    $command = new TestRepoDeployCommand();
    $repositories = [$this->repositories(self::COMMIT_B, self::COMMIT_A)];
    $tester = $this->deploy($command, $repositories, [], ['--if-changed' => TRUE]);
    $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
    $this->assertTrue($this->requested('VersionControlDeployment/create'));
  }

  /**
   * Tests that --if-changed deploys a repository that was never deployed.
   */
  public function testIfChangedDeploysWhenNeverDeployed(): void {
    $command = new TestRepoDeployCommand();
    $tester = $this->deploy($command, [$this->repositories(self::COMMIT_A)], [], ['--if-changed' => TRUE]);
    $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
    $this->assertTrue($this->requested('VersionControlDeployment/create'));
  }

  /**
   * Tests that the wait options are validated before sending any request.
   */
  public function testInvalidWaitOptions(): void {
    foreach ([['--timeout' => 'abc'], ['--interval' => '0'], ['--timeout' => '-5']] as $input) {
      $tester = $this->deploy(new TestRepoDeployCommand(), [$this->repositories(self::COMMIT_A)], [], $input + [
        '--wait' => TRUE,
      ]);
      $this->assertSame(Command::FAILURE, $tester->getStatusCode());
      $this->assertCount(0, $this->requests);
    }
  }

  /**
   * Returns the CPanel response of the deployments, with the deployment 12.
   *
   * @param string $state
   *   One of: queued, running, succeeded, failed or canceled.
   */
  private function deployments(string $state): string {
    $timestamps = ['queued' => '1525289448.1'];
    if ($state !== 'queued') {
      $timestamps['active'] = '1525289449.2';
    }
    if (in_array($state, ['succeeded', 'failed', 'canceled'])) {
      $timestamps[$state] = '1525289450.3';
    }
    return $this->apiOk([
      ['deploy_id' => 11, 'log_path' => '/other.log', 'timestamps' => ['queued' => '1', 'succeeded' => '2']],
      ['deploy_id' => 12, 'log_path' => self::LOG_PATH, 'timestamps' => $timestamps],
    ]);
  }

  /**
   * Runs the deploy command with a mocked CPanel API.
   *
   * @param \PhpCP\Tests\Fixtures\TestRepoDeployCommand $command
   *   The command.
   * @param string[] $repositories
   *   The CPanel response of the repositories in each request of the list, the
   *   first request finds the repository, the second is the final check.
   * @param string[] $deployments
   *   The CPanel response of the deployments in each request of the list.
   * @param array $input
   *   The command options.
   */
  private function deploy(
    TestRepoDeployCommand $command,
    array $repositories,
    array $deployments,
    array $input = []
  ): CommandTester {
    $command->setHttpClient($this->mockClient([
      'VersionControl/retrieve' => ['sequence' => $repositories],
      'VersionControlDeployment/create' => [
        'body' => $this->apiOk(['deploy_id' => '12', 'log_path' => self::LOG_PATH, 'timestamps' => ['queued' => '1']]),
      ],
      'VersionControlDeployment/retrieve' => ['sequence' => $deployments],
    ]));
    $tester = new CommandTester($command);
    $tester->execute($input + [
      'repo' => 'my-site',
      '--yes' => TRUE,
      '--cp-user' => 'me',
      '--cp-token' => 'TOKEN',
      '--cp-base-url' => 'https://cpanel.example.com:2083',
    ], ['interactive' => FALSE]);
    return $tester;
  }

  /**
   * Returns whether any request url contains the text.
   */
  private function requested(string $text): bool {
    foreach ($this->requestedUrls() as $url) {
      if (str_contains($url, $text)) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
