<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Ris\AutocompleteFormBundle\Form\Type\Autocomplete\Loader;

use Symfony\Bridge\Doctrine\Form\ChoiceList\EntityLoaderInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\DBAL\Connection;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * This class can be used by 'ChoiceType' form type to loads entity with 
 * specified query builder.
 * Courtesy of Symfony's DoctrineBridge
 *
 * @author Lim Afriyadi <lim.afriyadi.id@gmail.com>
 */
class ORMQueryBuilderLoader implements EntityLoaderInterface
{
    /**
     * Contains the query builder that builds the query for fetching the
     * entities.
     *
     * This property should only be accessed through queryBuilder.
     *
     * @var QueryBuilder
     */
    protected $queryBuilder;
    
    /**
     * Object manager
     * 
     * @var ObjectManager
     */
    private $manager;
    
    /**
     *
     * @var string;
     */
    private $className;
    
    /**
     * Construct an ORM Query Builder Loader.
     *
     * @param QueryBuilder|\Closure $queryBuilder The query builder or a closure
     *                                            for creating the query builder.
     *                                            Passing a closure is
     *                                            deprecated and will not be
     *                                            supported anymore as of
     *                                            Symfony 3.0.
     * @param ObjectManager         $manager      Deprecated.
     * @param string                $class        Deprecated.
     *
     * @throws UnexpectedTypeException
     */
    public function __construct(QueryBuilder $queryBuilder = null, 
            ObjectManager $manager = null, $class = null)
    {
        $this->manager   = $manager;
        $this->className = $class;
        if($queryBuilder) {
            $this->setQueryBuilder($queryBuilder);
        }
    }
    
    /**
     * Return current query builder
     * 
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }
    
    /**
     * Replace query builder with specified query builder
     * 
     * @param QueryBuilder $queryBuilder
     * @throws UnexpectedTypeException
     */
    public function setQueryBuilder(QueryBuilder $queryBuilder)
    {
        // If a query builder was passed, it must be a closure or QueryBuilder
        // instance
        if (!($queryBuilder instanceof QueryBuilder || $queryBuilder instanceof \Closure)) {
            throw new UnexpectedTypeException($queryBuilder, 'Doctrine\ORM\QueryBuilder or \Closure');
        }

        if ($queryBuilder instanceof \Closure) {
            @trigger_error('Passing a QueryBuilder closure to '.__CLASS__.'::__construct() is deprecated since version 2.7 and will be removed in 3.0.', E_USER_DEPRECATED);

            if (!$this->manager instanceof ObjectManager) {
                throw new UnexpectedTypeException($this->manager, 'Doctrine\Common\Persistence\ObjectManager');
            }

            @trigger_error('Passing an EntityManager to '.__CLASS__.'::__construct() is deprecated since version 2.7 and will be removed in 3.0.', E_USER_DEPRECATED);
            @trigger_error('Passing a class to '.__CLASS__.'::__construct() is deprecated since version 2.7 and will be removed in 3.0.', E_USER_DEPRECATED);

            $queryBuilder = $queryBuilder($this->manager->getRepository($this->className));

            if (!$queryBuilder instanceof QueryBuilder) {
                throw new UnexpectedTypeException($queryBuilder, 'Doctrine\ORM\QueryBuilder');
            }
        }

        $this->queryBuilder = $queryBuilder;
        
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getEntities()
    {
        return $this->queryBuilder->getQuery()->execute();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getEntitiesByIds($identifier, array $values)
    {
        $qb = clone $this->queryBuilder;
        $alias = current($qb->getRootAliases());
        $parameter = 'ORMQueryBuilderLoader_getEntitiesByIds_'.$identifier;
        $parameter = str_replace('.', '_', $parameter);
        $where = $qb->expr()->in($alias.'.'.$identifier, ':'.$parameter);

        // Guess type
        $entity = current($qb->getRootEntities());
        $metadata = $qb->getEntityManager()->getClassMetadata($entity);
        if (in_array($metadata->getTypeOfField($identifier), array('integer', 'bigint', 'smallint'))) {
            $parameterType = Connection::PARAM_INT_ARRAY;

            // Filter out non-integer values (e.g. ""). If we don't, some
            // databases such as PostgreSQL fail.
            $values = array_values(array_filter($values, function ($v) {
                return (string) $v === (string) (int) $v;
            }));
        } else {
            $parameterType = Connection::PARAM_STR_ARRAY;
        }
        if (!$values) {
            return array();
        }

        return $qb->andWhere($where)
                  ->getQuery()
                  ->setParameter($parameter, $values, $parameterType)
                  ->getResult();
    }
}