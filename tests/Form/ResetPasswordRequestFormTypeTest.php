<?php

namespace App\Tests\Form;

use App\Form\ResetPasswordRequestFormType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\Validation;

class ResetPasswordRequestFormTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        $validator = Validation::createValidator();

        return [
            new ValidatorExtension($validator),
        ];
    }

    public function testSubmitValidData(): void
    {
        $formData = [
            'email' => 'user@example.com',
        ];

        $form = $this->factory->create(ResetPasswordRequestFormType::class);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $this->assertSame('user@example.com', $form->get('email')->getData());
    }

    public function testSubmitInvalidEmail(): void
    {
        $formData = [
            'email' => 'invalid-email',
        ];

        $form = $this->factory->create(ResetPasswordRequestFormType::class);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
    }

    public function testSubmitEmptyEmail(): void
    {
        $formData = [
            'email' => '',
        ];

        $form = $this->factory->create(ResetPasswordRequestFormType::class);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());

        $errors = $form->get('email')->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals('Please enter your email', $errors[0]->getMessage());
    }

    public function testFormFields(): void
    {
        $form = $this->factory->create(ResetPasswordRequestFormType::class);

        $this->assertTrue($form->has('email'));
    }
}