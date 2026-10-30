<?php

namespace PhpCP\Tests\Command;

use PhpCP\Command\RepoDeployCommand;
use PhpCP\Command\RepoPullCommand;
use PhpCP\Tests\CpanelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class RepoActionCommandTest extends CpanelTestCase {

  /**
   * Tests that the pull runs without confirmation using --yes.
   */
  public function testPullWithYes(): void {
    $tester = $this->runCommand(new RepoPullCommand(), [$this->repository()], ['repo' => 'my-site', '--yes' => TRUE]);
    $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
    $this->assertTrue($this->requested('VersionControl/update'));
    $this->assertTrue($this->requested('branch=main'));
    $this->assertTrue($this->requested('repository_root=/home/user/repositories/my-site'));
  }

  /**
   * Tests that confirming the question runs the action.
   */
  public function testPullConfirmed(): void {
    $tester = $this->runCommand(new RepoPullCommand(), [$this->repository()], ['repo' => 'my-site'], ['yes']);
    $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
    $this->assertTrue($this->requested('VersionControl/update'));
  }

  /**
   * Tests that declining the confirmation does nothing and fails.
   */
  public function testPullDeclined(): void {
    $tester = $this->runCommand(new RepoPullCommand(), [$this->repository()], ['repo' => 'my-site'], ['no']);
    $this->assertSame(Command::FAILURE, $tester->getStatusCode());
    $this->assertStringContainsString('Aborted', $tester->getDisplay());
    $this->assertFalse($this->requested('VersionControl/update'));
  }

  /**
   * Tests that an unknown repository fails without running the action.
   */
  public function testRepositoryNotFound(): void {
    $tester = $this->runCommand(new RepoPullCommand(), [$this->repository()], ['repo' => 'missing', '--yes' => TRUE]);
    $this->assertSame(Command::FAILURE, $tester->getStatusCode());
    $this->assertStringContainsString('Could not find repository: missing', $tester->getDisplay());
    $this->assertFalse($this->requested('VersionControl/update'));
  }

  /**
   * Tests that a repository that is not deployable is not deployed.
   */
  public function testDeployNotDeployable(): void {
    $repository = $this->repository(['deployable' => 0]);
    $tester = $this->runCommand(new RepoDeployCommand(), [$repository], ['repo' => 'my-site', '--yes' => TRUE]);
    $this->assertSame(Command::FAILURE, $tester->getStatusCode());
    $this->assertStringContainsString('not deployable', $tester->getDisplay());
    $this->assertFalse($this->requested('VersionControlDeployment/create'));
  }

  /**
   * Tests that a deployable repository is deployed.
   */
  public function testDeploy(): void {
    $tester = $this->runCommand(new RepoDeployCommand(), [$this->repository()], ['repo' => 'my-site', '--yes' => TRUE]);
    $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
    $this->assertTrue($this->requested('VersionControlDeployment/create'));
  }

  /**
   * Tests that the CPanel errors are not hidden.
   */
  public function testApiErrorIsReported(): void {
    $command = new RepoPullCommand();
    $command->setHttpClient($this->mockClient(['retrieve' => ['body' => 'Unauthorized', 'status' => 401]]));
    $tester = new CommandTester($command);
    $this->expectExceptionMessage('CPanel rejected the credentials');
    $tester->execute([
      'repo' => 'my-site',
      '--yes' => TRUE,
      '--cp-user' => 'me',
      '--cp-token' => 'TOKEN',
      '--cp-base-url' => 'https://cpanel.example.com:2083',
    ], ['interactive' => FALSE]);
  }

  /**
   * Runs a repo command with a mocked CPanel API.
   *
   * @param \Symfony\Component\Console\Command\Command $command
   *   The command, it must use the CPanel API trait.
   * @param array $repositories
   *   The repositories returned by CPanel.
   * @param array $input
   *   The command arguments and options.
   * @param string[] $answers
   *   The answers to the interactive questions.
   */
  private function runCommand(Command $command, array $repositories, array $input, array $answers = []): CommandTester {
    $command->setHttpClient($this->mockClient([
      'VersionControl/retrieve' => ['body' => $this->apiOk($repositories)],
      'VersionControl/update' => ['body' => $this->apiOk([])],
      'VersionControlDeployment/create' => ['body' => $this->apiOk(['deploy_id' => 1])],
    ]));
    $tester = new CommandTester($command);
    if (!empty($answers)) {
      $tester->setInputs($answers);
    }
    $tester->execute($input + [
      '--cp-user' => 'me',
      '--cp-token' => 'TOKEN',
      '--cp-base-url' => 'https://cpanel.example.com:2083',
    ], ['interactive' => !empty($answers)]);
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
