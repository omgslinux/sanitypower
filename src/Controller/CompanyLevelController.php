<?php

namespace App\Controller;

use App\Entity\CompanyLevel;
use App\Form\CompanyLevelType;
use App\Repository\CompanyLevelRepository as REPO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/companylevel", name="company_level_")
 */
class CompanyLevelController extends AbstractController
{
    const PREFIX = 'company_level_';
    const TDIR = 'company_level';

    private $repo;
    public function __construct(REPO $repo)
    {
        $this->repo = $repo;
    }

    /**
     * @Route("/", name="index", methods={"GET"})
     */
    public function index(): Response
    {
        return $this->render('company_level/index.html.twig', [
            'company_levels' => $this->repo->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $entity = new CompanyLevel();
        $form = $this->createForm(CompanyLevelType::class, $companyLevel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->repo->add($entity, true);

            return $this->redirectToRoute(self::PREFIX . 'index');
        }

        return $this->render('company_level/new.html.twig', [
            'company_level' => $companyLevel,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="show", methods={"GET"})
     */
    public function show(CompanyLevel $companyLevel): Response
    {
        return $this->render('company_level/show.html.twig', [
            'company_level' => $companyLevel,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="edit", methods={"GET","POST"})
     */
    public function edit(Request $request, CompanyLevel $entity): Response
    {
        $form = $this->createForm(CompanyLevelType::class, $companyLevel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->repo->add($entity, true);

            return $this->redirectToRoute(self::PREFIX . 'index');
        }

        return $this->render('company_level/edit.html.twig', [
            'company_level' => $companyLevel,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="delete", methods={"POST"})
     */
    public function delete(Request $request, CompanyLevel $entity): Response
    {
        if ($this->isCsrfTokenValid('delete'.$companyLevel->getId(), $request->request->get('_token'))) {
            $this->repo->remove($entity, true);
        }

        return $this->redirectToRoute(self::PREFIX . 'index');
    }
}
