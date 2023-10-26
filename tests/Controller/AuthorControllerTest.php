<?php
// tests/Repository/UserRepositoryTest.php
namespace App\Tests\Controller;

use App\Repository\AuthorRepository;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthorControllerTest extends WebTestCase
{
   
    protected function createAuthenticatedClient($client, $username = 'user', $password = 'password')
    {
        // $client = static::createClient();
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

    public function testGetAuthorList(){
        $client = static::createClient();

        $client->request('GET', '/api/authors');

        $this->assertResponseStatusCodeSame(401);
        
        $Authclient = $this->createAuthenticatedClient($client, "user@bookapi.com", "password");
        $Authclient->request('GET', '/api/authors');
        $this->assertResponseIsSuccessful();
    } 

    public function testDeleteAuthor(){
        $client = static::createClient();
        $authorRepository = static::getContainer()->get(AuthorRepository::class);
        $lastAuthor = $authorRepository->findOneBy(array(), array('id' => 'DESC'));
        $this->assertNotEmpty($lastAuthor);
        $client->request("DELETE", "/api/admin/authors/".$lastAuthor->getId());
        $this->assertResponseStatusCodeSame(401);

        $authclient = $this->createAuthenticatedClient($client, "user@bookapi.com", "password");
        $authclient->request("DELETE", "/api/admin/authors/".$lastAuthor->getId());
        $this->assertResponseStatusCodeSame(403);

        $adminAuthclient = $this->createAuthenticatedClient($client, "admin@bookapi.com", "password");
        $adminAuthclient->request("DELETE", "/api/admin/authors/".$lastAuthor->getId());
        $this->assertResponseIsSuccessful();
    }

    public function testAddAuthor(){
        $client = static::createClient();

        $authorRepository = static::getContainer()->get(AuthorRepository::class);
        $lastAuthor = $authorRepository->findOneBy(array(), array('id' => 'DESC'));
        $author = json_encode([
            'firstName' => "TestName",
            'lastname' => "TestLastName"
        ]);

        $client->request("POST", "/api/admin/authors", [$author]);
        $this->assertResponseStatusCodeSame(401);

        $authclient = $this->createAuthenticatedClient($client, "user@bookapi.com", "password");
        $authclient->request("POST", "/api/admin/authors", [$author]);
        $this->assertResponseStatusCodeSame(403);   


        $adminAuthclient = $this->createAuthenticatedClient($client, "admin@bookapi.com", "password");
        $adminAuthclient->request("POST", "/api/admin/authors", [], [],['CONTENT_TYPE' => 'application/json'], $author);
        $this->assertResponseStatusCodeSame(201);   

        $authorRepository = static::getContainer()->get(AuthorRepository::class);
        $lastAuthor = $authorRepository->findOneBy(array(), array('id' => 'DESC'));
        $this->assertStringContainsString("TestName", $lastAuthor->getFirstName());
    }

    // public function testUpdateAuthor(){
    //     $client = static::createClient();

    // }
}