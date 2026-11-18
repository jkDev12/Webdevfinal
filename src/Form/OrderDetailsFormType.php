<?php

namespace App\Form;

use App\Dto\OrderDetailsData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class OrderDetailsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('shippingName', TextType::class, ['label' => 'Full name'])
            ->add('shippingPhone', TextType::class, ['label' => 'Phone', 'attr' => ['inputmode' => 'numeric']])
            ->add('shippingAddress', TextType::class, ['label' => 'Street address', 'attr' => ['size' => 50]])
            ->add('shippingCity', TextType::class, ['label' => 'City'])
            ->add('shippingPostalCode', TextType::class, ['label' => 'Postal code', 'attr' => ['inputmode' => 'numeric']])
            ->add('receiptEmail', EmailType::class, ['label' => 'Receipt email']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrderDetailsData::class,
            'csrf_message' => 'Your session expired. Please try again.',
            'csrf_token_id' => 'edit_order_details',
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}
