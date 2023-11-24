<?php

declare(strict_types=1);

namespace App\Form;

use App\Service\User\ChangePasswordData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'mapped' => false,
                'constraints' => [
                    new UserPassword(),
                ],
            ])
            ->add('password', PasswordType::class, ['empty_data' => ''])
            ->add('passwordRepeat', PasswordType::class, [
                'mapped' => false,
                'constraints' => [
                    new NotBlank(),
                    new Callback($this->passwordsMatch(...)),
                ],
            ])
            ->add('submit', SubmitType::class, ['label' => 'Change password'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ChangePasswordData::class,
        ]);
    }

    private function passwordsMatch(mixed $value, ExecutionContextInterface $context): void
    {
        if (null === $value) {
            return;
        }

        $form = $context->getRoot();
        if (!$form instanceof Form) {
            return;
        }

        $password = $form->get('password')->getData();
        if (null === $password || '' === $password) {
            return;
        }

        if ($value !== $password) {
            $context->buildViolation('Password does not match.')->addViolation();
        }
    }
}
