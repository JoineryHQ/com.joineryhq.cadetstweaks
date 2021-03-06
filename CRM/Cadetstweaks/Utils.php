<?php
use CRM_Cadetstweaks_ExtensionUtil as E;

class CRM_Cadetstweaks_Utils {

  /**
   * Process contacts (either the given contact or all contacts) by updating the
   * custom "age at cut-off" field based on contact birth_date.
   *
   * @param $cid int contact id
   *
   * @return array of query result per CRM_Core_DAO::executeQuery().
   */
  public static function runUpdateCutoffAges($cid = NULL) {
    // Get the custom field to get the custom group value column
    $getAgeCutOffField = \Civi\Api4\CustomField::get()
      ->addWhere('name', '=', 'Age_at_cut_off')
      ->setCheckPermissions(FALSE)
      ->execute()
      ->first();

    // Get the custom group to get the custom group value table
    $getCadetsExtra = \Civi\Api4\CustomGroup::get()
      ->addWhere('title', '=', 'Cadets Extra')
      ->setCheckPermissions(FALSE)
      ->execute()
      ->first();

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
      FROM `civicrm_contact`";

    // Add query params if $cid is not null
    $queryParams = [];
    if ($cid) {
      $query .= " WHERE id = %1";
      $queryParams[1] = [$cid, 'Int'];
    }

    // Execute query
    CRM_Core_DAO::executeQuery($query, $queryParams);

    // Insert/Update cutoff age of every contact using the custom group value table and column
    $query = "INSERT INTO `{$getCadetsExtra['table_name']}` (entity_id, {$getAgeCutOffField['column_name']})
      SELECT temp.id, temp.val
      FROM (SELECT id, IF(age_at_cutoff > 22, concat('Aged out (', age_at_cutoff, ')'), age_at_cutoff) AS val FROM `CRM_Cadetstweaks_Utils_buildUpdatesTable`) temp
      ON DUPLICATE KEY UPDATE
      {$getAgeCutOffField['column_name']} = temp.val";

    // Execute query
    $result = CRM_Core_DAO::executeQuery($query);

    // Drop the table. Yes, it's tempoarary, but if we process multiple separate
    // contacts in the same PHP call, that temporary table will still exist for the
    // next contact, leading to fatal SQL "already exists" error. Therefore we have
    // to drop it now.
    CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS `CRM_Cadetstweaks_Utils_buildUpdatesTable`");

    return $result;
  }

}
