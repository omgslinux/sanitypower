<?php

namespace App\Controller;

use App\Entity\BoardTitle;
use App\Form\BoardTitleType;
use App\Repository\BoardTitleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/board/title", name="board_title_")
 */
class BoardTitleController extends AbstractController
{
    /**
     * @Route("/", name="index", methods={"GET"})
     */
    public function index(BoardTitleRepository $boardTitleRepository): Response
    {
        return $this->render('board_title/index.html.twig', [
            'board_titles' => $boardTitleRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $boardTitle = new BoardTitle();
        $form = $this->createForm(BoardTitleType::class, $boardTitle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($boardTitle);
            $entityManager->flush();

            return $this->redirectToRoute('board_title_index');
        }

        return $this->render('board_title/new.html.twig', [
            'board_title' => $boardTitle,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="show", methods={"GET"})
     */
    public function show(BoardTitle $boardTitle): Response
    {
        return $this->render('board_title/show.html.twig', [
            'board_title' => $boardTitle,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="edit", methods={"GET","POST"})
     */
    public function edit(Request $request, BoardTitle $boardTitle): Response
    {
        $form = $this->createForm(BoardTitleType::class, $boardTitle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('board_title_index');
        }

        return $this->render('board_title/edit.html.twig', [
            'board_title' => $boardTitle,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="delete", methods={"POST"})
     */
    public function delete(Request $request, BoardTitle $boardTitle): Response
    {
        if ($this->isCsrfTokenValid('delete'.$boardTitle->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($boardTitle);
            $entityManager->flush();
        }

        return $this->redirectToRoute('board_title_index');
    }
}
