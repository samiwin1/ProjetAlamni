<?php

namespace App\Repository;

use App\Entity\Reponsereclamation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReponseReclamation>
 */
class ReponsereclamationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReponseReclamation::class);
    }
        
    public function countUnreadResponsesForUser($userEmail): int
    {
        return $this->createQueryBuilder('r')
            ->join('r.reclamation', 'rec')
            ->join('rec.user', 'u') // 🔹 Joindre avec l'utilisateur
            ->where('u.email = :userEmail') // 🔹 Filtrer par l'email de l'utilisateur
            ->andWhere('r.isRead = false') // 🔹 Vérifier que la réponse n'a pas été lue
            ->setParameter('userEmail', $userEmail)
            ->select('COUNT(r.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
    
    

}
