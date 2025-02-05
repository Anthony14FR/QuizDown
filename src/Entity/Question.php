<?php

namespace App\Entity;

use App\Repository\QuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: QuestionRepository::class)]
class Question
{
    public const TYPE_TRUE_FALSE = 'true_false';
    public const TYPE_SINGLE_CHOICE = 'single_choice';
    public const TYPE_MULTIPLE_CHOICE = 'multiple_choice';
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['quiz:read:full'])]
    private ?int $id = 0;

    #[ORM\Column(length: 255)]
    #[Groups(['quiz:read:full'])]
    private ?string $content = null;

    #[ORM\Column(length: 255)]
    #[Groups(['quiz:read:full'])]
    private ?string $type = null;

    /**
     * @var Collection<int, SubmissionAnswer>
     */
    #[ORM\OneToMany(targetEntity: SubmissionAnswer::class, mappedBy: 'question', cascade: ['remove'])]
    private Collection $submissionAnswers;

    /**
     * @var Collection<int, Answer>
     */
    #[ORM\OneToMany(targetEntity: Answer::class, mappedBy: 'question', cascade: ['persist', 'remove'])]
    #[Groups(['quiz:read:full'])]
    private Collection $answers;

    #[ORM\ManyToOne(inversedBy: 'questions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Quiz $quiz = null;

    public function __construct()
    {
        $this->submissionAnswers = new ArrayCollection();
        $this->answers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type ?? self::TYPE_SINGLE_CHOICE;

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
            $submissionAnswer->setQuestion($this);
        }

        return $this;
    }

    public function removeSubmissionAnswer(SubmissionAnswer $submissionAnswer): static
    {
        if ($this->submissionAnswers->removeElement($submissionAnswer)) {
            // set the owning side to null (unless already changed)
            if ($submissionAnswer->getQuestion() === $this) {
                $submissionAnswer->setQuestion(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Answer>
     */
    public function getAnswers(): Collection
    {
        return $this->answers;
    }

    public function addAnswer(Answer $answer): static
    {
        if (!$this->answers->contains($answer)) {
            $this->answers->add($answer);
            $answer->setQuestion($this);
        }

        return $this;
    }

    public function removeAnswer(Answer $answer): static
    {
        if ($this->answers->removeElement($answer)) {
            // set the owning side to null (unless already changed)
            if ($answer->getQuestion() === $this) {
                $answer->setQuestion(null);
            }
        }

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

    public function isMultipleChoice(): bool
    {
        return self::TYPE_MULTIPLE_CHOICE === $this->type;
    }
}
