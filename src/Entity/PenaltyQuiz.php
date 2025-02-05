<?php

namespace App\Entity;

use App\Repository\PenaltyQuizRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PenaltyQuizRepository::class)]
class PenaltyQuiz extends Quiz
{
    #[ORM\Column]
    private ?int $penaltyPoints = null;

    #[ORM\Column(nullable: true)]
    private ?int $timeLimit = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPenaltyPoints(): ?int
    {
        return $this->penaltyPoints;
    }

    public function setPenaltyPoints(int $penaltyPoints): static
    {
        $this->penaltyPoints = $penaltyPoints;

        return $this;
    }

    public function getTimeLimit(): ?int
    {
        return $this->timeLimit;
    }

    public function setTimeLimit(?int $timeLimit): static
    {
        $this->timeLimit = $timeLimit;

        return $this;
    }
}
