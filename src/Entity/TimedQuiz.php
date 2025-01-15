<?php

namespace App\Entity;

use App\Repository\TimedQuizRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TimedQuizRepository::class)]
class TimedQuiz extends Quiz
{
    #[ORM\Column]
    private ?int $timeLimit = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTimeLimit(): ?int
    {
        return $this->timeLimit;
    }

    public function setTimeLimit(int $timeLimit): static
    {
        $this->timeLimit = $timeLimit;

        return $this;
    }
}
