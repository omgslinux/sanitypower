<?php

namespace App\Controller;

use App\Entity\CompanyCategory;
use App\Form\CompanyCategoryType;
use App\Repository\CompanyCategoryRepository as REPO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/CompanyCategory", name="company_category_")
 */
class CompanyCategoryController extends AbstractController
{
    const ENTITY = 'entity';
    const PREFIX = 'company_category_';
    const TDIR = 'company_category';

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
        return $this->render(self::TDIR . '/index.html.twig', [
            'categories' => $this->repo->findAll(),
            'prefix' => self::PREFIX,
        ]);
    }

    /**
     * @Route("/new", name="new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $entity = new CompanyCategory();
        $form = $this->createForm(CompanyCategoryType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->repo->add($entity, true);

            return $this->redirectToRoute(self::PREFIX . 'index');
        }

        return $this->render(self::TDIR . '/new.html.twig', [
            self::ENTITY => $entity,
            'form' => $form->createView(),
            'prefix' => self::PREFIX,
        ]);
    }

    /**
     * @Route("/show/{id}", name="show", methods={"GET"})
     */
    public function show(CompanyCategory $entity): Response
    {
        return $this->render(self::TDIR . '/show.html.twig', [
            self::ENTITY => $entity,
            'prefix' => self::PREFIX,
        ]);
    }

    /**
     * @Route("/edit/{id}", name="edit", methods={"GET","POST"})
     */
    public function edit(Request $request, CompanyCategory $entity): Response
    {
        $form = $this->createForm(CompanyCategoryType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->repo->add($entity, true);

            return $this->redirectToRoute(self::PREFIX . 'index');
        }

        return $this->render(self::TDIR . '/edit.html.twig', [
            self::ENTITY => $entity,
            'form' => $form->createView(),
            'prefix' => self::PREFIX,
        ]);
    }

    /**
     * @Route("/delete/{id}", name="delete", methods={"POST"})
     */
    public function delete(Request $request, CompanyCategory $entity): Response
    {
        if ($this->isCsrfTokenValid('delete'.$entity->getId(), $request->request->get('_token'))) {
            $this->repo->remove($entity, true);
        }

        return $this->redirectToRoute(self::PREFIX . 'index');
    }
}
