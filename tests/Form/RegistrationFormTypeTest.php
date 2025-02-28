<?php

namespace App\Tests\Form;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\Validation;

class RegistrationFormTypeTest extends TypeTestCase
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
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john@example.com',
            'agreeTerms' => true,
            'plainPassword' => 'LK5aPUhCZU_R_Rd',
        ];

        $user = new User();
        $form = $this->factory->create(RegistrationFormType::class, $user);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        $this->assertEquals('John', $user->getFirstName());
        $this->assertEquals('Doe', $user->getLastName());
        $this->assertEquals('john@example.com', $user->getEmail());
    }

    public function testInvalidEmail(): void
    {
        $formData = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'invalid-email',
            'agreeTerms' => true,
            'plainPassword' => 'StrongPassword123!',
        ];

        $user = new User();
        $form = $this->factory->create(RegistrationFormType::class, $user);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
    }

    public function testRequiredFields(): void
    {
        $formData = [
            'firstName' => '',
            'lastName' => '',
            'email' => '',
            'agreeTerms' => false,
            'plainPassword' => '',
        ];

        $user = new User();
        $form = $this->factory->create(RegistrationFormType::class, $user);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());

        // Check specific field errors
        $this->assertTrue($form->get('firstName')->isSubmitted());
        $this->assertGreaterThan(0, count($form->get('firstName')->getErrors()));
        $this->assertEquals('First name is required.', $form->get('firstName')->getErrors()[0]->getMessage());

        $this->assertTrue($form->get('lastName')->isSubmitted());
        $this->assertGreaterThan(0, count($form->get('lastName')->getErrors()));
        $this->assertEquals('Last name is required.', $form->get('lastName')->getErrors()[0]->getMessage());

        $this->assertTrue($form->get('email')->isSubmitted());
        $this->assertGreaterThan(0, count($form->get('email')->getErrors()));
        $this->assertEquals('Email is required.', $form->get('email')->getErrors()[0]->getMessage());

        $this->assertTrue($form->get('agreeTerms')->isSubmitted());
        $this->assertGreaterThan(0, count($form->get('agreeTerms')->getErrors()));
        $this->assertEquals('You should agree to our terms.', $form->get('agreeTerms')->getErrors()[0]->getMessage());

        $this->assertTrue($form->get('plainPassword')->isSubmitted());
        $this->assertGreaterThan(0, count($form->get('plainPassword')->getErrors()));
        $this->assertEquals('Please enter a password', $form->get('plainPassword')->getErrors()[0]->getMessage());
    }

    public function testPasswordConstraints(): void
    {
        $formData = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john@example.com',
            'agreeTerms' => true,
            'plainPassword' => 'weak', // Too short and weak password
        ];

        $user = new User();
        $form = $this->factory->create(RegistrationFormType::class, $user);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());

        $errors = $form->get('plainPassword')->getErrors();
        $this->assertGreaterThan(0, count($errors));
    }

    public function testAgreeTermsConstraint(): void
    {
        $formData = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john@example.com',
            'agreeTerms' => false,
            'plainPassword' => 'StrongPassword123!',
        ];

        $user = new User();
        $form = $this->factory->create(RegistrationFormType::class, $user);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());

        $errors = $form->get('agreeTerms')->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals('You should agree to our terms.', $errors[0]->getMessage());
    }

    public function testFormFields(): void
    {
        $form = $this->factory->create(RegistrationFormType::class);

        $this->assertTrue($form->has('firstName'));
        $this->assertTrue($form->has('lastName'));
        $this->assertTrue($form->has('email'));
        $this->assertTrue($form->has('agreeTerms'));
        $this->assertTrue($form->has('plainPassword'));
    }
}