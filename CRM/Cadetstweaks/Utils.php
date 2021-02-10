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
      SELECT id,
        -- start with the BASE YEAR
        IF(
          -- If cutoff date is not yet passed in this year:
          '05-31' < DATE_FORMAT(NOW(), '%m-%d'),
          -- then BASE YEAR is next year,
          YEAR(NOW()) + 1,
          -- otherwise then BASE YEAR is this year,
          YEAR(NOW())
        )
        -- subtract the year of birth:
        - YEAR(birth_date)
        -- subtract 1 if cutoff date is before birthday: otherwise subtract 0.
        - (
        -- this boolean expression will evaluate to 1 or 0
          '05-31' < DATE_FORMAT(birth_date, '%m-%d')
        ) AS age_at_cutoff
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
      SET cadets.{$getAgeCutOffField['column_name']} = IF(cont.age_at_cutoff > 18, 'NA', cont.age_at_cutoff)";

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
