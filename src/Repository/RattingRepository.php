<?php

namespace App\Repository;

use App\Entity\Ratting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ratting>
 *
 * @method Ratting|null find($id, $lockMode = null, $lockVersion = null)
 * @method Ratting|null findOneBy(array $criteria, array $orderBy = null)
 * @method Ratting[]    findAll()
 * @method Ratting[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RattingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ratting::class);
    }

    public function save(Ratting $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Ratting $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    // Add your custom repository methods here
}
