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
  $result = CRM_Cadetstweaks_Utils::runUpdateCutoffAges();
  // Debugging purposes
  if (!$result) {
    throw new API_Exception('Unknown error occured after executing the query!', 'unknown_error');
  } else {
    $returnValues['success'] = TRUE;
  }

  return civicrm_api3_create_success($returnValues, $params, 'Cadetstweaks', 'Updatecutoffages');
}
