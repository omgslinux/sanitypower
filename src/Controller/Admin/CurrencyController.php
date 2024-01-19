<?php

namespace App\Controller\Admin;

use App\Entity\Currency;
use App\Form\MyCurrencyType;
use App\Repository\CurrencyRepository as REPO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/admin/currency', name: 'admin_currency_')]
class CurrencyController extends AbstractController
{
    const PREFIX = 'admin_currency_';
    const TDIR = 'currency';

    private $repo;
    public function __construct(REPO $repo)
    {
        $this->repo = $repo;
    }

    #[Route(path: '/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('currency/index.html.twig', [
            'currencies' => $this->repo->findAll(),
            'PREFIX' => self::PREFIX,
        ]);
    }

    #[Route(path: '/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $currency = new Currency();
        $form = $this->createForm(MyCurrencyType::class, $currency);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->repo->add($entity, true);

            return $this->redirectToRoute(self::PREFIX . 'index');
        }

        return $this->render('currency/new.html.twig', [
            'currency' => $currency,
            'form' => $form->createView(),
            'PREFIX' => self::PREFIX,
        ]);
    }

    #[Route(path: '/show/{id}', name: 'show', methods: ['GET'])]
    public function show(Currency $currency): Response
    {
        return $this->render('currency/show.html.twig', [
            'currency' => $currency,
            'PREFIX' => self::PREFIX,
        ]);
    }

    #[Route(path: '/edit/{id}', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Currency $currency): Response
    {
        $form = $this->createForm(MyCurrencyType::class, $currency);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->repo->add($entity, true);

            return $this->redirectToRoute(self::PREFIX . 'index');
        }

        return $this->render('currency/edit.html.twig', [
            'currency' => $currency,
            'form' => $form->createView(),
            'PREFIX' => self::PREFIX,
        ]);
    }

    #[Route(path: '/delete/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Currency $currency): Response
    {
        if ($this->isCsrfTokenValid('delete'.$currency->getId(), $request->request->get('_token'))) {
            $this->repo->remove($entity, true);
        }

        return $this->redirectToRoute(self::PREFIX . 'index');
    }
}
