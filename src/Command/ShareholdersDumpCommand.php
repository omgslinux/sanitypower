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
                if (!empty($subName = str_replace('"', '', $keys[0]))) {
                    if ($prev != $subName) {
                        if (null!=$prev) {
                            $this->HR->flush();
                        }
                        $subsidiary=$this->repo->findOneByFullname($subName);
                        if (null==$subsidiary) {
                            $io->error(sprintf('Fallo en: %s', $subName));
                        }
                        $prev=$subsidiary->getFullname();
                    }
                    if (!empty($holderName = str_replace('"', '', $keys[1])) && (strtolower($holderName)!='nan')) {
                        $holderCategory = $this->SCR->findOneByLetter(str_replace('"', '', $keys[4]));
                        if (null==$holderCategory) {
                            $io->error(sprintf('Fallo en: %s', $subName));
                        }
                        $_country = $country = str_replace('"', '', $keys[3]);
                        if ($country == 'n.d.') {
                            $country = '--';
                        }
                        if ($holderCategory->getLetter() == 'H') {
                            $holder = $subsidiary;
                        } else {
                            if (null == ($holder = $this->repo->findOneBy(
                                [
                                    'fullname' => $holderName,
                                    //'country' => $country,
                                ]
                            ))) {
                                $holder = new Company();
                                $holder->setFullname($holderName)
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
                                'subsidiary' => $subsidiary,
                            ]
                        ))) {
                            $via = (str_replace('"', '', $keys[2]));
                            $direct = str_replace('"', '', $keys[5]);
                            $total = str_replace('"', '', $keys[6]);
                            $data = [
                                'holder' => $holderName,
                                'subsidiary' => $subsidiary,
                                'country' => $_country,
                                'active' => false,
                                'via' => $via,
                                'direct' => $direct,
                                'total' => $total
                            ];
                            $entity = new Shareholder();
                            $entity->setHolder($holder)
                            ->setSubsidiary($subsidiary)
                            ->setVia(!empty($via))
                            ->setDirect((is_numeric($direct)?$direct:0))
                            ->setTotal((is_numeric($total)?$total:0))
                            ->setSkip(!($entity->getDirect()+$entity->getTotal())>0)
                            ->setHolderCategory($holderCategory)
                            ->setData($data)
                            ;
                            $holder->addHolder($entity);
                            $io->info(
                                sprintf(
                                    '(%d omitidos de %d), Participada: %s, Accionista: %s',
                                    $skipped,
                                    $lineNumber,
                                    $subsidiary->getFullname(),
                                    $holder->getFullname()
                                )
                            );
                            //dump($holder);
                            $this->repo->add($holder, true);
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
