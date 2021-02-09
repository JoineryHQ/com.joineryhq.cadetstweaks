<?php
use CRM_Cadetstweaks_ExtensionUtil as E;

class CRM_Cadetstweaks_Utils {

  /**
   * @param $cid int contact id
   *
   */
  public static function buildUpdatesTable($cid = NULL) {
    // Create temporary table to store contact id and age cutoff
    $query = "CREATE TEMPORARY TABLE `CRM_Cadetstweaks_Utils_buildUpdatesTable`
      (PRIMARY KEY id (id))
      SELECT id, concat(id, '', birth_date) AS age_at_cutoff
      FROM `civicrm_contact`
      WHERE birth_date IS NOT NULL";

    // Add query params if $cid is not null
    $queryParams = [];
    if ($cid) {
      $query .= " AND id = %1";
      $queryParams[1] = ['Int', $cid];
    }

    // Execute query
    CRM_Core_DAO::executeQuery($query, $queryParams);
  }

  /**
   * @param $cid int contact id
   *
   * @return array of query result
   */
  public static function runUpdateCutoffAges($cid = NULL) {
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

    self::buildUpdatesTable($cid);

    // Update cutoff age of every contact using the custom group value table and column
    $query = "UPDATE `{$getCadetsExtra['table_name']}` AS cadets
      INNER JOIN `CRM_Cadetstweaks_Utils_buildUpdatesTable` AS cont ON cadets.entity_id = cont.id
      SET cadets.{$getAgeCutOffField['column_name']} = cont.age_at_cutoff";

    // Execute query
    $result = CRM_Core_DAO::executeQuery($query);

    // $buildsupdateQuery = "SELECT * FROM CRM_Cadetstweaks_Utils_buildUpdatesTable";
    // $dao = CRM_Core_DAO::executeQuery($buildsupdateQuery);
    // $buildsupdate = [];
    // while ($dao->fetch()) {
    //   $buildsupdate[] = $dao->toArray();
    // }

    return $result;
  }
}
