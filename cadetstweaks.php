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
    ->addValue('name', 'Age_at_cut_off')
    ->addValue('label', 'Age at cut-off')
    ->addValue('data_type', 'String')
    ->addValue('html_type', 'Text')
    ->addValue('is_view', TRUE)
    ->addValue('is_searchable', TRUE)
    ->execute()
    ->first();

  // Create option group for hiding relationship type in user dashboard
  $createCadetstweakOptionGroup = \Civi\Api4\OptionGroup::create()
      ->setCheckPermissions(FALSE)
      ->addValue('name', 'cadetstweaks')
      ->addValue('title', 'Cadetstweak Extension Options')
      ->addValue('is_active', TRUE)
      ->addValue('is_locked', TRUE)
      ->addValue('is_reserved', TRUE)
      ->execute();
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

  // Delete option value
  $deleteCadetstweakOptionValue = \Civi\Api4\OptionValue::delete()
    ->addWhere('option_group_id:name', '=','cadetstweaks')
    ->execute();

  // Delete option group
  $deleteCadetstweakOptionGroup = \Civi\Api4\OptionGroup::delete()
    ->addWhere('name', '=','cadetstweaks')
    ->execute();
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

  // Delete optionvalue of related to RelationshipType if its deleted
  // It is not working since there is no post hook when RelationshipType is deleted
  if ($objectName == 'RelationshipType' && $op == 'delete') {
    $deleteOptionValue = \Civi\Api4\OptionValue::delete()
      ->addWhere('option_group_id:name', '=', 'cadetstweaks')
      ->addWhere('label', '=', "relationship_type_{$objectId}")
      ->execute();
  }
}

/**
 * Implements hook_civicrm_buildForm().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_buildForm
 */
function cadetstweaks_civicrm_buildForm($formName, $form) {
  if ($formName == 'CRM_Admin_Form_RelationshipType') {
    // Create new fields.
    $form->addElement('checkbox', 'cadetstweaks_hide_in_dashboard', E::ts('Hide Relationships Type in User Dashboard?'));

    // Assign bhfe fields to the template, so our new field has a place to live.
    $tpl = CRM_Core_Smarty::singleton();
    $bhfe = $tpl->get_template_vars('beginHookFormElements');
    if (!$bhfe) {
      $bhfe = array();
    }

    $bhfe[] = 'cadetstweaks_hide_in_dashboard';
    $form->assign('beginHookFormElements', $bhfe);

    // Add javascript that will relocate our field to a sensible place in the form.
    CRM_Core_Resources::singleton()->addScriptFile('com.joineryhq.cadetstweaks', 'js/CRM_Admin_Form_RelationshipType.js');

    // Set defaults so our field has the right value.
    $rid = $form->getVar('_id');
    if ($rid) {
      $settings = CRM_Cadetstweaks_Utils::getSettings($rid);
      $defaults = array(
        'cadetstweaks_hide_in_dashboard' => $settings['cadetstweaks_hide_in_dashboard'],
      );
      $form->setDefaults($defaults);
    }
  }
}

/**
 * Implements hook_civicrm_postProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postProcess
 */
function cadetstweaks_civicrm_postProcess($formName, &$form) {
  if ($formName == 'CRM_Admin_Form_RelationshipType') {
    // Get relationship type id if form is edit
    $rid = $form->getVar('_id');

    // Get relationship type id base on the label_a_b submit values...
    // if $rid is empty (newly created relationship type)
    if (empty($rid)) {
      $relType = \Civi\Api4\RelationshipType::get()
        ->addSelect('id')
        ->addWhere('label_a_b', '=', $form->_submitValues['label_a_b'])
        ->execute()
        ->first();

      $rid = $relType['id'];
    }

    // Save the value of the cadetstweaks_hide_in_dashboard in the settings
    $settings = CRM_Cadetstweaks_Utils::getSettings($rid);
    $settings['cadetstweaks_hide_in_dashboard'] = $form->_submitValues['cadetstweaks_hide_in_dashboard'];
    CRM_Cadetstweaks_Utils::saveAllSettings($rid, $settings);
  }
}

/**
 * Implements hook_civicrm_searchColumns().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_searchColumns
 */
function cadetstweaks_civicrm_searchColumns($objectName, &$headers, &$rows, &$selector) {
  // Hide relationship types in user dashboard
  if (
    $objectName == 'relationship.rows'
    && CRM_Utils_Request::retrieve('context', 'Alphanumeric') == 'user'
  ) {
    foreach ($rows as $key => $row) {
      // Get the list of relationship type that's need to be hidden
      $hiddenRelationshipTypes = CRM_Cadetstweaks_Utils::hiddenRelationshipTypes();

      // Get relationship type of the row
      $relationType = CRM_Cadetstweaks_Utils::getRelationshipType($row['relation']);

      // Unset relationship type if it's one that needs to be hidden
      if (in_array($relationType['id'], $hiddenRelationshipTypes)) {
        unset($rows[$key]);
      }
    }

    // Reset index of $rows to prevent empty data
    $rows = array_values($rows);
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
