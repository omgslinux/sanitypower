<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\Incoming;
use App\Entity\StaffMembership;
use App\Entity\CurrencyExchange;
use App\Entity\CompanyCategory;
use App\Entity\Subsidiary;
use App\Entity\Shareholder;
use App\Entity\CompanyEvent;
use App\Entity\CompanyLevel;
use App\Form\CompanyType;
use App\Form\IncomingType;
use App\Form\ShareholderType;
use App\Form\StaffMembershipType;
use App\Form\SubsidiaryType;
use App\Form\CompanyEventType;
use App\Repository\CompanyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/company", name="company_")
 */
class CompanyController extends AbstractController
{
    const TABS = [
        [
            'n' => 'incomings',
            't' => 'Ingresos explotación',
        ],
        [
            'n' => 'history',
            't' => 'Historial',
        ],
        [
            'n' => 'directiva',
            't' => 'Consejo de administración',
        ],
        [
            'n' => 'shareholders',
            't' => 'Accionistas',
        ],
        [
            'n' => 'participadas',
            't' => 'Participadas',
        ],
        [
            'n' => 'grupo',
            't' => 'Grupo de empresa',
        ],
    ];
    const PREFIX = 'company_';

    /**
     * @Route("/index/{page}", name="index", methods={"GET"})
     */
    public function index(CompanyRepository $repo, $page = 1): Response
    {
        $limit = 20;
        // ... get posts from DB...
        // Controller Action
        $paginator = $repo->getAllPaginated($page, $limit); // Returns 5 posts out of 20

        return $this->render('company/index.html.twig', [
            //'companies' => $companyRepository->getAllPaginated(),
            'companies' => $paginator->getIterator(),
            'maxPages' => ceil($paginator->count() / $limit),
            'thisPage' => $page,
        ]);
    }

    /**
     * @Route("/delete/{id}", name="delete", methods={"POST"})
     */
    public function companyDelete(Request $request, Company $company): Response
    {
        if ($this->isCsrfTokenValid('delete'.$company->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($company);
            $entityManager->flush();
        }

        return $this->redirectToRoute(self::PREFIX . 'index');
    }

    /**
     * @Route("/new", name="new", methods={"GET","POST"})
     */
    public function companyNew(Request $request): Response
    {
        $company = new Company();
        $form = $this->createForm(CompanyType::class, $company);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($company);
            $entityManager->flush();

            return $this->redirectToRoute('company_index');
        }

        return $this->render('company/new.html.twig', [
            'company' => $company,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/show/{id}/{activetab}", name="show", methods={"GET"})
     */
    public function companyShow(Company $company, $activetab = 'incomings'): Response
    {
        $em = $this->getDoctrine()->getManager();
        $subRepository = $em->getRepository(Subsidiary::class);
        return $this->render('company/show.html.twig', [
            'parent' => $company,
            'tabs' => self::TABS,
            'prefix' => self::PREFIX,
            'activetab' => $activetab,
            'incomings' => $this->incomingFindExchange($company),
            'groupparticipants' => $this->groupIndex($company),
            'subsidiaries' => $subRepository->findByCompanyOwner($company),
        ]);
    }

    /**
     * @Route("/edit/{id}", name="edit", methods={"GET","POST"})
     */
    public function companyEdit(Request $request, Company $company): Response
    {
        $form = $this->createForm(CompanyType::class, $company);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('company_show', ['id' => $company->getId()]);
        }

        return $this->render('company/edit.html.twig', [
            'company' => $company,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/group/index/{page}", name="group_index", methods={"GET"})
     */
    public function groupIndex(Company $company, $page = 1)
    {
        return $this->getDoctrine()->getManager()->getRepository(Subsidiary::class)
        ->findByCompanyGroup($company);
    }

    /**
     * @Route("/history/new/{id}", name="event_new", methods={"GET","POST"})
     */
    public function historyAdd(Request $request, Company $company): Response
    {
        $child = new CompanyEvent();
        $child->setCompany($company);
        $form = $this->createForm(CompanyEventType::class, $child);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($child);
            $em->flush();
            return $this->redirectToRoute(
                self::PREFIX . 'show',
                [
                    'id' => $company->getId(),
                    'activetab' => 'history',
                ]
            );
        }

        return $this->render('company/history/new.html.twig', [
            'parent' => $company,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/history/edit/{id}", name="event_edit", methods={"GET","POST"})
     */
    public function historyEdit(Request $request, CompanyEvent $entity): Response
    {
        $form = $this->createForm(CompanyEventType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();
            return $this->redirectToRoute(
                self::PREFIX . 'show',
                [
                    'id' => $entity->getCompany()->getId(),
                    'activetab' => 'history',
                ]
            );
        }

        return $this->render('company/history/edit.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/incomings/new/{id}", name="incomings_new", methods={"GET","POST"})
     */
    public function incomingsAdd(Request $request, Company $parent): Response
    {
        $entity = new Incoming();
        $entity->setCompany($parent);
        $form = $this->createForm(IncomingType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();
            return $this->redirectToRoute(
                self::PREFIX . 'show',
                [
                    'id' => $parent->getId(),
                    'activetab' => 'incomings',
                ]
            );
        }

        return $this->render('company/incomings/new.html.twig', [
            'parent' => $parent,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/incomings/edit/{id}", name="incoming_edit", methods={"GET","POST"})
     */
    public function incomingEdit(Request $request, Incoming $entity): Response
    {
        $form = $this->createForm(IncomingType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();
            return $this->redirectToRoute(
                self::PREFIX . 'show',
                [
                    'id' => $entity->getCompany()->getId(),
                    'activetab' => 'incomings',
                ]
            );
        }

        return $this->render('company/incomings/edit.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
        ]);
    }

    public function incomingFindExchange(Company $parent)
    {
        $em = $this->getDoctrine()->getManager();
        $allIncomings = $parent->getIncomings();
        $currencyExchangeRepo = $em->getRepository(CurrencyExchange::class);
        $converted = [];
        foreach ($allIncomings as $incoming) {
            $exchange = $currencyExchangeRepo->getExchange($incoming);
            $converted [] = [
                'incoming' => $incoming,
                'exchange' => $exchange[0],
            ];
        }

        return $converted;
    }

    /**
     * @Route("/membership/new/{id}", name="membership_new", methods={"GET","POST"})
     */
    public function membershipAdd(Request $request, Company $parent): Response
    {
        $entity = new StaffMembership();
        $entity->setCompany($parent);
        $form = $this->createForm(StaffMembershipType::class, $entity, [ 'batch' => true ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();
            return $this->redirectToRoute(
                self::PREFIX . 'show',
                [
                    'id' => $parent->getId(),
                    'activetab' => 'directiva',
                ]
            );
        }

        return $this->render('company/directiva/new.html.twig', [
            'parent' => $parent,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/membership/edit/{id}", name="membership_edit", methods={"GET","POST"})
     */
    public function membershipEdit(Request $request, StaffMembership $entity): Response
    {
        $form = $this->createForm(StaffMembershipType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();
            return $this->redirectToRoute(
                self::PREFIX . 'show',
                [
                    'id' => $entity->getCompany()->getId(),
                    'activetab' => 'directiva',
                ]
            );
        }

        return $this->render('company/directiva/edit.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/shareholder/new/{id}", name="shareholder_new", methods={"GET","POST"})
     */
    public function shareholderAdd(Request $request, Company $parent): Response
    {
        $entity = new Shareholder();
        $entity->setCompany($parent);
        $form = $this->createForm(ShareholderType::class, $entity, [ 'batch' => true ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $params = $request->request->all();
            $batch = false;
            $companyRepo = $em->getRepository(Company::class);
            $holderRepo = $em->getRepository(Shareholder::class);
            $categoryRepo = $em->getRepository(CompanyCategory::class);
            foreach ($params as $param) {
                if (!empty($param['batch'])) {
                    $level = $em->getRepository(CompanyLevel::class)->findOneBy(['level' => 'Sin identificar']);

                    $batch = true;
                    foreach (preg_split("/((\r?\n)|(\r\n?))/", $param['batch']) as $line) {
                        $keys = explode(",", $line);
                        if (!empty($fullname = str_replace('"', '', $keys[0]))) {
                            //dump($keys);
                            $category = $categoryRepo->findOneByLetter(str_replace('"', '', $keys[3]));
                            if (null == ($holder = $companyRepo->findOneBy(['fullname' => $fullname]))) {
                                $holder = new Company();
                                $holder->setFullname($fullname)
                                ->setCountry(str_replace('"', '', $keys[2]))
                                ->setActive(false)
                                ->setLevel($level)
                                ;
                            }
                            $holder->setCategory($category);
                            $em->persist($holder);
                            //dump($holder);
                            if (null == ($entity = $holderRepo->findOneBy(['holder' => $fullname]))) {
                                $entity = new Shareholder();
                                $directOwnership = str_replace('"', '', $keys[4]);
                                $totalOwnership = str_replace('"', '', $keys[5]);
                                $entity->setCompany($parent)
                                ->setHolder($holder)
                                ->setVia(!empty(str_replace('"', '', $keys[1])))
                                ->setDirectOwnership((is_numeric($directOwnership)?$directOwnership:0))
                                ->setTotalOwnership((is_numeric($totalOwnership)?$totalOwnership:0))
                                ;
                            }
                            $em->persist($entity);
                        }
                    }
                }
            }

            if (!$batch) {
                $em->persist($entity);
            }
            $em->flush();
            return $this->redirectToRoute(
                self::PREFIX . 'show',
                [
                    'id' => $parent->getId(),
                    'activetab' => 'shareholders',
                ]
            );
        }

        return $this->render('company/shareholders/new.html.twig', [
            'parent' => $parent,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/shareholder/edit/{id}", name="shareholder_edit", methods={"GET","POST"})
     */
    public function shareholderEdit(Request $request, Shareholder $entity): Response
    {
        $form = $this->createForm(ShareholderType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();
            return $this->redirectToRoute(
                self::PREFIX . 'show',
                [
                    'id' => $entity->getCompany()->getId(),
                    'activetab' => 'shareholders',
                ]
            );
        }

        return $this->render('company/shareholders/edit.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/subsidiary/new/{id}", name="subsidiary_new", methods={"GET","POST"})
     */
    public function subsidiaryAdd(Request $request, Company $parent): Response
    {
        $entity = new Subsidiary();
        $entity->setOwner($parent);
        $form = $this->createForm(SubsidiaryType::class, $entity, [ 'batch' => true ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $params = $request->request->all();
            $batch = false;
            $companyRepo = $em->getRepository(Company::class);
            $subsidiaryRepo = $em->getRepository(Subsidiary::class);
            $categoryRepo = $em->getRepository(CompanyCategory::class);
            foreach ($params as $param) {
                if (!empty($param['batch'])) {
                    $level = $em->getRepository(CompanyLevel::class)->findOneBy(['level' => 'Pendiente']);
                    $batch = true;
                    foreach (preg_split("/((\r?\n)|(\r\n?))/", $param['batch']) as $line) {
                        $keys = explode(",", $line);
                        if (!empty($fullname = str_replace('.', '', str_replace('"', '', $keys[0])))) {
                            $country = str_replace('"', '', $keys[1]);
                            if ($country == 'n.d.') {
                                $country = '--';
                            }
                            $category = $categoryRepo->findOneByLetter(str_replace('"', '', $keys[2]));
                            if (null == ($owned = $companyRepo->findOneBy(['fullname' => $fullname, 'country' => $country]))) {
                                $owned = new Company();
                                $owned->setFullname($fullname)
                                ->setCountry($country)
                                ->setActive(false)
                                ->setLevel($level);
                            }
                            $owned->setCategory($category);
                            $em->persist($owned);
                            //dump($owned);
                            $em->flush();
                            if (null == ($entity = $subsidiaryRepo->findOneBy(['owned' => $owned]))) {
                                $entity = new Subsidiary();
                                $_direct = $direct = str_replace('"', '', $keys[3]);
                                $_percent = $percent = str_replace('"', '', $keys[4]);
                                if ($_direct == "MO" || $_direct == ">50") {
                                    $direct = 50.01;
                                } elseif ($_direct == "WO") {
                                    $direct = 100;
                                } elseif (!is_numeric($_direct)) {
                                    $direct = 0;
                                }
                                if ($_percent == "MO" || $_percent == ">50") {
                                    $percent = 50.01;
                                } elseif ($_percent == "WO") {
                                    $percent = 100;
                                } elseif (!is_numeric($_percent)) {
                                    $percent = 0;
                                }
                                $entity->setOwner($parent)
                                ->setOwned($owned)
                                ->setDirect($direct)
                                ->setPercent($percent)
                                ;
                                $em->persist($entity);
                                //dump($entity);
                            }
                            $em->flush();
                        }
                    }
                }
            }

            if (!$batch) {
                $em->persist($entity);
            }
            $em->flush();
            return $this->redirectToRoute(
                self::PREFIX . 'show',
                [
                    'id' => $parent->getId(),
                    'activetab' => 'participadas',
                ]
            );
        }

        return $this->render('company/participadas/new.html.twig', [
            'parent' => $parent,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/subsidiary/edit/{id}", name="subsidiary_edit", methods={"GET","POST"})
     */
    public function subsidiaryEdit(Request $request, Subsidiary $entity): Response
    {
        $form = $this->createForm(SubsidiaryType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();
            return $this->redirectToRoute(
                self::PREFIX . 'show',
                [
                    'id' => $entity->getOwner()->getId(),
                    'activetab' => 'participadas',
                ]
            );
        }

        return $this->render('company/participadas/edit.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
        ]);
    }
}
