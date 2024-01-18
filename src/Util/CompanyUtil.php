<?php

namespace App\Util;

use App\Entity\Company;
use App\Entity\CompanyIncoming;
use App\Entity\StaffMembership;
use App\Entity\CurrencyExchange;
use App\Entity\Subsidiary;
use App\Entity\StaffTitle;
use App\Entity\Shareholder;
use App\Entity\CompanyEvent;
use App\Entity\CompanyLevel;
use App\Entity\StaffMembers;
use App\Repository\CompanyRepository as REPO;
//use App\Repository\SubsidiaryRepository;
use App\Repository\StaffTitleRepository;
use App\Repository\CompanyEventRepository;
use App\Repository\ShareholderRepository;
use App\Repository\ShareholderCategoryRepository;
use App\Repository\StaffMembersRepository;
use App\Repository\CompanyLevelRepository;
use App\Repository\StaffMembershipRepository;
use App\Repository\CompanyActivityCategoryRepository;
use App\Repository\CompanyIncomingRepository;
use App\Repository\CurrencyExchangeRepository;
use App\Util\PhpreaderHelper;
//use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

class CompanyUtil
{
    private $company; // Para entidad company
    private $repo; // Repositorio de company
    private $SHR; // Repositorio de shareholder
    private $shareholder; // Entidad shareholder
    private $EM; // EntityManager

    public function __construct(REPO $repo, ShareholderRepository $SHR)
    {
        //$this->EM = $SER->getEntityManager();
        //$this->repo = $SER->getRepository(REPO::class);
        //$this->SHR = $SER->getRepository(ShareholderRepository::class);
        $this->repo = $repo;
        $this->SHR = $SHR;
    }

    public function setCompany(Company $company)
    {
        $this->company = $company;
    }

    public function membershipAdd($param)
    {
        $company = $this->company;
        $entity = new StaffMembership();
        $entity->setCompany($company);

        if (!empty($param['batch'])) {
            $batch = true;
            foreach (preg_split("/((\r?\n)|(\r\n?))/", $param['batch']) as $line) {
                $keys = explode(",", $line);
                if (!empty($surname = str_replace('"', '', $keys[0]))) {
                    //dump($keys);

                    // Verificamos si existe el cargo, para ver si interesa
                    if (null != ($staffTitle=$staffTitleRepo->findOneByName(str_replace('"', '', $keys[2])))) {
                        $name = str_replace('"', '', $keys[1]);
                        if (null==($staffMember= $staffMembersRepo->findOneBy(
                            [
                                'surname' => $surname,
                                'name' => $name,
                            ]
                        ))) {
                            $staffMember = new StaffMembers();
                            $staffMember->setSurname($surname)
                            ->setName($name);
                            /*$em->persist($staffMember);
                            $em->flush();*/
                        }
                        if (null==($staffMembership=$staffMembershipRepo->findOneBy(
                            [
                                'company' => $company,
                                'title' => $staffTitle,
                                'staffMember' => $staffMember
                            ]
                        ))) {
                            $staffMembership = new StaffMembership();
                            $staffMembership->setCompany($company)
                            ->setTitle($staffTitle)
                            ->setStaffMember($staffMember);
                            //$em->persist($staffMembership);
                        }
                        $company->addStaffMembership($staffMembership);
                    }
                }
            }
        }
    }

    public function addHolders($holders)
    {
        $level = $this->em->getRepository(CompanyLevelRepository::class)->findOneBy(['level' => 'Sin identificar']);
        $companyCategory = $this->em->getRepository(CompanyActivityCategoryRepository::class)->findOneByLetter('K');
        $defaults = [
            'level' => $level,
            'companyCategory' => $companyCategory,
        ];
        foreach (preg_split("/((\r?\n)|(\r\n?))/", $holders) as $line) {
            $keys = explode(",", $line);
            $this->addSingleHolder($keys, $defaults);
        }
    }

    public function addSingleHolder($keys, $defaults)
    {
        // Viene una línea de un array completo con los campos con las comillas adecuadas y en orden
        // Company ya tiene el objeto en $this->company
        //"3M COMPANY","VANGUARD GROUP INC","","US","F","8.94","n.d."
        //"3M COMPANY","STATE STREET CORPORATION","VIA ITS FUNDS","US","B","-","7.36"
        if (!empty($holderRealname = str_replace('"', '', $keys[1]))) {
            $holderFullname = $this->stripCompanyName($holderRealname);
            //dump($keys);
            $holderCategory = $holdercatRepo->findOneByLetter(str_replace('"', '', $keys[4]));
            $_country = $country = str_replace('"', '', $keys[3]);
            if ($country == 'n.d.') {
                $country = '--';
            }
            $via = (str_replace('"', '', $keys[2]));
            $direct = str_replace('"', '', $keys[5]);
            $total = str_replace('"', '', $keys[6]);
            $data = [
                'country' => $_country,
                'name' => $holderFullname,
                'realname' => $holderRealname,
                'active' => false,
                'via' => $via,
                'direct' => $direct,
                'total' => $total
            ];
            if ($holderCategory->getLetter() == 'H') {
                $holder = $parent;
            } else {
                if (null == ($holder = $this->repo->findOneBy(
                    [
                        'fullname' => $holderFullname,
                        'country' => $country,
                    ]
                ))) {
                    $holder = new Company();
                    $holder->setFullname($holderFullname)
                    ->setRealname($holderRealname)
                    ->setCountry($country)
                    ->setCategory($defaults['companyCategory'])
                    ->setActive(false)
                    ->setLevel($defaults['level'])
                    ;
                } else {
                    if ($holder->getRealname()!=$holderRealname) {
                        $holder->setRealname($holderRealname);
                    }
                }
                //$em->persist($holder);
                $this->repo->add($holder, true);
            }
            //dump($holder);
            if (null == ($entity = $holderRepo->findOneBy(
                [
                    'holder' => $holder,
                    'subsidiary' => $parent,
                ]
            ))) {
                $entity = new Shareholder();
                $entity->setSubsidiary($parent)
                ->setHolder($holder)
                ->setVia(!empty($via))
                ->setDirect((is_numeric($direct)?$direct:0))
                ->setTotal((is_numeric($total)?$total:0))
                ->setSkip(!($entity->getDirect()+$entity->getTotal())>0)
                ->setHolderCategory($holderCategory)
                ->setData($data)
                ;
                $parent->addHolder($entity);
                $this->repo->add($parent, true);
            }
        }
    }

    private function originalCompanyFileName($companyFilename): string
    {
        $search = ['/', '’', ',', '.'];
        $replace = ['@@SLASH@@', '@@QUOTE@@', '@@COMMA@@', '@@DOT@@'];
        // Obtenemos el nombre de fichero de la empresa, con todos sus caracteres imprimibles
        //$_empresa = substr($company, 0, strpos($company, '.'));
        $empresa = trim(str_replace($search, $replace, strtoupper($companyFilename)));

        return $empresa;
    }

    /*public function stripCompanyFileName($companyFilename): string
    {
        $search = ['@@SLASH@@', '@@QUOTE@@', '@@COMMA@@', '@@DOT@@'];
        $replace = ['/', '’', ',', '.'];
        // Obtenemos el nombre formal de la empresa, con todos sus caracteres imprimibles
        //$_empresa = substr($company, 0, strpos($company, '.'));
        $empresa = trim(str_replace($search, $replace, strtoupper($companyFilename)));

        return $empresa;
    } */

    /*public function stripCompanyName($company): string
    {
        $search = [',', '.', '  '];
        $replace = [' ', '', ' '];
        // Obtenemos el nombre de la empresa para comparar con los informes
        //$_empresa = substr($company, 0, strpos($company, '.'));
        $empresa = trim(str_replace($search, $replace, strtoupper(self::stripCompanyFileName($company))));

        return $empresa;
    }*/

    public function addSingleSubsidiary($keys)
    {
        // Tenemos que intercambiar los dos primeros parámetros del request para añadir la participada en la tabla de accionistas
        $holder = $keys;
        $holder[1] = $keys[0];
        $holder[0] = $keys[1];
        return $this->addSingleHolder($keys);
    }


}
