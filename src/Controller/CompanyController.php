<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\Incoming;
use App\Entity\StaffMembership;
use App\Entity\Subsidiary;
use App\Entity\Shareholder;
use App\Entity\CompanyEvent;
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
            't' => 'Directiva',
        ],
        [
            'n' => 'participadas',
            't' => 'Participadas',
        ],
        [
            'n' => 'shareholders',
            't' => 'Accionistas',
        ],
        [
            'n' => 'grupo',
            't' => 'Grupo de empresas',
        ],
    ];
    const PREFIX = 'company_';

    /**
     * @Route("/", name="index", methods={"GET"})
     */
    public function index(CompanyRepository $companyRepository): Response
    {
        return $this->render('company/index.html.twig', [
            'companies' => $companyRepository->findAll(),
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
     * @Route("/show/{id}", name="show", methods={"GET"})
     */
    public function companyShow(Company $company): Response
    {
        return $this->render('company/show.html.twig', [
            'parent' => $company,
            'tabs' => self::TABS,
            'prefix' => self::PREFIX,
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
                    'id' => $company->getId()
                ]
            );
        }

        return $this->render('company/history/new.html.twig', [
            'parent' => $company,
            'form' => $form->createView(),
            'activetab' => 'history',
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
                    'id' => $entity->getCompany()->getId()
                ]
            );
        }

        return $this->render('company/history/edit.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
            'activetab' => 'history',
        ]);
    }

    /**
     * @Route("/incomings/new/{id}", name="incomings_new", methods={"GET","POST"})
     */
    public function incomingsAdd(Request $request, Company $parent): Response
    {
        $entity = new Incoming();
        $entity->setCompany($company);
        $form = $this->createForm(IncomingType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();
            return $this->redirectToRoute(
                self::PREFIX . 'show',
                [
                    'id' => $parent->getId()
                ]
            );
        }

        return $this->render('company/incomings/new.html.twig', [
            'parent' => $parent,
            'form' => $form->createView(),
            'activetab' => 'incomings',
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
                    'id' => $entity->getCompany()->getId()
                ]
            );
        }

        return $this->render('company/incomings/edit.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
            'activetab' => 'incomings',
        ]);
    }

    /**
     * @Route("/membership/new/{id}", name="membership_new", methods={"GET","POST"})
     */
    public function membershipAdd(Request $request, Company $parent): Response
    {
        $entity = new StaffMembership();
        $entity->setCompany($parent);
        $form = $this->createForm(StaffMembershipType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();
            return $this->redirectToRoute(
                self::PREFIX . 'show',
                [
                    'id' => $parent->getId()
                ]
            );
        }

        return $this->render('company/directiva/new.html.twig', [
            'parent' => $parent,
            'form' => $form->createView(),
            'activetab' => 'directiva',
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
                    'id' => $entity->getCompany()->getId()
                ]
            );
        }

        return $this->render('company/directiva/edit.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
            'activetab' => 'directiva',
        ]);
    }


    /**
     * @Route("/shareholder/new/{id}", name="shareholder_new", methods={"GET","POST"})
     */
    public function shareholderAdd(Request $request, Company $parent): Response
    {
        $entity = new Shareholder();
        $entity->setCompany($parent);
        $form = $this->createForm(ShareholderType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();
            return $this->redirectToRoute(
                self::PREFIX . 'show',
                [
                    'id' => $parent->getId()
                ]
            );
        }

        return $this->render('company/shareholders/new.html.twig', [
            'parent' => $parent,
            'form' => $form->createView(),
            'activetab' => 'shareholders',
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
                    'id' => $entity->getCompany()->getId()
                ]
            );
        }

        return $this->render('company/shareholders/edit.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
            'activetab' => 'shareholders',
        ]);
    }

    /**
     * @Route("/subsidiary/new/{id}", name="subsidiary_new", methods={"GET","POST"})
     */
    public function subsidiaryAdd(Request $request, Company $parent): Response
    {
        $entity = new Subsidiary();
        $entity->setOwner($parent);
        $form = $this->createForm(SubsidiaryType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();
            return $this->redirectToRoute(
                self::PREFIX . 'show',
                [
                    'id' => $parent->getId()
                ]
            );
        }

        return $this->render('company/participadas/new.html.twig', [
            'parent' => $parent,
            'form' => $form->createView(),
            'activetab' => 'participadas',
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
                    'id' => $entity->getOwner()->getId()
                ]
            );
        }

        return $this->render('company/participadas/edit.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
            'activetab' => 'participadas',
        ]);
    }
}
