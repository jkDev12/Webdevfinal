<?php

namespace App\Controller;

use App\Dto\ChangePasswordData;
use App\Entity\User;
use App\Form\ChangePasswordFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class AccountController extends AbstractController
{
    #[Route('/account', name: 'app_account', methods: ['GET'])]
    public function profile(#[CurrentUser] User $user): Response
    {
        return $this->render('account/profile.html.twig', ['user' => $user]);
    }

    #[Route('/account/password', name: 'app_account_password', methods: ['GET', 'POST'])]
    public function changePassword(
        Request $request,
        #[CurrentUser] User $user,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
    ): Response {
        $form = $this->createForm(ChangePasswordFormType::class, new ChangePasswordData());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();
            $user->setPassword($hasher->hashPassword($user, (string) $data->newPassword));
            $em->flush();

            $this->addFlash('success', 'Your password was changed.');

            return $this->redirectToRoute('app_account');
        }

        return $this->render('account/password.html.twig', ['form' => $form]);
    }
}
