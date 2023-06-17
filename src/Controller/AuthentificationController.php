<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Json;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AuthentificationController extends AbstractController
{
  public function __construct()
  {
  }

  /**
  * @Route("api/register", name="register", methods={"POST"})
  */
  public function Register(Request $request, UserRepository $userRepository, SerializerInterface $serializer,  UserPasswordHasherInterface $passwordHasher) : JsonResponse
  {
    $jsonData = json_decode($request->getContent());
    $user = $userRepository->create($jsonData, $passwordHasher);

    return new JsonResponse([
      'user' => $serializer->serialize($user, 'json')
    ], 201);
  }
/*
  #[Route('/profile', name: 'user.profile')]
  public function profile() : JsonResponse
  {
    $currentUser = $this->security->getUser();
    $user = $this->serializer->serialize($currentUser, 'json');
    return new JsonResponse([
      $user
    ], 200);
  }
*/
}