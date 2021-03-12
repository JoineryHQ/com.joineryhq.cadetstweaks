CRM.$(function($) {
  // Add id attribute to bhfe table, so it's easy to reference later.
  CRM.$('label[for="cadetstweaks_hide_in_dashboard"]').closest('table').attr('id', 'bhfe-table');
  // Move bhfe form elements into main form.
  CRM.$('tr.crm-relationshiptype-form-block-is_active').after(CRM.$('label[for="cadetstweaks_hide_in_dashboard"]').closest('tr'));
  // Remove bhfe table if there is no tr
  if (!CRM.$('table#bhfe-table tr').length) {
    CRM.$('table#bhfe-table').remove();
  }
});
