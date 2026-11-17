<?php

namespace App\Controller\Admin;

use App\Dto\UserData;
use App\Entity\User;
use App\Exception\StoreException;
use App\Form\UserFormType;
use App\Repository\UserRepository;
use App\Service\UserManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
final class UserController extends AbstractAdminController
{
    #[Route('', name: 'app_admin_user_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $users): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $role = (string) $request->query->get('role', '');

        return $this->render('admin/users/index.html.twig', [
            'users' => $users->search($q, $role ?: null),
            'filters' => ['q' => $q, 'role' => $role],
        ]);
    }

    #[Route('/new', name: 'app_admin_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, UserManager $manager): Response
    {
        $data = new UserData();
        $data->passwordRequired = true;

        $form = $this->createForm(UserFormType::class, $data, ['is_new' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $user = $manager->create($data);
                $this->addFlash('success', sprintf('Account "%s" created as %s.', $user->getUsername(), $user->getRole()));

                return $this->redirectToRoute('app_admin_user_index');
            } catch (StoreException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('admin/users/form.html.twig', ['form' => $form, 'target' => null, 'title' => 'Create Account']);
    }

    #[Route('/{id<\d+>}/edit', name: 'app_admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(User $target, Request $request, UserManager $manager, #[CurrentUser] User $me): Response
    {
        $data = UserData::fromUser($target);
        $isSelf = $target->getId() === $me->getId();

        $form = $this->createForm(UserFormType::class, $data, ['lock_self' => $isSelf]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $manager->update($target, $data, $me);
                $this->addFlash('success', sprintf('Account "%s" updated.', $target->getUsername()));

                return $this->redirectToRoute('app_admin_user_index');
            } catch (StoreException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('admin/users/form.html.twig', ['form' => $form, 'target' => $target, 'title' => 'Edit Account']);
    }

    #[Route('/{id<\d+>}/toggle', name: 'app_admin_user_toggle', methods: ['POST'])]
    public function toggle(User $target, Request $request, UserManager $manager, #[CurrentUser] User $me): RedirectResponse
    {
        if (!$this->csrfIsValid($request)) {
            return $this->redirectWith('error', 'Your session expired. Please try again.');
        }

        try {
            $active = $manager->toggleActive($target, $me);
        } catch (StoreException $e) {
            return $this->redirectWith('error', $e->getMessage());
        }

        return $this->redirectWith('success', sprintf('Account "%s" is now %s.', $target->getUsername(), $active ? 'active' : 'disabled'));
    }

    #[Route('/{id<\d+>}/delete', name: 'app_admin_user_delete', methods: ['POST'])]
    public function delete(User $target, Request $request, UserManager $manager, #[CurrentUser] User $me): RedirectResponse
    {
        if (!$this->csrfIsValid($request)) {
            return $this->redirectWith('error', 'Your session expired. Please try again.');
        }

        try {
            $name = $target->getUsername();
            $manager->delete($target, $me);
        } catch (StoreException $e) {
            return $this->redirectWith('error', $e->getMessage());
        }

        return $this->redirectWith('success', sprintf('Account "%s" was deleted.', $name));
    }

    private function redirectWith(string $type, string $message): RedirectResponse
    {
        $this->addFlash($type, $message);

        return $this->redirectToRoute('app_admin_user_index');
    }
}
