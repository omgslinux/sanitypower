<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Util\SheetReader;

class SheetProcessCommand extends Command
{
    protected static $defaultName = 'app:sheet:process';
    protected static $defaultDescription = 'Process spreadsheets';

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'File/dir to process')
            ->addOption('prefix', 'p', InputOption::VALUE_OPTIONAL, 'Prefix for files', 'TEST')
            ->addOption(
                'section',
                's',
                InputOption::VALUE_OPTIONAL,
                'Section: "0"(all), "M"anagers, "A"ccionistas, "P"articipadas',
                '0'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filedir = $input->getArgument('file');
        $prefix = $input->getOption('prefix')??'TEST';
        $section = $input->getOption('section');
        $writeResults = true;

        if (is_readable($filedir)) {
            $process = new SheetReader();
            $process->setPrefix($prefix)
            ->setSection($section)
            ;
            $outdir = 'var/OUT/' . $prefix;
            if (!$process->setOutdir($outdir, true)) {
                $io->error('¡Ha ocurrido un error: no se puede crear $outdir');
                return Command::FAILURE;
            };
            $process->openResultsFiles();
            if (is_dir($filedir) && ($dh = opendir($filedir))) {
                dump(getcwd(), $dh);
                $sortedFiles = [];
                while (($file = readdir($dh)) !== false) {
                    if ($file != '.' && $file != '..') {
                        $sortedFiles [] = $file;
                    }
                }
                closedir($dh);
                sort($sortedFiles);
                $index = 0;
                foreach ($sortedFiles as $file) {
                    $index++;
                    $io->info(" ($index) filename: $filedir$file \n");
                    $process->processFile($filedir . $file, $writeResults);
                    //$io->info("Procesado " . $process->getCompany());
                }
            } else {
                $process->processFile($filedir, $writeResults);
            }
        } else {
            // No existe o no se puede abrir
            $io->error('¡Ha ocurrido un error: no se puede abrir $filedir');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
/*
        if ($input->getOption('option1')) {
            // ...
        }

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.'); */
    }
}
