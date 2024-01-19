<?php

namespace App\Controller\Admin;

use App\Entity\StaffTitle;
use App\Form\StaffTitleType;
use App\Repository\StaffTitleRepository as REPO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/admin/staff/title', name: 'admin_staff_title_')]
class StaffTitleController extends AbstractController
{
    const PREFIX = 'admin_staff_title_';
    const TDIR = 'staff_title';

    private $repo;
    public function __construct(REPO $repo)
    {
        $this->repo = $repo;
    }

    #[Route(path: '/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render(self::TDIR . '/index.html.twig', [
            'entities' => $this->repo->findAll(),
            'prefix' => self::PREFIX,
        ]);
    }

    #[Route(path: '/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $StaffTitle = new StaffTitle();
        $form = $this->createForm(StaffTitleType::class, $StaffTitle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->repo->add($entity, true);

            return $this->redirectToRoute(self::PREFIX . 'index');
        }

        return $this->render(self::TDIR . '/new.html.twig', [
            'entity' => $StaffTitle,
            'form' => $form->createView(),
            'prefix' => self::PREFIX,
            'tdir' => self::TDIR,
        ]);
    }

    #[Route(path: '/{id}', name: 'show', methods: ['GET'])]
    public function show(StaffTitle $StaffTitle): Response
    {
        return $this->render(self::TDIR . '/show.html.twig', [
            'entity' => $StaffTitle,
            'prefix' => self::PREFIX,
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, StaffTitle $StaffTitle): Response
    {
        $form = $this->createForm(StaffTitleType::class, $StaffTitle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->repo->add($entity, true);

            return $this->redirectToRoute(self::PREFIX . 'index');
        }

        return $this->render(self::TDIR . '/edit.html.twig', [
            'entity' => $StaffTitle,
            'form' => $form->createView(),
            'prefix' => self::PREFIX,
            'tdir' => self::TDIR,
        ]);
    }

    #[Route(path: '/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, StaffTitle $StaffTitle): Response
    {
        if ($this->isCsrfTokenValid('delete'.$StaffTitle->getId(), $request->request->get('_token'))) {
            $this->repo->remove($entity, true);
        }

        return $this->redirectToRoute(self::PREFIX . 'index');
    }
}
