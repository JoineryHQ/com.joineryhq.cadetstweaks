<?php
use CRM_Cadetstweaks_ExtensionUtil as E;

class CRM_Cadetstweaks_Relationshiptype_Utils {

  /**
   * Get settings for a given section, as defined by an ID.
   *
   * @param int $id RelationshipType id
   * @return array Json-decoded settings from optionValue for this section.
   */
  public static function getRelationshipTypeSettingsValue($id) {
    $settingName = "relationship_type_{$id}";
    $result = \Civi\Api4\OptionValue::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('option_group_id:name', '=', 'cadetstweaks_relationship_type')
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
  public static function saveRelationshipTypeSettingsValue($id, $settings) {
    $settingName = "relationship_type_{$id}";
    $result = \Civi\Api4\OptionValue::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('option_group_id:name', '=', 'cadetstweaks_relationship_type')
      ->addWhere('name', '=', $settingName)
      ->execute()
      ->first();

    $createParams = array();

    if ($optionValueId = CRM_Utils_Array::value('id', $result)) {
      $createParams['id'] = $optionValueId;
    }
    else {
      $createParams['name'] = $settingName;
      $createParams['option_group_id'] = "cadetstweaks_relationship_type";
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
   * @return int RelationshipTypeId
   */
  public static function getRelationshipType($relationshipId) {
    // Get Relationship Type ID base on RelationshipId
    $relationships = \Civi\Api4\Relationship::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('id', '=', $relationshipId)
      ->execute()
      ->first();

    return $relationships['relationship_type_id'];
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
      ->setCheckPermissions(FALSE)
      ->addSelect('value')
      ->addWhere('option_group_id:name', '=', 'cadetstweaks_relationship_type')
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
