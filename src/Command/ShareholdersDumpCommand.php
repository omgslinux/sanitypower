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
use App\Repository\CompanyLevelRepository;
use App\Repository\CompanyCategoryRepository;

class ShareholdersDumpCommand extends Command
{
    protected static $defaultName = 'app:shareholders:dump';
    protected static $defaultDescription = 'Massive dump for shareholders';

    private $HR;
    private $CR;
    private $CLR;
    private $repo;

    public function __construct(
        ShareholderRepository $holderRepo,
        CompanyCategoryRepository $categoryRepo,
        CompanyLevelRepository $companyLevelRepo,
        CompanyRepository $repo
    ) {
        $this->HR = $holderRepo;
        $this->CR = $categoryRepo;
        $this->CLR = $companyLevelRepo;
        $this->repo = $repo;
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
            while (($keys = fgetcsv($handle, 1000, ",")) !== false) {
                //"3M COMPANY","VANGUARD GROUP INC","","US","F","8.94","n.d."
                //"3M COMPANY","STATE STREET CORPORATION","VIA ITS FUNDS","US","B","-","7.36"
                //$io->note(sprintf('Contenido: %s', $contents));
                //$keys = explode(",", $line);
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
                        $category = $this->CR->findOneByLetter(str_replace('"', '', $keys[4]));
                        $country = str_replace('"', '', $keys[3]);
                        if ($country == 'n.d.') {
                            $country = '--';
                        }
                        if ($category->getLetter() == 'H') {
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
                                ;
                            }
                            $holder->setCategory($category);
                            //$em->persist($holder);
                            $this->repo->add($holder);
                        }
                        //dump($holder);
                        if (null == ($entity = $this->HR->findOneBy(
                            [
                                'holder' => $holder,
                                'company' => $parent,
                            ]
                        ))) {
                            $entity = new Shareholder();
                            $directOwnership = str_replace('"', '', $keys[5]);
                            $totalOwnership = str_replace('"', '', $keys[6]);
                            $entity->setCompany($parent)
                            ->setHolder($holder)
                            ->setVia(!empty(str_replace('"', '', $keys[2])))
                            ->setDirectOwnership((is_numeric($directOwnership)?$directOwnership:0))
                            ->setTotalOwnership((is_numeric($totalOwnership)?$totalOwnership:0))
                            ->setSkip(!($entity->getDirectOwnership()+$entity->getTotalOwnership())>0)
                            ;
                            $parent->addCompanyHolder($entity);
                            $io->note(sprintf('Company: %s, Accionista: %s', $parent->getFullname(), $holder->getFullname()));
                            $this->repo->add($parent, true);
                        }
                    }
                }
            }
            $this->repo->flush();
        }
/*
        if ($input->getOption('option1')) {
            // ...
        }

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.'); */

        return Command::SUCCESS;
    }
}
