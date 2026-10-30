<?php

namespace PhpCP\Tests;

use PHPUnit\Framework\TestCase;
use PhpCP\SecretMasker;

class SecretMaskerTest extends TestCase {

  /**
   * Tests that nested secrets are masked and the rest is kept.
   */
  public function testMasksNestedSecrets(): void {
    $config = [
      'phpcp' => [
        'github' => ['token' => 'ghp_abc', 'user' => 'joao'],
        'cpanel' => ['user' => 'me', 'token' => 's3cret', 'url' => 'https://host:2083'],
      ],
    ];
    $masked = SecretMasker::mask($config);
    $this->assertSame(SecretMasker::MASK, $masked['phpcp']['github']['token']);
    $this->assertSame(SecretMasker::MASK, $masked['phpcp']['cpanel']['token']);
    $this->assertSame('joao', $masked['phpcp']['github']['user']);
    $this->assertSame('me', $masked['phpcp']['cpanel']['user']);
    $this->assertSame('https://host:2083', $masked['phpcp']['cpanel']['url']);
  }

  /**
   * Tests that a single value uses the last part of the dotted key.
   */
  public function testMasksSingleValueByDottedKey(): void {
    $this->assertSame(SecretMasker::MASK, SecretMasker::mask('s3cret', 'phpcp.cpanel.token'));
    $this->assertSame('me', SecretMasker::mask('me', 'phpcp.cpanel.user'));
    $this->assertSame('value', SecretMasker::mask('value'));
  }

  /**
   * Tests that unresolved placeholders and empty values are not masked.
   */
  public function testKeepsPlaceholdersAndEmptyValues(): void {
    $this->assertSame('${env.PHPCP_CPANEL_TOKEN}', SecretMasker::mask('${env.PHPCP_CPANEL_TOKEN}', 'token'));
    $this->assertSame('', SecretMasker::mask('', 'token'));
    $this->assertNull(SecretMasker::mask(NULL, 'token'));
  }

  /**
   * Tests that the secret keys are case insensitive and match the key end.
   */
  public function testMatchesKeyEnd(): void {
    $masked = SecretMasker::mask(['API_KEY' => 'abc', 'Password' => 'abc', 'keyboard' => 'abc']);
    $this->assertSame(SecretMasker::MASK, $masked['API_KEY']);
    $this->assertSame(SecretMasker::MASK, $masked['Password']);
    $this->assertSame('abc', $masked['keyboard']);
  }

}
