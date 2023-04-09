<?php

namespace App\BusinessLayer;

use App\Entity\User;
use App\Form\UserType;
use App\Utils\Constants;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;

class UserBusinessLayer
{

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em){
        $this->em = $em;
    }

    /**
     * @throws BadRequestException
     */
    public function createEditUser($userJson,  FormInterface $form, User $user, $client, $edit = false): User
    {

        $form->submit($userJson);

        if(!$form->isValid()){
            throw new BadRequestException("Parameters sent not valid or missing", 400);
        }

        $this->checkCategory($user->getCategory());

        if(!$edit){
            $user->setClient($client);
            $user->setActive(true);
            $user->setCreatedAt(new \DateTimeImmutable());
            $this->em->persist($user);
        }else {
            $user->setActive($userJson['active'] ?? false);
            $user->setUpdatedAt(new \DateTime());
        }


        $this->em->flush();

        return $user;
    }

    public function activateDeactivateUser(User $user, $active){
        $user->setActive($active);
        $this->em->flush();
        return $user;
    }

    public function search($filters, $queryBuilder, $client, $page, $perPage, $firstResult) : array{
        foreach ($filters as $filterKey => $filterValue) {
            if (strpos($filterKey, '_') === false) {
                continue;
            }

            [$type, $field] = explode('_', $filterKey, 2);
            $this->checkParamsFormat($field, $type, $filterValue);
            switch ($type) {
                case 'equals':
                    $filterValue = explode(',', $filterValue);
                    $queryBuilder->andWhere("u.{$field} IN (:{$filterKey})")
                        ->setParameter($filterKey, (array) $filterValue);
                    break;

                case 'notEquals':
                    $filterValue = explode(',', $filterValue);
                    $queryBuilder->andWhere("u.{$field} NOT IN (:{$filterKey})")
                        ->setParameter($filterKey, (array) $filterValue);
                    break;

                case 'greaterThan':
                    $queryBuilder->andWhere("u.{$field} > :{$filterKey}")
                        ->setParameter($filterKey, $filterValue);
                    break;

                case 'lessThan':
                    $queryBuilder->andWhere("u.{$field} < :{$filterKey}")
                        ->setParameter($filterKey, $filterValue);
                    break;
                default:
                    throw new BadRequestException('Wrong filters operators combination.');
            }
        }

        $queryBuilder->andWhere("u.client = :client")->setParameter('client',$client->getId());

        $queryBuilder->setFirstResult($firstResult)
            ->setMaxResults($perPage);

        $users = $queryBuilder->getQuery()->getResult();

        $totalUsers = $queryBuilder->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalPages = (int) ceil($totalUsers / $perPage);

        return ['data' => $this->usersArrayToJson($users), 'page' => $page, 'perPage' => $perPage, 'totalPages' => $totalPages, 'totalCount' => $totalUsers];
    }

    public function usersArrayToJson($users): array
    {
        $usersSerialized = [];
        foreach ($users as $user){
            $usersSerialized[] = $user->toJson();
        }
        return $usersSerialized;
    }

    private function checkParamsFormat($filterName, $operator, $filterValue){

        if($filterName != 'name' && $filterName != 'lastName' && $filterName != 'city' &&
            $filterName != 'active' && $filterName != 'id' && $filterName != 'age' &&
            $filterName != 'createdAt' && $filterName != 'updatedAt' ){
            throw new BadRequestException('Wrong filters operators combination.');
        }

        if(($operator == 'greaterThan' || $operator == 'lessThan' ) &&
            (   $filterName == 'name' || $filterName == 'lastName' ||
                $filterName == 'city' || $filterName == 'active'   )) {

            throw new BadRequestException('Wrong filters operators combination.');
        }

        if($filterName == 'createdAt' || $filterName == 'updatedAt'){
            $dateArr = explode(',', $filterValue);
            foreach ($dateArr as $dateString){
                $date = \DateTime::createFromFormat('Y-m-d', $dateString);
                if(!$date || $date->format('Y-m-d') != $dateString)
                    throw new BadRequestException('Wrong Date Format. The format expected is YYYY-MM-DD');
            }

        }

    }

    private function checkCategory($category){

        switch ($category){
            case Constants::$USER_CAT_Y:
            case Constants::$USER_CAT_Z:
            case Constants::$USER_CAT_X:
                return true;

            default:
                throw new BadRequestException('Category Not Supported', 400);
        }
    }

}