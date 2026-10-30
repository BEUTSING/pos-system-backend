<?php

namespace App\Repository\Product;

use App\Entity\Product\ProductMedia;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductMedia>
 */
class ProductMediaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductMedia::class);
    }

    /** @return ProductMedia[] */
    public function findForProduct(int $productId): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.product = :product')
            ->setParameter('product', $productId)
            ->orderBy('m.isPrimary', 'DESC')
            ->addOrderBy('m.position', 'ASC')
            ->addOrderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
