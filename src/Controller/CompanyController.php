<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\Incoming;
use App\Entity\StaffMembership;
use App\Entity\Subsidiary;
use App\Entity\CompanyEvent;
use App\Form\CompanyType;
use App\Form\IncomingType;
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
            'n' => 'eventos',
            't' => 'Eventos',
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
            'n' => 'accionistas',
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
        $incoming = new Incoming();
        $incoming ->setCompany($company);
        $incomingForm = $this->createForm(
            IncomingType::class,
            $incoming,
            [
                'action' => self::PREFIX . 'incomings'
            ]
        );

        return $this->render('company/show.html.twig', [
            'company' => $company,
            'incomingForm' => $incomingForm->createView(),
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
     * @Route("/event/new/{id}", name="event_new", methods={"GET","POST"})
     */
    public function eventAdd(Request $request, Company $company): Response
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

        return $this->render('company/eventos/new.html.twig', [
            'parent' => $company,
            'form' => $form->createView(),
            'activetab' => 'eventos',
        ]);
    }

    /**
     * @Route("/event/edit/{id}", name="event_edit", methods={"GET","POST"})
     */
    public function eventEdit(Request $request, CompanyEvent $entity): Response
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

        return $this->render('company/eventos/edit.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
            'activetab' => 'eventos',
        ]);
    }

    /**
     * @Route("/incomings/new/{id}", name="incomings_new", methods={"GET","POST"})
     */
    public function incomingsAdd(Request $request, Company $company): Response
    {
        $incoming = new Incoming();
        $incoming->setCompany($company);
        $form = $this->createForm(IncomingType::class, $incoming);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($incoming);
            $em->flush();
            return $this->redirectToRoute(
                self::PREFIX . 'show',
                [
                    'id' => $incoming->getCompany()->getId()
                ]
            );
        }

        return $this->render('company/incomings/new.html.twig', [
            'parent' => $company,
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
                    'id' => $entity->getCompany()->getId()
                ]
            );
        }

        return $this->render('company/incomings/edit.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/membership/new/{id}", name="membership_new", methods={"GET","POST"})
     */
    public function membershipAdd(Request $request, Company $company): Response
    {
        $membership = new StaffMembership();
        $membership->setCompany($company);
        $form = $this->createForm(StaffMembershipType::class, $membership);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($membership);
            $em->flush();
            return $this->redirectToRoute(
                self::PREFIX . 'show',
                [
                    'id' => $company->getId()
                ]
            );
        }

        return $this->render('company/directiva/new.html.twig', [
            'entity' => $company,
            'form' => $form->createView(),
            'activetab' => 'directiva',
        ]);
    }

    /**
     * @Route("/membership/edit/{id}", name="membership_edit", methods={"GET","POST"})
     */
    public function membershipEdit(Request $request, StaffMembership $membership): Response
    {
        $form = $this->createForm(StaffMembershipType::class, $membership);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($membership);
            $em->flush();
            return $this->redirectToRoute(
                self::PREFIX . 'show',
                [
                    'id' => $membership->getCompany()->getId()
                ]
            );
        }

        return $this->render('company/directiva/edit.html.twig', [
            'entity' => $membership,
            'form' => $form->createView(),
            'activetab' => 'eventos',
        ]);
    }


    /**
     * @Route("/subsidiary/new/{id}", name="subsidiary_new", methods={"GET","POST"})
     */
    public function subsidiaryAdd(Request $request, Company $company): Response
    {
        $entity = new Subsidiary();
        $entity->setOwner($company);
        $form = $this->createForm(SubsidiaryType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();
            return $this->redirectToRoute(
                self::PREFIX . 'show',
                [
                    'id' => $company->getId()
                ]
            );
        }

        return $this->render('company/participadas/new.html.twig', [
            'parent' => $company,
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
