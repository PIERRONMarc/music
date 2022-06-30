<?php

namespace App\Form;

use App\Document\Guest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotNull;

class HandleGuestRoleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('role', ChoiceType::class, [
                'choices' => [
                    Guest::ROLE_ADMIN => Guest::ROLE_ADMIN,
                    Guest::ROLE_GUEST => Guest::ROLE_GUEST,
                ],
                'required' => true,
                'invalid_message' => 'Not a valid role',
                'constraints' => [
                    new NotNull(),
                ],
            ]);
    }
}
