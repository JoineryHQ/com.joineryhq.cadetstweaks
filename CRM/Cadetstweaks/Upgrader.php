<?php
use CRM_Cadetstweaks_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Cadetstweaks_Upgrader extends CRM_Cadetstweaks_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Example: Run an external SQL script when the module is installed.
   */
  public function install() {
    // $this->executeSqlFile('sql/myinstall.sql');
    try {
      // Add custom group when installed
      $createCadetsExtra = \Civi\Api4\CustomGroup::create()
        ->setCheckPermissions(FALSE)
        ->addValue('title', 'Cadets Extra')
        ->addValue('extends', 'Individual')
        ->addValue('style', 'Inline')
        ->addValue('is_active', TRUE)
        ->addValue('collapse_display', FALSE)
        ->execute()
        ->first();

      // Add custom field when installed and relate it to the custom group
      $createAgeCutOffField = \Civi\Api4\CustomField::create()
        ->setCheckPermissions(FALSE)
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
        ->addValue('name', 'cadetstweaks_relationship_type')
        ->addValue('title', 'Cadetstweak Extension Options')
        ->addValue('is_active', TRUE)
        ->addValue('is_locked', TRUE)
        ->addValue('is_reserved', TRUE)
        ->execute();
    } catch (API_Exception $e) {
    }
  }

  /**
   * Example: Work with entities usually not available during the install step.
   *
   * This method can be used for any post-install tasks. For example, if a step
   * of your installation depends on accessing an entity that is itself
   * created during the installation (e.g., a setting or a managed entity), do
   * so here to avoid order of operation problems.
   */
  // public function postInstall() {
  //  $customFieldId = civicrm_api3('CustomField', 'getvalue', array(
  //    'return' => array("id"),
  //    'name' => "customFieldCreatedViaManagedHook",
  //  ));
  //  civicrm_api3('Setting', 'create', array(
  //    'myWeirdFieldSetting' => array('id' => $customFieldId, 'weirdness' => 1),
  //  ));
  // }

  /**
   * Example: Run an external SQL script when the module is uninstalled.
   */
  public function uninstall() {
    // $this->executeSqlFile('sql/myuninstall.sql');
    try {
      // Get custom group
      $getCadetsExtra = \Civi\Api4\CustomGroup::get()
        ->setCheckPermissions(FALSE)
        ->addWhere('title', '=', 'Cadets Extra')
        ->execute()
        ->first();

      // Remove custom field related to custom group
      $deleteCadetsExtraFields = \Civi\Api4\CustomField::delete()
        ->setCheckPermissions(FALSE)
        ->addWhere('custom_group_id', '=', $getCadetsExtra['id'])
        ->execute();

      // Remove custom group
      $deleteCadetsExtra = \Civi\Api4\CustomGroup::delete()
        ->setCheckPermissions(FALSE)
        ->addWhere('title', '=', 'Cadets Extra')
        ->execute();

      // Remove custom value related to the custom group
      CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS {$getCadetsExtra['table_name']}");

      // Delete option value
      $deleteCadetstweakOptionValue = \Civi\Api4\OptionValue::delete()
        ->setCheckPermissions(FALSE)
        ->addWhere('option_group_id:name', '=','cadetstweaks_relationship_type')
        ->execute();

      // Delete option group
      $deleteCadetstweakOptionGroup = \Civi\Api4\OptionGroup::delete()
        ->setCheckPermissions(FALSE)
        ->addWhere('name', '=','cadetstweaks_relationship_type')
        ->execute();
    } catch (API_Exception $e) {
    }
  }

  /**
   * Create option group for hiding relationship type in user dashboard
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_1001() {
    $this->ctx->log->info('Applying update 1001 (Create option group for hiding relationship type in user dashboard)');

    try {
      $createCadetstweakOptionGroup = \Civi\Api4\OptionGroup::create()
        ->setCheckPermissions(FALSE)
        ->addValue('name', 'cadetstweaks_relationship_type')
        ->addValue('title', 'Cadetstweak Extension Options')
        ->addValue('is_active', TRUE)
        ->addValue('is_locked', TRUE)
        ->addValue('is_reserved', TRUE)
        ->execute();
    } catch (API_Exception $e) {
    }

    return TRUE;
  }

  /**
   * Example: Run a simple query when a module is enabled.
   */
  // public function enable() {
  //  CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 1 WHERE bar = "whiz"');
  // }

  /**
   * Example: Run a simple query when a module is disabled.
   */
  // public function disable() {
  //   CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 0 WHERE bar = "whiz"');
  // }

  /**
   * Example: Run a couple simple queries.
   *
   * @return TRUE on success
   * @throws Exception
   *
  public function upgrade_4200() {
    $this->ctx->log->info('Applying update 4200');
    CRM_Core_DAO::executeQuery('UPDATE foo SET bar = "whiz"');
    CRM_Core_DAO::executeQuery('DELETE FROM bang WHERE willy = wonka(2)');
    return TRUE;
  } // */


  /**
   * Example: Run an external SQL script.
   *
   * @return TRUE on success
   * @throws Exception
   */
  // public function upgrade_4201() {
  //   $this->ctx->log->info('Applying update 4201');
  //   // this path is relative to the extension base dir
  //   $this->executeSqlFile('sql/upgrade_4201.sql');
  //   return TRUE;
  // }


  /**
   * Example: Run a slow upgrade process by breaking it up into smaller chunk.
   *
   * @return TRUE on success
   * @throws Exception
   */
  // public function upgrade_4202() {
  //   $this->ctx->log->info('Planning update 4202'); // PEAR Log interface

  //   $this->addTask(E::ts('Process first step'), 'processPart1', $arg1, $arg2);
  //   $this->addTask(E::ts('Process second step'), 'processPart2', $arg3, $arg4);
  //   $this->addTask(E::ts('Process second step'), 'processPart3', $arg5);
  //   return TRUE;
  // }
  // public function processPart1($arg1, $arg2) { sleep(10); return TRUE; }
  // public function processPart2($arg3, $arg4) { sleep(10); return TRUE; }
  // public function processPart3($arg5) { sleep(10); return TRUE; }

  /**
   * Example: Run an upgrade with a query that touches many (potentially
   * millions) of records by breaking it up into smaller chunks.
   *
   * @return TRUE on success
   * @throws Exception
   */
  // public function upgrade_4203() {
  //   $this->ctx->log->info('Planning update 4203'); // PEAR Log interface

  //   $minId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(min(id),0) FROM civicrm_contribution');
  //   $maxId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(max(id),0) FROM civicrm_contribution');
  //   for ($startId = $minId; $startId <= $maxId; $startId += self::BATCH_SIZE) {
  //     $endId = $startId + self::BATCH_SIZE - 1;
  //     $title = E::ts('Upgrade Batch (%1 => %2)', array(
  //       1 => $startId,
  //       2 => $endId,
  //     ));
  //     $sql = '
  //       UPDATE civicrm_contribution SET foobar = whiz(wonky()+wanker)
  //       WHERE id BETWEEN %1 and %2
  //     ';
  //     $params = array(
  //       1 => array($startId, 'Integer'),
  //       2 => array($endId, 'Integer'),
  //     );
  //     $this->addTask($title, 'executeSql', $sql, $params);
  //   }
  //   return TRUE;
  // }

}
