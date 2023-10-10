<?php
// tests/Repository/UserRepositoryTest.php
namespace App\Tests\Repository;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserRepositoryTest extends WebTestCase
{
   
    protected function createAuthenticatedClient($username = 'user', $password = 'password')
    {
        $client = static::createClient();
        $client->request(
        'POST',
        '/api/login_check',
        [],
        [],
        ['CONTENT_TYPE' => 'application/json'],
        json_encode([
            'username' => $username,
            'password' => $password,
        ])
        );

        $data = json_decode($client->getResponse()->getContent(), true);
        $client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $data['token']));

        return $client;
    }

    public function testPage(): void
    {
        $client = $this->createAuthenticatedClient("user@bookapi.com", "password");
        
        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneByEmail('user@bookapi.com');
        $this->assertNotEmpty($testUser);


        $client->request('GET', '/api/authors');

        $this->assertResponseIsSuccessful();
        // retrieve the test user

        // simulate $testUser being logged in
    }
}
