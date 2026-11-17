<?php

namespace App\Form;

use App\Dto\ProductData;
use App\Entity\ProductStock;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ProductFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Name'])
            ->add('price', NumberType::class, [
                'label' => 'Price (₱)',
                'scale' => 2,
                'input' => 'string',
                'html5' => true,
                'attr' => ['min' => 0, 'step' => '0.01'],
            ])
            ->add('image', FileType::class, [
                'label' => $options['is_new'] ? 'Image' : 'Replace image (optional)',
                'required' => false,
                'attr' => ['accept' => '.jpg,.jpeg,.png,.webp'],
            ]);

        $stock = $builder->create('stock', FormType::class, ['label' => 'Stock per size']);
        foreach (ProductStock::SIZES as $size) {
            $stock->add($size, IntegerType::class, ['label' => $size, 'required' => false, 'attr' => ['min' => 0]]);
        }
        $builder->add($stock);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductData::class,
            'is_new' => false,
            'csrf_message' => 'Your session expired. Please try again.',
            'csrf_token_id' => 'product_form',
            'attr' => ['novalidate' => 'novalidate'],
        ]);
        $resolver->setAllowedTypes('is_new', 'bool');
    }
}
