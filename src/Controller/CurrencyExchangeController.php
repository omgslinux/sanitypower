<?php

namespace App\Controller;

use App\Entity\CurrencyExchange;
use App\Form\CurrencyExchangeType;
use App\Repository\CurrencyExchangeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/currencyexchange", name="currency_exchange_")
 */
class CurrencyExchangeController extends AbstractController
{
    /**
     * @Route("/", name="index", methods={"GET"})
     */
    public function index(CurrencyExchangeRepository $currencyExchangeRepository): Response
    {
        return $this->render('currency_exchange/index.html.twig', [
            'currency_exchanges' => $currencyExchangeRepository->findAll(),
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
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($currencyExchange);
            $entityManager->flush();

            return $this->redirectToRoute('currency_exchange_index');
        }

        return $this->render('currency_exchange/new.html.twig', [
            'currency_exchange' => $currencyExchange,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="show", methods={"GET"})
     */
    public function show(CurrencyExchange $currencyExchange): Response
    {
        return $this->render('currency_exchange/show.html.twig', [
            'currency_exchange' => $currencyExchange,
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
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('currency_exchange_index');
        }

        return $this->render('currency_exchange/edit.html.twig', [
            'currency_exchange' => $currencyExchange,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="delete", methods={"POST"})
     */
    public function delete(Request $request, CurrencyExchange $currencyExchange): Response
    {
        if ($this->isCsrfTokenValid('delete'.$currencyExchange->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($currencyExchange);
            $entityManager->flush();
        }

        return $this->redirectToRoute('currency_exchange_index');
    }
}
