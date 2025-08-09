<?php

namespace App\Repository\Checkout;

use App\Entity\Checkout\CustomerOrder;
use App\Entity\Checkout\Sale;
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


    public function findSalesByPeriod(\DateTimeImmutable $startdate, \DateTimeImmutable $enddate): array
    {
        return $this->createQueryBuilder('s')
        ->andWhere('s.createdAt BETWEEN :start AND :end')
        ->setParameter('start', $startdate)
        ->setParameter('end', $enddate)
        ->getQuery()
        ->getResult();}


    public function findSaleByCategory(int $categoryId){

        return $this->createQueryBuilder('s')
                    ->join('s.customerOrder','co') 
                    ->join('co.orderitems','oi') 
                    ->join('oi.product','p') 
                    ->join('p.category','c') 
                    ->andWhere('c.id= :categoryId')
                    ->setParameter('categoryId', $categoryId)
                    ->getQuery()
                    ->getResult();

    }

    public function findSalebyproduct(Product $product)
    {
        return $this->createQueryBuilder('s')
        ->join('s.customerOrder','co')
        ->join('co.orderitems','oi')
        ->andWhere('oi.product=:product')
        ->setParameter('product', $product)
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
