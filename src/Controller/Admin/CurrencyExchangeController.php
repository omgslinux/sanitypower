<?php

namespace App\Controller\Admin;

use App\Entity\CurrencyExchange;
use App\Form\CurrencyExchangeType;
use App\Repository\CurrencyExchangeRepository as REPO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/currencyexchange", name="admin_currency_exchange_")
 */
class CurrencyExchangeController extends AbstractController
{
    const PREFIX = 'admin_currency_exchange_';
    const TDIR = 'currency_exchange';

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
        return $this->render('currency_exchange/index.html.twig', [
            'currency_exchanges' => $this->repo->findAll(),
            'PREFIX' => self::PREFIX,
        ]);
    }

    /**
     * @Route("/new", name="new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $currencyExchange = new CurrencyExchange();
        $form = $this->createForm(CurrencyExchangeType::class, $currencyExchange);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->repo->add($entity, true);

            return $this->redirectToRoute(self::PREFIX . 'index');
        }

        return $this->render('currency_exchange/new.html.twig', [
            'currency_exchange' => $currencyExchange,
            'form' => $form->createView(),
            'PREFIX' => self::PREFIX,
        ]);
    }

    /**
     * @Route("/{id}", name="show", methods={"GET"})
     */
    public function show(CurrencyExchange $currencyExchange): Response
    {
        return $this->render('currency_exchange/show.html.twig', [
            'currency_exchange' => $currencyExchange,
            'PREFIX' => self::PREFIX,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="edit", methods={"GET","POST"})
     */
    public function edit(Request $request, CurrencyExchange $currencyExchange): Response
    {
        $form = $this->createForm(CurrencyExchangeType::class, $currencyExchange);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->repo->add($entity, true);

            return $this->redirectToRoute(self::PREFIX . 'index');
        }

        return $this->render('currency_exchange/edit.html.twig', [
            'currency_exchange' => $currencyExchange,
            'form' => $form->createView(),
            'PREFIX' => self::PREFIX,
        ]);
    }

    /**
     * @Route("/{id}", name="delete", methods={"POST"})
     */
    public function delete(Request $request, CurrencyExchange $currencyExchange): Response
    {
        if ($this->isCsrfTokenValid('delete'.$currencyExchange->getId(), $request->request->get('_token'))) {
            $this->repo->remove($entity, true);

            return $this->redirectToRoute(self::PREFIX . 'index');
        }
    }
}
