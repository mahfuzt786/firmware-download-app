<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Controller for the admin login and logout pages.
 *
 * Handles rendering the custom login form and processing authentication errors.
 * The actual authentication logic is handled by Symfony's security component
 * (configured in config/packages/security.yaml).
 */
class LoginController extends AbstractController
{
    /**
     * Render the admin login page.
     *
     * If the user is already authenticated with ROLE_ADMIN, they are
     * redirected straight to the admin dashboard.
     */
    #[Route('/admin/login', name: 'admin_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Redirect to dashboard if already logged in
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_software_versions_index');
        }

        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // Last username entered by the user (pre-fill the form)
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('admin/login.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error,
        ]);
    }

    /**
     * Logout route — handled entirely by the Symfony security component.
     * This method will never actually execute; the firewall intercepts the request.
     */
    #[Route('/admin/logout', name: 'admin_logout')]
    public function logout(): void
    {
        // Intentionally left empty — intercepted by the security firewall
        throw new \LogicException('This should never be reached.');
    }
}
