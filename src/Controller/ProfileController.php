<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/profile')]
class ProfileController extends AbstractController
{
    #[Route('', name: 'app_profile')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $submissions = $user->getSubmissions();

        $averageScore = 0;
        if ($submissions->count() > 0) {
            $totalScore = 0;
            $validSubmissions = 0;
            foreach ($submissions as $submission) {
                $maxScore = $submission->getQuiz()->getDefaultScore() * $submission->getQuiz()->getQuestions()->count();
                if ($maxScore > 0) {
                    $totalScore += ($submission->getScore() / $maxScore) * 20;
                    ++$validSubmissions;
                }
            }
            $averageScore = $validSubmissions > 0 ? round($totalScore / $validSubmissions, 1) : 0;
        }

        $stats = [
            'total_quizzes' => $user->getQuizzes()->count(),
            'total_submissions' => $submissions->count(),
            'total_badges' => $user->getBadges()->count(),
            'total_comments' => $user->getComments()->count(),
            'average_score' => $averageScore,
        ];

        return $this->render('profile/index.html.twig', [
            'stats' => $stats,
            'recent_submissions' => $submissions->slice(0, 5),
            'recent_created_quizzes' => $user->getQuizzes()->slice(-5),
        ]);
    }

    #[Route('/change-password', name: 'app_profile_change_password', methods: ['POST'])]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $currentPassword = $request->request->get('current_password');
        $newPassword = $request->request->get('new_password');
        $repeatPassword = $request->request->get('repeat_password');

        if (!$currentPassword || !$passwordHasher->isPasswordValid($user, $currentPassword)) {
            $this->addFlash('error', 'Le mot de passe actuel est incorrect.');

            return $this->redirectToRoute('app_profile');
        }

        if (strlen($newPassword) < 8) {
            $this->addFlash('error', 'Le nouveau mot de passe doit contenir au moins 8 caractères.');

            return $this->redirectToRoute('app_profile');
        }

        if (!preg_match('/[0-9\W]/', $newPassword)) {
            $this->addFlash('error', 'Le nouveau mot de passe doit contenir au moins un chiffre ou un caractère spécial.');

            return $this->redirectToRoute('app_profile');
        }

        if ($newPassword !== $repeatPassword) {
            $this->addFlash('error', 'Les nouveaux mots de passe ne correspondent pas.');

            return $this->redirectToRoute('app_profile');
        }

        if ($passwordHasher->isPasswordValid($user, $newPassword)) {
            $this->addFlash('error', 'Le nouveau mot de passe doit être différent de l\'ancien.');

            return $this->redirectToRoute('app_profile');
        }

        $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
        $entityManager->flush();

        $this->addFlash('success', 'Mot de passe modifié avec succès.');

        return $this->redirectToRoute('app_profile');
    }
}
