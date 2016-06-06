<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Ris\AutocompleteFormBundle\Test;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\EntityManager;

/**
 * Provides utility functions needed in tests.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class Helper
{
    /**
     * Returns an entity manager for testing.
     *
     * @return EntityManager
     */
    public static function createTestEntityManager()
    {
        if (!extension_loaded('pdo_sqlite')) {
            \PHPUnit_Framework_TestCase::markTestSkipped('Extension pdo_sqlite is required.');
        }

        $config = new \Doctrine\ORM\Configuration();
        $config->setEntityNamespaces(array(
            'RisAutocompleteFormBundleTestsDoctrine' => 'Ris\AutocompleteFormBundle\Tests\Entity'
        ));
        $config->setAutoGenerateProxyClasses(true);
        $config->setProxyDir(\sys_get_temp_dir());
        $config->setProxyNamespace('Ris\AutocompleteFormBundle\Tests\Doctrine');
        $config->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));
        $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ArrayCache());
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache());

        $params = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        return EntityManager::create($params, $config);
    }

    /**
     * This class cannot be instantiated.
     */
    private function __construct()
    {
    }
}
