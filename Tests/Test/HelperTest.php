<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Ris\AutocompleteFormBundle\Tests\Test;

use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use Doctrine\ORM\EntityManager;
use LLA\AutocompleteFormBundle\Test\Helper;

/**
 * Description of HelperTest
 *
 * @author Lim Afriyadi <lim.afriyadi@rajawalisoftware.com>
 */
class HelperTest extends TestCase
{
    public function testCreateEntityManager()
    {
        $this->assertTrue(Helper::createTestEntityManager() instanceof EntityManager);
    }
}