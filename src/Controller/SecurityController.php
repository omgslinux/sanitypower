<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Repository\CompanyRepository;
use App\Repository\SubsidiaryRepository;
use App\Repository\ShareholderRepository;

/**
 * Controller used to manage the application security.
 * See https://symfony.com/doc/current/cookbook/security/form_login_setup.html.
 *
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class SecurityController extends AbstractController
{
    #[Route(path: '/', name: 'homepage')]
    public function homepage(CompanyRepository $CR, ShareholderRepository $HR)
    {
        $totalCompanies = $CR->getInlistCount();
        $totalHolders = $HR->getHolderCount();
        $totalSubsidiaries = $HR->getSubsidiaryCount();

        //return $this->redirectToRoute('company_index');
        return $this->render('security/homepage.html.twig', [
            'companiesNumber' => $totalCompanies,
            'holders' => $totalHolders,
            'subsidiaries' => $totalSubsidiaries
        ]);
    }

    #[Route(path: '/login', name: 'app_login')]
    public function loginAction(AuthenticationUtils $helper)
    {
        return $this->render('security/login.html.twig', [
            // last username entered by the user (if any)
            'last_username' => $helper->getLastUsername(),
            // last authentication error (if any)
            'error' => $helper->getLastAuthenticationError(),
        ]);
    }

    /**
     * This is the route the user can use to logout.
     *
     * But, this will never be executed. Symfony will intercept this first
     * and handle the logout automatically. See logout in app/config/security.yml
     */
    #[Route(path: '/logout', name: 'app_logout')]
    public function logoutAction()
    {
        throw new \Exception('This should never be reached!');
    }
}
