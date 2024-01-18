<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserType;
use App\Form\ManagePasswordType;
use App\Repository\UserRepository as REPO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/admin/user', name: 'admin_user_')]
class UserController extends AbstractController
{
    const PREFIX = 'admin_user_';
    const TDIR = 'user';
    const VARS = [
        'modalSize' => 'modal-md',
        'PREFIX' => self::PREFIX,
        'included' => 'domain/_form',
        'tdir' => 'domain',
        'BASEDIR' => 'domain/',
        'modalId' => 'users',
    ];


    private $repo;
    public function __construct(REPO $repo)
    {
        $this->repo = $repo;
    }

    #[Route(path: '/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $this->repo->findAll(),
            'VARS' => self::VARS,
        ]);
    }

    #[Route(path: '/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);
//dump($form);
        if ($form->isSubmitted() && $form->isValid()) {
            //$this->repo->add($user, true);
            $this->repo->formSubmit($form);

            return $this->redirectToRoute(self::PREFIX . 'index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
            'VARS' => self::VARS,
        ]);
    }

    #[Route(path: '/{id}', name: 'show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
            'VARS' => self::VARS,
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user): Response
    {
        $form = $this->createForm(UserType::class, $user, ['new' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //$this->repo->add($user, true);
            $this->repo->formSubmit($form);

            return $this->redirectToRoute(self::PREFIX . 'index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
            'VARS' => self::VARS,
        ]);
    }

    #[Route(path: '/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $this->repo->remove($user, true);
        }

        return $this->redirectToRoute(self::PREFIX . 'index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Displays a form to change the password.
     */
    #[Route(path: '/pass', name: 'pass', methods: ['GET', 'POST'])]
    public function password(Request $request)
    {
        $user=$this->getUser();
        $form = $this->createForm(
            ManagePasswordType::class,
            $user,
            [
                'action' => $this->generateUrl(self::VARS['PREFIX'] . 'pass'),
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->repo->formSubmit($form);

            return $this->redirectToRoute('homepage');
        }

        return $this->render('user/_pass.html.twig', array(
            'user' => $user,
            'form' => $form->createView(),
            'VARS' => self::VARS,
        ));
    }
}
