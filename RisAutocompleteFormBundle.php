<?php

namespace LLA\AutocompleteFormBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use LLA\AutocompleteFormBundle\DependencyInjection\Compiler\FormThemePass;

class RisAutocompleteFormBundle extends Bundle
{
    public function build(\Symfony\Component\DependencyInjection\ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new FormThemePass());
    }
}
