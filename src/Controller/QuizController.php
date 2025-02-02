<?php

namespace App\Controller;

use App\Entity\Quiz;
use App\Entity\Submission;
use App\Entity\SubmissionAnswer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/quiz')]
class QuizController extends AbstractController
{
    #[Route('/{id}/play', name: 'app_quiz_play')]
    public function play(Quiz $quiz): Response
    {
        return $this->render('quiz/play.html.twig', [
            'quiz' => $quiz
        ]);
    }

    #[Route('/{id}/submit', name: 'app_quiz_submit', methods: ['POST'])]
    public function submit(Request $request, Quiz $quiz, EntityManagerInterface $em): Response
    {
        $answers = $request->request->all('answers');
        $score = 0;

        $submission = new Submission();
        $submission->setQuiz($quiz);
        $submission->setPlayer($this->getUser());
        $submission->setSubmittedAt(new \DateTimeImmutable());

        foreach ($quiz->getQuestions() as $question) {
            $submissionAnswer = new SubmissionAnswer();
            $submissionAnswer->setQuestion($question);
            $submissionAnswer->setSubmission($submission);

            $userAnswer = $answers[$question->getId()] ?? null;
            $submissionAnswer->setUserAnswer($userAnswer);

            $isCorrect = false;
            foreach ($question->getAnswers() as $answer) {
                if ($question->getType() === 'true_false' || $question->getType() === 'single_choice') {
                    if ($answer->getId() == $userAnswer && $answer->isCorrect()) {
                        $isCorrect = true;
                        $score += $quiz->getDefaultScore();
                    }
                } elseif ($question->getType() === 'multiple_choice') {
                    $userAnswers = is_array($userAnswer) ? $userAnswer : [];
                    $correctAnswers = $question->getAnswers()->filter(fn($a) => $a->isCorrect())->map(fn($a) => $a->getId())->toArray();
                    if ($userAnswers == $correctAnswers) {
                        $isCorrect = true;
                        $score += $quiz->getDefaultScore();
                    }
                }
            }

            $submissionAnswer->setIsCorrect($isCorrect);
            $em->persist($submissionAnswer);
        }

        $submission->setScore($score);
        $em->persist($submission);
        $em->flush();

        return $this->redirectToRoute('app_quiz_result', ['id' => $submission->getId()]);
    }

    #[Route('/result/{id}', name: 'app_quiz_result')]
    public function result(Submission $submission): Response
    {
        return $this->render('quiz/result.html.twig', [
            'submission' => $submission
        ]);
    }
}
