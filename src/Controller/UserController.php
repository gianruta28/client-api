<?php

namespace App\Controller;

use App\BusinessLayer\UserBusinessLayer;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\ClientRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\SerializerInterface;

class UserController extends AbstractController
{
    /**
     * @Route("/api/users", name="test", methods={"POST"})
     *
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param TokenStorageInterface $tokenStorageInterface
     * @param JWTTokenManagerInterface $jwtManager
     * @param ClientRepository $clientRepository
     * @param UserBusinessLayer $userBusinessLayer
     * @return JsonResponse
     */
    public function createUserAction(Request $request,SerializerInterface $serializer, TokenStorageInterface $tokenStorageInterface, JWTTokenManagerInterface $jwtManager, ClientRepository $clientRepository, UserBusinessLayer $userBusinessLayer): JsonResponse
    {

        try {
            $decodedJwtToken = $jwtManager->decode($tokenStorageInterface->getToken());

            $clientUserame = $decodedJwtToken['username'];
            $client = $clientRepository->findOneBy(['username' => $clientUserame]);

            $data = json_decode($request->getContent(), true);

            $newUser = new User();
            $form = $this->createForm(UserType::class, $newUser);

            $user = $userBusinessLayer->createEditUser($data, $form, $newUser, $client);
            return new JsonResponse(['user' => $user->toJson()]);
        }catch (JWTDecodeFailureException $jwtException){
            return new JsonResponse(['error' => 'Auth Error. Try again later'], 400);
        } catch (BadRequestException $badRequestException) {
            return new JsonResponse(['error' => $badRequestException->getMessage()], 400);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @Route("/api/users/{userId}", name="get_user", methods={"GET"})
     *
     * @param int $userId
     * @param TokenStorageInterface $tokenStorageInterface
     * @param JWTTokenManagerInterface $jwtManager
     * @param UserRepository $userRepository
     * @param ClientRepository $clientRepository
     * @return JsonResponse
     */
    public function getUserAction(int $userId,TokenStorageInterface $tokenStorageInterface, JWTTokenManagerInterface $jwtManager, UserRepository $userRepository, ClientRepository $clientRepository): JsonResponse
    {
        try{
            $decodedJwtToken = $jwtManager->decode($tokenStorageInterface->getToken());

            $clientUserame = $decodedJwtToken['username'];

            $client = $clientRepository->findOneBy(['username' => $clientUserame]);

            $user = $userRepository->findOneBy(['id' => $userId, 'client' => $client]);

            if(!$user){
                throw new NotFoundHttpException('User not found');
            }

            return new JsonResponse(['user' => $user->toJson()]);

        }catch (JWTDecodeFailureException $jwtException){
            return new JsonResponse(['error' => 'Auth Error. Try again later'], 400);
        }catch (NotFoundHttpException $notFoundException){
            return new JsonResponse(['error' => $notFoundException->getMessage()], 404);
        }catch (\Exception $e){
            return new JsonResponse(['error' => 'Unexpected Error. Try again later'], 500);
        }

    }

    /**
     * @Route("/api/users/{userId}", name="edit_user", methods={"PUT"})
     *
     * @param int $userId
     * @param Request $request
     * @param TokenStorageInterface $tokenStorageInterface
     * @param JWTTokenManagerInterface $jwtManager
     * @param UserRepository $userRepository
     * @param ClientRepository $clientRepository
     * @param UserBusinessLayer $userBusinessLayer
     * @return JsonResponse
     */
    public function editUserAction(int $userId, Request $request,TokenStorageInterface $tokenStorageInterface, JWTTokenManagerInterface $jwtManager, UserRepository $userRepository, ClientRepository $clientRepository, UserBusinessLayer $userBusinessLayer): JsonResponse
    {
        try{
            $decodedJwtToken = $jwtManager->decode($tokenStorageInterface->getToken());

            $data = json_decode($request->getContent(), true);
            $clientUserame = $decodedJwtToken['username'];

            $client = $clientRepository->findOneBy(['username' => $clientUserame]);

            $user = $userRepository->findOneBy(['id' => $userId, 'client' => $client]);

            if(!$user){
                throw new NotFoundHttpException('User not found');
            }

            if(count($data) == 1 && isset($data['active'])){
                $editedUser = $userBusinessLayer->activateDeactivateUser($user, $data['active']);

            }else {
                $form = $this->createForm(UserType::class, $user);
                $editedUser = $userBusinessLayer->createEditUser($data, $form,$user, $client, true);
            }

            return new JsonResponse(['user' => $editedUser->toJson()]);

        }catch (JWTDecodeFailureException $jwtException){
            return new JsonResponse(['error' => 'Auth Error. Try again later'], 400);
        }catch (NotFoundHttpException $notFoundException){
            return new JsonResponse(['error' => $notFoundException->getMessage()], 404);
        }catch (BadRequestException $badRequestException) {
            return new JsonResponse(['error' => $badRequestException->getMessage()], 400);
        }catch (\Exception $e){
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }

    }

    /**
     * @Route("/api/users/{userId}", name="delete_user", methods={"DELETE"})
     *
     * @param int $userId
     * @param TokenStorageInterface $tokenStorageInterface
     * @param JWTTokenManagerInterface $jwtManager
     * @param UserRepository $userRepository
     * @param ClientRepository $clientRepository
     * @return JsonResponse
     */
    public function deleteUserAction(int $userId,TokenStorageInterface $tokenStorageInterface, JWTTokenManagerInterface $jwtManager, UserRepository $userRepository, ClientRepository $clientRepository): JsonResponse
    {
        try{
            $decodedJwtToken = $jwtManager->decode($tokenStorageInterface->getToken());

            $clientUserame = $decodedJwtToken['username'];

            $client = $clientRepository->findOneBy(['username' => $clientUserame]);

            $user = $userRepository->findOneBy(['id' => $userId, 'client' => $client]);

            if(!$user){
                throw new NotFoundHttpException('User not found');
            }

            $userRepository->remove($user);
            return new JsonResponse(['message' => 'User deleted'], 200);

        }catch (JWTDecodeFailureException $jwtException){
            return new JsonResponse(['error' => 'Auth Error. Try again later'], 400);
        }catch (NotFoundHttpException $notFoundException){
            return new JsonResponse(['error' => $notFoundException->getMessage()], 404);
        }catch (\Exception $e){
            return new JsonResponse(['error' => 'Unexpected Error. Try again later'], 500);
        }

    }

    /**
     * @Route("/api/users", name="get_users", methods={"GET"})
     *
     * @param int $userId
     * @param TokenStorageInterface $tokenStorageInterface
     * @param JWTTokenManagerInterface $jwtManager
     * @param UserRepository $userRepository
     * @param ClientRepository $clientRepository
     * @return JsonResponse
     */
    public function getUsersAction(Request $request,TokenStorageInterface $tokenStorageInterface, JWTTokenManagerInterface $jwtManager, UserRepository $userRepository, ClientRepository $clientRepository, UserBusinessLayer $userBusinessLayer): JsonResponse
    {
        try{
            $decodedJwtToken = $jwtManager->decode($tokenStorageInterface->getToken());

            $clientUserame = $decodedJwtToken['username'];

            $page = $request->query->getInt('page', 1);
            $perPage = $request->query->getInt('perPage', 10);
            $firstResult = ($page - 1) * $perPage;
            $filters = $request->query->all();


            $client = $clientRepository->findOneBy(['username' => $clientUserame]);

            $queryBuilder = $userRepository->createQueryBuilder('u');

            $users = $userBusinessLayer->search($filters, $queryBuilder, $client, $page, $perPage, $firstResult);



            return new JsonResponse($users, 200);

        }catch (JWTDecodeFailureException $jwtException){
            return new JsonResponse(['error' => 'Auth Error. Try again later'], 400);
        }catch (NotFoundHttpException $notFoundException){
            return new JsonResponse(['error' => $notFoundException->getMessage()], 404);
        }catch (BadRequestException $badRequestException){
            return new JsonResponse(['error' => $badRequestException->getMessage()], 400);
        }catch (\Exception $e){
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }

    }

}