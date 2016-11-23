<?php
/**
 * Created by PhpStorm.
 * User: Nemchus
 * Date: 11/19/2016
 * Time: 1:05 AM
 */

namespace Drupal\hello_world\Controller;

use Drupal\Core\Controller\ControllerBase;

class HelloController extends ControllerBase {

//    public function content() {
//
//        return array(
//            '#type' => 'markup',
//            '#markup' => $this->t('Hello, World!'),
//        );
//    }

    public function content() {
        $form = \Drupal::formBuilder()->getForm('Drupal\test\Form\ContactForm');
        \Drupal::service('renderer')->render($form);
        return $form;
    }

}
