<?php

namespace App\Controller;

use App\Dto\ProductData;
use App\Entity\Product;
use App\Entity\User;
use App\Exception\StoreException;
use App\Form\ProductFormType;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use App\Security\Voter\ProductVoter;
use App\Service\ProductManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/products')]
#[IsGranted('ROLE_STAFF')]
final class ProductController extends AbstractController
{
    #[Route('', name: 'app_product_index', methods: ['GET'])]
    public function index(Request $request, ProductRepository $products, UserRepository $users, #[CurrentUser] User $me): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $creator = (string) $request->query->get('creator', '');
        $availability = (string) $request->query->get('availability', '');

        $creatorId = match (true) {
            'mine' === $creator => $me->getId(),
            ctype_digit($creator) && '' !== $creator => (int) $creator,
            default => null,
        };

        return $this->render('products/index.html.twig', [
            'products' => $products->search($q, $creatorId, $availability ?: null),
            'creators' => $users->findStaffAndAdmins(),
            'filters' => ['q' => $q, 'creator' => $creator, 'availability' => $availability],
        ]);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ProductManager $manager, #[CurrentUser] User $me): Response
    {
        $form = $this->createForm(ProductFormType::class, ProductData::blank(), ['is_new' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $product = $manager->create($form->getData(), $me);
                $this->addFlash('success', sprintf('Added new product "%s".', $product->getName()));

                return $this->redirectToRoute('app_product_index');
            } catch (StoreException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('products/form.html.twig', ['form' => $form, 'product' => null, 'title' => 'Add New Product']);
    }

    #[Route('/{id<\d+>}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    #[IsGranted(ProductVoter::EDIT, 'product', 'You can only edit records you created.')]
    public function edit(Product $product, Request $request, ProductManager $manager): Response
    {
        $form = $this->createForm(ProductFormType::class, ProductData::fromProduct($product));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $manager->update($product, $form->getData());
                $this->addFlash('success', sprintf('Updated "%s".', $product->getName()));

                return $this->redirectToRoute('app_product_index');
            } catch (StoreException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('products/form.html.twig', ['form' => $form, 'product' => $product, 'title' => 'Edit Product']);
    }

    #[Route('/{id<\d+>}/delete', name: 'app_product_delete', methods: ['POST'])]
    #[IsGranted(ProductVoter::DELETE, 'product', 'You can only delete records you created.')]
    public function delete(Product $product, Request $request, ProductManager $manager): RedirectResponse
    {
        $token = $request->request->get('csrf_token');
        if (!$this->isCsrfTokenValid('delete_product_'.$product->getId(), is_string($token) ? $token : null)) {
            $this->addFlash('error', 'Your session expired. Please try again.');

            return $this->redirectToRoute('app_product_index');
        }

        try {
            $name = $product->getName();
            $manager->delete($product);
            $this->addFlash('success', sprintf('Deleted "%s".', $name));
        } catch (StoreException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_product_index');
    }
}
