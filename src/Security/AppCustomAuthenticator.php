<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class AppCustomAuthenticator extends AbstractAuthenticator
{
    private $userRepository;
    private RouterInterface $router;

    public function __construct(UserRepository $URepo, RouterInterface $router)
    {
        $this->userRepository = $URepo;
        $this->router = $router;
    }

    public function supports(Request $request): ?bool
    {
        $verify = "NONE";
        if ($request->server->get('SSL_CLIENT_VERIFY')) {
            $verify = $request->server->get('SSL_CLIENT_VERIFY');
        }
        return $verify=="SUCCESS";
    }

    public function authenticate(Request $request): Passport
    {
        $rawcert = $request->server->get('SSL_CLIENT_CERT');
        if (strpos($rawcert, '%20')) {
          $cert= openssl_x509_parse(urldecode($rawcert));
        } else {
          $cert= openssl_x509_parse($rawcert);
        }
        $email = $cert['subject']['emailAddress'];
        return new SelfValidatingPassport(new UserBadge($email));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // TODO: Implement onAuthenticationFailure() method.
        //dd('failure');
        return new RedirectResponse(
            $this->router->generate('homepage')
        );
    }

//    public function start(Request $request, AuthenticationException $authException = null): Response
//    {
//        /*
//         * If you would like this class to control what happens when an anonymous user accesses a
//         * protected page (e.g. redirect to /login), uncomment this method and make this class
//         * implement Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface.
//         *
//         * For more details, see https://symfony.com/doc/current/security/experimental_authenticators.html#configuring-the-authentication-entry-point
//         */
//    }
}
