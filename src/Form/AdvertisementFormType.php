<?php

namespace App\Form;

use App\Entity\Advertisement;
use App\Entity\Category;
use App\Service\LocationProvider;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\All;

class AdvertisementFormType extends AbstractType
{
    public function __construct(private LocationProvider $locationProvider)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a title',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a description',
                    ]),
                ],
            ])
            ->add('price', MoneyType::class, [
                'currency' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a price',
                    ]),
                ],
            ])
            ->add('state', ChoiceType::class, [
                'label' => 'County',
                'attr' => ['class' => 'county-select form-control'],
                'choices' => array_combine(
                    $this->locationProvider->getCounties(),
                    $this->locationProvider->getCounties()
                ),
                'placeholder' => 'Select a county',
            ])
            ->add('categories', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please select at least one category',
                    ]),
                ],
            ])
            ->add('images', FileType::class, [
                'mapped' => false,
                'multiple' => true,
                'required' => false,
                'constraints' => [
                    new Count([
                        'max' => 5,
                        'maxMessage' => 'You can upload maximum {{ limit }} images'
                    ]),
                    new All([ // Apply these constraints to each file
                        'constraints' => [
                            new Image([
                                'maxSize' => '5M',
                                'mimeTypes' => [
                                    'image/jpeg',
                                    'image/png',
                                ],
                                'mimeTypesMessage' => 'Please upload a valid image',
                            ]),
                        ],
                    ]),
                ],
            ]);
        // Add city field dynamically based on selected state
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();

            $cities = [];
            if (!empty($data['state'])) {
                $cities = $this->locationProvider->getCitiesByCounty($data['state']);
            }

            $form->add('city', ChoiceType::class, [
                'attr' => ['class' => 'city-select form-control'],
                'choices' => array_combine($cities, $cities),
                'placeholder' => 'Select a city',
            ]);
        });

        // Add initial city field
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();

            $cities = [];
            if ($data && $data->getState()) {
                $cities = $this->locationProvider->getCitiesByCounty($data->getState());
            }

            $form->add('city', ChoiceType::class, [
                'attr' => ['class' => 'city-select form-control'],
                'choices' => array_combine($cities, $cities),
                'placeholder' => 'Select a city',
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Advertisement::class,
        ]);
    }
}