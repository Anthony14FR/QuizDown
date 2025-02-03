<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Quiz;
use App\Entity\Tag;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuizType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('defaultScore', IntegerType::class, [
                'label' => 'Score par défaut',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de Quiz',
                'choices' => [
                    'Standard' => 'base',
                    'Chronométré' => 'timed',
                    'Avec pénalités' => 'penalty',
                ],
                'mapped' => false,
                'data' => $options['quiz_type'],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('categories', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'label' => 'Catégories',
                'attr' => ['class' => 'select select-multiple w-full'],
                'data' => $options['data']->getCategories(),
                'by_reference' => false,
            ])
            ->add('tags', EntityType::class, [
                'class' => Tag::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'label' => 'Tags',
                'attr' => ['class' => 'select select-multiple w-full'],
                'data' => $options['data']->getTags(),
                'by_reference' => false,
            ])
            ->add('questions', CollectionType::class, [
                'entry_type' => QuestionType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
                'delete_empty' => true,
            ])
            ->add('timeLimit', IntegerType::class, [
                'label' => 'Temps limite (en secondes)',
                'attr' => ['class' => 'form-control'],
                'mapped' => false,
                'required' => false,
                'data' => $options['time_limit'] ?? null,
            ])
            ->add('penaltyPoints', IntegerType::class, [
                'label' => 'Points de pénalité',
                'attr' => ['class' => 'form-control'],
                'mapped' => false,
                'required' => false,
                'data' => $options['penalty_points'] ?? null,
            ])
            ->add('timePenalty', IntegerType::class, [
                'label' => 'Pénalité de temps (en secondes)',
                'attr' => ['class' => 'form-control'],
                'mapped' => false,
                'required' => false,
                'data' => $options['time_penalty'] ?? null,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Quiz::class,
            'quiz_type' => 'base',
            'time_limit' => null,
            'penalty_points' => null,
            'time_penalty' => null,
        ]);
    }
}
