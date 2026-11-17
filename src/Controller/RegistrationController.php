<?php

namespace App\Controller;

use App\Dto\RegistrationInput;
use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserRepository $users,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
        Security $security,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_storefront');
        }

        $input = new RegistrationInput();
        $form = $this->createForm(RegistrationFormType::class, $input, ['attr' => ['novalidate' => 'novalidate']]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $users->findOneByUsernameOrEmail((string) $input->username, (string) $input->email)) {
            $form->addError(new FormError('That username or email is already registered.'));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $isStaff = User::ROLE_STAFF === $input->account_type;

            $user = (new User())
                ->setUsername(trim((string) $input->username))
                ->setEmail(trim((string) $input->email))
                ->setRole($isStaff ? User::ROLE_STAFF : User::ROLE_CUSTOMER)
                ->setActive(!$isStaff);
            $user->setPassword($hasher->hashPassword($user, (string) $input->password));

            $em->persist($user);
            $em->flush();

            if ($isStaff) {
                $this->addFlash('success', 'Staff account created. An administrator must approve it before you can log in.');

                return $this->redirectToRoute('app_login');
            }

            $this->addFlash('success', 'Welcome, '.$user->getUsername().'! Your account is ready.');
            $security->login($user, 'form_login', 'main');

            return $this->redirectToRoute('app_storefront');
        }

        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        return $this->render('registration/register.html.twig', [
            'form' => $form,
            'error' => $errors ? implode(' ', $errors) : null,
        ]);
    }
}
