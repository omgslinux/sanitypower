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

    private $HR;
    private $CCR;
    private $CLR;
    private $repo;
    private $SCR;
    const VIASTR = 'via its funds';

    public function __construct(
        ShareholderRepository $holderRepo,
        CompanyActivityCategoryRepository $companycatRepo,
        CompanyLevelRepository $companyLevelRepo,
        CompanyRepository $repo,
        ShareholderCategoryRepository $holdercatRepo
    ) {
        $this->HR = $holderRepo;
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
            $level = $this->CLR->findOneBy(['level' => 'Sin identificar']);
            // Asignamos por defecto el tipo 'K' a las empresas
            $companyCategory = $this->CCR->findOneByLetter('K');
            $lineNumber = $skipped = 0;
            while (($keys = fgetcsv($handle, 1000, "\t")) !== false) {
                //ACCIONISTAREALNAME{tab}PARTICIPADA [via its funds]{tab}PAIS{tab}TIPO{tab}Directo{tab}Total
                //"DEUTSCHE BANK AG","ABSALON CREDIT FUND DESIGNATED ACTIVITY COMPANY",,IE,E,100.00,100.00
                //"DEUTSCHE BANK AG","ALFRED HERRHAUSEN GESELLSCHAFT MBH",DE,C,100.00,100.00
                //"DEUTSCHE BANK AG","AMBER INVESTMENTS SÀ RL",LU,E,100.00,100.00
                //"DEUTSCHE BANK AG","AMBIDEXTER GMBH via its funds",DE,C,100.00,100.00
                //"DEUTSCHE BANK AG","AO DB SECURITIES",KZ,E,100.00,100.00
                $lineNumber++;
                if (!empty($holderRealName = str_replace('"', '', $keys[0]))) {
                    if ($prev != $holderRealName) {
                        /*if (null!=$prev) {
                            $this->repo->flush();
                        } */
                        unset($holder);
                        $holder=null;
                        $holder=$this->repo->findOneBy(
                            [
                                'realname' => $holderRealName,
                                'inList' => true,
                            ]
                        );
                        $holderName = $this->repo->getStrippedCN($holderRealName);
                        if (null==$holder) {
                            $holder=$this->repo->findOneBy(
                                [
                                    'fullname' => $holderName,
                                    'inList' => true,
                                ]
                            );
                        } else {
                            $holder->setFullname($holderName);
                        }
                        if (null==$holder) {
                            $io->error(sprintf('Fallo en: %s', $holderRealName));
                        }
                        if (($holder->getRealname() != $holderRealName) || ($holder->getFullname() != $holderName)) {
                            $holder
                            ->setRealname($holderRealName)
                            ->setFullname($holderName);
                            $this->repo->add($holder, true);
                        }
                        $prev=$holder->getRealname();
                    }
                    if (!empty($subRawName = str_replace('"', '', strtoupper($keys[1])))) {
                        $via = false;
                        if ($viapos = stripos($subRawName, self::VIASTR)) {
                            $subRawName = trim(substr($subRawName, 0, $viapos));
                            $via = true;
                        }
                        $subName = $this->repo->getStrippedCN($subRawName);
                        $_country = $subCountry = str_replace('"', '', $keys[2]);
                        if ($subCountry == 'n.d.') {
                            $subCountry = '--';
                        }
                        $subCategory = $this->SCR->findOneByLetter(str_replace('"', '', $keys[3]));
                        $direct = str_replace('"', '', $keys[4]);
                        $total = str_replace('"', '', $keys[5]);
                        //dump($data);
                        if ($subCategory->getLetter() == 'H') {
                            $subsidiary = $holder;
                        } else {
                            if (null == ($subsidiary = $this->repo->findOneBy(
                                [
                                    'fullname' => $subName,
                                    'country' => $subCountry,
                                ]
                            ))) {
                                $subsidiary = new Company();
                                $subsidiary->setFullname($subName)
                                ->setRealname($subRawName)
                                ->setCountry($subCountry)
                                ->setActive(false)
                                ->setLevel($level)
                                ->setCategory($companyCategory);
                                ;
                                //dump("Creando empresa: $subName");
                            } else {
                                //dump("Ya existe empresa: $subName");
                            }
                            $this->repo->add($subsidiary);
                        }
                        //dump($holder);
                        //dump($data);
                        if (null == ($entity = $this->HR->findOneBy(
                            [
                                'holder' => $holder,
                                'subsidiary' => $subsidiary,
                                'via' => $via,
                            ]
                        ))) {
                            $data = [
                                'holder' => $holderName,
                                'realname' => $holderRealName,
                                'subsidiary' => $subsidiary->getRealname(),
                                'country' => $_country,
                                'active' => false,
                                'via' => $via,
                                'direct' => $direct,
                                'total' => $total
                            ];
                            $entity = new Shareholder();
                            $entity->setHolder($holder)
                            ->setSubsidiary($subsidiary)
                            ->setVia($via)
                            ->setDirect((is_numeric($direct)?$direct:0))
                            ->setTotal((is_numeric($total)?$total:0))
                            ->setSkip(!($entity->getDirect()+$entity->getTotal())>0)
                            ->setHolderCategory($subCategory)
                            ->setData($data)
                            ;
                            $holder->addHolder($entity);
                            $io->info(
                                sprintf(
                                    '(%d omitidos de %d), Accionista: %s, Participada: %s',
                                    $skipped,
                                    $lineNumber,
                                    $holder->getFullname(),
                                    $subsidiary->getFullname(),
                                )
                            );
                            //dump($holder);
                            $this->repo->add($holder, true);
                        } else {
                            //$io->error(sprintf('Se omite duplicado: (a: %s), (p: %s)', $holderName, $subName));
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
