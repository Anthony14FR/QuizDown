<?php

namespace App\EventListener;

use App\Entity\Badge;
use App\Entity\Submission;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::postPersist, entity: Submission::class)]
class BadgeListener
{
    private const QUIZ_BADGES = [
        1 => 'Apprenti Quizzer',
        5 => 'Quiz Explorer',
        10 => 'Quiz Enthusiast',
        25 => 'Quiz Addict',
        50 => 'Quiz Master',
        100 => 'Quiz Champion',
        250 => 'Quiz Virtuoso',
        500 => 'Quiz Legend',
        1000 => 'Ultimate Quizzer',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function postPersist(Submission $submission): void
    {
        $user = $submission->getPlayer();
        $submissionCount = count($user->getSubmissions());

        foreach (self::QUIZ_BADGES as $threshold => $badgeName) {
            if ($submissionCount === $threshold) {
                $badge = $this->entityManager->getRepository(Badge::class)
                    ->findOneBy(['name' => $badgeName]);

                if ($badge && !$user->getBadges()->contains($badge)) {
                    $user->addBadge($badge);
                    $this->entityManager->flush();
                }
            }
        }
    }
}
