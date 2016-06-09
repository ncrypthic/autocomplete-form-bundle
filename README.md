Installation
============

Step 1: Download the Bundle
---------------------------

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```bash
$ composer require lla/autocomplete-form-bundle "~1"
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Step 2: Enable the Bundle
-------------------------

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...

            new LLA\AutocompleteFormBundle\LLAAutocompleteFormBundle(),
        );

        // ...
    }

    // ...
}
```

Step 3: Use the form type
-------------------------

You can use the form as normal form usage. See [FormComponent](http://symfony.com/doc/current/components/form/introduction.html)

```php
<?php
// src/AppBundle/Controller/TestController.php

// ...
class TestController extends Controller
{
    public function registerBundles()
    {
        // ...
        $form = $this->createForm('form_name', 'autocomplete', array(
            'class' => 'AppBundle:YourEntity',
            'max_result' => 10, // Initial options length
            'query_builder' => function(EntityRepository $repo) {
                return $repo->createQueryBuilder();
            }
        ));
```