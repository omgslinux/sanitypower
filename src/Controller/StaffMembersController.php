<?php

namespace App\Controller;

use App\Entity\StaffMembers;
use App\Form\StaffMembersType;
use App\Repository\StaffMembersRepository as REPO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/members', name: 'staff_members_')]
class StaffMembersController extends AbstractController
{
    const PREFIX = 'staff_members_';
    const TDIR = 'members';

    private $repo;
    public function __construct(REPO $repo)
    {
        $this->repo = $repo;
    }

    #[Route(path: '/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('members/index.html.twig', [
            'members' => $this->repo->findAll(),
            'prefix' => self::PREFIX,
        ]);
    }

    #[Route(path: '/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $member = new StaffMembers();
        $form = $this->createForm(StaffMembersType::class, $member);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->repo->add($entity, true);

            return $this->redirectToRoute(self::PREFIX . 'index');
        }

        return $this->render('members/new.html.twig', [
            'member' => $member,
            'form' => $form->createView(),
            'prefix' => self::PREFIX,
        ]);
    }

    #[Route(path: '/{id}', name: 'show', methods: ['GET'])]
    public function show(StaffMembers $member): Response
    {
        return $this->render('members/show.html.twig', [
            'member' => $member,
            'prefix' => self::PREFIX,
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, StaffMembers $member): Response
    {
        $form = $this->createForm(StaffMembersType::class, $member);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->repo->add($entity, true);

            return $this->redirectToRoute(self::PREFIX . 'index');
        }

        return $this->render('members/edit.html.twig', [
            'member' => $member,
            'form' => $form->createView(),
            'prefix' => self::PREFIX,
        ]);
    }

    #[Route(path: '/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Members $member): Response
    {
        if ($this->isCsrfTokenValid('delete'.$member->getId(), $request->request->get('_token'))) {
            $this->repo->remove($entity, true);
        }

        return $this->redirectToRoute(self::PREFIX . 'index');
    }
}
