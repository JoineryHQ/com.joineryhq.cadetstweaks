<?php

require_once 'cadetstweaks.civix.php';
// phpcs:disable
use CRM_Cadetstweaks_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function cadetstweaks_civicrm_config(&$config) {
  _cadetstweaks_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
 */
function cadetstweaks_civicrm_xmlMenu(&$files) {
  _cadetstweaks_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function cadetstweaks_civicrm_install() {
  _cadetstweaks_civix_civicrm_install();

  // Add custom group when installed
  $createCadetsExtra = \Civi\Api4\CustomGroup::create()
    ->addValue('title', 'Cadets Extra')
    ->addValue('extends', 'Individual')
    ->addValue('style', 'Inline')
    ->addValue('is_active', TRUE)
    ->addValue('collapse_display', FALSE)
    ->execute()
    ->first();

  // Add custom field when installed and relate it to the custom group
  $createAgeCutOffField = \Civi\Api4\CustomField::create()
    ->addValue('custom_group_id', $createCadetsExtra['id'])
    ->addValue('label', 'Age at cut-off')
    ->addValue('data_type', 'String')
    ->addValue('html_type', 'Text')
    ->addValue('is_view', TRUE)
    ->addValue('is_searchable', TRUE)
    ->execute()
    ->first();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function cadetstweaks_civicrm_postInstall() {
  _cadetstweaks_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function cadetstweaks_civicrm_uninstall() {
  _cadetstweaks_civix_civicrm_uninstall();

  // Get custom group
  $getCadetsExtra = \Civi\Api4\CustomGroup::get()
    ->addWhere('title', '=', 'Cadets Extra')
    ->execute()
    ->first();

  // Remove custom field related to custom group
  $deleteCadetsExtraFields = \Civi\Api4\CustomField::delete()
    ->addWhere('custom_group_id', '=', $getCadetsExtra['id'])
    ->execute();

  // Remove custom group
  $deleteCadetsExtra = \Civi\Api4\CustomGroup::delete()
    ->addWhere('title', '=', 'Cadets Extra')
    ->execute();

  // Remove custom value related to the custom group
  CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS {$getCadetsExtra['table_name']}");
}

/**
 * Implements hook_civicrm_post().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_post
 */
function cadetstweaks_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  if ($objectName == 'Individual' && ($op == 'edit' || $op == 'create')) {
    CRM_Cadetstweaks_Utils::runUpdateCutoffAges($objectId);
  }
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function cadetstweaks_civicrm_enable() {
  _cadetstweaks_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function cadetstweaks_civicrm_disable() {
  _cadetstweaks_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function cadetstweaks_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _cadetstweaks_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
 */
function cadetstweaks_civicrm_managed(&$entities) {
  _cadetstweaks_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_caseTypes
 */
function cadetstweaks_civicrm_caseTypes(&$caseTypes) {
  _cadetstweaks_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
 */
function cadetstweaks_civicrm_angularModules(&$angularModules) {
  _cadetstweaks_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function cadetstweaks_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _cadetstweaks_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function cadetstweaks_civicrm_entityTypes(&$entityTypes) {
  _cadetstweaks_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_thems().
 */
function cadetstweaks_civicrm_themes(&$themes) {
  _cadetstweaks_civix_civicrm_themes($themes);
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_preProcess
 */
//function cadetstweaks_civicrm_preProcess($formName, &$form) {
//
//}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 */
//function cadetstweaks_civicrm_navigationMenu(&$menu) {
//  _cadetstweaks_civix_insert_navigation_menu($menu, 'Mailings', array(
//    'label' => E::ts('New subliminal message'),
//    'name' => 'mailing_subliminal_message',
//    'url' => 'civicrm/mailing/subliminal',
//    'permission' => 'access CiviMail',
//    'operator' => 'OR',
//    'separator' => 0,
//  ));
//  _cadetstweaks_civix_navigationMenu($menu);
//}
