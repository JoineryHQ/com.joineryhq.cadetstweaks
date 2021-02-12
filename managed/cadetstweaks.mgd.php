<?php

// This file declares managed database entities which will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
return array(
  // Add schedule jobs to update the cutoff age of every contact
  array(
    'module' => 'com.joineryhq.cadetstweaks',
    'name' => 'cadetstweaksjob',
    'entity' => 'Job',
    'params' => array(
      'version' => 3,
      'run_frequency' => 'Daily',
      'name' => 'Update Cut-off Age in Every Contact',
      'api_entity' => 'Cadetstweaks',
      'api_action' => 'updatecutoffages',
      'is_active' => 1,
    ),
  ),
);
