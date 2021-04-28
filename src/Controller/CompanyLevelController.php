<?php

namespace App\Controller;

use App\Entity\CompanyLevel;
use App\Form\CompanyLevelType;
use App\Repository\CompanyLevelRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/companylevel", name="company_level_")
 */
class CompanyLevelController extends AbstractController
{
    /**
     * @Route("/", name="index", methods={"GET"})
     */
    public function index(CompanyLevelRepository $companyLevelRepository): Response
    {
        return $this->render('company_level/index.html.twig', [
            'company_levels' => $companyLevelRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $companyLevel = new CompanyLevel();
        $form = $this->createForm(CompanyLevelType::class, $companyLevel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($companyLevel);
            $entityManager->flush();

            return $this->redirectToRoute('company_level_index');
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
    public function edit(Request $request, CompanyLevel $companyLevel): Response
    {
        $form = $this->createForm(CompanyLevelType::class, $companyLevel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('company_level_index');
        }

        return $this->render('company_level/edit.html.twig', [
            'company_level' => $companyLevel,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="delete", methods={"POST"})
     */
    public function delete(Request $request, CompanyLevel $companyLevel): Response
    {
        if ($this->isCsrfTokenValid('delete'.$companyLevel->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($companyLevel);
            $entityManager->flush();
        }

        return $this->redirectToRoute('company_level_index');
    }
}
