<?php

namespace PhpCP\Command;

use PhpCP\AbstractCpanelCommand;
use PhpCP\Traits\ConfigAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FileDownload extends AbstractCpanelCommand {

  use ConfigAwareTrait;

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setName('file:download')
      ->setDescription('Download a CPanel file')
      ->setAliases(['fetch'])
      ->addArgument(
        'dir',
        InputArgument::REQUIRED,
        'The directory where the file exists'
      )
      ->addArgument(
        'file',
        InputArgument::REQUIRED,
        'The filename to download'
      )
      ->addOption(
        'output',
        'o',
        InputOption::VALUE_REQUIRED,
        'Write to file instead of printing'
      )
      ->addUsage('/home/user composer.json')
      ->addUsage('/home/user composer.json --output=remote-composer.json')
      ->setHelp('For more details see https://api.docs.cpanel.net/openapi/cpanel/operation/get_file_content/');
    parent::configure();
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $content = $this->cpanelGetFileContent($input->getArgument('dir'), $input->getArgument('file'));
    if ($outputFile = $input->getOption('output')) {
      file_put_contents($outputFile, $content);
    }
    else {
      $output->write($content);
    }
    return Command::SUCCESS;
  }

}
