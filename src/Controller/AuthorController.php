<?php

namespace App\Controller;

use App\Entity\Author;
use App\Repository\AuthorRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
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
}
