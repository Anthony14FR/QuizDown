<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class ThemeApiController extends AbstractController
{
    #[Route('/theme', name: 'api_theme_update', methods: ['POST'])]
    public function updateTheme(Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->isCsrfTokenValid('theme', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['error' => 'Token invalide'], 400);
        }

        $theme = $request->request->get('theme');

        /** @var User */
        $user = $this->getUser();

        if (!in_array($theme, ['emerald', 'halloween'])) {
            return new JsonResponse(['error' => 'Theme invalide'], 400);
        }

        $user->setTheme($theme);
        $em->flush();

        return new JsonResponse(['theme' => $theme]);
    }
}
