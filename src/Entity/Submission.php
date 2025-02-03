<?php

namespace App\Entity;

use App\Repository\SubmissionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubmissionRepository::class)]
class Submission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id = 0;

    #[ORM\Column]
    private ?\DateTimeImmutable $submittedAt = null;

    #[ORM\Column]
    private ?int $score = null;

    /**
     * @var Collection<int, SubmissionAnswer>
     */
    #[ORM\OneToMany(targetEntity: SubmissionAnswer::class, mappedBy: 'submission')]
    private Collection $submissionAnswers;

    #[ORM\ManyToOne(inversedBy: 'submissions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $player = null;

    #[ORM\ManyToOne(inversedBy: 'submissions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Quiz $quiz = null;

    public function __construct()
    {
        $this->submissionAnswers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubmittedAt(): ?\DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function setSubmittedAt(\DateTimeImmutable $submittedAt): static
    {
        $this->submittedAt = $submittedAt;

        return $this;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(int $score): static
    {
        $this->score = $score;

        return $this;
    }

    /**
     * @return Collection<int, SubmissionAnswer>
     */
    public function getSubmissionAnswers(): Collection
    {
        return $this->submissionAnswers;
    }

    public function addSubmissionAnswer(SubmissionAnswer $submissionAnswer): static
    {
        if (!$this->submissionAnswers->contains($submissionAnswer)) {
            $this->submissionAnswers->add($submissionAnswer);
            $submissionAnswer->setSubmission($this);
        }

        return $this;
    }

    public function removeSubmissionAnswer(SubmissionAnswer $submissionAnswer): static
    {
        if ($this->submissionAnswers->removeElement($submissionAnswer)) {
            // set the owning side to null (unless already changed)
            if ($submissionAnswer->getSubmission() === $this) {
                $submissionAnswer->setSubmission(null);
            }
        }

        return $this;
    }

    public function getPlayer(): ?User
    {
        return $this->player;
    }

    public function setPlayer(?User $player): static
    {
        $this->player = $player;

        return $this;
    }

    public function getQuiz(): ?Quiz
    {
        return $this->quiz;
    }

    public function setQuiz(?Quiz $quiz): static
    {
        $this->quiz = $quiz;

        return $this;
    }
}
