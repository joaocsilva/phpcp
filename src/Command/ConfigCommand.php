<?php

namespace PhpCP\Command;

use PhpCP\Traits\ConfigAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class ConfigCommand extends Command {

  use ConfigAwareTrait;

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setName('config')
      ->setDescription('Print configurations')
      ->addArgument(
        'key',
        InputArgument::OPTIONAL,
        'The configuration key to print'
      )
      ->addUsage('phpcp.cpanel');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $config = $this->getConfig();
    $value = ($key = $input->getArgument('key')) ? $config->get($key) : $config->all();
    $output->writeln(Yaml::dump($value, 10, 2));
    return Command::SUCCESS;
  }

}
