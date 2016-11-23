<?php
/**
 * Created by PhpStorm.
 * User: Nemchus
 * Date: 11/18/2016
 * Time: 11:58 PM
 */


namespace Drupal\test\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class ContactForm extends FormBase
{

    public function getFormId() {
        return 'test_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['contact_firstname'] = array(
            '#type' => 'textfield',
            '#title' => t('First Name:'),
            '#attributes' => array(
                'placeholder' => t('John'),
            ),
            '#required' => TRUE,
        );
        $form['contact_lastname'] = array(
            '#type' => 'textfield',
            '#title' => t('Last Name:'),
            '#attributes' => array(
                'placeholder' => t('Doe'),
            ),
            '#required' => TRUE,
        );
        $form['contact_mail'] = array(
            '#type' => 'email',
            '#title' => t('Email:'),
            '#attributes' => array(
                'placeholder' => t('johndoe@example.com'),
            ),
            '#required' => TRUE,
        );

        $form['contact_number'] = array(
            '#type' => 'tel',
            '#title' => t('Telephone number:'),
            '#maxlength' => 15,
            '#attributes' => array(
                'placeholder' => t('Example: 00381691234567'),
            ),
        );
        $form['contact_address'] = array(
            '#type' => 'textfield',
            '#title' => t('Address:'),
            '#attributes' => array(
                'placeholder' => t('Niš'),
            ),
        );
        $form['contact_message'] = array(
            '#type' => 'textarea',
            '#title' => t('Message:'),
            '#attributes' => array(
                'placeholder' => t('Hi, I\'m John Doe'),
            ),
        );

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Save'),
            '#button_type' => 'primary',
        );
        return $form;
    }


    public function validateForm(array &$form, FormStateInterface $form_state) {
        $contact_number = $form_state->getValue('contact_number');

        if (!preg_match('/^[a-z ]+$/i',$form_state->getValue('contact_firstname'))) {
            $form_state->setErrorByName('contact_firstname', $this->t('Your first name contains invalid characters.'));
        }
        if (!preg_match('/^[a-z ]+$/i',$form_state->getValue('contact_lastname'))) {
            $form_state->setErrorByName('contact_lastname', $this->t('Your last name contains invalid characters.'));
        }

        if(!preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/',$form_state->getValue('contact_mail'))) {
            $form_state->setErrorByName('contact_mail', $this->t('Email address is invalid.'));
        }
//      Opcionalno
//         if(filter_var($form_state->getValue('contact_mail'), FILTER_VALIDATE_EMAIL) === false) {
//             $form_state->setErrorByName('contact_mail', $this->t('Email address is invalid.'));
//         }
        if (!ctype_digit($contact_number)) {
            $form_state->setErrorByName('contact_number', $this->t('Telephone number must be numeric only.'));
        }
        if (strlen($contact_number) <= 12) {
            $form_state->setErrorByName('contact_number', $this->t('Telephone number must be minimum 12 characters.'));
        }

//      Opcionalno
        if(substr($contact_number, 0, 2) != "00") {
            $form_state->setErrorByName('contact_number', $this->t('Telephone number must start with 00.'));
        }

    }

    public function get_location($form_address) {

        // url encode the address
        $address = urlencode($form_address);

        // google map geocode api url
        $url = "http://maps.google.com/maps/api/geocode/json?address={$address}";

        // get the json response
        $resp_json = file_get_contents($url);

        // decode the json
        $resp = json_decode($resp_json, true);

        // response status will be 'OK', if able to geocode given address
        if ($resp['status'] == 'OK') {

            // get the important data
            $lati = $resp['results'][0]['geometry']['location']['lat'];
            $longi = $resp['results'][0]['geometry']['location']['lng'];
            $formatted_address = $resp['results'][0]['formatted_address'];
//            drupal_set_message($lati . ': ' . $longi . $formatted_address);

//             verify if data is complete
            if ($lati && $longi && $formatted_address) {

                // put the data in the array
                $data_arr = array();
                $data_arr['longitude'] = $longi;
                $data_arr['latitude'] = $lati;
                $data_arr['address'] = $formatted_address;
                return $data_arr;

            } else {
                return false;
            }

        } else {
            return false;
        }
    }

    public function sendMail($data_array, FormStateInterface $form_state ) {

        $mailManager = \Drupal::service('plugin.manager.mail');

        $module = 'dmail';
        $key = 'contact_sent';
        $to = 'mitrovic_n7@yahoo.com';
        $params['name'] = $form_state->getValue('contact_firstname')." ".$form_state->getValue('contact_lastname');
        $params['email'] = $form_state->getValue('contact_mail');
        $params['number'] = $form_state->getValue('contact_number');
        $params['address'] = $data_array['address']. " (longitude: ".$data_array['longitude'].", latitude: ".$data_array['latitude'].')';
        $params['message'] = $form_state->getValue('contact_message');
        $langcode = "en";
        $send = true;

        $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
        if ( ! $result['result']) {
            $message = t('There was a problem sending your email notification.');
            drupal_set_message($message, 'error');
            \Drupal::logger('dmail')->error($message);
            return;
        }
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {

        $data_array = $this->get_location($form_state->getValue("contact_address"));
        $this->sendMail($data_array, $form_state);
         drupal_set_message($this->t('@contact_name , Form is being submitted!', array('@contact_name' => $form_state->getValue('contact_firstname'))));
//        foreach ($form_state->getValues() as $key => $value) {
//            drupal_set_message($key . ': ' . $value);
//
//        }

    }
}