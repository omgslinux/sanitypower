<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Entity\Company;
use App\Entity\Shareholder;
use App\Repository\CompanyRepository;
use App\Repository\ShareholderRepository;
use App\Repository\ShareholderCategoryRepository;
use App\Repository\CompanyLevelRepository;
use App\Repository\CompanyActivityCategoryRepository;

class SubsidiariesDumpCommand extends Command
{
    protected static $defaultName = 'app:subsidiaries:dump';
    protected static $defaultDescription = 'Massive dump for subsidiaries';

    private $SR;
    private $CACR;
    private $CLR;
    private $repo;
    private $SCR;

    public function __construct(
        ShareholderRepository $holderRepo,
        CompanyActivityCategoryRepository $companycatRepo,
        CompanyLevelRepository $companyLevelRepo,
        CompanyRepository $repo,
        ShareholderCategoryRepository $holdercatRepo
    ) {
        $this->SR = $holderRepo;
        $this->CACR = $companycatRepo;
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
            $level = $this->CLR->findOneBy(['level' => 'Sin identificar']);
            // Asignamos por defecto el tipo 'K' a las empresas
            $companyCategory = $this->CACR->findOneByLetter('K');
            $lineNumber = $skipped = 0;
            while (($keys = fgetcsv($handle, 1000, ",")) !== false) {
                //"DEUTSCHE BANK AG","ABSALON CREDIT FUND DESIGNATED ACTIVITY COMPANY",,IE,E,100.00,100.00
                //"DEUTSCHE BANK AG","ALFRED HERRHAUSEN GESELLSCHAFT MBH",,DE,C,100.00,100.00
                //"DEUTSCHE BANK AG","AMBER INVESTMENTS SÀ RL",,LU,E,100.00,100.00
                //"DEUTSCHE BANK AG","AMBIDEXTER GMBH",,DE,C,100.00,100.00
                //"DEUTSCHE BANK AG","AO DB SECURITIES",,KZ,E,100.00,100.00
                //$io->note(sprintf('Contenido: %s', $contents));
                //$keys = explode(",", $line);
                $lineNumber++;
                if (!empty($holderName = str_replace('"', '', $keys[0]))) {
                    if ($prev != $holderName) {
                        /*if (null!=$prev) {
                            $this->repo->flush();
                        } */
                        unset($holder);
                        $holder=null;
                        $holder=$this->repo->findOneByFullname($holderName);
                        if (null==$holder) {
                            $io->error(sprintf('Fallo en: %s', $holderName));
                        }
                        $prev=$holder->getFullname();
                    }
                    if (!empty($subName = str_replace('"', '', $keys[1])) && (strtolower($subName)!='nan')) {
                        $subCategory = $this->SCR->findOneByLetter(str_replace('"', '', $keys[4]));
                        $_country = $subCountry = str_replace('"', '', $keys[3]);
                        if ($subCountry == 'n.d.') {
                            $subCountry = '--';
                        }
                        $via = (str_replace('"', '', $keys[2]));
                        $direct = str_replace('"', '', $keys[5]);
                        $total = str_replace('"', '', $keys[6]);
                        $data = [
                            'holder' => $holderName,
                            'subsidiary' => $subName,
                            'country' => $_country,
                            'active' => false,
                            'via' => $via,
                            'direct' => $direct,
                            'total' => $total
                        ];
                        //dump($data);
                        if ($subCategory->getLetter() == 'H') {
                            $sub = $holder;
                        } else {
                            if (null == ($sub = $this->repo->findOneBy(
                                [
                                    'fullname' => $subName,
                                    //'country' => $subCountry,
                                ]
                            ))) {
                                $sub = new Company();
                                $sub->setFullname($subName)
                                ->setCountry($subCountry)
                                ->setActive(false)
                                ->setLevel($level)
                                ->setCategory($companyCategory);
                                ;
                                //dump("Creando empresa: $subName");
                            } else {
                                //dump("Ya existe empresa: $subName");
                            }
                            $this->repo->add($sub, true);
                        }
                        //dump($holder);
                        //dump($data);
                        if (null == ($entity = $this->SR->findOneBy(
                            [
                                'holder' => $holder,
                                'subsidiary' => $sub,
                            ]
                        ))) {
                            $entity = new Shareholder();
                            $entity->setHolder($holder)
                            ->setSubsidiary($sub)
                            ->setVia(!empty($via))
                            ->setDirect((is_numeric($direct)?$direct:0))
                            ->setTotal((is_numeric($total)?$total:0))
                            ->setSkip(!($entity->getDirect()+$entity->getTotal())>0)
                            ->setHolderCategory($subCategory)
                            ->setData($data)
                            ;
                            $this->SR->add($entity, true);
                            $io->info(
                                sprintf(
                                    '(%d omitidos de %d), Accionista: %s, Participada: %s',
                                    $skipped,
                                    $lineNumber,
                                    $holder->getFullname(),
                                    $sub->getFullname(),
                                )
                            );
                            //dump($data);
                            //$this->repo->add($holder, true);
                        } else {
                            //dump("NO SE CREA entrada share:", $entity);
                            $skipped++;
                        }
                    }
                }
            }
            $this->repo->flush();
            $io->success(
                sprintf(
                    '¡Se terminó de volcar el fichero %s con %d participadas. %d omitidas',
                    $file,
                    $lineNumber,
                    $skipped
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
