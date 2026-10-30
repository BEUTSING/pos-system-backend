<?php

namespace App\Repository\Checkout;

use App\Entity\Checkout\CustomerOrder;
use App\Entity\Checkout\Sale;
use App\Entity\Company\Company;
use App\Entity\Product\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sale>
 */
class SaleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sale::class);
    }


    /** @return Sale[] */
    public function findSalesByPeriodAndCompany(\DateTimeImmutable $startdate, \DateTimeImmutable $enddate, Company $company): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.createdAt BETWEEN :start AND :end')
            ->andWhere('s.company = :company')
            ->setParameter('start', $startdate)
            ->setParameter('end', $enddate)
            ->setParameter('company', $company)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return Sale[] */
    public function findSaleByCategoryAndCompany(int $categoryId, Company $company): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.customerOrder', 'co')
            ->join('co.orderItems', 'oi')
            ->join('oi.product', 'p')
            ->join('p.category', 'c')
            ->andWhere('c.id = :categoryId')
            ->andWhere('s.company = :company')
            ->setParameter('categoryId', $categoryId)
            ->setParameter('company', $company)
            ->distinct()
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return Sale[] */
    public function findSaleByProductAndCompany(Product $product, Company $company): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.customerOrder', 'co')
            ->join('co.orderItems', 'oi')
            ->andWhere('oi.product = :product')
            ->andWhere('s.company = :company')
            ->setParameter('product', $product)
            ->setParameter('company', $company)
            ->distinct()
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Sale[] Returns an array of Sale objects
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

    //    public function findOneBySomeField($value): ?Sale
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
