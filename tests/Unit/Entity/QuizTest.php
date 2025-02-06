<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Question;
use App\Entity\Quiz;
use PHPUnit\Framework\TestCase;

class QuizTest extends TestCase
{
    private Quiz $quiz;

    protected function setUp(): void
    {
        $this->quiz = new Quiz();
    }

    public function testQuizInitialization(): void
    {
        $this->assertEmpty($this->quiz->getQuestions());
        $this->assertEmpty($this->quiz->getComments());
        $this->assertEmpty($this->quiz->getSubmissions());
    }

    public function testAddQuestion(): void
    {
        $question = new Question();
        $question->setContent('Test Question');
        $question->setType('single_choice');

        $this->quiz->addQuestion($question);

        $this->assertCount(1, $this->quiz->getQuestions());
        $this->assertSame($question, $this->quiz->getQuestions()->first());
        $this->assertSame($this->quiz, $question->getQuiz());
    }

    public function testDefaultScoreValue(): void
    {
        $score = 10;
        $this->quiz->setDefaultScore($score);

        $this->assertEquals($score, $this->quiz->getDefaultScore());
    }
}
