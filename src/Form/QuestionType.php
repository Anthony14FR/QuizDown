<?php

namespace App\Form;

use App\Entity\Question;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
    $builder
        ->add('type', ChoiceType::class, [
            'choices' => [
                'Vrai/Faux' => Question::TYPE_TRUE_FALSE,
                'Choix unique' => Question::TYPE_SINGLE_CHOICE,
                'Choix multiple' => Question::TYPE_MULTIPLE_CHOICE
            ],
                'label' => 'Type de question',
                'attr' => ['class' => 'question-type-select']
            ])
            ->add('content', TextType::class, [
                'label' => 'Question',
                'attr' => ['class' => 'question-content']
            ])
            ->add('answers', CollectionType::class, [
                'entry_type' => AnswerType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'attr' => ['class' => 'answers-collection'],
                'delete_empty' => true
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Question::class
        ]);
    }
}
