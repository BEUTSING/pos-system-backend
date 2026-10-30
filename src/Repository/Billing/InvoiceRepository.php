<?php

namespace App\Repository\Billing;

use App\Entity\Billing\Invoice;
use App\Entity\Checkout\Sale;
use App\Entity\Company\Company;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Invoice>
 */
class InvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    /** @return Invoice[] */
    public function findForCompany(Company $company): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.company = :company')
            ->setParameter('company', $company)
            ->orderBy('i.issuedAt', 'DESC')
            ->addOrderBy('i.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneForCompany(int $id, Company $company): ?Invoice
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.id = :id')
            ->andWhere('i.company = :company')
            ->setParameter('id', $id)
            ->setParameter('company', $company)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneForSale(Sale $sale, Company $company): ?Invoice
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.sale = :sale')
            ->andWhere('i.company = :company')
            ->setParameter('sale', $sale)
            ->setParameter('company', $company)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countForCompanyAndYear(Company $company, int $year): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('i.company = :company')
            ->andWhere('i.issuedAt >= :start')
            ->andWhere('i.issuedAt < :end')
            ->setParameter('company', $company)
            ->setParameter('start', new \DateTimeImmutable(sprintf('%d-01-01 00:00:00', $year)))
            ->setParameter('end', new \DateTimeImmutable(sprintf('%d-01-01 00:00:00', $year + 1)))
            ->getQuery()
            ->getSingleScalarResult();
    }
}
