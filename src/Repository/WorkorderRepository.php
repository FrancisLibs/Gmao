<?php

namespace App\Repository;

use App\Data\SearchPreventive;
use App\Entity\Workorder;
use App\Data\SearchWorkorder;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

class WorkorderRepository extends ServiceEntityRepository
{
    private $paginator;

    public function __construct(ManagerRegistry $registry, PaginatorInterface $paginator)
    {
        parent::__construct($registry, Workorder::class);
        $this->paginator = $paginator;
    }

    /**
     * Récupère les bons de travail de la page d'accueil
     *
     * @param SearchWorkorder $search
     * @return PaginationInterface
     */
    public function findAllWorkorders($organisation)
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.organisation = :val')
            ->setParameter('val', $organisation)
            ->andWhere('w.template = :enabled')
            ->setParameter('enabled', false)
            ->orderBy('w.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les bons de travail liés à une recherche
     *
     * @param SearchWorkorder $search
     * @return PaginationInterface
     */
    public function findSearch(SearchWorkorder $search): PaginationInterface
    {
        $query = $this->createQueryBuilder('w')
            ->orderBy('w.createdAt', 'ASC')
            ->select('w', 'm', 'u', 'o', 's')
            ->join('w.machines', 'm')
            ->join('w.user', 'u')
            ->join('w.organisation', 'o')
            ->join('w.workorderStatus', 's')
            ->andWhere('w.organisation = :val')
            ->setParameter('val', $search->organisation)
            ->andWhere('w.template = :disabled')
            ->setParameter('disabled', false)
        ;

        if (!empty($search->machine)) {
            $query = $query
                ->andWhere('m.designation LIKE :machine')
                ->setParameter('machine', "%{$search->machine}%");
        }

        if (!empty($search->user)) {
            $query = $query
                ->andWhere('u.id = :user')
                ->setParameter('user', $search->user);
        }

        if (!empty($search->status)) {
            $query = $query
                ->andWhere('s.id = :status')
                ->setParameter('status', $search->status);
        }

        $query = $query->getQuery();

        return $this->paginator->paginate(
            $query,
            $search->page,
            15
        );
    }

    /**
     * @return Workorder[] Returns an array of workorder
     */

    public function findByOrganisation($organisation)
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.organisation = :val')
            ->setParameter('val', $organisation)
            ->orderBy('w.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les bons de travail préventifs liés à une recherche
     *
     * @param SearchWorkorder $search
     * @return PaginationInterface
     */
    public function findPreventiveWorkorders(SearchPreventive $search): PaginationInterface
    {
        $query = $this->createQueryBuilder('w')
            ->orderBy('w.createdAt', 'ASC')
            ->select('w', 'm')
            ->join('w.machines', 'm')
            ->andWhere('w.organisation = :val')
            ->setParameter('val', $search->organisation)
            ->andWhere('w.preventive = :enabled')
            ->setParameter('enabled', true);

        if (!empty($search->machine)) {
            $query = $query
                ->andWhere('m.designation LIKE :machine')
                ->setParameter('machine', "%{$search->machine}%");
        }

        $query = $query->getQuery();

        return $this->paginator->paginate(
            $query,
            $search->page,
            15
        );
    }
}