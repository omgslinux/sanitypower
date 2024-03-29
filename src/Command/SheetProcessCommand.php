<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Util\SheetReader;

#[AsCommand(
    name: 'app:sheet:process',
    description: 'Process spreadsheets',
)]
class SheetProcessCommand extends Command
{

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
            $sectionText = 'TODO';
            if ($section == 'A') {
                $sectionText = 'ACCIONISTAS';
            } else {
                if ($section == 'P') {
                    $sectionText = 'PARTICIPADAS';
                } else {
                    if ($section == 'M') {
                        $sectionText = 'MANAGERS';
                    }
                }
            }
            $process->openResultsFiles();
            if (is_dir($filedir) && ($dh = opendir($filedir))) {
                dump(getcwd());
                $sortedFiles = [];
                while (($file = readdir($dh)) !== false) {
                    if ($file != '.' && $file != '..' && !stripos($file, '#')) {
                        $sortedFiles [] = $file;
                    }
                }
                closedir($dh);
                sort($sortedFiles);
                $total = count($sortedFiles);
                $index = 0;
                foreach ($sortedFiles as $file) {
                    $index++;
                    $io->info(" ($index de $total) seccion: ($sectionText) filename: $filedir$file \n");
                    $process->processFile($filedir . $file, $writeResults);
                    //$io->info("Procesado " . $process->getCompany());
                }
            } else {
                $io->info("Seccion: ($sectionText) filename: $filedir\n");
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
