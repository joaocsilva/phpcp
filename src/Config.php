<?php

namespace PhpCP;

use Adbar\Dot;
use Grasmash\Expander\Expander;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

class Config extends Dot {

  /**
   * The configuration files to load.
   */
  protected array $configFiles = [
    // Phpcp default configuration.
    __DIR__ . '/../default.yml',
    // Project's default configuration.
    'phpcp.yml.dist',
    // Project's development configuration.
    'phpcp.yml',
  ];

  /**
   * Construct and prepare the configurations.
   */
  public function __construct() {
    parent::__construct();
    return $this
      ->parse()
      ->expand()
      ->validate();
  }

  /**
   * Parse the config files.
   */
  protected function parse(): Config {
    foreach ($this->configFiles as $configFile) {
      if (file_exists($configFile)) {
        $content = Yaml::parseFile($configFile);
        if (!empty($content) && is_array($content)) {
          $this->mergeRecursiveDistinct($content);
        }
      }
    }
    return $this;
  }

  /**
   * Expand configuration values.
   */
  protected function expand(): Config {
    $expander = new Expander();
    $this->setArray($expander->expandArrayProperties($this->all()));
    return $this;
  }

  /**
   * Validate configurations with TreeBuilder.
   *
   * @see ConfigTreeBuilder::getConfigTreeBuilder() and config.yml.
   */
  protected function validate(): Config {
    $processor = new Processor();
    // Validate only phpcp configurations.
    $config = ['phpcp' => $this->get('phpcp')];
    $processor->processConfiguration((new ConfigTreeBuilder()), $config);
    return $this;
  }

}
