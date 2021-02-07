<?php
use CRM_Cadetstweaks_ExtensionUtil as E;

/**
 * Cadetstweaks.Updatecutoffages API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
// function _civicrm_api3_cadetstweaks_Updatecutoffages_spec(&$spec) {}

/**
 * Cadetstweaks.Updatecutoffages API
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @see civicrm_api3_create_success
 *
 * @throws API_Exception
 */
function civicrm_api3_cadetstweaks_Updatecutoffages($params) {
  // Get the custom field to get the custom group value column
  $getAgeCutOffField = \Civi\Api4\CustomField::get()
    ->addWhere('label', '=', 'Age at cut-off')
    ->execute()
    ->first();

  // Get the custom group to get the custom group value table
  $getCadetsExtra = \Civi\Api4\CustomGroup::get()
    ->addWhere('title', '=', 'Cadets Extra')
    ->execute()
    ->first();

  // Update cutoff age of every contact using the custom group value table and column
  $query = "UPDATE {$getCadetsExtra['table_name']} AS cadets
    INNER JOIN civicrm_contact AS cont ON cadets.entity_id = cont.id
    SET cadets.{$getAgeCutOffField['column_name']} = IF(DATE_FORMAT(NOW(), '05-31') < DATE_FORMAT(NOW(), '%m-%d'), DATE_FORMAT(NOW(), '%Y-05-31') + 1, DATE_FORMAT(NOW(), '%Y-05-31')) - DATE_FORMAT(cont.birth_date, '%Y-%m-%d') - (DATE_FORMAT(NOW(), '00-05-31') < DATE_FORMAT(cont.birth_date, '00-%m-%d'))
    WHERE cont.birth_date IS NOT NULL";

  // Run query
  $result = CRM_Core_DAO::executeQuery($query);

  // Debugging purposes
  if (!$result) {
    $returnValues = [
      [
        'success' => FALSE,
        'error' => $result,
        'query' => $query,
      ],
    ];
    // throw new API_Exception(/*error_message*/ 'Everyone knows that the magicword is "sesame"', /*error_code*/ 'magicword_incorrect');
  } else {
    $returnValues = [
      [
        'success' => TRUE,
        'query' => $query,
      ],
    ];
  }

  return civicrm_api3_create_success($returnValues, $params, 'Cadetstweaks', 'Updatecutoffages');
}
