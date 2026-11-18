<?php

namespace App\Controller;

use App\Dto\LogEntryData;
use App\Entity\ProductStock;
use App\Entity\StockMovement;
use App\Form\LogEntryFormType;
use App\Repository\StockMovementRepository;
use App\Repository\UserRepository;
use App\Security\Voter\StockMovementVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Activity logs. Staff and admins can read every entry and search / filter them;
 * who may EDIT an entry is decided by StockMovementVoter.
 */
#[Route('/logs')]
#[IsGranted('ROLE_STAFF')]
final class LogController extends AbstractController
{
    #[Route('', name: 'app_log_index', methods: ['GET'])]
    public function index(Request $request, StockMovementRepository $movements, UserRepository $users): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $type = (string) $request->query->get('type', '');
        $size = strtoupper((string) $request->query->get('size', ''));
        $by = (string) $request->query->get('by', '');
        $from = (string) $request->query->get('from', '');
        $to = (string) $request->query->get('to', '');

        $rows = $movements->search(
            $q,
            in_array($type, StockMovement::TYPES, true) ? $type : null,
            in_array($size, ProductStock::SIZES, true) ? $size : null,
            ctype_digit($by) && '' !== $by ? (int) $by : null,
            $this->parseDate($from),
            $this->parseDate($to),
        );

        return $this->render('logs/index.html.twig', [
            'movements' => $rows,
            'actors' => $users->findStaffAndAdmins(),
            'sizes' => ProductStock::SIZES,
            'filters' => ['q' => $q, 'type' => $type, 'size' => $size, 'by' => $by, 'from' => $from, 'to' => $to],
        ]);
    }

    #[Route('/{id<\d+>}/edit', name: 'app_log_edit', methods: ['GET', 'POST'])]
    #[IsGranted(StockMovementVoter::EDIT, 'movement', 'You are not allowed to edit this log entry.')]
    public function edit(StockMovement $movement, Request $request, EntityManagerInterface $em): Response
    {
        $data = LogEntryData::fromMovement($movement);

        $form = $this->createForm(LogEntryFormType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $note = trim((string) $data->note);
            $movement->revise((string) $data->size, (string) $data->type, (int) $data->quantity, '' === $note ? null : $note);
            $em->flush();

            $this->addFlash('success', 'Log entry updated.');

            return $this->redirectToRoute('app_log_index');
        }

        return $this->render('logs/edit.html.twig', ['form' => $form, 'movement' => $movement]);
    }

    /** Removes only the history row; the product's stock is not touched. */
    #[Route('/{id<\d+>}/delete', name: 'app_log_delete', methods: ['POST'])]
    #[IsGranted(StockMovementVoter::DELETE, 'movement', 'You are not allowed to delete this log entry.')]
    public function delete(StockMovement $movement, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $token = $request->request->get('csrf_token');
        if (!$this->isCsrfTokenValid('delete_log_'.$movement->getId(), is_string($token) ? $token : null)) {
            $this->addFlash('error', 'Your session expired. Please try again.');

            return $this->redirectToRoute('app_log_index');
        }

        $em->remove($movement);
        $em->flush();
        $this->addFlash('success', 'Log entry deleted.');

        return $this->redirectToRoute('app_log_index');
    }

    private function parseDate(string $value): ?\DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value ? $date : null;
    }
}
