<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Ris\AutocompleteFormBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityRepository;
use Ris\AutocompleteFormBundle\Entity\Test;

/**
 * Default controller
 *
 * @author Lim Afriyadi <lim.afriyadi@rajawalisoftware.com>
 */
class DefaultController extends Controller
{
    public function indexAction() 
    {
        $entity = new Test();
        $form = $this->createTestForm();
        
        return $this->render('RisAutocompleteFormBundle:Default:form.html.twig', 
            array(
                'form' => $form->createView()
            ));
    }
    
    public function submitAction(Request $req)
    {
        $selected = null;
        $form     = $this->createTestForm(null);
        $form->handleRequest($req);
        if($form->isValid()) {
            return $this->render('RisAutocompleteFormBundle:Default:form.html.twig', 
                array(
                    'form' => $form->createView(),
                    'selected' => $form->get('test')
                ));
        }
        return $this->render('RisAutocompleteFormBundle:Default:form.html.twig', 
            array(
                'form' => $form->createView()
            ));
    }
    
    private function createTestForm() 
    {
        return $this->createFormBuilder()->add('test', 'autocomplete', array(
            'class' => 'Ris\AutocompleteFormBundle\Entity\Test',
            'choice_label' => function(Test $test) {
                return $test->getName();
            },
            'max_results' => 1,
            'query_builder' => function(EntityRepository $repo) {
                return $repo->createQueryBuilder('q');
            },
            'widget' => 'text'
        ))->getForm();
    }
}