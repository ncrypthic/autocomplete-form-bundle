<?php

namespace Ris\AutocompleteFormBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Ris\AutocompleteFormBundle\DependencyInjection\Compiler\FormThemePass;

class RisAutocompleteFormBundle extends Bundle
{
    public function build(\Symfony\Component\DependencyInjection\ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new FormThemePass());
    }
}
