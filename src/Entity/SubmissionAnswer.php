<?php

namespace App\Entity;

use App\Repository\SubmissionAnswerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubmissionAnswerRepository::class)]
class SubmissionAnswer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $userAnswer = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isCorrect = null;

    #[ORM\ManyToOne(inversedBy: 'submissionAnswers')]
    private ?Submission $submission = null;

    #[ORM\ManyToOne(inversedBy: 'submissionAnswers')]
    private ?Question $question = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserAnswer(): ?string
    {
        return $this->userAnswer;
    }

    public function setUserAnswer(?string $userAnswer): static
    {
        $this->userAnswer = $userAnswer;

        return $this;
    }

    public function isCorrect(): ?bool
    {
        return $this->isCorrect;
    }

    public function setIsCorrect(?bool $isCorrect): static
    {
        $this->isCorrect = $isCorrect;

        return $this;
    }

    public function getSubmission(): ?Submission
    {
        return $this->submission;
    }

    public function setSubmission(?Submission $submission): static
    {
        $this->submission = $submission;

        return $this;
    }

    public function getQuestion(): ?Question
    {
        return $this->question;
    }

    public function setQuestion(?Question $question): static
    {
        $this->question = $question;

        return $this;
    }
}
