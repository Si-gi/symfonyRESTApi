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
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class AuthorController extends AbstractController
{
    /**
     * @Route("/api/authors", name="authors", methods={"GET"})
     * @IsGranted("ROLE_USER", message="Vous n'avez pas les droits suffisants")
     */
    public function getAllAuthor(AuthorRepository $authorRepository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cache): JsonResponse
    {
        $limit = $request->get('limit') ?? 2;
        $page = $request->get('page') ?? 1;

        $idCache = "getAuthor-" . $page . "-" . $limit;

        $jsonAuth = $cache->get($idCache, function (ItemInterface $item) use ($authorRepository, $page, $limit, $serializer) {
            $item->tag("AuthorCache");
            $authorList = $authorRepository->findAllWithPagination($page, $limit);
            $context = SerializationContext::create()->setGroups(['getAuthors']);

            return $serializer->serialize($authorList, 'json', $context);
        });


        return new JsonResponse($jsonAuth, Response::HTTP_OK, [], true);
    }

    /**
     * @Route("/api/authors/{id}", name="authorDetail", methods={"GET"})
     */
    public function getAuthor(Author $author,  SerializerInterface $serializer) : JsonResponse {
        $context = SerializationContext::create()->setGroups(['getAuthors']);
        
        $jsonAuth = $serializer->serialize($author, 'json',  $context);
        return new JsonResponse($jsonAuth, Response::HTTP_OK, ['accept' => 'json'], true);
    }

     /**
     * @Route("/api/admin/authors/{id}", name="deteleAuthor", methods={"DELETE"})
     * @IsGranted("ROLE_ADMIN", message="Vous n'avez pas les droits suffisants")
     * 
     */
   public function deleteAuthor(Author $author, EntityManagerInterface $em, TagAwareCacheInterface $cachePool ): JsonResponse 
    {
        $cachePool->invalidateTags(['AuthorCache']);
        $em->remove($author);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/api/admin/authors", name="createAuthor", methods={"POST"})
     * @IsGranted("ROLE_ADMIN", message="Vous n'avez pas les droits suffisants")
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
        $context = SerializationContext::create()->setGroups(['getAuthors']);

        $jsonAuthor = $serializer->serialize($author, 'json', $context);
        
        $location = $urlGenerator->generate('authorDetail', ['id' => $author->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonAuthor, Response::HTTP_CREATED, ["Location" => $location], true);
   }

    /**
     * @Route("/api/admin/authors/{id}", name="updateAuthor", methods={"PUT"})
     * @IsGranted("ROLE_ADMIN", message="Vous n'avez pas les droits suffisants")
     * 
     */
   public function updateAuthor(Request $request, 
   SerializerInterface $serializer, 
   Author $currentAuthor, 
   EntityManagerInterface $em,
   ValidatorInterface $validator,
   TagAwareCacheInterface $cachePool


   ): JsonResponse {
        $newBook = $serializer->deserialize($request->getContent(), Book::class, 'json');
        $currentAuthor->setFirstName($newBook->getTitle());


        // On vérifie les erreurs
        $errors = $validator->validate($currentAuthor);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        
        $em->persist($currentAuthor);
        $em->flush();
        $cachePool->invalidateTags(["AuthorsCache"]);
       return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
  }
}
