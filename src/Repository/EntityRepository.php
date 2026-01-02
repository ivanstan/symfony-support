<?php

namespace Ivanstan\SymfonySupport\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Ivanstan\SymfonySupport\Services\ApiEntityMetadata;

class EntityRepository extends ServiceEntityRepository
{
    protected const SEARCH_QUERY_PARAM = 'search';

    protected ?ApiEntityMetadata $meta = null;

    private string $entityClassName;

    public function __construct(ManagerRegistry $registry, string $entityClass)
    {
        parent::__construct($registry, $entityClass);
        $this->entityClassName = $entityClass;
    }

    protected function getMeta(): ApiEntityMetadata
    {
        if ($this->meta === null) {
            $this->meta = new ApiEntityMetadata($this->getEntityManager()->getClassMetadata($this->entityClassName));
        }

        return $this->meta;
    }

    public function get(string $entity, string $id)
    {
        $builder = $this->getEntityManager()->createQueryBuilder();
        $identifier = $this->getMeta()->getIdentifier();
        $alias = $this->getMeta()->getAlias();

        $builder->select($alias);
        $builder->from($entity, $alias);

        $builder
            ->where("$alias.$identifier = :id")
            ->setParameter('id', $id);

        return $builder->getQuery()->getOneOrNullResult();
    }

    public function search(QueryBuilder $builder, array $fields, ?string $query): QueryBuilder
    {
        if ($query === null || empty($query)) {
            return $builder;
        }

        $conditions = array_map(
            static fn($field) => \sprintf('%s LIKE :%s', $field, self::SEARCH_QUERY_PARAM),
            $fields
        );

        $orX = $builder->expr()->orX();
        $orX->addMultiple($conditions);

        $builder->where($orX);

        $builder->setParameter(self::SEARCH_QUERY_PARAM, '%'.$query.'%');

        return $builder;
    }
}
