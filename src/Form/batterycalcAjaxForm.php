<?php

namespace Drupal\batterycalc\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\AfterCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Database\Database;
use Drupal\batterycalc\Form\CycleData;

define("gravity", 9.81);

/**
 * Provides a battery pack calculation form.
 */
class batterycalcAjaxForm extends FormBase {

/**
 * {@inheritdoc}
 */
 public function getFormId() {
    return 'batterycalc_ajax';
 }
 
 /**
  * {@inheritdoc}
  */
 public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attributes'] = ['class' => 'container',];
    $form['Batt_Title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Project Title'),
        '#required' => TRUE,
        '#default_value' => 'Project 001',
        '#suffix' => '<div class="error" id = "batt_title"></div>'
    ];
    $form['information'] = [
        '#type' => 'vertical_tabs',
        '#default_tab' => 'edit-powertrain',
      ];
    $form['powertrain'] = [
        '#type' => 'details',
        '#title' => $this->t('Powertrain'),
        '#group' => 'information',
    ];
    $form['vehicle'] = [
        '#type' => 'details',
        '#title' => $this->t('Vehicle'),
        '#group' => 'information',
      ];
    $form['environment'] = [
        '#type' => 'details',
        '#title' => $this->t('Environment Conditions'),
        '#group' => 'information',
    ];
    // $form['powertrain']['Plot'] = [
    //     '#type' => 'markup',
    //     '#markup' => '<canvas id="line-chart" width="800" height="450"></canvas>',
    //     '#allowed_tags' => ['canvas', 'button','span','svg', 'path'],  
    // ];
    $form['Plotly'] =  [
        '#type' => 'markup',
        '#markup' => '<div id = "myDiv"></div>',
         '#allowed_tags' => ['canvas', 'button','span','svg', 'path', 'div'],  
      ];
    $form['Speed_Info_Container'] = [
        '#type' => 'container',
        '#markup' => '<div id="speed_info" class="alert alert-primary"></div>',
        // <i>This drive cycle has a minimum speed of 0 km/h, a maximum speed of 131.3 km/h, an average speed of 48.6 km/h, and a total distance of 22.6 km</i>
    ];

    $cycle_options = array_map(function($key) {
        return $key . ' -  ' . CycleData::$cycleData[$key]['title'];
     }, array_keys(CycleData::$cycleData));

    $form['Cycle_Select'] = [
        '#type' => 'select',
        '#title' => $this->t('Select predefined test cycle or select "Custom Cycle" to define your own'),
        '#description' => $this->t("note: predefined cycles are editable"),
        '#options' => array_combine(array_keys(CycleData::$cycleData),$cycle_options),
        '#default_value' => 'WLTC3b',  
        '#ajax' => [
            'callback' => '::selectCycle',
            'event' => 'change',
            'wrapper' => 'Speed_Container',
            'effect' => 'fade',
        ]        
    ];

    $form['Speed_Container'] = [
        '#type' => 'container',
        '#attributes' =>  ['id' => 'Speed_Container'],
    ];

    $form['Speed_Container']['vehicle_Speed'] = [ // expect speed in km/h
        '#type' => 'textarea',
        '#title' => $this->t('Speed Profile [km/h] (comma seperated values, 1s sample time)'),
        '#required' => TRUE,
        '#default_value' =>  implode(',',CycleData::$cycleData['WLTC3b']['data']),
        // '#default_value' => '0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.2,1.7,5.4,9.9,13.1,16.9,21.7,26.0,27.5,28.1,28.3,28.8,29.1,30.8,31.9,34.1,36.6,39.1,41.3,42.5,43.3,43.9,44.4,44.5,44.2,42.7,39.9,37.0,34.6,32.3,29.0,25.1,22.2,20.9,20.4,19.5,18.4,17.8,17.8,17.4,15.7,13.1,12.1,12.0,12.0,12.0,12.3,12.6,14.7,15.3,15.9,16.2,17.1,17.8,18.1,18.4,20.3,23.2,26.5,29.8,32.6,34.4,35.5,36.4,37.4,38.5,39.3,39.5,39.0,38.5,37.3,37.0,36.7,35.9,35.3,34.6,34.2,31.9,27.3,22.0,17.0,14.2,12.0,9.1,5.8,3.6,2.2,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.2,1.9,6.1,11.7,16.4,18.9,19.9,20.8,22.8,25.4,27.7,29.2,29.8,29.4,27.2,22.6,17.3,13.3,12.0,12.6,14.1,17.2,20.1,23.4,25.5,27.6,29.5,31.1,32.1,33.2,35.2,37.2,38.0,37.4,35.1,31.0,27.1,25.3,25.1,25.9,27.8,29.2,29.6,29.5,29.2,28.3,26.1,23.6,21.0,18.9,17.1,15.7,14.5,13.7,12.9,12.5,12.2,12.0,12.0,12.0,12.0,12.5,13.0,14.0,15.0,16.5,19.0,21.2,23.8,26.9,29.6,32.0,35.2,37.5,39.2,40.5,41.6,43.1,45.0,47.1,49.0,50.6,51.8,52.7,53.1,53.5,53.8,54.2,54.8,55.3,55.8,56.2,56.5,56.5,56.2,54.9,52.9,51.0,49.8,49.2,48.4,46.9,44.3,41.5,39.5,37.0,34.6,32.3,29.0,25.1,22.2,20.9,20.4,19.5,18.4,17.8,17.8,17.4,15.7,14.5,15.4,17.9,20.6,23.2,25.7,28.7,32.5,36.1,39.0,40.8,42.9,44.4,45.9,46.0,45.6,45.3,43.7,40.8,38.0,34.4,30.9,25.5,21.4,20.2,22.9,26.6,30.2,34.1,37.4,40.7,44.0,47.3,49.2,49.8,49.2,48.1,47.3,46.8,46.7,46.8,47.1,47.3,47.3,47.1,46.6,45.8,44.8,43.3,41.8,40.8,40.3,40.1,39.7,39.2,38.5,37.4,36.0,34.4,33.0,31.7,30.0,28.0,26.1,25.6,24.9,24.9,24.3,23.9,23.9,23.6,23.3,20.5,17.5,16.9,16.7,15.9,15.6,15.0,14.5,14.3,14.5,15.4,17.8,21.1,24.1,25.0,25.3,25.5,26.4,26.6,27.1,27.7,28.1,28.2,28.1,28.0,27.9,27.9,28.1,28.2,28.0,26.9,25.0,23.2,21.9,21.1,20.7,20.7,20.8,21.2,22.1,23.5,24.3,24.5,23.8,21.3,17.7,14.4,11.9,10.2,8.9,8.0,7.2,6.1,4.9,3.7,2.3,0.9,0.0,0.0,0.0,0.0,0.0,0.0,0.5,2.1,4.8,8.3,12.3,16.6,20.9,24.2,25.6,25.6,24.9,23.3,21.6,20.2,18.7,17.0,15.3,14.2,13.9,14.0,14.2,14.5,14.9,15.9,17.4,18.7,19.1,18.8,17.6,16.6,16.2,16.4,17.2,19.1,22.6,27.4,31.6,33.4,33.5,32.8,31.9,31.3,31.1,30.6,29.2,26.7,23.0,18.2,12.9,7.7,3.8,1.3,0.2,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.5,2.5,6.6,11.8,16.8,20.5,21.9,21.9,21.3,20.3,19.2,17.8,15.5,11.9,7.6,4.0,2.0,1.0,0.0,0.0,0.0,0.2,1.2,3.2,5.2,8.2,13.0,18.8,23.1,24.5,24.5,24.3,23.6,22.3,20.1,18.5,17.2,16.3,15.4,14.7,14.3,13.7,13.3,13.1,13.1,13.3,13.8,14.5,16.5,17.0,17.0,17.0,15.4,10.1,4.8,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,1.0,2.1,4.8,9.1,14.2,19.8,25.5,30.5,34.8,38.8,42.9,46.4,48.3,48.7,48.5,48.4,48.2,47.8,47.0,45.9,44.9,44.4,44.3,44.5,45.1,45.7,46.0,46.0,46.0,46.1,46.7,47.7,48.9,50.3,51.6,52.6,53.0,53.0,52.9,52.7,52.6,53.1,54.3,55.2,55.5,55.9,56.3,56.7,56.9,56.8,56.0,54.2,52.1,50.1,47.2,43.2,39.2,36.5,34.3,31.0,26.0,20.7,15.4,13.1,12.0,12.5,14.0,19.0,23.2,28.0,32.0,34.0,36.0,38.0,40.0,40.3,40.5,39.0,35.7,31.8,27.1,22.8,21.1,18.9,18.9,21.3,23.9,25.9,28.4,30.3,30.9,31.1,31.8,32.7,33.2,32.4,28.3,25.8,23.1,21.8,21.2,21.0,21.0,20.9,19.9,17.9,15.1,12.8,12.0,13.2,17.1,21.1,21.8,21.2,18.5,13.9,12.0,12.0,13.0,16.0,18.5,20.6,22.5,24.0,26.6,29.9,34.8,37.8,40.2,41.6,41.9,42.0,42.2,42.4,42.7,43.1,43.7,44.0,44.1,45.3,46.4,47.2,47.3,47.4,47.4,47.5,47.9,48.6,49.4,49.8,49.8,49.7,49.3,48.5,47.6,46.3,43.7,39.3,34.1,29.0,23.7,18.4,14.3,12.0,12.8,16.0,19.1,22.4,25.6,30.1,35.3,39.9,44.5,47.5,50.9,54.1,56.3,58.1,59.8,61.1,62.1,62.8,63.3,63.6,64.0,64.7,65.2,65.3,65.3,65.4,65.7,66.0,65.6,63.5,59.7,54.6,49.3,44.9,42.3,41.4,41.3,42.1,44.7,48.4,51.4,52.7,53.0,52.5,51.3,49.7,47.4,43.7,39.7,35.5,31.1,26.3,21.9,18.0,17.0,18.0,21.4,24.8,27.9,30.8,33.0,35.1,37.1,38.9,41.4,44.0,46.3,47.7,48.2,48.7,49.3,49.8,50.2,50.9,51.8,52.5,53.3,54.5,55.7,56.5,56.8,57.0,57.2,57.7,58.7,60.1,61.1,61.7,62.3,62.9,63.3,63.4,63.5,64.5,65.8,66.8,67.4,68.8,71.1,72.3,72.8,73.4,74.6,76.0,76.6,76.5,76.2,75.8,75.4,74.8,73.9,72.7,71.3,70.4,70.0,70.0,69.0,68.0,68.0,68.0,68.1,68.4,68.6,68.7,68.5,68.1,67.3,66.2,64.8,63.6,62.6,62.1,61.9,61.9,61.8,61.5,60.9,59.7,54.6,49.3,44.9,42.3,41.4,41.3,42.1,44.7,48.4,51.4,52.7,54.0,57.0,58.1,59.2,59.0,59.1,59.5,60.5,62.3,63.9,65.1,64.1,62.7,62.0,61.3,60.9,60.5,60.2,59.8,59.4,58.6,57.5,56.6,56.0,55.5,55.0,54.4,54.1,54.0,53.9,53.9,54.0,54.2,55.0,55.8,56.2,56.1,55.1,52.7,48.4,43.1,37.8,32.5,27.2,25.1,26.0,29.3,34.6,40.4,45.3,49.0,51.1,52.1,52.2,52.1,51.7,50.9,49.2,45.9,40.6,35.3,30.0,24.7,19.3,16.0,13.2,10.7,8.8,7.2,5.5,3.2,1.1,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.8,3.6,8.6,14.6,20.0,24.4,28.2,31.7,35.0,37.6,39.7,41.5,43.6,46.0,48.4,50.5,51.9,52.6,52.8,52.9,53.1,53.3,53.1,52.3,50.7,48.8,46.5,43.8,40.3,36.0,30.7,25.4,21.0,16.7,13.4,12.0,12.1,12.8,15.6,19.9,23.4,24.6,25.2,26.4,28.8,31.8,35.3,39.5,44.5,49.3,53.3,56.4,58.9,61.2,62.6,63.0,62.5,60.9,59.3,58.6,58.6,58.7,58.8,58.8,58.8,59.1,60.1,61.7,63.0,63.7,63.9,63.5,62.3,60.3,58.9,58.4,58.8,60.2,62.3,63.9,64.5,64.4,63.5,62.0,61.2,61.3,62.6,65.3,68.0,69.4,69.7,69.3,68.1,66.9,66.2,65.7,64.9,63.2,60.3,55.8,50.5,45.2,40.1,36.2,32.9,29.8,26.6,23.0,19.4,16.3,14.6,14.2,14.3,14.6,15.1,16.4,19.1,22.5,24.4,24.8,22.7,17.4,13.8,12.0,12.0,12.0,13.9,18.8,25.1,29.8,33.8,38.2,43.4,48.9,53.8,57.8,61.5,65.0,68.4,71.6,73.0,74.3,76.2,77.9,79.5,81.0,82.3,83.5,84.6,85.5,86.3,87.1,88.1,89.1,90.1,91.0,91.7,92.3,92.8,93.1,93.1,93.1,93.1,93.1,93.1,93.1,93.1,93.1,93.1,93.2,93.2,93.3,93.7,94.2,95.0,95.8,96.4,96.8,97.0,97.1,97.2,97.3,97.4,97.4,97.4,97.4,97.3,97.3,97.3,97.3,97.2,97.1,97.0,96.9,96.7,96.4,96.1,95.7,95.5,95.3,95.2,95.0,94.9,94.7,94.5,94.4,94.4,94.3,94.3,94.1,93.9,93.4,92.8,92.0,91.3,90.6,90.0,89.3,88.7,88.1,87.4,86.7,86.0,85.3,84.7,84.1,83.5,82.9,82.3,81.7,81.1,80.5,79.9,79.4,79.0,78.7,78.7,78.8,79.1,79.4,79.6,79.8,79.8,79.6,79.3,78.9,78.5,78.2,77.9,77.7,77.7,77.8,77.9,78.1,78.3,78.3,78.4,78.4,78.4,78.2,78.0,77.7,77.3,76.9,76.6,76.2,75.7,75.2,74.7,74.4,74.3,74.4,74.6,74.9,75.1,75.3,75.5,75.8,75.9,76.0,76.0,76.0,75.9,75.9,75.8,75.7,75.5,75.2,75.0,74.7,74.1,73.7,73.3,73.5,74.0,74.9,76.1,77.7,79.2,80.3,80.8,81.0,81.0,81.0,81.0,81.0,80.9,80.6,80.3,80.0,79.9,79.8,79.8,79.8,79.9,80.0,80.4,80.8,81.2,81.5,81.6,81.6,81.4,80.7,79.6,78.2,76.8,75.3,73.8,72.1,70.2,68.2,66.1,63.8,61.6,60.2,59.8,60.4,61.8,62.6,62.7,61.9,60.0,58.4,57.8,57.8,57.8,57.3,56.2,54.3,50.8,45.5,40.2,34.9,29.6,27.3,29.3,32.9,35.6,36.7,37.6,39.4,42.5,46.5,50.2,52.8,54.3,54.9,54.9,54.7,54.1,53.2,52.1,50.7,49.1,47.4,45.2,41.8,36.5,31.2,27.6,26.9,27.3,27.5,27.4,27.1,26.7,26.8,28.2,31.1,34.8,38.4,40.9,41.7,40.9,38.3,35.3,34.3,34.6,36.3,39.5,41.8,42.5,41.9,40.1,36.6,31.3,26.0,20.6,19.1,19.7,21.1,22.0,22.1,21.4,19.6,18.3,18.0,18.3,18.5,17.9,15.0,9.9,4.6,1.2,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,2.2,4.4,6.3,7.9,9.2,10.4,11.5,12.9,14.7,17.0,19.8,23.1,26.7,30.5,34.1,37.5,40.6,43.3,45.7,47.7,49.3,50.5,51.3,52.1,52.7,53.4,54.0,54.5,55.0,55.6,56.3,57.2,58.5,60.2,62.3,64.7,67.1,69.2,70.7,71.9,72.7,73.4,73.8,74.1,74.0,73.6,72.5,70.8,68.6,66.2,64.0,62.2,60.9,60.2,60.0,60.4,61.4,63.2,65.6,68.4,71.6,74.9,78.4,81.8,84.9,87.4,89.0,90.0,90.6,91.0,91.5,92.0,92.7,93.4,94.2,94.9,95.7,96.6,97.7,98.9,100.4,102.0,103.6,105.2,106.8,108.5,110.2,111.9,113.7,115.3,116.8,118.2,119.5,120.7,121.8,122.6,123.2,123.6,123.7,123.6,123.3,123.0,122.5,122.1,121.5,120.8,120.0,119.1,118.1,117.1,116.2,115.5,114.9,114.5,114.1,113.9,113.7,113.3,112.9,112.2,111.4,110.5,109.5,108.5,107.7,107.1,106.6,106.4,106.2,106.2,106.2,106.4,106.5,106.8,107.2,107.8,108.5,109.4,110.5,111.7,113.0,114.1,115.1,115.9,116.5,116.7,116.6,116.2,115.2,113.8,112.0,110.1,108.3,107.0,106.1,105.8,105.7,105.7,105.6,105.3,104.9,104.4,104.0,103.8,103.9,104.4,105.1,106.1,107.2,108.5,109.9,111.3,112.7,113.9,115.0,116.0,116.8,117.6,118.4,119.2,120.0,120.8,121.6,122.3,123.1,123.8,124.4,125.0,125.4,125.8,126.1,126.4,126.6,126.7,126.8,126.9,126.9,126.9,126.8,126.6,126.3,126.0,125.7,125.6,125.6,125.8,126.2,126.6,127.0,127.4,127.6,127.8,127.9,128.0,128.1,128.2,128.3,128.4,128.5,128.6,128.6,128.5,128.3,128.1,127.9,127.6,127.4,127.2,127.0,126.9,126.8,126.7,126.8,126.9,127.1,127.4,127.7,128.1,128.5,129.0,129.5,130.1,130.6,131.0,131.2,131.3,131.2,130.7,129.8,128.4,126.5,124.1,121.6,119.0,116.5,114.1,111.8,109.5,107.1,104.8,102.5,100.4,98.6,97.2,95.9,94.8,93.8,92.8,91.8,91.0,90.2,89.6,89.1,88.6,88.1,87.6,87.1,86.6,86.1,85.5,85.0,84.4,83.8,83.2,82.6,82.0,81.3,80.4,79.1,77.4,75.1,72.3,69.1,65.9,62.7,59.7,57.0,54.6,52.2,49.7,46.8,43.5,39.9,36.4,33.2,30.5,28.3,26.3,24.4,22.5,20.5,18.2,15.5,12.3,8.7,5.2,0.0,0.0,0.0,0.0,0.0,0.0',
        '#suffix' => '<div class="error" id="vehicle_speed_error"></div>',
        '#ajax' => [
            'callback' => '::calculateSpeedStatistics',
            'wrapper' => 'Speed_Info_Container',
            'disable-refocus' => FALSE,
            'event' => 'keyup',
            'effect' => 'fade',
        ],
    ];

    // An AJAX request calls the form builder function for every change.
    // We can change how we build the form based on $form_state.
    $value = $form_state->getValue('Cycle_Select');
    // The getValue() method returns NULL by default if the form element does
    // not exist. It won't exist yet if we're building it for the first time.
    if ($value !== NULL) {
        $form['Speed_Container']['vehicle_Speed']['#value'] =
        implode(',',CycleData::$cycleData[$value]['data']);
    }
    $form['environment']['Environment_Container'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Environment Conditions'),
    ];
    $form['environment']['Environment_Container']['Air_Density'] = [
        '#type' => 'number',
        '#step'=>0.001,
        '#title' => $this->t('Air Density [kg/m3]'),
		'#default_value' => 1.2,
        '#required' => TRUE,
        '#suffix' => '<div class="error" id = "air_density"></div>',
        '#ajax' => [
            'callback' => '::energy_per_km',
            'wrapper' => 'Results',
            'disable-refocus' => FALSE,
            'event' => 'change',
            'effect' => 'fade',
        ],
    ];
    $form['environment']['Environment_Container']['Road_Slope'] = [
        '#type' => 'number',
        '#step'=> 0.1,
        '#title' => $this->t('Road Slope [°]'),
        '#required' => TRUE,
		'#default_value' => 0.0,
        '#suffix' => '<div class="error" id = "road_slope"></div>',
        '#ajax' => [
            'callback' => '::energy_per_km',
            'wrapper' => 'Results',
            'disable-refocus' => FALSE,
            'event' => 'change',
            'effect' => 'fade',
        ],
    ];
    $form['vehicle']['Vehicle_Container'] = [
        '#type'=>'fieldset',
        '#title' => $this->t('Vechicle specification'),
    ];
    $form['vehicle']['Vehicle_Container']['vehicle_Mass'] = [
        '#type' => 'number',
        '#step'=> 0.1,
        '#title' => $this->t('vehicle Mass [kg]'),
		'#default_value' => 2000,
        '#required' => TRUE,
        '#suffix' => '<div class="error" id="vehicle_mass"></div>',
        '#ajax' => [
            'callback' => '::energy_per_km',
            'wrapper' => 'Results',
            'disable-refocus' => FALSE,
            'event' => 'change',
            'effect' => 'fade',
        ],
    ];
    $form['vehicle']['Vehicle_Container']['Frontal_Area'] = [
        '#type' => 'number',
        '#step'=>0.01,
        '#title' => $this->t('Frontal Area [m2]'),
		'#default_value' => 2.5,
        '#required' => TRUE,
        '#suffix' => '<div class="error" id = "frontal_area"></div>',
        '#ajax' => [
            'callback' => '::energy_per_km',
            'wrapper' => 'Results',
            'disable-refocus' => FALSE,
            'event' => 'change',
            'effect' => 'fade',
        ],
    ];
    $form['vehicle']['Vehicle_Container']['Wheel_Radius'] = [
        '#type' => 'number',
        '#step'=>0.001,
        '#title' => $this->t('Wheel Radius [m]'),
        '#required' => TRUE,
		'#default_value' => 0.372,
        '#suffix' => '<div class="error" id = "rolling_resitance"></div>',
        '#ajax' => [
            'callback' => '::energy_per_km',
            'wrapper' => 'Results',
            'disable-refocus' => FALSE,
            'event' => 'change',
            'effect' => 'fade',
        ],
    ];
    $form['vehicle']['Vehicle_Container']['Rolling_Resistance'] = [
        '#type' => 'number',
        '#step'=>0.001,
        '#title' => $this->t('Rolling Resistance [-]'),
        '#required' => TRUE,
		'#default_value' => 0.012,
        '#suffix' => '<div class="error" id = "rolling_resitance"></div>',
        '#ajax' => [
            'callback' => '::energy_per_km',
            'wrapper' => 'Results',
            'disable-refocus' => FALSE,
            'event' => 'change',
            'effect' => 'fade',
        ],
    ];
	$form['vehicle']['Vehicle_Container']['Drag_Coefficient'] = [
        '#type' => 'number',
        '#step'=> 0.01,
        '#title' => $this->t('Aerodynamic Drag [-]'),
        '#required' => TRUE,
		'#default_value' => 0.35,
        '#suffix' => '<div class="error" id = "drag_coefficient"></div>',
        '#ajax' => [
            'callback' => '::energy_per_km',
            'wrapper' => 'Results',
            'disable-refocus' => FALSE,
            'event' => 'change',
            'effect' => 'fade',
        ],
    ];
	$form['vehicle']['Vehicle_Container']['Vehicle_Range'] = [
        '#type' => 'number',
        '#step'=>1,
        '#default_value' => 500,
        '#title' => $this->t('vehicle Range [km]'),
        '#required' => TRUE,
        '#suffix' => '<div class="error" id="vehicle_range"></div>',
        '#ajax' => [
            'callback' => '::energy_per_km',
            'wrapper' => 'Results',
            'disable-refocus' => FALSE,
            'event' => 'change',
            'effect' => 'fade',
        ],
    ];
    $form['powertrain']['Powertrain_Container'] = [
        '#type' => 'fieldset',
        '#title' => 'powertrain Specifications',
    ];
    $form['powertrain']['Powertrain_Container']['Drive_Type'] = [
        '#type' => 'radios',
        '#title' => $this->t('drive type'),
        '#options' => [0 => $this->t('Front wheel drive'),
                       1 => $this->t('Rear wheel drive'),
                       2 => $this->t('All wheel drive')
                    ],
        '#default_value' => 0,
        '#ajax' => [
            'callback' => '::motorConfiguration',
            'event' => 'change',
            'wrapper' => 'motor-container',
            'effect' => 'fade',
        ]
    ];
    $form['powertrain']['Powertrain_Container']['Drive_Ratio_Differential'] = [
        '#type' => 'number',
        '#step' => 0.01,
        '#title' => $this->t('Differential gear ratio'),
        '#required' => TRUE,
        '#default_value' => 2.57,
        '#suffix' => '<div class="error" id = "drive_differential_ratio"></div>',
        '#ajax' => [
            'callback' => '::energy_per_km',
            'wrapper' => 'Results',
            'disable-refocus' => FALSE,
            'event' => 'change',
            'effect' => 'fade',
        ],
    ];
    $form['powertrain']['Powertrain_Container']['Drive_Ratio_Gear'] = [
        '#type' => 'number',
        '#step' => 0.01,
        '#title' => $this->t('Drive gear ratio'),
        '#required' => TRUE,
        '#default_value' => 1,
        '#suffix' => '<div class="error" id = "drive_ratio_gear"></div>',
        '#ajax' => [
            'callback' => '::energy_per_km',
            'wrapper' => 'Results',
            'disable-refocus' => FALSE,
            'event' => 'change',
            'effect' => 'fade',
        ],
    ];
    $form['powertrain']['Powertrain_Container']['Ancillary_Energy'] = [
        '#type' => 'number',
        '#step' => 0.1,
        '#title' => $this->t('Ancillaries [wh/km]'),
        '#required' => TRUE,
        '#default_value'=> 10,
        '#suffix' => '<div class="error" id = "ancillary_consumption"></div>',
        '#ajax' => [
            'callback' => '::energy_per_km',
            'wrapper' => 'Results',
            'disable-refocus' => FALSE,
            'event' => 'change',
            'effect' => 'fade',
        ],
    ];
    $form['powertrain']['Powertrain_Container']['Powertrain_Efficiency'] = [
        '#type' => 'number',
        '#step' => 0.01,
        '#title' => $this->t('powertrain Efficiency [-]'),
        '#required' => TRUE,
        '#default_value'=> 0.9,
        '#suffix' => '<div class="error" id = "powertrain_efficiency"></div>',
        '#ajax' => [
            'callback' => '::energy_per_km',
            'wrapper' => 'Results',
            'disable-refocus' => FALSE,
            'event' => 'change',
            'effect' => 'fade',
        ],
    ];
    $form['powertrain']['Powertrain_Container']['Motor'] = [
        '#type' => 'container',
        '#title' => 'motor power specification(s)',
        '#attributes' => ['id' => 'motor-container',],
    ];
    switch ($form_state->getValue('Drive_Type')){
        case 0:
        default:
            $form['powertrain']['Powertrain_Container']['Motor']['Front_Motor_Power_Peak'] = [
                '#type' => 'number',
                '#step'=>1,
                '#default_value' => 400,
                '#title' => $this->t('Front Motor Peak Power [kW]'),
                '#required' => TRUE,
                '#suffix' => '<div class="error" id="front_motor_power_peak"></div>',
            ];
            $form['powertrain']['Powertrain_Container']['Motor']['Front_Motor_Power_Continuous'] = [
                '#type' => 'number',
                '#step'=>1,
                '#default_value' => 200,
                '#title' => $this->t('Front Motor Continuous Power [kW]'),
                '#required' => TRUE,
                '#suffix' => '<div class="error" id="front_motor_power_continuous"></div>',
            ];
            break;
        case 1:
            $form['powertrain']['Powertrain_Container']['Motor']['Rear_Motor_Power_Peak'] = [
                '#type' => 'number',
                '#step'=>1,
                '#default_value' => 400,
                '#title' => $this->t('Rear Motor Peak Power [kW]'),
                '#required' => TRUE,
                '#suffix' => '<div class="error" id="rear_motor_power_peak"></div>',
            ];
            $form['powertrain']['Powertrain_Container']['Motor']['Rear_Motor_Continuous_Peak'] = [
                '#type' => 'number',
                '#step'=>1,
                '#default_value' => 200,
                '#title' => $this->t('Rear Motor Continuous Power [kW]'),
                '#required' => TRUE,
                '#suffix' => '<div class="error" id="rear_motor_power_continuous"></div>',
            ];
            break;
        case 2:
            $form['powertrain']['Powertrain_Container']['Motor']['Front_Motor_Power_Peak'] = [
                '#type' => 'number',
                '#step'=>1,
                '#default_value' => 400,
                '#title' => $this->t('Front Motor Peak Power [kW]'),
                '#required' => TRUE,
                '#prefix' => '<div class="row align-items-center p-auto"><div class="col">',
                '#suffix' => '<div class="error" id="front_motor_power_peak"></div>',
            ];
            $form['powertrain']['Powertrain_Container']['Motor']['Rear_Motor_Power_Peak'] = [
                '#type' => 'number',
                '#step'=>1,
                '#default_value' => 400,
                '#title' => $this->t('Rear Motor Peak Power [kW]'),
                '#required' => TRUE,
                '#suffix' => '<div class="error" id="rear_motor_power_peak"></div></div>',
            ];
            $form['powertrain']['Powertrain_Container']['Motor']['Front_Motor_Power_Continuous'] = [
                '#type' => 'number',
                '#step'=>1,
                '#default_value' => 200,
                '#title' => $this->t('Front Motor Continuous Power [kW]'),
                '#required' => TRUE,
                '#prefix' => '<div class="col">',
                '#suffix' => '<div class="error" id="front_motor_power_continuous"></div>',
            ];
            $form['powertrain']['Powertrain_Container']['Motor']['Rear_Motor_Continuous_Peak'] = [
                '#type' => 'number',
                '#step'=> 1,
                '#default_value' => 200,
                '#title' => $this->t('Rear Motor Continuous Power [kW]'),
                '#required' => TRUE,
                '#suffix' => '<div class="error" id="rear_motor_power_continuous"></div></div></div>',
            ];
            break;
    }

    $form['powertrain']['Powertrain_Container']['Drive_Ratio'] = [
        '#type' => 'number',
        '#step' => 0.01,
        '#default_value' => 0.43,
        '#title' => $this->t('Drive ratio'),
        '#required'=> TRUE,
        '#suffix' => '<div class="error" id="drive_ratio"></div>',
    ];
    $form['powertrain']['Powertrain_Container']['Regen_Capacity'] = [
        '#type' => 'number',
        '#step' => 1,
        '#default_value' => 60,
        '#title' => $this->t('Regenerative Braking %'),
        '#required'=> TRUE,
        '#suffix' => '<div class="error" id="regen_braking"></div>',
    ];
    $form['powertrain']['Powertrain_Container']['Useable_Capacity'] = [
        '#type' => 'number',
        '#step' => 1,
        '#default_value' => 95,
        '#title' => $this->t('Useable Capacity %'),
        '#required'=> TRUE,
        '#suffix' => '<div class="error" id="useable_capacity"></div>',
    ];
    $form['actions'] = [
		'#type' => 'actions',
	];
	$form['actions']['submit'] = [
		'#type' => 'button',
		'#value' => $this->t('save'),
        '#ajax' => [
            'callback' => '::submitData',
            'wrapper'=> 'Results',
            'disable-refocus' => TRUE,
            'progress' => [
              'type' => 'throbber',
              'message' => $this->t('Calculating entry...'),
            ]
        ]
	];
    $form['Results'] = [
        '#type' => 'container',
        '#markup' => '<div id="results" class="alert alert-primary">Battery Pack Size [kwh] here...</div>'
      ];

    $form['CalculatePackSize'] = [
        '#type' =>'button',
        '#value' => $this->t('Calculate powertrain Requirements'),
        '#ajax' => [
            'callback' => '::energy_per_km',
            'wrapper'=> 'Results',
            'disable-refocus' => TRUE,
            'progress' => [
              'type' => 'throbber',
              'message' => $this->t('Calculating entry...'),
            ]
        ]
    ];
    $form['pack'] = [
        '#type' => 'details',
        '#title' => $this->t('Battery Pack Sizing Calculations'),
      ];
      $form['pack']['Pack_Energy_Container'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Pack Energy'),
        ];
    $form['pack']['Pack_Energy_Container']['Pack_Size'] = [
        '#type' => 'number',
        '#attributes' => ['id' => 'pack_energy_container' ],
        '#title' => $this->t('Pack Energy [kWh]'),
        '#required' => TRUE,
        '#default_value' => 85,
        '#suffix' => '<div class="error" id = "pack_energy"></div>'
    ];
    $form['pack']['Voltage_Architecture'] = [
        '#type' => 'radios',
        '#title' => $this->t('Voltage Architecture'),
        '#default_value' => 1,
        '#options' => [
            0 => $this->t('400V'),
            1 => $this->t('800V'),
        ],
    ];
$form['pack']['Cell_Geom_Container'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Cell Geometry'),
    ];
    $form['pack']['Cell_Geom_Container']['Cell_Geometry'] = [
        '#type' => 'select',
        '#options' => [
            'cylindrical' => 'Cylindrical',
            'prismatic' => 'Prismatic',
            'pouch' => 'Pouch'
        ],
        '#default_value' => 'cylindrical',
        '#ajax' => [
            'callback' => '::cellGeometryParameters',
            'event' => 'change',
            'wrapper' => 'geometry-container',
            'effect' => 'fade',
        ]
    ];
    $form['pack']['Cell_Geom_Container']['Geometry']=[
        '#type' => 'container',
        '#attributes' => ['id' => 'geometry-container',],
    ];
    $form['pack']['Cell_Voltage_Nom'] = [ //[V]
        '#type' => 'number',
        '#title' => $this->t('Nominal Cell Voltage [V]'),
        '#step'=> 0.1,
        '#required' => TRUE,
        '#default_value' => 3.3,
        '#suffix' => '<div class="error" id = "cell_voltage_nominal"></div>'
    ];
    $form['pack']['Cell_Voltage_Min'] = [ //[V]
        '#type' => 'number',
        '#title' => $this->t('Minimum Cell Voltage [V]'),
        '#step'=> 0.1,
        '#required' => TRUE,
        '#default_value' => 2.5,
        '#suffix' => '<div class="error" id = "cell_voltage_minimum"></div>'
    ];
    $form['pack']['Cell_Voltage_Max'] = [ //[V]
        '#type' => 'number',
        '#title' => $this->t('Maximum Cell Voltage [V]'),
        '#step'=> 0.1,
        '#required' => TRUE,
        '#default_value' => 4.2,
        '#suffix' => '<div class="error" id = "cell_voltage_maximum"></div>'
    ];
    $form['pack']['Cell_Tab_Resistance'] = [ //[mOhm]
        '#type' => 'number',
        '#title' => $this->t('Cell tab resistance [mOhm]'),
        '#step'=> 0.0001,
        '#required' => TRUE,
        '#default_value' => 0.0030,
        '#suffix' => '<div class="error" id = "cell_tab_resistance"></div>'
    ];
    $form['pack']['Cell_Internal_Resistance'] = [ //[Ohm]
        '#type' => 'number',
        '#title' => $this->t('Cell internal resistance [Ohm]'),
        '#step'=> 0.001,
        '#required' => TRUE,
        '#default_value' => 0.014,
        '#suffix' => '<div class="error" id = "cell_internal_resistance"></div>'
    ];
    $form['pack']['EE_Internal_Resistance'] = [ //[Ohm]
        '#type' => 'number',
        '#title' => $this->t('EE internal resistance [Ohm]'),
        '#step'=> 0.0001,
        '#required' => TRUE,
        '#default_value' => 0.005,
        '#suffix' => '<div class="error" id = "ee_internal_resistance"></div>'
    ];
    $form['pack']['Cell_Capacity'] = [ // [Ah]
        '#type' => 'number',
        '#title' => $this->t('Cell Capacity [mAh]'),
        '#required' => TRUE,
		'#default_value' => 2500, //[mAh]
        '#suffix' => '<div class="error" id="cell_capacity"></div>',
    ];
    $form['pack']['Cell_Mass'] = [ // [Ah]
        '#type' => 'number',
        '#title' => $this->t('Cell Mass [g]'),
        '#required' => TRUE,
		'#default_value' => 76, //[g]
        '#suffix' => '<div class="error" id="cell_mass"></div>',
    ];
    $form['pack']['C_rate'] = [
        '#type'=> 'number',
        '#title' =>$this->t('Cell C-rate'),
        '#require' => TRUE,
        '#default_value' => 10,
        '#suffix' => '<div class="error" id="c_rate"></div>',
    ];

    switch ($form_state->getValue('Cell_Geometry')) {
        case 'prismatic':
        case 'pouch':
            $form['pack']['Cell_Geom_Container']['Geometry']['Width'] = [
                '#type' => 'number',
                '#title' => $this->t('Width [mm]'),
                '#required' => TRUE,
                '#default_value' => 160, //mm
                '#prefix' => '<div class="row align-items-center"><div class="col">',
                '#suffix' => '</div>',
            ];
            $form['pack']['Cell_Geom_Container']['Geometry']['Height'] = [
                '#type' => 'number',
                '#title' => $this->t('Height [mm]'),
                '#required' => TRUE,
                '#default_value' => 227, //mm
                '#prefix' => '<div class="col">',
                '#suffix' => '</div>',
            ];
            $form['pack']['Cell_Geom_Container']['Geometry']['Depth'] = [
                '#type' => 'number',
                '#title' => $this->t('Depth [mm]'),
                '#required' => TRUE,
                '#default_value' => 7.25, //mm
                '#prefix' => '<div class="col">',
                '#suffix' => '</div><div>',
            ];
            break;
        case 'cylindrical':
        default:
            $form['pack']['Cell_Geom_Container']['Geometry']['Diameter'] = [
                '#type' => 'number',
                '#title' => $this->t('Diameter [mm]'),
                '#required' => TRUE,
                '#default_value' => 26, //mm
                '#prefix' => '<div class="row align-items-center"><div class="col">',
                '#suffix' => '</div>',
            ];
            $form['pack']['Cell_Geom_Container']['Geometry']['Height'] = [
                '#type' => 'number',
                '#title' => $this->t('Height [mm]'),
                '#required' => TRUE,
                '#default_value' => 65, //mm
                '#prefix' => '<div class="col">',
                '#suffix' => '</div>',
            ];
            break;
    }
    $form['PackParameters'] = [
        '#type' => 'container',
        '#markup' => '<div id="packParameters" class="alert alert-primary">Battery Pack Parameters here...</div>'
      ];

    $form['CalculatePackParameters'] = [
        '#type' =>'button',
        '#value' => $this->t('Calculate Pack Parameters'),
        '#ajax' => [
            'callback' => '::batteryPackParameters',
            'wrapper'=> 'packParameters',
            'disable-refocus' => TRUE,
            'progress' => [
              'type' => 'throbber',
              'message' => $this->t('Calculating entry...'),
            ]
        ]
    ];
    $form['#attached']['library'][] = 'batterycalc/chart_library';

    $form['#theme'] = 'batterycalc_form';

    return $form;
 }

public function selectCycle($form, FormStateInterface $form_state) {
    // one way to return a form element
    $ajax_response = new AjaxResponse();
    $this->calculateSpeedStatistics($form, $form_state);
    return $ajax_response->addCommand(new ReplaceCommand('#Speed_Container', $form['Speed_Container']));
}

public function motorConfiguration($form, FormStateInterface $form_state) {
    return $form['powertrain_Container']['Motor'];
}
public function cellGeometryParameters($form, FormStateInterface $form_state) {
    // other way to return a form element
    return $form['Cell_Geom_Container']['Geometry'];
}

public function batteryPackParameters($form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();
    //get values
    $formField = $form_state->getValues();
    
    $packEnergy = $formField['Pack_Size'];
    $c_rate = $formField['C_rate'];

    switch ($formField['Voltage_Architecture']){
        case 0:
            $packVoltage = 400;
            break;
        case 1:
            $packVoltage = 800;
            break;
        default:
            $packVoltage = 400;
        break;        
    }
    
    $cellMass = $formField['Cell_Mass'];
    $cellCapacity = $formField['Cell_Capacity'];
    $cellVoltage = $formField['Cell_Voltage_Nom'];
    $cellHeight = $formField['Height'];
    
    $CellTabResistance = $formField['Cell_Tab_Resistance'];
    $CellInternalResitance = $formField['Cell_Internal_Resistance'];
    $EEInternalResistance = $formField['EE_Internal_Resistance'];

    switch ($formField['Cell_Geometry']) {

        case 'pouch':
        case 'prismatic':
            $cellWidth = $formField['Width'];
            $cellDepth = $formField['Depth'];
            $cellVolume = round($cellHeight * $cellWidth * $cellDepth * 10 ** -3,3); // [cm3]
        break;
        case 'cylindrical':
        default:
            $cellDiameter = $formField['Diameter'];
            $cellVolume = round(M_PI * $cellDiameter ** 2 * $cellHeight/ 4 * 10 ** -3,3); // [cm3]
        break;
    }

    $cellEnergy = round($cellCapacity * $cellVoltage / 1000,1); // [Wh]
    $cellSpecificEnergyDensity = round($cellEnergy * 1000 / $cellMass); // [Wh/kg]
    $cellVolumicEnergyDensity = round($cellEnergy * 1000 / ($cellVolume),2); // [Wh/cm3]

    $numCellsInSeries = ceil($packVoltage/$cellVoltage);
    $numStringsInParallel = ceil(($packEnergy*1000/$packVoltage)/($cellCapacity/1000));

    $packResistance = ($CellInternalResitance + $CellTabResistance / 1000) * $numCellsInSeries
                   + $EEInternalResistance;
    $newPackSize = $numStringsInParallel * $packVoltage  * $cellCapacity * 10 ** -6; // [kWh]
    $newPackCapacity = $newPackSize * 1000 / $packVoltage; // [Ah]
    $totalNumberOfCells = $numCellsInSeries * $numStringsInParallel;
    $packMass = $totalNumberOfCells * $cellMass / 1000; // [kg]
    $packVolume = round($totalNumberOfCells * $cellVolume * 10 ** -3,2); // [dm3]

    $current = $numStringsInParallel * $c_rate * $cellCapacity / 1000;

    $power = $current * $packVoltage / 1000;

    $text = $this->t('<div class="card-group">
      <div class="card alert alert-primary">
      <h5 class="card-header alert alert-warning text-center mt-0 fs-4 fw-bold">Cell Statistics</h5>
        <div class="card-body">
          <p class="card-text">Cell volume: @cell_volume cm3</p>
          <p class="card-text">Cell specific energy density: @specific_energy_density Wh/kg</p>
          <p class="card-text">Cell volumetric density: @volumetric_energy_density Wh/cm3</p>
          <p class="card-text">Cell energy: @cell_energy Wh.</p>
        </div>
      </div>
      <div class="card alert alert-primary">
      <h5 class="card-header alert alert-warning text-center mt-0 fs-4 fw-bold">Battery Pack Statistics</h5>
        <div class="card-body alert">
          <p class="card-text">Pack capacity: @new_pack_capacity Ah</p>
          <p class="card-text">Pack energy: @new_pack_size kWh</p>
          <p class="card-text">Pack S/P (series/parallel): @number_of_cells_in_series/@number_of_strings_in_parallel</p>
          <p class="card-text">Number of cells in the pack: @total_number_of_cells</p>
          <p class="card-text">Pack resistance: @packResitance Ohm</p>
          <p class="card-text">Pack mass: @pack_mass kg</p>
          <p class="card-text">Pack volume: @pack_volume L</p>
          <p class="card-text">The current draw at a @c_rate C is @current A</p>
          <p class="card-text">The power delivered @c_rate C is @power kW</p>
        </div>
      </div>
  </div>'
  ,[
    '@cell_volume' => $cellVolume,
    '@cell_energy' => $cellEnergy,
    '@specific_energy_density' => $cellSpecificEnergyDensity,
    '@volumetric_energy_density' => $cellVolumicEnergyDensity,
    '@number_of_cells_in_series' => $numCellsInSeries,
    '@number_of_strings_in_parallel' => $numStringsInParallel,
    '@new_pack_capacity' => $newPackCapacity,
    '@new_pack_size' => $newPackSize,
    '@total_number_of_cells' => $totalNumberOfCells,
    '@packResitance' => $packResistance,
    '@pack_mass' => $packMass,
    '@pack_volume' => $packVolume,
    '@c_rate' => $c_rate,
    '@current' => $current,
    '@power' => $power,
]);

    return $ajax_response->addCommand(new HtmlCommand('#packParameters', $text));
}

public function speed_array(array $form, FormStateInterface $form_state){
    $formField = $form_state->getValues();
    $speed = array_map('floatval',explode(",",$formField['vehicle_Speed']));
    return $speed;
}

 public function calculateSpeedStatistics(array $form, FormStateInterface $form_state){  
    
    $ajax_response = new AjaxResponse();  
    $text='';
    
    return $ajax_response->addCommand(new HtmlCommand('#speed_info', $text));
 }

 /**
 * Energy consumption[kwh/km]
 */
public function energy_per_km($form, FormStateInterface $form_state){
    $ajax_response = new AjaxResponse();
    $formField = $form_state->getValues();

    $speed = $this->speed_array($form, $form_state);
    $distance = array_sum($speed)/3600; 
    $range = $formField['vehicle_Range'];
    $useable_capacity = $formField['Useable_Capacity'] / 100;
    $regen_capacity = $formField['Regen_Capacity'] / 100;

    $inertiaE= $this->inertial_energy($form, $form_state);
    $roadLoadE = $this->road_load_energy($form, $form_state);
    $aeroE = $this->aero_drag_energy($form, $form_state);

    $efficiency = $formField['powertrain_Efficiency'];
    $ancillary_energy_per_km = $formField['Ancillary_Energy'];

    // vehicle Motor Power [kW]
    $motor_power_continuous = $formField['Front_Motor_Power_Continuous'] ?? 0 + $formField['Rear_Motor_Power_Continuous'] ?? 0;
    $motor_power_peak = $formField['Front_Motor_Power_Peak'] ?? 0 + $formField['Rear_Motor_Power_Peak'] ?? 0;

    // Regen Power [kW]
    $regen_continuous = $regen_capacity * $motor_power_continuous;
    $regen_peak = $regen_capacity * $motor_power_peak;

    $energy_per_km = ((array_sum($inertiaE) + array_sum($roadLoadE) + array_sum($aeroE)) / $distance + $ancillary_energy_per_km)
                    / $efficiency; // [wh/km]

    $packEnergy = $energy_per_km * $range / $useable_capacity / 1000; //[kwh]

    $text = $this->t('<i>The energy requirement of this vehicle is @efficiency Wh/km.
                      To achieve a range of @range km on the cycle this vehicle requires a battery of @batterySize kWh 
                      if @useable_capacity% of the battery capacity is useable.',
                    ['@efficiency' => round($energy_per_km,2),
                    '@batterySize' =>round($packEnergy,2),
                    '@range' => round($range,2),
                    '@useable_capacity' => $useable_capacity * 100
                    ]);

    // $form_state->setValue('Pack_Size', $packEnergy);
    // $ajax_response->addCommand(new ReplaceCommand('#pack_energy_container', $form['Pack_Energy_Container']));
    $ajax_response->addCommand(new HtmlCommand('#results', $text));
    
    return $ajax_response;
}

 public function submitData(array &$form, FormStateInterface $form_state){
    $ajax_response = new AjaxResponse();
    $conn = Database::getConnection();

    $formField = $form_state->getValues();

    $flag = TRUE;
    if(trim($formField['Batt_Title']) == ''){
        return $ajax_response->addCommand(new HtmlCommand('#batt_title', 'Please enter the project title.'));
        $flag = FALSE;
    }
    if(trim($formField['vehicle_Speed']) == ''){
        return $ajax_response->addCommand(new HtmlCommand('#vehicle_speed_error', 'Please enter the vehicle speed profile.'));
        $flag = FALSE;
    }
    if(trim($formField['vehicle_Mass']) == ''){
        return $ajax_response->addCommand(new HtmlCommand('#vehicle_mass', 'Please enter the vehicle mass'));
        $flag = FALSE;
    }
    if(trim($formField['Frontal_Area']) == ''){
        return $ajax_response->addCommand(new HtmlCommand('#frontal_area', 'Please enter the frontal area'));
        $flag = FALSE;
    }
    if(trim($formField['Air_Density']) == ''){
        return $ajax_response->addCommand(new HtmlCommand('#air-density', 'Please enter the air density'));
        $flag = FALSE;
    }
    if(trim($formField['Road_Slope']) == ''){
        return $ajax_response->addCommand(new HtmlCommand('#road_slope', 'Please enter the road slope'));
        $flag = FALSE;
    }
    if(trim($formField['Rolling_Resistance']) == ''){
        return $ajax_response->addCommand(new HtmlCommand('#rolling_resistance', 'Please enter the rolling resistance'));
        $flag = FALSE;
    }
    if(trim($formField['Drag_Coefficient']) == ''){
        return $ajax_response->addCommand(new HtmlCommand('#drag_coefficient', 'Please enter the drag coefficient'));
        $flag = FALSE;
    }
    if(trim($formField['powertrain_Efficiency']) == ''){
        return $ajax_response->addCommand(new HtmlCommand('#powertrain_efficiency', 'Please enter the rolling resistance'));
        $flag = FALSE;
    }
    if(trim($formField['Ancillary_Energy']) == ''){
        return $ajax_response->addCommand(new HtmlCommand('#ancillary_energy', 'Please enter the drag coefficient'));
        $flag = FALSE;
    }

    if ($flag){
        $formData['Batt_Title'] = $formField['Batt_Title'];
        $formData['vehicle_Speed'] = $formField['vehicle_Speed'];
        $formData['vehicle_Mass'] = $formField['vehicle_Mass'];
        $formData['Frontal_Area'] = $formField['Frontal_Area'];
        $formData['Air_Density'] = $formField['Air_Density'];
        $formData['Road_Slope'] = $formField['Road_Slope'];
        $formData['Rolling_Resistance'] = $formField['Rolling_Resistance'];
        $formData['Drag_Coefficient'] = $formField['Drag_Coefficient'];
        $formData['Batt_Comment'] = $formField['Batt_Comment'];

        $conn->insert('batterycalc')
            ->fields($formData)
            ->execute();
        }

    return $this->energy_per_km($form, $form_state);    
} 

/**
 * {@inheritdoc}
*/
public function validateForm(array &$form, FormStateInterface $form_state) {
    // $this->calculateSpeedStatistics($form, $form_state);
	// 	$formField = $form_state ->getValues();
        

        // $vehicleSpeed = trim($formField['vehicle_Speed']);
        // $vehicleMass = trim($formField['vehicle_Mass']);
        // $frontalArea = trim($formField['Frontal_Area']);
        // $airDensity = trim($formField['Air_Density']);
        // $roadSlope = trim($formField['Road_Slope']);
        // $rollingResistance = trim($formField['Rolling_Resistance']);
        // $dragCoefficient = trim($formField['Drag_Coefficient']);
        // $cellVoltage = trim($formField['Cell_Voltage']);
        // $cellCapacity = trim($formField['Cell_Capacity']);
        // $cellMass = trim($formField['Cell_Mass']);
        // $cRate = trim($formField['C_rate']);

        // if(!preg_match("/^(\d*\.?\d+? ?,*)+/",$vehicleSpeed)){
        //     $form_state->setErrorByName('vehicle_Speed',$this->t('Enter comma separated values for the vehicle speed'));
        // }
        // if(!preg_match("/^(\d+\.?\d*)/",$vehicleMass)){
        //     $form_state->setErrorByName('vehicle_Mass',$this->t('Enter a decimal value'));
        // }
        // if(!preg_match("/^(\d+\.?\d*)/",$frontalArea)){
        //     $form_state->setErrorByName('Frontal_Area',$this->t('Enter a decimal value'));
        // }
        // if(!preg_match("/^(\d+\.?\d*)/",$airDensity)){
        //     $form_state->setErrorByName('Air_Density',$this->t('Enter a decimal value'));
        // }
        // if(!preg_match("/^(\d+\.?\d*)/",$roadSlope)){
        //     $form_state->setErrorByName('Road_Slope',$this->t('Enter a decimal value'));
        // }
        // if(!preg_match("/^(\d+\.?\d*)/",$rollingResistance)){
        //     $form_state->setErrorByName('Rolling Resistance',$this->t('Enter a decimal value'));
        // }
        // if(!preg_match("/^(\d+\.?\d*)/",$dragCoefficient)){
        //     $form_state->setErrorByName('Drag_Coefficient',$this->t('Enter a decimal value'));
        // }
        // if(!preg_match("/^(\d+\.?\d*)/",$cellVoltage)){
        //     $form_state->setErrorByName('Cell_Voltage',$this->t('Enter a decimal value'));
        // }
        // if(!preg_match("/^(\d+\.?\d*)/",$cellCapacity)){
        //     $form_state->setErrorByName('Cell_Capacity',$this->t('Enter a decimal value'));
        // }
        // if(!preg_match("/^(\d+\.?\d*)/",$cellMass)){
        //     $form_state->setErrorByName('Cell_Mass',$this->t('Enter a decimal value'));
        // }
        // if(!preg_match("/^(\d+\.?\d*)/",$cRate)){
        //     $form_state->setErrorByName('C_rate',$this->t('Enter a decimal value'));
        // }
    } 
/**
 * {@inheritdoc}
*/ 
public function submitForm(array &$form, FormStateInterface $form_state){

}

/**
 * inertial force
 * m * a
 */
public function inertial_energy(array &$form, FormStateInterface $form_state){
    $formField = $form_state->getValues();
    $speed = $this->speed_array($form, $form_state);
    $mass = $formField['vehicle_Mass'];
    $dt = 1;
    $energy = [];

    for ($i = 0; $i < count($speed)-1; $i++) {
        array_push($energy, $mass * ($speed[$i+1] - $speed[$i]) / $dt * 1000/3600 * $speed[$i+1] * 1000/3600 * $dt / 3600 );
    }

    return $energy;   //[Wh]
}

/**
 *  road load
 * m * g * Cr *cos(alpha) + m * g * sin(alpha)
*/
public function road_load_energy(array &$form, FormStateInterface $form_state){
    $formField = $form_state->getValues();
    $speed = $this->speed_array($form, $form_state);
    $mass = $formField['vehicle_Mass'];
    $alpha = $formField['Road_Slope']*M_PI/180;
    $rr = $formField['Rolling_Resistance'];
    $dt = 1; //time step, 1sec
    $energy = [];

    for ($i = 0; $i < count($speed); $i++){
        array_push($energy, $mass * gravity * ($rr * cos($alpha) + sin($alpha) )
                 * $speed[$i]*1000/3600 
                 * $dt / 3600);
    }

    return $energy;  //[wh]
}

/**
 * aerodynamic drag force
 * 0.5 * rho * Cd * A * v^2
 */
public function aero_drag_energy(array &$form, FormStateInterface $form_state){
    $formField = $form_state->getValues();
    $speed = $this->speed_array($form, $form_state);
    $Cd = $formField['Drag_Coefficient'];
    $rho = $formField['Air_Density'];
    $frontalArea = $formField['Frontal_Area'];
    $dt = 1;
    $energy = [];

    for ($i = 0; $i < count($speed); $i++){
        array_push($energy, 0.5 * $rho *  $Cd * $frontalArea * pow($speed[$i]*1000/3600,2) * $speed[$i] * 1000 / 3600 * $dt / 3600 );
    }
    return $energy;  //[wh]
}



}