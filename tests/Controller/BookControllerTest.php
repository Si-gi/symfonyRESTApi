<?php
// tests/Repository/UserRepositoryTest.php
namespace App\Tests\Controller;

use App\Repository\BookRepository;
use App\Repository\AuthorRepository;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookControllerTest extends WebTestCase
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

    public function testGetBookList(){
        $client = static::createClient();

        $client->request('GET', '/api/books');

        $this->assertResponseStatusCodeSame(401);
        
        $Authclient = $this->createAuthenticatedClient($client, "user@bookapi.com", "password");
        $Authclient->request('GET', '/api/books');
        $this->assertResponseIsSuccessful();
    } 

    public function testDeleteBook(){
        $client = static::createClient();
        $bookRepository = static::getContainer()->get(BookRepository::class);
        $lastBook = $bookRepository->findOneBy(array(), array('id' => 'DESC'));
        $this->assertNotEmpty($lastBook);
        $client->request("DELETE", "/api/admin/books/".$lastBook->getId());
        $this->assertResponseStatusCodeSame(401);

        $authclient = $this->createAuthenticatedClient($client, "user@bookapi.com", "password");
        $authclient->request("DELETE", "/api/admin/books/".$lastBook->getId());
        $this->assertResponseStatusCodeSame(403);

        $adminAuthclient = $this->createAuthenticatedClient($client, "admin@bookapi.com", "password");
        $adminAuthclient->request("DELETE", "/api/admin/books/".$lastBook->getId());
        $this->assertResponseIsSuccessful();
    }

    public function testAddBook(){
        $client = static::createClient();

        $authorRepository = static::getContainer()->get(AuthorRepository::class);
        $lastAuthor = $authorRepository->findOneBy(array(), array('id' => 'DESC'));
        $book = json_encode([
            'title' => "Title",
            'coverText' => "Cover text",
            'idAuthor' => $lastAuthor->getId()
        ]);

        $client->request("POST", "/api/admin/books", [$book]);
        $this->assertResponseStatusCodeSame(401);

        $authclient = $this->createAuthenticatedClient($client, "user@bookapi.com", "password");
        $authclient->request("POST", "/api/admin/books", [$book]);
        $this->assertResponseStatusCodeSame(403);   


        $adminAuthclient = $this->createAuthenticatedClient($client, "admin@bookapi.com", "password");
        $adminAuthclient->request("POST", "/api/admin/books", [], [],['CONTENT_TYPE' => 'application/json'], $book);
        $this->assertResponseStatusCodeSame(201);   

        $bookRepository = static::getContainer()->get(BookRepository::class);
        $lastBook = $bookRepository->findOneBy(array(), array('id' => 'DESC'));
        $this->assertStringContainsString("Title", $lastBook->getTitle());
    }

    // public function testUpdateBook(){
    //     $client = static::createClient();

    // }
}