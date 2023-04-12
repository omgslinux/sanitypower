<?php

namespace App\Controller;

use App\Entity\Glossary;
use App\Form\GlossaryType;
use App\Repository\GlossaryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/user/glossary", name="app_glossary_")
 */
class GlossaryController extends AbstractController
{
    const PREFIX = 'app_glossary_';
    /**
     * @Route("/", name="index", methods={"GET"})
     */
    public function index(GlossaryRepository $glossaryRepository): Response
    {
        return $this->render('glossary/index.html.twig', [
            'glossaries' => $glossaryRepository->findAll(),
            'PREFIX' => self::PREFIX,
        ]);
    }

    /**
     * @Route("/new", name="new", methods={"GET", "POST"})
     */
    public function new(Request $request, GlossaryRepository $glossaryRepository): Response
    {
        $glossary = new Glossary();
        $form = $this->createForm(GlossaryType::class, $glossary);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $glossaryRepository->add($glossary, true);

            return $this->redirectToRoute(self::PREFIX . 'index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('glossary/new.html.twig', [
            'glossary' => $glossary,
            'form' => $form,
            'PREFIX' => self::PREFIX,
        ]);
    }

    /**
     * @Route("/{id}", name="show", methods={"GET"})
     */
    public function show(Glossary $glossary): Response
    {
        return $this->render('glossary/show.html.twig', [
            'glossary' => $glossary,
            'PREFIX' => self::PREFIX,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Glossary $glossary, GlossaryRepository $glossaryRepository): Response
    {
        $form = $this->createForm(GlossaryType::class, $glossary);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $glossaryRepository->add($glossary, true);

            return $this->redirectToRoute(self::PREFIX . 'index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('glossary/edit.html.twig', [
            'glossary' => $glossary,
            'form' => $form,
            'PREFIX' => self::PREFIX,
        ]);
    }

    /**
     * @Route("/{id}", name="delete", methods={"POST"})
     */
    public function delete(Request $request, Glossary $glossary, GlossaryRepository $glossaryRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$glossary->getId(), $request->request->get('_token'))) {
            $glossaryRepository->remove($glossary, true);
        }

        return $this->redirectToRoute(self::PREFIX . 'index', [], Response::HTTP_SEE_OTHER);
    }
}
