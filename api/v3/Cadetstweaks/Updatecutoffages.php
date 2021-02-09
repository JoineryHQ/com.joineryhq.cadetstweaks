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
    $returnValues = [
      [
        'success' => FALSE,
        'result' => $result,
      ],
    ];
    // throw new API_Exception(/*error_message*/ 'Everyone knows that the magicword is "sesame"', /*error_code*/ 'magicword_incorrect');
  } else {
    $returnValues = [
      [
        'success' => TRUE,
        'result' => $result,
      ],
    ];
  }

  return civicrm_api3_create_success($returnValues, $params, 'Cadetstweaks', 'Updatecutoffages');
}
