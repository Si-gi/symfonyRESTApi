<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\BookRepository;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class BookController extends AbstractController
{
    /**
     * @Route("/api/books", name="books", methods={"GET"})
     */
    public function getBookList(BookRepository $bookRepository,  SerializerInterface $serializer): JsonResponse
    {
        $bookList = $bookRepository->findAll();
        $jsonBookList = $serializer->serialize($bookList, 'json',  ['groups' => 'getBooks']);

        return new JsonResponse($jsonBookList, Response::HTTP_OK, [], true);
    }

    /**
     * @Route("/api/books/{id}", name="detailBook", methods={"GET"})
     */
    public function getDetailBook(Book $book,  SerializerInterface $serializer): JsonResponse
    {
        $jsonBook = $serializer->serialize($book, 'json',  ['groups' => 'getBooks']);
        return new JsonResponse($jsonBook, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    /**
     * @Route("/api/admin/books/{id}", name="deteleBook", methods={"DELETE"})
     * @IsGranted("ROLE_ADMIN", subject="book", message="Vous n'avez pas les droits suffisants")
     */
    public function deleteBook(Book $book, EntityManagerInterface $em): JsonResponse 
    {
        $em->remove($book);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
    /**
     * @Route("/api/admin/books", name="createBook", methods={"POST"})
     * @IsGranted("ROLE_ADMIN", subject="book", message="Vous n'avez pas les droits suffisants")
     */
    public function createBook(Request $request, 
            SerializerInterface $serializer, 
            EntityManagerInterface $em, 
            UrlGeneratorInterface $urlGenerator,
            AuthorRepository $authorRepository,
            ValidatorInterface $validator
    ): JsonResponse {

        $book = $serializer->deserialize($request->getContent(), Book::class, 'json');

        $content = $request->toArray();


        $idAuthor = $content['idAuthor'] ?? -1;
        $book->setAuthor($authorRepository->find($idAuthor));

        $errors = $validator->validate($book);

        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }


        $em->persist($book);
        $em->flush();

        $jsonBook = $serializer->serialize($book, 'json', ['groups' => 'getBooks']);
        
        $location = $urlGenerator->generate('detailBook', ['id' => $book->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonBook, Response::HTTP_CREATED, ["Location" => $location], true);
   }

    /**
     * @Route("/api/admin/books/{id}", name="updateBook", methods={"PUT"})
     * @IsGranted("ROLE_ADMIN", subject="book", message="Vous n'avez pas les droits suffisants")
     * 
     */
   public function updateBook(Request $request, 
   SerializerInterface $serializer, 
   Book $currentBook, 
   EntityManagerInterface $em, 
   AuthorRepository $authorRepository
   ): JsonResponse {
       $updatedBook = $serializer->deserialize($request->getContent(), 
               Book::class, 
               'json', 
               [AbstractNormalizer::OBJECT_TO_POPULATE => $currentBook]);
       $content = $request->toArray();
       $idAuthor = $content['idAuthor'] ?? -1;
       $updatedBook->setAuthor($authorRepository->find($idAuthor));
       
       $em->persist($updatedBook);
       $em->flush();
       return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
  }
}
