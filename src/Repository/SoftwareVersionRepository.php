<?php

namespace App\Repository;

use App\Entity\SoftwareVersion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for SoftwareVersion entity.
 *
 * Provides custom query methods for firmware version lookup, grouping,
 * and management operations used by both the API and admin panel.
 *
 * @extends ServiceEntityRepository<SoftwareVersion>
 */
class SoftwareVersionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SoftwareVersion::class);
    }

    /**
     * Find software versions by their alternative version string (case-insensitive).
     *
     * This is the primary lookup method used by the API when a customer
     * submits their current software version. The match is case-insensitive
     * because users may enter versions in any case.
     *
     * @param string $versionAlt The version string without the 'v' prefix
     * @return SoftwareVersion[] Array of matching versions (may span multiple products)
     */
    public function findByVersionAlt(string $versionAlt): array
    {
        return $this->createQueryBuilder('sv')
            ->where('LOWER(sv.systemVersionAlt) = LOWER(:version)')
            ->setParameter('version', $versionAlt)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retrieve all software versions grouped by product name.
     *
     * Used by the admin panel to display versions organized by product.
     * Results are sorted by product name (ascending) and then by ID (ascending)
     * to maintain insertion order within each group.
     *
     * @return array<string, SoftwareVersion[]> Associative array keyed by product name
     */
    public function findAllGroupedByName(): array
    {
        $versions = $this->createQueryBuilder('sv')
            ->orderBy('sv.name', 'ASC')
            ->addOrderBy('sv.isLatest', 'DESC')
            ->addOrderBy('sv.id', 'DESC')
            ->getQuery()
            ->getResult();

        $grouped = [];
        foreach ($versions as $version) {
            $grouped[$version->getName()][] = $version;
        }

        return $grouped;
    }

    /**
     * Find any version marked as "latest" within a hardware family.
     *
     * Hardware families are:
     *   - Standard: product names that do NOT start with "LCI"
     *   - LCI: product names that start with "LCI"
     *
     * This is used to dynamically determine the latest version label
     * shown to customers (e.g., "The latest version of software is v3.3.7").
     *
     * @param bool $isLCI Whether to search in the LCI family (true) or standard (false)
     * @return SoftwareVersion|null The latest version entry, or null if none found
     */
    public function findLatestInFamily(bool $isLCI): ?SoftwareVersion
    {
        $qb = $this->createQueryBuilder('sv')
            ->where('sv.isLatest = true');

        if ($isLCI) {
            $qb->andWhere('sv.name LIKE :prefix')
               ->setParameter('prefix', 'LCI%');
        } else {
            $qb->andWhere('sv.name NOT LIKE :prefix')
               ->setParameter('prefix', 'LCI%');
        }

        return $qb->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Unmark all "latest" flags for versions with the given product name.
     *
     * Called before marking a new version as "latest" to ensure only one
     * version per product is flagged as the current/latest release.
     *
     * @param string $productName The exact product name (e.g., "MMI Prime CIC")
     */
    public function unmarkLatestForProduct(string $productName): void
    {
        $this->createQueryBuilder('sv')
            ->update()
            ->set('sv.isLatest', 'false')
            ->where('sv.name = :name')
            ->andWhere('sv.isLatest = true')
            ->setParameter('name', $productName)
            ->getQuery()
            ->execute();
    }

    /**
     * Find the most recently added version for a product.
     *
     * Used as a fallback when the current latest version is deleted,
     * so the newest remaining version can be promoted to latest.
     */
    public function findMostRecentByProduct(string $productName): ?SoftwareVersion
    {
        return $this->createQueryBuilder('sv')
            ->where('sv.name = :name')
            ->setParameter('name', $productName)
            ->orderBy('sv.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
