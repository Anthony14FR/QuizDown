<?php

namespace App\Controller;

use App\Entity\Answer;
use App\Entity\Question;
use App\Entity\Quiz;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/ai/quiz')]
#[IsGranted('ROLE_ADMIN')]
class AIQuizController extends AbstractController
{
    #[Route('/new', name: 'app_quiz_ai_new')]
    public function new(): Response
    {
        return $this->render('quiz/ai_new.html.twig');
    }

    #[Route('/generate', name: 'app_quiz_ai_generate', methods: ['POST'])]
    public function generate(Request $request, HttpClientInterface $client, EntityManagerInterface $em): Response
    {
        $theme = $request->request->get('theme');
        $instructions = $request->request->get('instructions');
        $questionCount = (int) $request->request->get('questionCount', 1);

        if (empty($theme)) {
            $this->addFlash('error', 'Le thème est obligatoire');

            return $this->redirectToRoute('app_quiz_ai_new');
        }

        if ($questionCount < 1 || $questionCount > 20) {
            $this->addFlash('error', 'Le nombre de questions doit être entre 1 et 20');

            return $this->redirectToRoute('app_quiz_ai_new');
        }

        $response = $client->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer '.$this->getParameter('app.openai_key'),
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Tu dois générer un quiz au format JSON avec la structure suivante: {title: string, description: string, questions: [{content: string, type: string (true_false, single_choice, multiple_choice), answers: [{content: string, isCorrect: boolean}]}]} Fait bien attention à ce que la question et ses réponses correspondent à sont type de questions. IMPORTANT LE FORMAT DE REPONSE DOIT ABSOLUMENT ETRE RESPECTE !',
                    ],
                    [
                        'role' => 'user',
                        'content' => sprintf('Génère un quiz de %d questions sur le thème: %s. Instructions additionnelles: %s', $questionCount, $theme, $instructions),
                    ],
                ],
            ],
        ]);
        try {
            $responseData = json_decode($response->getContent(), true)['choices'][0]['message'];
            $jsonString = trim(str_replace(['```json', '```'], '', $responseData['content']));
            $quizData = json_decode($jsonString, true);

            $quiz = new Quiz();
            $quiz->setTitle($quizData['title']);
            $quiz->setDescription($quizData['description']);
            $quiz->setDefaultScore(1);
            $quiz->setCreator($this->getUser() instanceof User ? $this->getUser() : null);

            foreach ($quizData['questions'] as $questionData) {
                $question = new Question();
                $question->setContent($questionData['content']);
                $question->setType($questionData['type']);

                foreach ($questionData['answers'] as $answerData) {
                    $answer = new Answer();
                    $answer->setContent($answerData['content']);
                    $answer->setIsCorrect($answerData['isCorrect']);
                    $question->addAnswer($answer);
                }
                $quiz->addQuestion($question);
            }

            $em->persist($quiz);
            $em->flush();

            return $this->redirectToRoute('app_quiz_edit', ['id' => $quiz->getId()]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la génération du quiz');

            return $this->redirectToRoute('app_quiz_ai_new');
        }
    }
}
