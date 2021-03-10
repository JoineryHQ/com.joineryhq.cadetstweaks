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

  /**
   * Get settings for a given section, as defined by an ID.
   *
   * @param int $id RelationshipType id
   * @return array Json-decoded settings from optionValue for this section.
   */
  public static function getSettings($id) {
    $settingName = "relationship_type_{$id}";
    $result = \Civi\Api4\OptionValue::get()
      ->addWhere('option_group_id:name', '=', 'cadetstweaks')
      ->addWhere('name', '=', $settingName)
      ->execute();

    $resultValue = CRM_Utils_Array::value(0, $result, array());
    $settingJson = CRM_Utils_Array::value('value', $resultValue, '{}');
    return json_decode($settingJson, TRUE);
  }

  /**
   * Save value as json-encoded settings, for a given optionValue, as defined by an ID.
   *
   * @param int $id RelationshipType id
   * @param array $settings Full list of all settings to save. This will NOT be merged with any existing settings.
   *
   * @return void
   */
  public static function saveAllSettings($id, $settings) {
    $settingName = "relationship_type_{$id}";
    $result = \Civi\Api4\OptionValue::get()
      ->addWhere('option_group_id:name', '=', 'cadetstweaks')
      ->addWhere('name', '=', $settingName)
      ->execute()
      ->first();

    $createParams = array();

    if ($optionValueId = CRM_Utils_Array::value('id', $result)) {
      $createParams['id'] = $optionValueId;
    }
    else {
      $createParams['name'] = $settingName;
      $createParams['option_group_id'] = "cadetstweaks";
    }

    // Add relationship_type_id to settings. Without this, optionValue.create api was failing
    // to save new settings with a message like "value already exists in the database"
    // if the values for this relationshipType are the same as for some other relationshipType. So by
    // adding relationship_type_id, we make it unique to this relationshipType.
    $settings["relationship_type_id"] = $id;
    $createParams['value'] = json_encode($settings);

    civicrm_api3('optionValue', 'create', $createParams);
  }

  /**
   * Get the data of relationship type in user dashboard
   *
   * @param string $relation in datatables data (civicrm/ajax/contactrelationships?context=user&cid=CONTACTID) in user dashboard
   *
   * @return array RelationType
   */
  public static function getRelationshipType($relation) {
    // Get the label of the relationship type by removing html tags
    $label = strip_tags($relation);
    preg_match_all('/<a[^>]+href=([\'"])(?<href>.+?)\1[^>]*>/i', $relation, $result);

    // Loop href since there are multiple link in $relation
    foreach ($result['href'] as $href) {
      $urlParam = parse_url($href, PHP_URL_QUERY);
      parse_str($urlParam, $relQuery);
      // if amp;rtype exist, add it on the extractedData
      if ($relQuery['amp;rtype']) {
        $relType = $relQuery['amp;rtype'];
      }
    }

    // Get api base on extracted data in the $relation
    $relationshipType = \Civi\Api4\RelationshipType::get()
      ->addWhere("label_{$relType}", '=', $label)
      ->execute()
      ->first();

    return $relationshipType;
  }

  /**
   * Get all the data of relationship type that's need to be hidden
   *
   * @return array of relationship type id
   */
  public static function hiddenRelationshipTypes() {
    // Initialize variable array
    $hiddenRelationshipTypes = [];

    // Get all option value of cadetstweaks option group
    $optionValues = \Civi\Api4\OptionValue::get()
      ->addSelect('value')
      ->addWhere('option_group_id:name', '=', 'cadetstweaks')
      ->execute();
    foreach ($optionValues as $optionValue) {
      $value = json_decode($optionValue['value']);
      // if cadetstweaks_hide_in_dashboard is 1, assign it to the $hiddenRelationshipTypes
      if ($value->cadetstweaks_hide_in_dashboard) {
        $hiddenRelationshipTypes[] = $value->relationship_type_id;
      }
    }

    return $hiddenRelationshipTypes;
  }

}
