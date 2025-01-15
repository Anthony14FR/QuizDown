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
    private ?int $timePenalty = null;

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

    public function getTimePenalty(): ?int
    {
        return $this->timePenalty;
    }

    public function setTimePenalty(?int $timePenalty): static
    {
        $this->timePenalty = $timePenalty;

        return $this;
    }
}
