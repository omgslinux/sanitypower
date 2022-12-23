<?php

namespace App\Controller;

use App\Entity\ShareholderCategory;
use App\Form\ShareholderCategoryType;
use App\Repository\ShareholderCategoryRepository as REPO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/shareholdercategory", name="app_shareholder_category_")
 */
class ShareholderCategoryController extends AbstractController
{
    const PREFIX = 'company_';

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
        return $this->render('shareholder_category/index.html.twig', [
            'shareholder_categories' => $this->repo->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="new", methods={"GET", "POST"})
     */
    public function new(Request $request): Response
    {
        $shareholderCategory = new ShareholderCategory();
        $form = $this->createForm(ShareholderCategoryType::class, $shareholderCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->repo->add($shareholderCategory, true);

            return $this->redirectToRoute(self::PREFIX . 'index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('shareholder_category/new.html.twig', [
            'shareholder_category' => $shareholderCategory,
            'form' => $form,
            'prefix' => self::PREFIX,
        ]);
    }

    /**
     * @Route("/{id}", name="show", methods={"GET"})
     */
    public function show(ShareholderCategory $shareholderCategory): Response
    {
        return $this->render('shareholder_category/show.html.twig', [
            'shareholder_category' => $shareholderCategory,
            'prefix' => self::PREFIX,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, ShareholderCategory $shareholderCategory): Response
    {
        $form = $this->createForm(ShareholderCategoryType::class, $shareholderCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->repo->add($shareholderCategory, true);

            return $this->redirectToRoute(self::PREFIX . 'index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('shareholder_category/edit.html.twig', [
            'shareholder_category' => $shareholderCategory,
            'form' => $form,
            'prefix' => self::PREFIX,
        ]);
    }

    /**
     * @Route("/{id}", name="delete", methods={"POST"})
     */
    public function delete(Request $request, ShareholderCategory $shareholderCategory): Response
    {
        if ($this->isCsrfTokenValid('delete'.$shareholderCategory->getId(), $request->request->get('_token'))) {
            $this->repo->remove($shareholderCategory, true);
        }

        return $this->redirectToRoute(self::PREFIX . 'index', [], Response::HTTP_SEE_OTHER);
    }
}
