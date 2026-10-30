<?php

namespace PhpCP\Command;

use PhpCP\AbstractCpanelCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class FileDownload extends AbstractCpanelCommand {

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
    $outputFile = $input->getOption('output');
    if (empty($outputFile)) {
      $output->write($content);
      return Command::SUCCESS;
    }

    $io = new SymfonyStyle($input, $output);
    $outputDir = dirname($outputFile);
    if (!is_dir($outputDir) || !is_writable($outputDir)) {
      $io->error("Cannot write to $outputFile, the directory $outputDir does not exist or is not writable.");
      return Command::FAILURE;
    }
    $bytes = file_put_contents($outputFile, $content);
    if ($bytes === FALSE) {
      $io->error("Could not write the file $outputFile.");
      return Command::FAILURE;
    }
    $io->success("Saved $bytes bytes to $outputFile");
    return Command::SUCCESS;
  }

}
