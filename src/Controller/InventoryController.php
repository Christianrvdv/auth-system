<?php
namespace App\Controller;

use App\Entity\InventoryItem;
use App\Security\InventoryVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/inventory')]
class InventoryController extends AbstractController
{
    #[Route('', name: 'inventory_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $this->denyAccessUnlessGranted(InventoryVoter::VIEW);

        return $this->json(['message' => 'Acceso permitido a inventario']);
    }

    #[Route('', name: 'inventory_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(InventoryVoter::CREATE);

        return $this->json(['message' => 'Item creado'], 201);
    }

    #[Route('/{id}', name: 'inventory_edit', methods: ['PUT'])]
    public function edit(int $id): JsonResponse
    {
        $item = new InventoryItem();
        $this->denyAccessUnlessGranted(InventoryVoter::EDIT, $item);

        return $this->json(['message' => 'Item editado']);
    }

    #[Route('/{id}', name: 'inventory_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted(InventoryVoter::DELETE);

        return $this->json(['message' => 'Item eliminado']);
    }
}
