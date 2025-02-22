<?php

namespace App\Repository;

use App\Entity\Planning;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Planning>
 */
class PlanningRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Planning::class);
    }
    public function findByStudentLevel(string $studentLevel)
{
    return $this->createQueryBuilder('p')
        ->andWhere('p.studentLevel = :studentLevel')
        ->setParameter('studentLevel', $studentLevel)
        ->getQuery()
        ->getResult();
}
public function findPlanningForUserLevel(string $userLevel): array
{
    return $this->createQueryBuilder('p')
        ->where('p.studentLevel = :userLevel')
        ->setParameter('userLevel', $userLevel)
        ->getQuery()
        ->getResult();
}
// src/Repository/PlanningRepository.php

public function findPlanningForTeacher(string $teacherName): array
{
    return $this->createQueryBuilder('p')
        ->leftJoin('p.teacher', 't') // Assuming 'teacher' is the relation in Planning entity
        ->where('CONCAT(t.nom, \' \', t.prenom) = :teacherName')
        ->setParameter('teacherName', $teacherName)
        ->getQuery()
        ->getResult();
}

    //    /**
    //     * @return Planning[] Returns an array of Planning objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Planning
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
