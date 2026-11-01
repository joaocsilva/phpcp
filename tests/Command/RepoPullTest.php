<?php

namespace PhpCP\Tests\Command;

use PhpCP\Command\RepoPullCommand;
use PhpCP\Tests\CpanelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class RepoPullTest extends CpanelTestCase {

  /**
   * Tests that the commits before and the last deployed are printed.
   */
  public function testPrintsTheCommitsBeforePulling(): void {
    $tester = $this->pull([
      $this->repositories(self::COMMIT_A, self::COMMIT_A),
      $this->repositories(self::COMMIT_A, self::COMMIT_A),
    ]);
    $this->assertStringContainsString('HEAD commit: a1b2c3d, last deployed commit: a1b2c3d', $tester->getDisplay());
  }

  /**
   * Tests that a pull with new commits prints the HEAD change.
   */
  public function testNewCommit(): void {
    $tester = $this->pull([
      $this->repositories(self::COMMIT_A, self::COMMIT_A),
      $this->repositories(self::COMMIT_B, self::COMMIT_A),
    ]);
    $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
    $this->assertStringContainsString('Updated main from the commit a1b2c3d to f00dbab.', $tester->getDisplay());
    $this->assertStringContainsString('not deployed yet, the last deployed commit is a1b2c3d', $tester->getDisplay());
  }

  /**
   * Tests that a pull without new commits says it.
   */
  public function testAlreadyUpToDate(): void {
    $tester = $this->pull([
      $this->repositories(self::COMMIT_A, self::COMMIT_A),
      $this->repositories(self::COMMIT_A, self::COMMIT_A),
    ]);
    $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
    $this->assertStringContainsString('Already up to date, main is at the commit a1b2c3d.', $tester->getDisplay());
    $this->assertStringNotContainsString('not deployed yet', $tester->getDisplay());
  }

  /**
   * Tests that a pull with a commit that was never deployed prints it.
   */
  public function testNeverDeployed(): void {
    $tester = $this->pull([
      $this->repositories(self::COMMIT_A),
      $this->repositories(self::COMMIT_A),
    ]);
    $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
    $this->assertStringContainsString('the last deployed commit is (none)', $tester->getDisplay());
  }

  /**
   * Runs the pull command with a mocked CPanel API.
   *
   * @param string[] $repositories
   *   The CPanel response of the repositories in each request of the list, the
   *   first request finds the repository, the second is after the pull.
   */
  private function pull(array $repositories): CommandTester {
    $command = new RepoPullCommand();
    $command->setHttpClient($this->mockClient([
      'VersionControl/retrieve' => ['sequence' => $repositories],
      'VersionControl/update' => ['body' => $this->apiOk([])],
    ]));
    $tester = new CommandTester($command);
    $tester->execute([
      'repo' => 'my-site',
      '--yes' => TRUE,
      '--cp-user' => 'me',
      '--cp-token' => 'TOKEN',
      '--cp-base-url' => 'https://cpanel.example.com:2083',
    ], ['interactive' => FALSE]);
    return $tester;
  }

}
