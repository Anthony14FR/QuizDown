<?php

namespace App\Form;

use App\Entity\Answer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AnswerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('content', TextType::class, [
            'label' => 'Réponse',
        ]);

        if ($options['is_true_false']) {
            $builder->add('isCorrect', RadioType::class, [
                'required' => false,
                'label' => 'Bonne réponse',
            ]);
        } elseif ($options['is_multiple']) {
            $builder->add('isCorrect', CheckboxType::class, [
                'required' => false,
                'label' => 'Bonne réponse',
            ]);
        } else {
            $builder->add('isCorrect', RadioType::class, [
                'required' => false,
                'label' => 'Bonne réponse',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Answer::class,
            'is_true_false' => false,
            'is_multiple' => false,
        ]);
    }
}
