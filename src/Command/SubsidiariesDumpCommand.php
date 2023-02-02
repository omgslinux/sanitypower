<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Entity\Company;
use App\Entity\Subsidiary;
use App\Repository\CompanyRepository;
use App\Repository\SubsidiaryRepository;
use App\Repository\ShareholderCategoryRepository;
use App\Repository\CompanyLevelRepository;
use App\Repository\CompanyActivityCategoryRepository;

class SubsidiariesDumpCommand extends Command
{
    protected static $defaultName = 'app:subsidiaries:dump';
    protected static $defaultDescription = 'Massive dump for subsidiaries';

    private $SR;
    private $CCR;
    private $CLR;
    private $repo;
    private $SCR;

    public function __construct(
        SubsidiaryRepository $subsidiaryRepo,
        CompanyActivityCategoryRepository $companycatRepo,
        CompanyLevelRepository $companyLevelRepo,
        CompanyRepository $repo,
        ShareholderCategoryRepository $holdercatRepo
    ) {
        $this->SR = $subsidiaryRepo;
        $this->CCR = $companycatRepo;
        $this->CLR = $companyLevelRepo;
        $this->repo = $repo;
        $this->SCR = $holdercatRepo;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = $input->getArgument('file');

        $bom = "\xef\xbb\xbf";
        if (($handle=fopen($file, "r"))!==false) {
            //$io->note(sprintf('You passed an argument: %s', $file));
            // Progress file pointer and get first 3 characters to compare to the BOM string.
            if (fgets($handle, 4) !== $bom) {
                // BOM not found - rewind pointer to start of file.
                rewind($handle);
            }
            $prev = null;
            $level = $this->CLR->findOneBy(['level' => 'Pendiente']);
            // Asignamos por defecto el tipo 'C' a las empresas
            $companyCategory = $this->CCR->findOneByLetter('C');
            $lineNumber = $skipped = 0;
            while (($keys = fgetcsv($handle, 1000, ",")) !== false) {
                //"3M COMPANY","3 M MAROC","MA","C","100.00","100.00"
                //"3M COMPANY","3M (EAST) AG","CH","C","100.00","100.00"
                //"3M COMPANY","3M (SCHWEIZ) GMBH","CH","C","100.00","100.00"
                //"3M COMPANY","3M ARGENTINA SAA","AR","C","100.00","100.00"
                //$io->note(sprintf('Contenido: %s', $contents));
                //$keys = explode(",", $line);
                $lineNumber++;
                if (!empty($parentname = str_replace('"', '', $keys[0]))) {
                    if ($prev != $parentname) {
                        if (null!=$prev) {
                            $this->SR->flush();
                        }
                        $parent=$this->repo->findOneByFullname($parentname);
                        if (null==$parent) {
                            $io->error(sprintf('Fallo en: %s', $parentname));
                        }
                        $prev=$parent->getFullname();
                    }
                    if (!empty($fullname = str_replace('"', '', $keys[1])) && (strtolower($fullname)!='nan')) {
                        $holderCategory = $this->SCR->findOneByLetter(str_replace('"', '', $keys[4]));
                        $_country = $country = str_replace('"', '', $keys[3]);
                        if ($country == 'n.d.') {
                            $country = '--';
                        }
                        if (strlen($country)>2) {
                            $io->error(sprintf('Error en el pais(%s), participada %s', $country, $fullname));
                        }
                        $_direct = $direct = str_replace('"', '', $keys[5]);
                        $_total = $total = str_replace('"', '', $keys[6]);
                        if (null == ($owned = $this->repo->findOneBy(
                            [
                                'fullname' => $fullname,
                                'country' => $country,
                            ]
                        ))) {
                            $owned = new Company();
                            $owned->setFullname($fullname)
                            ->setCountry($country)
                            ->setActive(false)
                            ->setLevel($level)
                            ->setCategory($companyCategory);
                        }
                        $this->repo->add($owned, true);

                        if (null == ($entity = $this->SR->findOneBy(
                            [
                                'owned' => $owned,
                                'owner' => $parent,
                            ]
                        ))) {
                            $via = (str_replace('"', '', $keys[2]));
                            $data = [
                                'country' => $_country,
                                'name' => $fullname,
                                'active' => false,
                                'via' => $via,
                                'direct' => $_direct,
                                'total' => $_total
                            ];
                            $entity = new Subsidiary();
                            if ($_direct == "MO" || $_direct == ">50") {
                                $direct = 50.01;
                            } elseif ($_direct == "WO") {
                                $direct = 100;
                            } elseif (!is_numeric($_direct)) {
                                $direct = 0;
                            }
                            if ($_total == "MO" || $_total == ">50") {
                                $total = 50.01;
                            } elseif ($_total == "WO") {
                                $total = 100;
                            } elseif (!is_numeric($_total)) {
                                $total = 0;
                            }
                            $entity->setOwner($parent)
                            ->setOwned($owned)
                            ->setDirect($direct)
                            ->setPercent($total)
                            ->setVia(!empty($via))
                            ->setData($data)
                            ;
                            //$em->persist($entity);
                            $io->info(
                                sprintf(
                                    '(%d omitidos de %d), Company: %s, Participada: %s',
                                    $skipped,
                                    $lineNumber,
                                    $parent->getFullname(),
                                    $owned->getFullname()
                                )
                            );
                            $this->SR->add($entity);
                            //dump($entity);
                        }
                        //$em->flush();
                        $this->repo->flush();
                    } else {
                        // $fullname en blanco o nan
                        $io->info(
                            sprintf(
                                'Se incrementan las omitidas con %s a %d',
                                $parentname,
                                $skipped++
                            )
                        );
                    }
                }
            }
//$this->repo->flush();
            $io->success(
                sprintf(
                    '¡Se terminó de volcar el fichero %s con %d participadas.',
                    $file,
                    $lineNumber
                )
            );
            return Command::SUCCESS;
        } else {
            $io->error('¡Ha ocurrido un error con el fichero de participadas $file.');
            return Command::FAILURE;
        }
/*
        if ($input->getOption('option1')) {
            // ...
        }

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.'); */
    }
}
