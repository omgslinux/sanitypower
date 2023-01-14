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

class ShareholdersDumpCommand extends Command
{
    protected static $defaultName = 'app:shareholders:dump';
    protected static $defaultDescription = 'Massive dump for shareholders';

    private $HR;
    private $CCR;
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
            // Asignamos por defecto el tipo 'C' a las empresas
            $companyCategory = $this->CCR->findOneByLetter('C');
            $lineNumber = $skipped = 0;
            while (($keys = fgetcsv($handle, 1000, ",")) !== false) {
                //"3M COMPANY","VANGUARD GROUP INC","","US","F","8.94","n.d."
                //"3M COMPANY","STATE STREET CORPORATION","VIA ITS FUNDS","US","B","-","7.36"
                //$io->note(sprintf('Contenido: %s', $contents));
                //$keys = explode(",", $line);
                $lineNumber++;
                if (!empty($parentname = str_replace('"', '', $keys[0]))) {
                    if ($prev != $parentname) {
                        if (null!=$prev) {
                            $this->HR->flush();
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
                        if ($holderCategory->getLetter() == 'H') {
                            $holder = $parent;
                        } else {
                            if (null == ($holder = $this->repo->findOneBy(
                                [
                                    'fullname' => $fullname,
                                    'country' => $country,
                                ]
                            ))) {
                                $holder = new Company();
                                $holder->setFullname($fullname)
                                ->setCountry($country)
                                ->setActive(false)
                                ->setLevel($level)
                                ->setCategory($companyCategory);
                                ;
                            }
                            $this->repo->add($holder);
                        }
                        //dump($holder);
                        if (null == ($entity = $this->HR->findOneBy(
                            [
                                'holder' => $holder,
                                'company' => $parent,
                            ]
                        ))) {
                            $via = (str_replace('"', '', $keys[2]));
                            $directOwnership = str_replace('"', '', $keys[5]);
                            $totalOwnership = str_replace('"', '', $keys[6]);
                            $data = [
                                'country' => $_country,
                                'name' => $fullname,
                                'active' => false,
                                'via' => $via,
                                'direct' => $directOwnership,
                                'total' => $totalOwnership
                            ];
                            $entity = new Shareholder();
                            $entity->setCompany($parent)
                            ->setHolder($holder)
                            ->setVia(!empty($via))
                            ->setDirectOwnership((is_numeric($directOwnership)?$directOwnership:0))
                            ->setTotalOwnership((is_numeric($totalOwnership)?$totalOwnership:0))
                            ->setSkip(!($entity->getDirectOwnership()+$entity->getTotalOwnership())>0)
                            ->setHolderCategory($holderCategory)
                            ->setData($data)
                            ;
                            $parent->addCompanyHolder($entity);
                            $io->info(
                                sprintf(
                                    '(%d omitidos de %d), Company: %s, Accionista: %s',
                                    $skipped,
                                    $lineNumber,
                                    $parent->getFullname(),
                                    $holder->getFullname()
                                )
                            );
                            $this->repo->add($parent, true);
                        } else {
                            $skipped++;
                        }
                    }
                }
            }
            $this->repo->flush();
            $io->success(
                sprintf(
                    '¡Se terminó de volcar el fichero %s con %d accionistas.',
                    $file,
                    $lineNumber
                )
            );
            return Command::SUCCESS;
        } else {
            $io->error('¡Ha ocurrido un error con el fichero de accionistas $file.');
            return Command::FAILURE;
        }
/*
        if ($input->getOption('option1')) {
            // ...
        }

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.'); */
    }
}
