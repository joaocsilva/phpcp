<?php

namespace PhpCP;

/**
 * Masks the values of secret configuration keys, i.e: tokens and passwords.
 */
class SecretMasker {

  /**
   * The text shown instead of a secret value.
   */
  public const MASK = '********';

  /**
   * Keys (or the end of them) that hold secrets.
   */
  private const SECRET_KEY_PATTERN = '/(token|password|secret|key)$/i';

  /**
   * Mask the secret values in a configuration value.
   *
   * Unresolved placeholders, i.e: ${env.PHPCP_CPANEL_TOKEN}, and empty values
   * are not secrets, they are kept because they help to debug a missing
   * configuration.
   *
   * @param mixed $value
   *   A scalar or a (nested) array of configurations.
   * @param string $key
   *   The key of the value, only the part after the last dot is used.
   *
   * @return mixed
   *   The value with the secrets masked.
   */
  public static function mask(mixed $value, string $key = ''): mixed {
    if (is_array($value)) {
      foreach ($value as $childKey => $childValue) {
        $value[$childKey] = self::mask($childValue, (string) $childKey);
      }
      return $value;
    }
    if (!is_string($value) || $value === '' || self::isPlaceholder($value)) {
      return $value;
    }
    $name = substr($key, (int) strrpos('.' . $key, '.'));
    return preg_match(self::SECRET_KEY_PATTERN, $name) ? self::MASK : $value;
  }

  /**
   * Whether the value is an unresolved ${env.NAME} placeholder.
   */
  private static function isPlaceholder(string $value): bool {
    return (bool) preg_match('/^\$\{env\.[A-Za-z0-9_]+\}$/', $value);
  }

}
