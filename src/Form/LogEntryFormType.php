<?php

namespace App\Form;

use App\Dto\LogEntryData;
use App\Entity\ProductStock;
use App\Entity\StockMovement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class LogEntryFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Action',
                'choices' => ['Posted (added)' => StockMovement::TYPE_ADD, 'Removed' => StockMovement::TYPE_REMOVE],
            ])
            ->add('size', ChoiceType::class, [
                'label' => 'Size',
                'choices' => array_combine(ProductStock::SIZES, ProductStock::SIZES),
            ])
            ->add('quantity', IntegerType::class, ['label' => 'Quantity', 'attr' => ['min' => 1]])
            ->add('note', TextType::class, ['label' => 'Note', 'required' => false, 'attr' => ['maxlength' => 255, 'size' => 60]]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LogEntryData::class,
            'csrf_message' => 'Your session expired. Please try again.',
            'csrf_token_id' => 'edit_log_entry',
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}
