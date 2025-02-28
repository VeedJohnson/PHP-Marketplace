<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase
{

    public function testGetRegisterPage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Register');
        $this->assertCount(1, $crawler->filter('form[name="registration_form"]'));
    }

    public function testValidRegistration(): void
    {
        $client = static::createClient();
        $client->catchExceptions(false);


        $crawler = $client->request('GET', '/register');


        $form = $crawler->selectButton('Register')->form();

        $form['registration_form[firstName]'] = 'John';
        $form['registration_form[lastName]'] = 'Doe';
        $form['registration_form[email]'] = 'john.doe@example.com';
        $form['registration_form[plainPassword]'] = 'LK5aPUhCZU_R_Rd!';
        $form['registration_form[agreeTerms]'] = true;

        // Submit the form
        $client->submit($form);

        // Verify the user in the database
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'john.doe@example.com']);

        $this->assertNotNull($user);
        $this->assertEquals('John', $user->getFirstName());
        $this->assertEquals('Doe', $user->getLastName());
        $this->assertFalse($user->isVerified());
    }
    public function testInvalidRegistration(): void
    {
        $client = static::createClient();

        $client->request('GET', '/register');
        $client->submitForm('Register', [
            'registration_form[firstName]' => '',
            'registration_form[lastName]' => '',
            'registration_form[email]' => 'invalid-email',
            'registration_form[plainPassword]' => 'weak',
            'registration_form[agreeTerms]' => false,
        ]);

        $this->assertResponseStatusCodeSame(422);

        // Check for error messages in the expected structure
        $this->assertSelectorExists('ul > li'); // Ensure at least one error is displayed
        $this->assertSelectorTextContains('ul > li', 'First name is required.');
    }

    /**
     * @throws Exception
     */
    public function testEmailVerification(): void
    {
        $client = static::createClient();

        $mockEmailVerifier = $this->createMock(EmailVerifier::class);
        $mockEmailVerifier->method('handleEmailConfirmation')
            ->willReturnCallback(function ($request, $user) {
                $user->setVerified(true);
            });

        self::getContainer()->set('App\Security\EmailVerifier', $mockEmailVerifier);

        // Generate a unique email for testing
        $uniqueEmail = 'john.doe+' . time() . '@example.com';

        // Simulate a registered user
        $user = new User();
        $user->setFirstName('John')
            ->setLastName('Doe')
            ->setEmail($uniqueEmail)
            ->setPassword('hashed-password')
            ->setVerified(false);

        $entityManager = self::getContainer()->get('doctrine')->getManager();
        $entityManager->persist($user);
        $entityManager->flush();

        // Simulate a valid signature for the verification link
        $id = $user->getId();
        $signature = 'dummy_signature'; // Replace this with a mock signature if necessary

        // Send the verification request
        $client->request('GET', '/verify/email', [
            'id' => $id,
            'signature' => $signature,
        ]);

        // Assert redirection to the home page
        $this->assertResponseRedirects('/');

        // Verify the user is now marked as verified
        $verifiedUser = self::getContainer()->get(UserRepository::class)->find($id);
        $this->assertTrue($verifiedUser->isVerified());
    }

    public function testInvalidEmailVerification(): void
    {
        $client = static::createClient();

        $client->request('GET', '/verify/email?id=99999');
        $this->assertResponseRedirects('/login');
    }
}