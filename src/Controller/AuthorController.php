<?php

namespace App\Controller;

use App\Entity\Author;
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

class AuthorController extends AbstractController
{
    /**
     * @Route("/api/authors", name="authors", methods={"GET"})
     */
    public function getAllAuthor(AuthorRepository $authorRepository, SerializerInterface $serializer): JsonResponse
    {
        $authors= $authorRepository->findAll();
        $authrsJson = $serializer->serialize($authors, "json",  ['groups' => 'getAuthors']);

        return new JsonResponse($authrsJson, Response::HTTP_OK, [], true);
    }

    /**
     * @Route("/api/authors/{id}", name="authorDetail", methods={"GET"})
     */
    public function getAuthor(Author $author,  SerializerInterface $serializer) : JsonResponse {
        $jsonAuth = $serializer->serialize($author, 'json',  ['groups' => 'getAuthors']);
        return new JsonResponse($jsonAuth, Response::HTTP_OK, ['accept' => 'json'], true);
    }

     /**
     * @Route("/api/authors/{id}", name="deteleAuthor", methods={"DELETE"})
     */
    public function deleteAuthor(Author $author, EntityManagerInterface $em): JsonResponse 
    {
        $em->remove($author);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/api/authors", name="createAuthor", methods={"POST"})
     */
    public function createAuthor(
        Request $request, 
        SerializerInterface $serializer, 
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator
        ): JsonResponse {

        $author = $serializer->deserialize($request->getContent(), Author::class, 'json');

        $errors = $validator->validate($author);

        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $em->persist($author);
        $em->flush();

        $jsonAuthor = $serializer->serialize($author, 'json', ['groups' => 'getAuthors']);
        
        $location = $urlGenerator->generate('authorDetail', ['id' => $author->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonAuthor, Response::HTTP_CREATED, ["Location" => $location], true);
   }

    /**
     * @Route("/api/authors/{id}", name="updateAuthor", methods={"PUT"})
     */
   public function updateAuthor(Request $request, 
   SerializerInterface $serializer, 
   Author $currentAuthor, 
   EntityManagerInterface $em
   ): JsonResponse {
       $updatedAuthor = $serializer->deserialize($request->getContent(), 
               Author::class, 
               'json', 
               [AbstractNormalizer::OBJECT_TO_POPULATE => $currentAuthor]);
       
       $em->persist($updatedAuthor);
       $em->flush();
       return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
  }
}
