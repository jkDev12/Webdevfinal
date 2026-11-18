<?php

namespace App\Controller\Admin;

use App\Dto\NewProductInput;
use App\Entity\User;
use App\Exception\StoreException;
use App\Service\ProductCreator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** "Add New Product" form. */
#[IsGranted('ROLE_ADMIN')]
final class ProductController extends AbstractAdminController
{
    #[Route('/admin/product/add', name: 'app_admin_product_add', methods: ['POST'])]
    public function add(Request $request, ValidatorInterface $validator, ProductCreator $creator, #[CurrentUser] User $me): RedirectResponse
    {
        if (!$this->csrfIsValid($request)) {
            return $this->expired();
        }

        $input = NewProductInput::fromRequest($request);

        $violations = $validator->validate($input);
        if (count($violations) > 0) {
            return $this->backToDashboard('error', (string) $violations[0]->getMessage());
        }

        try {
            $product = $creator->create($input, $me);
        } catch (StoreException $e) {
            return $this->backToDashboard('error', $e->getMessage());
        }

        return $this->backToDashboard('success', sprintf('Added new product "%s".', $product->getName()));
    }
}
