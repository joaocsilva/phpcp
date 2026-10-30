<?php

namespace PhpCP\Tests\Command;

use PhpCP\Command\FileDownload;
use PhpCP\Tests\CpanelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class FileDownloadTest extends CpanelTestCase {

  /**
   * The temporary directory for the downloads.
   */
  private string $directory;

  /**
   * Tests that the content is printed when there is no output file.
   */
  public function testPrintsContent(): void {
    $tester = $this->download([]);
    $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
    $this->assertSame("{\"name\": \"x\"}\n", $tester->getDisplay());
  }

  /**
   * Tests that the content is saved to the output file.
   */
  public function testWritesOutputFile(): void {
    $file = $this->directory . '/remote.json';
    $tester = $this->download(['--output' => $file]);
    $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
    $this->assertSame("{\"name\": \"x\"}\n", file_get_contents($file));
    $this->assertStringContainsString('Saved 14 bytes', $tester->getDisplay());
  }

  /**
   * Tests that a missing output directory fails with a message.
   */
  public function testMissingOutputDirectory(): void {
    $tester = $this->download(['--output' => $this->directory . '/missing/remote.json']);
    $this->assertSame(Command::FAILURE, $tester->getStatusCode());
    $this->assertStringContainsString('Cannot write to', $tester->getDisplay());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->directory = sys_get_temp_dir() . '/phpcp-test-' . bin2hex(random_bytes(4));
    mkdir($this->directory);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    foreach (glob($this->directory . '/*') as $file) {
      unlink($file);
    }
    rmdir($this->directory);
  }

  /**
   * Runs the command with a mocked CPanel API.
   *
   * @param array $input
   *   The command arguments and options.
   */
  private function download(array $input): CommandTester {
    $command = new FileDownload();
    $command->setHttpClient($this->mockClient([
      'get_file_content' => ['body' => $this->apiOk(['content' => "{\"name\": \"x\"}\n"])],
    ]));
    $tester = new CommandTester($command);
    $tester->execute($input + [
      'dir' => '/home/user',
      'file' => 'composer.json',
      '--cp-user' => 'me',
      '--cp-token' => 'TOKEN',
      '--cp-base-url' => 'https://cpanel.example.com:2083',
    ], ['interactive' => FALSE]);
    return $tester;
  }

}
