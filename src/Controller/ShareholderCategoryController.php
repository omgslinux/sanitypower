<?php

namespace App\Controller;

use App\Entity\ShareholderCategory;
use App\Form\ShareholderCategoryType;
use App\Repository\ShareholderCategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/shareholdercategory", name="shareholder_category_")
 */
class ShareholderCategoryController extends AbstractController
{
    const ENTITY = 'entity';
    const PREFIX = 'shareholder_category_';
    const TDIR = 'shareholder_category';

    /**
     * @Route("/", name="index", methods={"GET"})
     */
    public function index(ShareholderCategoryRepository $shareholderCategoryRepository): Response
    {
        return $this->render(self::TDIR . '/index.html.twig', [
            'shareholder_categories' => $shareholderCategoryRepository->findAll(),
            'prefix' => self::PREFIX,
        ]);
    }

    /**
     * @Route("/new", name="new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $entity = new ShareholderCategory();
        $form = $this->createForm(ShareholderCategoryType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($entity);
            $entityManager->flush();

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
    public function show(ShareholderCategory $entity): Response
    {
        return $this->render(self::TDIR . '/show.html.twig', [
            self::ENTITY => $entity,
            'prefix' => self::PREFIX,
        ]);
    }

    /**
     * @Route("/edit/{id}", name="edit", methods={"GET","POST"})
     */
    public function edit(Request $request, ShareholderCategory $entity): Response
    {
        $form = $this->createForm(ShareholderCategoryType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

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
    public function delete(Request $request, ShareholderCategory $entity): Response
    {
        if ($this->isCsrfTokenValid('delete'.$entity->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($entity);
            $entityManager->flush();
        }

        return $this->redirectToRoute(self::PREFIX . 'index');
    }
}
