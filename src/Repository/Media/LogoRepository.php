<?php

namespace App\Repository\Media;

use App\Entity\Checkout\Customer;
use App\Entity\Company\Company;
use App\Entity\Media\Logo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Logo>
 */
class LogoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Logo::class);
    }

    /** @return Logo[] */
    public function findForCompany(Company $company): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.company = :company')
            ->setParameter('company', $company)
            ->orderBy('l.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return Logo[] */
    public function findForCustomer(Customer $customer): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.customer = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('l.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
