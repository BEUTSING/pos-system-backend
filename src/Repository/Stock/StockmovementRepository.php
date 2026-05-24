<?php

namespace App\Repository\Stock;

use App\Entity\Company\Company;
use App\Entity\Stock\Stockmovement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Stockmovement>
 */
class StockmovementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stockmovement::class);
    }


     // ─── Find stock movements by product name within a specific company ───────────
    public function findByProductNameAndCompany(string $name, Company $company): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.product', 'p')
            ->where('p.productname LIKE :name')
            ->andWhere('s.company = :company')
            ->setParameter('name', '%' . $name . '%')
            ->setParameter('company', $company)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
    //    /**
    //     * @return Stockmovement[] Returns an array of Stockmovement objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Stockmovement
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
