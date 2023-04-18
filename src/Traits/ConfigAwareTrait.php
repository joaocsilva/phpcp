<?php

namespace PhpCP\Traits;

use PhpCP\Config;

trait ConfigAwareTrait {

  /**
   * @var \PhpCP\Config
   */
  protected Config $config;

  /**
   * Returns the current configuration.
   */
  protected function getConfig(): Config {
    if (!isset($config)) {
      $this->config = new Config();
    }
    return $this->config;
  }

}
