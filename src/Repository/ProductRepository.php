<?php

namespace App\Repository;

use App\Entity\Product;
use App\Service\ElasticSearchClient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly ElasticSearchClient $elasticSearchClient,
    ) {
        parent::__construct($registry, Product::class);
    }

    public function findByFilters(array $filters): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p');

        foreach ($filters as $attributeName => $selectedValues) {
            if (!empty($selectedValues)) {
                $alias = 'pa_' . \str_replace(' ', '_', $attributeName);

                $qb->leftJoin('p.productAttributes', $alias)->addSelect($alias)
                    ->andWhere(sprintf('%s.attribute_name = :attributeName_%s', $alias, $attributeName))
                    ->andWhere(sprintf('%s.attribute_value IN (:attributeValues_%s)', $alias, $attributeName))
                    ->setParameter('attributeName_' . $attributeName, \str_replace('_', ' ', $attributeName))
                    ->setParameter('attributeValues_' . $attributeName, $selectedValues);
            }
        }

        return $qb;
    }

    public function findDistinctAttributeValues(string $attributeName): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('DISTINCT pa.attribute_value')
            ->leftJoin('p.productAttributes', 'pa')
            ->where('pa.attribute_name = :attributeName')
            ->setParameter('attributeName', $attributeName)
            ->orderBy('pa.attribute_value', 'ASC');

        return \array_column($qb->getQuery()->getArrayResult(), 'attribute_value');
    }

    public function searchByNameOrEan(string $nameOrEan): QueryBuilder
    {
        $params = [
            'index' => 'products',
            'body'  => [
                'size' => 15,
                'query' => [
                    'bool' => [
                        'should' => [
                            ['match' => ['name' => $nameOrEan]],
                            ['match' => ['ean' => $nameOrEan]],
                        ],
                        'minimum_should_match' => 1,
                    ],
                ],
            ],
        ];

        $elasticResults = $this->elasticSearchClient->search($params)['hits']['hits'];
        $productIds = \array_column($elasticResults, '_id');

        return $this->createQueryBuilder('p')
            ->where('p.id IN (:productIds)')
            ->setParameter('productIds', $productIds);
    }
}
