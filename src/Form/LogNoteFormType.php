<?php

namespace App\Form;

use App\Dto\LogNoteData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class LogNoteFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('note', TextType::class, [
            'label' => 'Note',
            'required' => false,
            'attr' => ['maxlength' => 255, 'size' => 60],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LogNoteData::class,
            'csrf_message' => 'Your session expired. Please try again.',
            'csrf_token_id' => 'edit_log_note',
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}
