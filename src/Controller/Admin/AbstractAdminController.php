<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractAdminController extends AbstractController
{
    protected function csrfIsValid(Request $request): bool
    {
        $token = $request->request->get('csrf_token');

        return $this->isCsrfTokenValid('dashboard', is_string($token) ? $token : null);
    }

    protected function backToDashboard(string $type, string $message): RedirectResponse
    {
        $this->addFlash($type, $message);

        return $this->redirectToRoute('app_admin_dashboard');
    }

    protected function expired(): RedirectResponse
    {
        return $this->backToDashboard('error', 'Your session expired. Please try again.');
    }
}
