<?php

namespace App\Controller\Admin;

use App\Entity\CompanyLevel;
use App\Form\CompanyLevelType;
use App\Repository\CompanyLevelRepository as REPO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/companylevel", name="admin_company_level_")
 */
class CompanyLevelController extends AbstractController
{
    const PREFIX = 'admin_company_level_';
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
            'PREFIX' => self::PREFIX,
        ]);
    }

    /**
     * @Route("/new", name="new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $entity = new CompanyLevel();
        $form = $this->createForm(CompanyLevelType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->repo->add($entity, true);

            return $this->redirectToRoute(self::PREFIX . 'index');
        }

        return $this->render('company_level/new.html.twig', [
            'company_level' => $entity,
            'form' => $form->createView(),
            'PREFIX' => self::PREFIX,
        ]);
    }

    /**
     * @Route("/{id}", name="show", methods={"GET"})
     */
    public function show(CompanyLevel $entity): Response
    {
        return $this->render('company_level/show.html.twig', [
            'company_level' => $entity,
            'PREFIX' => self::PREFIX,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="edit", methods={"GET","POST"})
     */
    public function edit(Request $request, CompanyLevel $entity): Response
    {
        $form = $this->createForm(CompanyLevelType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->repo->add($entity, true);

            return $this->redirectToRoute(self::PREFIX . 'index');
        }

        return $this->render('company_level/edit.html.twig', [
            'company_level' => $entity,
            'form' => $form->createView(),
            'PREFIX' => self::PREFIX,
        ]);
    }

    /**
     * @Route("/{id}", name="delete", methods={"POST"})
     */
    public function delete(Request $request, CompanyLevel $entity): Response
    {
        if ($this->isCsrfTokenValid('delete'.$entity->getId(), $request->request->get('_token'))) {
            $this->repo->remove($entity, true);
        }

        return $this->redirectToRoute(self::PREFIX . 'index');
    }
}
