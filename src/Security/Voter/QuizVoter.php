<?php

namespace App\Security\Voter;

use App\Entity\Quiz;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class QuizVoter extends Voter
{
    public const EDIT = 'QUIZ_EDIT';
    public const VIEW = 'QUIZ_VIEW';
    public const DELETE = 'QUIZ_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])
            && $subject instanceof Quiz;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        $quiz = $subject;

        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());

        switch ($attribute) {
            case self::VIEW:
                return true;
            case self::EDIT:
            case self::DELETE:
                return $isAdmin || $user->getId() === $quiz->getCreator()->getId();
        }

        return false;
    }
}
