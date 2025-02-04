<?php

namespace App\Controller;

use App\Entity\Badge;
use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\PenaltyQuiz;
use App\Entity\Quiz;
use App\Entity\Tag;
use App\Entity\TimedQuiz;
use App\Entity\User;
use App\Form\BadgeType;
use App\Form\CategoryType;
use App\Form\CommentType;
use App\Form\QuizType;
use App\Form\TagType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_dashboard')]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig');
    }

    #[Route('/quiz/new', name: 'app_admin_quiz_new')]
    public function newQuiz(Request $request, EntityManagerInterface $em): Response
    {
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

            $quiz->setCreator($this->getUser() instanceof User ? $this->getUser() : null);
            $em->persist($quiz);
            $em->flush( );

            $this->addFlash('success', 'Quiz créé avec succès');

            return $this->redirectToRoute('app_admin_quiz_list');
        }

        return $this->render('admin/quiz/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/quiz', name: 'app_admin_quiz_list')]
    public function quizList(EntityManagerInterface $em): Response
    {
        $quizzes = $em->getRepository(Quiz::class)->findAll();

        return $this->render('admin/quiz/list.html.twig', [
            'quizzes' => $quizzes,
        ]);
    }

    #[Route('/quiz/{id}/edit', name: 'app_admin_quiz_edit')]
    public function editQuiz(Quiz $quiz, Request $request, EntityManagerInterface $em): Response
    {
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
                    $em->remove($question);
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
                    $em->remove($answer);
                }
            }

            if ($quiz instanceof TimedQuiz) {
                $timeLimit = $form->get('timeLimit')->getData();
                if (null !== $timeLimit) {
                    $quiz->setTimeLimit($timeLimit);
                }
            }

            if ($quiz instanceof PenaltyQuiz) {
                $penaltyPoints = $form->get('penaltyPoints')->getData();
                $timePenalty = $form->get('timePenalty')->getData();

                if (null !== $penaltyPoints) {
                    $quiz->setPenaltyPoints($penaltyPoints);
                }
                if (null !== $timePenalty) {
                    $quiz->setTimePenalty($timePenalty);
                }
            }

            $em->flush();
            $this->addFlash('success', 'Quiz modifié avec succès');

            return $this->redirectToRoute('app_admin_quiz_list');
        }

        return $this->render('admin/quiz/edit.html.twig', [
            'form' => $form,
            'quiz' => $quiz,
        ]);
    }

    #[Route('/quiz/{id}/delete', name: 'app_admin_quiz_delete', methods: ['POST'])]
    public function deleteQuiz(Quiz $quiz, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$quiz->getId(), $request->request->get('_token'))) {
            $em->remove($quiz);
            $em->flush();
            $this->addFlash('success', 'Quiz supprimé avec succès');
        }

        return $this->redirectToRoute('app_admin_quiz_list');
    }

    #[Route('/category', name: 'app_admin_category_list')]
    public function categoryList(EntityManagerInterface $em): Response
    {
        $categories = $em->getRepository(Category::class)->findAll();

        return $this->render('admin/category/list.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/category/new', name: 'app_admin_category_new')]
    public function newCategory(Request $request, EntityManagerInterface $em): Response
    {
        $category = new Category();

        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($category);
            $em->flush();

            $this->addFlash('success', 'Catégorie créée avec succès');

            return $this->redirectToRoute('app_admin_category_list');
        }

        return $this->render('admin/category/form.html.twig', [
            'form' => $form,
            'is_edit' => false,
        ]);
    }

    #[Route('/category/{id}/edit', name: 'app_admin_category_edit')]
    public function editCategory(Category $category, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Catégorie modifiée avec succès');

            return $this->redirectToRoute('app_admin_category_list');
        }

        return $this->render('admin/category/form.html.twig', [
            'form' => $form,
            'is_edit' => true,
            'category' => $category,
        ]);
    }

    #[Route('/category/{id}/delete', name: 'app_admin_category_delete', methods: ['POST'])]
    public function deleteCategory(Category $category, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->request->get('_token'))) {
            $em->remove($category);
            $em->flush();
            $this->addFlash('success', 'Catégorie supprimée avec succès');
        }

        return $this->redirectToRoute('app_admin_category_list');
    }

    #[Route('/tag', name: 'app_admin_tag_list')]
    public function tagList(EntityManagerInterface $em): Response
    {
        $tags = $em->getRepository(Tag::class)->findAll();

        return $this->render('admin/tag/list.html.twig', [
            'tags' => $tags,
        ]);
    }

    #[Route('/tag/new', name: 'app_admin_tag_new')]
    public function newTag(Request $request, EntityManagerInterface $em): Response
    {
        $tag = new Tag();

        $form = $this->createForm(TagType::class, $tag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($tag);
            $em->flush();

            $this->addFlash('success', 'Tag créé avec succès');

            return $this->redirectToRoute('app_admin_tag_list');
        }

        return $this->render('admin/tag/form.html.twig', [
            'form' => $form,
            'is_edit' => false,
        ]);
    }

    #[Route('/tag/{id}/edit', name: 'app_admin_tag_edit')]
    public function editTag(Tag $tag, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(TagType::class, $tag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Tag modifié avec succès');

            return $this->redirectToRoute('app_admin_tag_list');
        }

        return $this->render('admin/tag/form.html.twig', [
            'form' => $form,
            'is_edit' => true,
            'tag' => $tag,
        ]);
    }

    #[Route('/tag/{id}/delete', name: 'app_admin_tag_delete', methods: ['POST'])]
    public function deleteTag(Tag $tag, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tag->getId(), $request->request->get('_token'))) {
            $em->remove($tag);
            $em->flush();
            $this->addFlash('success', 'Tag supprimé avec succès');
        }

        return $this->redirectToRoute('app_admin_tag_list');
    }

    #[Route('/badge', name: 'app_admin_badge_list')]
    public function badgeList(EntityManagerInterface $em): Response
    {
        $badges = $em->getRepository(Badge::class)->findAll();

        return $this->render('admin/badge/list.html.twig', [
            'badges' => $badges,
        ]);
    }

    #[Route('/badge/new', name: 'app_admin_badge_new')]
    public function newBadge(Request $request, EntityManagerInterface $em): Response
    {
        $badge = new Badge();

        $form = $this->createForm(BadgeType::class, $badge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($badge);
            $em->flush();

            $this->addFlash('success', 'Badge créé avec succès');

            return $this->redirectToRoute('app_admin_badge_list');
        }

        return $this->render('admin/badge/form.html.twig', [
            'form' => $form,
            'is_edit' => false,
        ]);
    }

    #[Route('/badge/{id}/edit', name: 'app_admin_badge_edit')]
    public function editBadge(Badge $badge, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(BadgeType::class, $badge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Badge modifié avec succès');

            return $this->redirectToRoute('app_admin_badge_list');
        }

        return $this->render('admin/badge/form.html.twig', [
            'form' => $form,
            'is_edit' => true,
            'badge' => $badge,
        ]);
    }

    #[Route('/badge/{id}/delete', name: 'app_admin_badge_delete', methods: ['POST'])]
    public function deleteBadge(Badge $badge, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$badge->getId(), $request->request->get('_token'))) {
            $em->remove($badge);
            $em->flush();
            $this->addFlash('success', 'Badge supprimé avec succès');
        }

        return $this->redirectToRoute('app_admin_badge_list');
    }

    #[Route('/comment', name: 'app_admin_comment_list')]
    public function commentList(EntityManagerInterface $em): Response
    {
        $comments = $em->getRepository(Comment::class)->findAll();

        return $this->render('admin/comment/list.html.twig', [
            'comments' => $comments,
        ]);
    }

    #[Route('/comment/new', name: 'app_admin_comment_new')]
    public function newComment(Request $request, EntityManagerInterface $em): Response
    {
        $comment = new Comment();

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setCreatedAt(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));
            $em->persist($comment);
            $em->flush();

            $this->addFlash('success', 'Commentaire créé avec succès');

            return $this->redirectToRoute('app_admin_comment_list');
        }

        return $this->render('admin/comment/form.html.twig', [
            'form' => $form,
            'is_edit' => false,
        ]);
    }

    #[Route('/comment/{id}/edit', name: 'app_admin_comment_edit')]
    public function editComment(Comment $comment, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Commentaire modifié avec succès');

            return $this->redirectToRoute('app_admin_comment_list');
        }

        return $this->render('admin/comment/form.html.twig', [
            'form' => $form,
            'is_edit' => true,
            'comment' => $comment,
        ]);
    }

    #[Route('/comment/{id}/delete', name: 'app_admin_comment_delete', methods: ['POST'])]
    public function deleteComment(Comment $comment, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->request->get('_token'))) {
            $em->remove($comment);
            $em->flush();
            $this->addFlash('success', 'Commentaire supprimé avec succès');
        }

        return $this->redirectToRoute('app_admin_comment_list');
    }
}
