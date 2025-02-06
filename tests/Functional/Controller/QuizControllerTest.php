<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Quiz;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class QuizControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    public function testQuizList(): void
    {
        $testUser = $this->createUser();

        $quiz = new Quiz();
        $quiz->setTitle('Test Quiz');
        $quiz->setDescription('Test Description');
        $quiz->setDefaultScore(10);
        $quiz->setCreator($testUser);

        $this->entityManager->persist($quiz);
        $this->entityManager->flush();

        $this->client->loginUser($testUser);

        $crawler = $this->client->request('GET', '/quiz');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Nos Quiz');
        $this->assertSelectorExists('.card');
        $this->assertSelectorTextContains('.card', 'Test Quiz');
    }

    public function testQuizCreation(): void
    {
        $testUser = $this->createUser();
        $this->client->loginUser($testUser);

        $crawler = $this->client->request('GET', '/quiz/new');
        $this->assertResponseIsSuccessful();

        $token = $crawler->filter('input[name="quiz[_token]"]')->attr('value');

        $formData = [
            'quiz' => [
                'title' => 'Test Quiz with Questions',
                'description' => 'Description of test quiz',
                'defaultScore' => 10,
                'type' => 'base',
                '_token' => $token,
                'questions' => [
                    0 => [
                        'type' => 'single_choice',
                        'content' => 'What is 2+2?',
                        'answers' => [
                            0 => [
                                'content' => '4',
                                'isCorrect' => '1',
                            ],
                            1 => [
                                'content' => '5',
                                'isCorrect' => '0',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->client->request(
            'POST',
            '/quiz/new',
            $formData
        );

        $response = $this->client->getResponse();

        $this->assertSame(
            Response::HTTP_FOUND,
            $response->getStatusCode(),
            $response->getContent()
        );

        $quiz = $this->entityManager->getRepository(Quiz::class)
            ->findOneBy(['title' => 'Test Quiz with Questions']);

        $this->assertNotNull($quiz);
        $this->assertEquals('Description of test quiz', $quiz->getDescription());
        $this->assertCount(1, $quiz->getQuestions());
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setUsername('testuser');
        $user->setPassword('$2y$13$hK2qnQM3OGfqtPG0ICIq4.kJ.IxW6WZNuC0N0uGD1g9HV2h/SLS7e');
        $user->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
        $this->entityManager = null;
    }
}
