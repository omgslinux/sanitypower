<?php

namespace App\Controller;

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
use App\Form\CompanyType;
use App\Form\CompanyEventType;
use App\Form\CompanySearchType;
use App\Form\CompanyIncomingType;
use App\Form\ShareholderType;
use App\Form\StaffMembershipType;
use App\Form\SubsidiaryType;
use App\Repository\CompanyRepository as REPO;
use App\Repository\SubsidiaryRepository;
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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use App\Util\CompanyUtil;

#[Route(path: '/manage/company', name: 'company_')]
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
            'n' => 'managers',
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
            'n' => 'control',
            't' => 'Valor de control',
        ],
        /*[
            'n' => 'grupo',
            't' => 'Grupo',
        ],*/
    ];
    const PREFIX = 'company_';

    private $repo;
    public function __construct(REPO $repo)
    {
        $this->repo = $repo;
    }


    #[Route(path: '/index/{page}', name: 'index', methods: ['GET'])]
    public function index($page = 1): Response
    {
        $limit = 50;
        // ... get posts from DB...
        // Controller Action
        $paginator = $this->repo->getActivePaginated($page, $limit); // Returns 5 posts out of 20

        return $this->render('company/index.html.twig', [
            //'companies' => $companyRepository->getAllPaginated(),
            'companies' => $paginator->getIterator(),
            'maxPages' => ceil($paginator->count() / $limit),
            'thisPage' => $page,
        ]);
    }

    #[Route(path: '/matriz/{page}', name: 'matriz', methods: ['GET'])]
    public function indexMatriz($page = 1): Response
    {
        $limit = 40;
        // ... get posts from DB...
        // Controller Action
        $paginator = $this->repo->getActiveMatrizOLD($page, $limit); // Returns 5 posts out of 20

        return $this->render('company/matriz+participada.html.twig', [
            //'companies' => $repo->getAllPaginated(),
            'companies' => $paginator->getIterator(),
            'maxPages' => ceil($paginator->count() / $limit),
            //'maxPages' => ceil(count($paginator) / $limit),
            'thisPage' => $page,
        ]);
    }

    #[Route(path: '/delete/{id}', name: 'delete', methods: ['POST'])]
    public function companyDelete(Request $request, Company $company): Response
    {
        if ($this->isCsrfTokenValid('delete'.$company->getId(), $request->request->get('_token'))) {
            $this->repo->remove($company, true);
        }

        return $this->redirectToRoute(self::PREFIX . 'index');
    }

    #[Route(path: '/edit/{id}', name: 'edit', methods: ['GET', 'POST'])]
    public function companyEdit(Request $request, Company $company): Response
    {
        $form = $this->createForm(CompanyType::class, $company);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->repo->flush();

            return $this->redirectToRoute('company_show', ['id' => $company->getId()]);
        }

        return $this->render('company/edit.html.twig', [
            'company' => $company,
            'form' => $form->createView(),
        ]);
    }


    #[Route(path: '/new', name: 'new', methods: ['GET', 'POST'])]
    public function companyNew(Request $request): Response
    {
        $company = new Company();
        $form = $this->createForm(CompanyType::class, $company);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->repo->add($company, true);

            return $this->redirectToRoute('company_index');
        }

        return $this->render('company/new.html.twig', [
            'company' => $company,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/search', name: 'search', methods: ['GET', 'POST'])]
    public function companySearch(Request $request): Response
    {
        // Para dibujar el cuadro de búsqueda
        $form = $this->createFormBuilder(null)
            ->add(
                'pattern',
                SearchType::class,
                [
                    'attr' => [

                        'placeholder' => 'Buscar...',
                    ]
                ]
            )
            ->setAction($this->generateUrl('company_searchResults'))
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pattern = $request->get('pattern');

            return $this->redirectToRoute('company_searchResults', [$pattern]);
        }

        return $this->render('company/search.html.twig', [
            'searchForm' => $form->createView(),
        ]);
    }

    #[Route(path: '/searchResults/{page}', name: 'searchResults', methods: ['GET', 'POST'])]
    public function companySearchResults(Request $request, $page = 1): Response
    {
        $pattern = $request->get('form')['pattern'];
        $limit = 40;
        // ... get posts from DB...
        // Controller Action
        $paginator = $this->repo->getSearchPaginated($pattern, $page, $limit); // Returns 5 posts out of 20

        return $this->render('company/index.html.twig', [
            //'companies' => $companyRepository->getAllPaginated(),
            'companies' => $paginator->getIterator(),
            'maxPages' => ceil($paginator->count() / $limit),
            'thisPage' => $page,
            'search' => $pattern,
        ]);
    }

    #[Route(path: '/show/{id}/{activetab}', name: 'show', methods: ['GET'])]
    public function companyShow(
        Company $company,
        CurrencyExchangeRepository $cexRepo,
        ShareholderRepository $SHR,
        $activetab = 'incomings'
    ): Response {
        return $this->render('company/show.html.twig', [
            'parent' => $company,
            'tabs' => self::TABS,
            'prefix' => self::PREFIX,
            'activetab' => $activetab,
            'incomings' => $this->incomingFindExchange($company, $cexRepo),
            'subsidiaries' => $SHR->findSubsidiariesByHolder($company),
            //'groupparticipants' => $SHR->findByCompanyGroup($company), // activar cuando se active la pestaña de grupo
        ]);
    }


    #[Route(path: '/group/index/{page}', name: 'group_index', methods: ['GET'])]
    public function groupIndex(Company $company, SubsidiaryRepository $subrepo, $page = 1)
    {
        return $subrepo->findByCompanyGroup($company);
    }

    #[Route(path: '/history/new/{id}', name: 'event_new', methods: ['GET', 'POST'])]
    public function historyAdd(Request $request, Company $company): Response
    {
        $child = new CompanyEvent();
        $child->setCompany($company);
        $form = $this->createForm(CompanyEventType::class, $child);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /*$em = $this->getDoctrine()->getManager();
            $em->persist($child);
            $em->flush(); */
            $company->addCompanyEvent($child);
            $this->repo->add($company, true);
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

    #[Route(path: '/history/edit/{id}', name: 'event_edit', methods: ['GET', 'POST'])]
    public function historyEdit(Request $request, CompanyEventRepository $ceRepo, CompanyEvent $entity): Response
    {
        $form = $this->createForm(CompanyEventType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /*$em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();*/
            $ceRepo->add($entity, true);
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

    #[Route(path: '/incomings/new/{id}', name: 'incomings_new', methods: ['GET', 'POST'])]
    public function incomingsAdd(Request $request, Company $parent): Response
    {
        $entity = new CompanyIncoming();
        $entity->setCompany($parent);
        $form = $this->createForm(CompanyIncomingType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /*$em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();*/
            $parent->addCompanyIncoming($entity);
            $this->repo->add($parent, true);
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

    #[Route(path: '/incomings/edit/{id}', name: 'incoming_edit', methods: ['GET', 'POST'])]
    public function incomingEdit(Request $request, CompanyIncomingRepository $ciRepo, CompanyIncoming $entity): Response
    {
        $form = $this->createForm(CompanyIncomingType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /*$em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();*/
            $ciRepo->add($entity, true);

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

    public function incomingFindExchange(Company $parent, CurrencyExchangeRepository $cuRepo)
    {
        //$em = $this->getDoctrine()->getManager();
        $allIncomings = $parent->getCompanyIncomings();
        //$currencyExchangeRepo = $em->getRepository(CurrencyExchange::class);
        $converted = [];
        //dump($allIncomings);
        if (count($allIncomings)) {
            foreach ($allIncomings as $incoming) {
                $exchange = $cuRepo->getExchange($incoming);
                if (count($exchange)) {
                    $converted [] = [
                        'incoming' => $incoming,
                        'exchange' => $exchange[0],
                    ];
                }
            }
        }

        return $converted;
    }

    #[Route(path: '/membership/new/{id}', name: 'membership_new', methods: ['GET', 'POST'])]
    public function membershipAdd(
        Request $request,
        Company $parent,
        StaffMembersRepository $staffMembersRepo,
        StaffTitleRepository $staffTitleRepo,
        StaffMembershipRepository $staffMembershipRepo
    ): Response {
        $entity = new StaffMembership();
        $entity->setCompany($parent);
        $form = $this->createForm(StaffMembershipType::class, $entity, [ 'batch' => true ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //$em = $this->getDoctrine()->getManager();

            $params = $request->request->all();
            $batch = false;
            //$staffMembersRepo = $em->getRepository(StaffMembers::class);
            //$staffTitleRepo = $em->getRepository(StaffTitle::class);
            //$staffMembershipRepo = $em->getRepository(StaffMembership::class);
            foreach ($params as $param) {
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
                                        'company' => $parent,
                                        'title' => $staffTitle,
                                        'staffMember' => $staffMember
                                    ]
                                ))) {
                                    $staffMembership = new StaffMembership();
                                    $staffMembership->setCompany($parent)
                                    ->setTitle($staffTitle)
                                    ->setStaffMember($staffMember);
                                    //$em->persist($staffMembership);
                                }
                                $parent->addStaffMembership($staffMembership);
                            }
                        }
                    }
                }
            }

            if (!$batch) {
                $this->repo->add($parent);
                //$em->persist($entity);
            }

            $this->repo->flush();
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
            'activetab' => 'directiva',
        ]);
    }

    #[Route(path: '/membership/edit/{id}', name: 'membership_edit', methods: ['GET', 'POST'])]
    public function membershipEdit(Request $request, StaffMembersRepository $smRepo, StaffMembership $entity): Response
    {
        $form = $this->createForm(StaffMembershipType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /*$em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();*/
            $smRepo->add($entity, true);
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
            'activetab' => 'directiva',
        ]);
    }


    #[Route(path: '/shareholder/new/{id}', name: 'shareholder_new', methods: ['GET', 'POST'])]
    public function shareholderAdd(
        Request $request,
        Company $parent,
        ShareholderRepository $holderRepo,
        CompanyActivityCategoryRepository $categoryRepo,
        CompanyLevelRepository $companyLevelRepo,
        ShareholderCategoryRepository $holdercatRepo
    ): Response {
        $entity = new Shareholder();
        $entity->setSubsidiary($parent);
        $form = $this->createForm(ShareholderType::class, $entity, [ 'batch' => true ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $params = $request->request->all();
            $batch = false;
            //$companyRepo = $em->getRepository(Company::class);
            //$holderRepo = $em->getRepository(Shareholder::class);
            //$categoryRepo = $em->getRepository(CompanyCategory::class);
            foreach ($params as $param) {
                if (!empty($param['batch'])) {
                    //$level = $em->getRepository(CompanyLevel::class)->findOneBy(['level' => 'Sin identificar']);
                    $level = $companyLevelRepo->findOneBy(['level' => 'Sin identificar']);
                    $companyCategory = $categoryRepo->findOneByLetter('K');

                    $batch = true;
                    foreach (preg_split("/((\r?\n)|(\r\n?))/", $param['batch']) as $line) {
                        //"VANGUARD GROUP INC via its funds"{tab}"US"{tab}"F"{tab}"8.94"{tab}"n.d."
                        //"STATE STREET CORPORATION"{tab}"US"{tab}"B"{tab}"-"{tab}"7.36"
                        $keys = explode("\t", $line);
                        if (!empty($holderRealName = str_replace('"', '', $keys[0]))) {
                            $via = false;
                            if ($viapos = stripos($holderRealName, 'via its funds')) {
                                $holderRealName = trim(substr($holderRealName, 0, $viapos));
                                $via = true;
                            }
                            //$holderName = $this->repo->getStrippedCN($holderRealName);
                            $holderFullname = $this->repo->getStrippedCN($holderRealName);
                            //dump($keys);
                            $holderCategory = $holdercatRepo->findOneByLetter(str_replace('"', '', $keys[2]));
                            $_country = $country = str_replace('"', '', $keys[1]);
                            if ($country == 'n.d.') {
                                $country = '--';
                            }
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
                                    ->setRealname($holderRealName)
                                    ->setCountry($country)
                                    ->setCategory($companyCategory)
                                    ->setActive(false)
                                    ->setLevel($level)
                                    ;
                                } else {
                                    if ($holder->getRealname()!=$holderRealName) {
                                        $holder->setRealname($holderRealName);
                                    }
                                }
                                //$em->persist($holder);
                                $this->repo->add($holder, true);
                            }
                            //dump($holder);
                            //$via = !empty(str_replace('"', '', $keys[1]));
                            $direct = str_replace('"', '', $keys[3]);
                            $total = str_replace('"', '', $keys[4]);
                            $data = [
                                'country' => $_country,
                                'name' => $holderFullname,
                                'realname' => $holderRealName,
                                'active' => false,
                                'via' => $via,
                                'direct' => $direct,
                                'total' => $total
                            ];
                            if (null == ($entity = $holderRepo->findOneBy(
                                [
                                    'holder' => $holder,
                                    'subsidiary' => $parent,
                                    'via' => $via,
                                ]
                            ))) {
                                $entity = new Shareholder();
                                $entity->setSubsidiary($parent)
                                ->setHolder($holder)
                                ->setVia($via)
                                ->setDirect((is_numeric($direct)?$direct:0))
                                ->setTotal((is_numeric($total)?$total:0))
                                ->setSkip(!($entity->getDirect()+$entity->getTotal())>0)
                                ->setHolderCategory($holderCategory)
                                ->setData($data)
                                ;
                                //$parent->addHolder($entity);
                                //$this->repo->add($parent, true);
                                $holderRepo->add($entity, true);
                            }
                        }
                    }
                }
            }

            if (!$batch) {
                //$em->persist($entity);
                $parent->addHolder($entity);
            }
            //$em->flush();
            $this->repo->add($parent, true);
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

    #[Route(path: '/shareholder/edit/{id}', name: 'shareholder_edit', methods: ['GET', 'POST'])]
    public function shareholderEdit(Request $request, ShareholderRepository $sRepo, Shareholder $entity): Response
    {
        $form = $this->createForm(ShareholderType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /*$em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();*/
            $sRepo->add($entity, true);
            return $this->redirectToRoute(
                self::PREFIX . 'show',
                [
                    'id' => $entity->getHolder()->getId(),
                    'activetab' => 'shareholders',
                ]
            );
        }

        return $this->render('company/shareholders/edit.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/subsidiary/new/{id}', name: 'subsidiary_new', methods: ['GET', 'POST'])]
    public function subsidiaryAdd(
        Request $request,
        Company $parent,
        SubsidiaryRepository $subsidiaryRepo,
        CompanyActivityCategoryRepository $categoryRepo,
        CompanyLevelRepository $companyLevelRepo
    ): Response {
        set_time_limit(100);
        $entity = new Subsidiary();
        $entity->setOwner($parent);
        $formOptions =
            [
                'batch' => true,
                'inlist' => $parent->isInList()
            ];
        $form = $this->createForm(
            SubsidiaryType::class,
            $entity,
            $formOptions
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->has('textowned')) {
                $textowned = $form->get('textowned')->getData();
                $country = $form->get('textcountry')->getData();
                $owned = $this->repo->findBy(
                    [
                        'fullname' => $textowned,
                        'country' => $country
                    ]
                );
                //dump($textowned, $owned);
                //die();
                if (count($owned)==1) {
                    $entity->setOwned($owned[0]);
                    $subsidiaryRepo->add($entity, true);
                }
            } else {
                $params = $request->request->all();
                $batch = false;
                //$subsidiaryRepo = $em->getRepository(Subsidiary::class);
                //$categoryRepo = $em->getRepository(CompanyCategory::class);
                foreach ($params as $param) {
                    if (!empty($param['batch'])) {
                        $level = $companyLevelRepo->findOneBy(['level' => 'Pendiente']);
                        $batch = true;
                        foreach (preg_split("/((\r?\n)|(\r\n?))/", $param['batch']) as $line) {
                            $keys = explode(",", $line);
                            if (!empty($fullname = str_replace('.', '', str_replace('"', '', $keys[0])))) {
                                $_country = $country = str_replace('"', '', $keys[2]);
                                if ($country == 'n.d.') {
                                    $country = '--';
                                }
                                $via = (str_replace('"', '', $keys[1]));
                                $category = $categoryRepo->findOneByLetter(str_replace('"', '', $keys[3]));
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
                                    ->setLevel($level);
                                }
                                $owned->setCategory($category);
                                $this->repo->add($owned, true);
                                if (null == ($entity = $subsidiaryRepo->findOneBy(
                                    [
                                        'owned' => $owned,
                                        'owner' => $parent,
                                    ]
                                ))) {
                                    $_direct = $direct = str_replace('"', '', $keys[4]);
                                    $_percent = $percent = str_replace('"', '', $keys[5]);
                                    $data =
                                    [
                                        'country' => $_country,
                                        'name' => $fullname,
                                        'active' => false,
                                        'via' => $via,
                                        'direct' => $_direct,
                                        'total' => $_percent
                                    ];
                                    $entity = new Subsidiary();
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
                                    ->setVia(!empty($via))
                                    ->setData($data)
                                    ;
                                    //dump($data); die();
                                    //$em->persist($entity);
                                    $subsidiaryRepo->add($entity);
                                    //dump($entity);
                                }
                                //$em->flush();
                                $this->repo->flush();
                            }
                        }
                    }
                }

                if (!$batch) {
                    //$em->persist($entity);
                    $subsidiaryRepo->add($entity);
                }
            }
            //$em->flush();
            $this->repo->flush();

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
            'formoptions' => $formOptions
        ]);
    }

    #[Route(path: '/subsidiary/edit/{id}', name: 'subsidiary_edit', methods: ['GET', 'POST'])]
    public function subsidiaryEdit(Request $request, SubsidiaryRepository $sRepo, Subsidiary $entity): Response
    {
        $formOptions =
            [
                'batch' => false,
                'inlist' => true
            ];
        $form = $this->createForm(
            SubsidiaryType::class,
            $entity,
            $formOptions
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /*$em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();*/
            $sRepo->add($entity, true);

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
