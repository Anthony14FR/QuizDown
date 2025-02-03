<?php

namespace App\Controller;

use App\Entity\Quiz;
use App\Entity\TimedQuiz;
use App\Entity\PenaltyQuiz;
use App\Entity\Submission;
use App\Entity\SubmissionAnswer;
use App\Form\QuizType;
use App\Repository\QuizRepository;
use App\Security\Voter\QuizVoter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/quiz')]
class QuizController extends AbstractController
{
    public function __construct(
        private QuizRepository $quizRepository
    ) {}

    #[Route('', name: 'app_quiz_index', methods: ['GET'])]
    public function indexQuizzes(): Response
    {
        $quizzes = $this->quizRepository->findAll();
        
        return $this->render('quiz/index.html.twig', [
            'quizzes' => $quizzes
        ]);
    }

    #[Route('/new', name: 'app_quiz_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $quizData = $request->request->all('quiz');
        $quizType = $quizData['type'] ?? $request->query->get('type', 'base');

        switch ($quizType) {
            case 'timed':
                $quiz = new TimedQuiz();
                break;
            case 'penalty':
                $quiz = new PenaltyQuiz();
                break;
            default:
                $quiz = new Quiz();
        }

        $quiz->setCreator($this->getUser());

        $form = $this->createForm(QuizType::class, $quiz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($quiz instanceof TimedQuiz) {
                $quiz->setTimeLimit($quizData['timeLimit'] ?? null); 
            }
            if ($quiz instanceof PenaltyQuiz) {
                $quiz->setPenaltyPoints($quizData['penaltyPoints'] ?? null);
                $quiz->setTimePenalty($quizData['timePenalty'] ?? null);
            }

            $entityManager->persist($quiz);
            $entityManager->flush();

            $this->addFlash('success', 'Votre quiz a été créé avec succès.');
            return $this->redirectToRoute('app_quiz_index');
        }

        return $this->render('quiz/new.html.twig', [
            'form' => $form,
            'quiz' => $quiz
        ]);
    }

    #[Route('/{id}/edit', name: 'app_quiz_edit', methods: ['GET', 'POST'])]
    public function edit(Quiz $quiz, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(QuizVoter::EDIT, $quiz);

        $originalQuestions = new ArrayCollection();
        foreach ($quiz->getQuestions() as $question) {
            $originalQuestions->add($question);
        }
    
        $originalAnswers = new ArrayCollection();
        foreach ($quiz->getQuestions() as $question) {
            foreach ($question->getAnswers() as $answer) {
                $originalAnswers->add($answer);
            }
        }

        $form = $this->createForm(QuizType::class, $quiz, [
            'quiz_type' => $quiz->getType(),
            'time_limit' => $quiz instanceof TimedQuiz ? $quiz->getTimeLimit() : null,
            'penalty_points' => $quiz instanceof PenaltyQuiz ? $quiz->getPenaltyPoints() : null,
            'time_penalty' => $quiz instanceof PenaltyQuiz ? $quiz->getTimePenalty() : null,
        ]);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($originalQuestions as $question) {
                if (!$quiz->getQuestions()->contains($question)) {
                    $entityManager->remove($question);
                }
            }
    
            foreach ($originalAnswers as $answer) {
                $found = false;
                foreach ($quiz->getQuestions() as $question) {
                    if ($question->getAnswers()->contains($answer)) {
                        $found = true;
                        break;
                    }
                }
    
                if (!$found) {
                    $entityManager->remove($answer);
                }
            }

            if ($quiz instanceof TimedQuiz) {
                $timeLimit = $form->get('timeLimit')->getData();
                if ($timeLimit !== null) {
                    $quiz->setTimeLimit($timeLimit);
                }
            }
        
            if ($quiz instanceof PenaltyQuiz) {
                $penaltyPoints = $form->get('penaltyPoints')->getData();
                $timePenalty = $form->get('timePenalty')->getData();
        
                if ($penaltyPoints !== null) {
                    $quiz->setPenaltyPoints($penaltyPoints);
                }
                if ($timePenalty !== null) {
                    $quiz->setTimePenalty($timePenalty);
                }
            }
            
            $entityManager->flush();

            $this->addFlash('success', 'Le quiz a été modifié avec succès.');
            return $this->redirectToRoute('app_quiz_index');
        }

        return $this->render('quiz/edit.html.twig', [
            'form' => $form,
            'quiz' => $quiz
        ]);
    }

    #[Route('/{id}/delete', name: 'app_quiz_delete', methods: ['POST'])]
    public function delete(Quiz $quiz, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(QuizVoter::DELETE, $quiz);

        if ($this->isCsrfTokenValid('delete'.$quiz->getId(), $request->request->get('_token'))) {
            $entityManager->remove($quiz);
            $entityManager->flush();

            $this->addFlash('success', 'Le quiz a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_quiz_index');
    }

    #[Route('/{id}/play', name: 'app_quiz_play')]
    public function play(Quiz $quiz, SerializerInterface $serializer): Response
    {
        $quizData = json_decode($serializer->serialize($quiz, 'json', [
            'groups' => ['quiz:read:full'],
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }
        ]), true);
    
        return $this->render('quiz/play.html.twig', [
            'quiz' => $quizData
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
