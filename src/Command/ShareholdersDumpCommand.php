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
use App\Util\CompanyUtil;

#[AsCommand(
    name: 'app:shareholders:dump',
    description: 'Massive dump for shareholders',
)]
class ShareholdersDumpCommand extends Command
{
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
            // Asignamos por defecto el tipo 'C' a las empresas
            $companyCategory = $this->CCR->findOneByLetter('C');
            $lineNumber = $skipped = 0;
            while (($keys = fgetcsv($handle, 1000, "\t")) !== false) {
                //SUBSIDIARYREALNAME{tab}ACCIONISTA(HOLDER) [via its funds]{tab}PAIS{tab}TIPO{tab}Directo{tab}Total
                //"3M COMPANY"{tab}"VANGUARD GROUP INC via its funds"{tab}"US"{tab}"F"{tab}"8.94"{tab}"n.d."
                //"3M COMPANY","STATE STREET CORPORATION","US","B","-","7.36"
                //$io->note(sprintf('Contenido: %s', $contents));
                //$keys = explode(",", $line);
                $lineNumber++;
                if (!empty($subRealName = str_replace('"', '', $keys[0]))) {
                    if ($prev != $subRealName) {
                        if (null!=$prev) {
                            $this->HR->flush();
                        }
                        $subName = $this->repo->getStrippedCN($subRealName);
                        $subsidiary=$this->repo->findOneBy(
                            [
                                'realname' => $subRealName,
                                'inList' => true,
                            ]
                        );
                        if (null==$subsidiary) {
                            $io->error(sprintf('No se encontró nombre real: %s', $subRealName));
                            $subsidiary=$this->repo->findOneBy(
                                [
                                    'fullname' => $subName,
                                    'inList' => true,
                                ]
                            );
                        } else {
                            $subsidiary
                            ->setRealname($subRealName)
                            ->setFullname($subName);
                        }
                        if (null==$subsidiary) {
                            $io->error(sprintf('Fallo en: %s', $subName));
                        }
                        //dump($subsidiary);
                        $this->repo->add($subsidiary, true);
                        $prev=$subsidiary->getRealname();
                    }
                    if (!empty($holderRealName = str_replace('"', '', strtoupper($keys[1])))) {
                        $via = false;
                        if ($viapos = stripos($holderRealName, self::VIASTR)) {
                            $holderRealName = trim(substr($holderRealName, 0, $viapos));
                            $via = true;
                        }
                        $holderName = $this->repo->getStrippedCN($holderRealName);

                        $_country = $country = str_replace('"', '', $keys[2]);
                        if ($country == 'n.d.') {
                            $country = '--';
                        }
                        $holderCategory = $this->SCR->findOneByLetter(str_replace('"', '', $keys[3]));
                        if (null==$holderCategory) {
                            $io->error(sprintf('Fallo en: %s', $subName));
                        }
                        $direct = str_replace('"', '', $keys[4]);
                        $total = str_replace('"', '', $keys[5]);
                        if ($holderCategory->getLetter() == 'H') {
                            $holder = $subsidiary;
                        } else {
                            if (null == ($holder = $this->repo->findOneBy(
                                [
                                    'realname' => $holderRealName,
                                    'country' => $country,
                                ]
                            ))) {
                                if (null == ($holder = $this->repo->findOneBy(
                                    [
                                        'fullname' => $holderName,
                                        'country' => $country,
                                    ]
                                ))) {
                                    $holder = new Company();
                                    $holder->setFullname($holderName)
                                    ->setRealname($holderRealName)
                                    ->setCountry($country)
                                    ->setActive(false)
                                    ->setLevel($level)
                                    ->setCategory($companyCategory);
                                    ;
                                } else {
                                    $holder->setRealname($holderRealName);
                                }
                            }
                            $this->repo->add($holder, true);
                        }
                        //dump($holder);
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
                            ->setHolderCategory($holderCategory)
                            ->setData($data)
                            ;
                            $holder->addHolder($entity);
                            $io->info(
                                sprintf(
                                    '(%d omitidos de %d), Participada: %s, Accionista: %s',
                                    $skipped,
                                    $lineNumber,
                                    $subsidiary->getRealname(),
                                    $holder->getFullname()
                                )
                            );
                            //dump($holder);
                            $this->repo->add($holder, true);
                        } else {
                            $io->error(sprintf('Se omite por existir accionista %s, participada %s', $holderName, $subName));
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
