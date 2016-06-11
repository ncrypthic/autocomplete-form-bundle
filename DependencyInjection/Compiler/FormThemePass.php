<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LLA\AutocompleteFormBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Description of FormThemePass
 *
 * @author Lim Afriyadi <lim.afriyadi@rajawalisoftware.com>
 */
class FormThemePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $defs = $container->getDefinition('twig');
        $resources = $container->getParameter('twig.form.resources');
        array_push($resources, 'LLAAutocompleteFormBundle:Form:autocomplete_form.html.twig');
        $container->setParameter('twig.form.resources', $resources);
    }
}