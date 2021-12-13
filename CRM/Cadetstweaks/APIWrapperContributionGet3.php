<?php

/**
 * APIWrapper class implementation, per https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_apiWrappers/
 */
class CRM_Cadetstweaks_APIWrapperContributionGet3 implements API_Wrapper {

  /**
   * Per https://app.asana.com/0/241603776072319/1201439840173634:
   * Conditionally alter params on contribution.get api calls:
   *  - remove 'limit' option
   *  - filter on receive_date >= 2020-05-01.
   * Specifically, we want to do this only when displaying the list of completed
   * contributions on the user dashboard.
   */
  public function fromApiInput($apiRequest) {
    // We only want to do this once, so keep track of that in a static variable:
    static $found = FALSE;

    // Get the 'q' value so we only do this if we're on the user dashboard.
    $null = NULL;
    $q = CRM_Utils_Request::retrieve('q', 'String', $null, $null, $null, 'GET');
    if ($q == 'civicrm/user' && !$found) {
      // Get the call stack so we only do this if we're being called by CRM_Contribute_Page_UserDashboard::listContribution().
      // (Naturally this will need maintenance as civicrm codebase changes.)
      $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);
      foreach ($bt as $subroutine) {
        if ($subroutine['class'] . '::' . $subroutine['function'] == 'CRM_Contribute_Page_UserDashboard::listContribution') {
          $found = TRUE;
          $apiRequest['params']['options']['limit'] = 0;
          $apiRequest['params']['receive_date'] = ['>=' => "2020-05-01"];
          break;
        }
      }
    }
    return $apiRequest;
  }

  /**
   * alter the result before returning it to the caller.
   */
  public function toApiOutput($apiRequest, $result) {
    return $result;
  }

}
