<?php

require_once 'primaryaddress.civix.php';
// phpcs:disable
use CRM_Primaryaddress_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function primaryaddress_civicrm_config(&$config): void {
  _primaryaddress_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function primaryaddress_civicrm_install(): void {
  _primaryaddress_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function primaryaddress_civicrm_enable(): void {
  _primaryaddress_civix_civicrm_enable();
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_preProcess
 */
//function primaryaddress_civicrm_preProcess($formName, &$form): void {
//
//}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 */
//function primaryaddress_civicrm_navigationMenu(&$menu): void {
//  _primaryaddress_civix_insert_navigation_menu($menu, 'Mailings', [
//    'label' => E::ts('New subliminal message'),
//    'name' => 'mailing_subliminal_message',
//    'url' => 'civicrm/mailing/subliminal',
//    'permission' => 'access CiviMail',
//    'operator' => 'OR',
//    'separator' => 0,
//  ]);
//  _primaryaddress_civix_navigationMenu($menu);
//}

/**
 * Implements hook_civicrm_postCommit().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postCommit/
 */
function primaryaddress_civicrm_postCommit($op, $objectName, $objectId, &$objectRef) {
  // Hook is hit for each entity, so set the variables as needed relevant to the Object.
  if ($op === 'create' && $objectName === 'Address') {
    $onlyOtherIsBilling = FALSE;
    // Get the id for the "User Entered Online" location type.
    $onlineLocationId = \Civi\Api4\LocationType::get(FALSE)
      ->addSelect('id')
      ->addWhere('name', '=', 'Online')
      ->execute()
      ->first()['id'] ?? NULL;
    // Change the $objectRef->location_type_id into an integer.
    $objectRefLocationTypeIdInt = intval($objectRef->location_type_id);
    // Return early if the object is not the User Entered Online Address.
    if ($objectRefLocationTypeIdInt !== $onlineLocationId) {
      return;
    }
    $today = date("Y-m-d");
    // Get the created date of the Coontact.
    $createdDate = \Civi\Api4\Contact::get(FALSE)
      ->addSelect('created_date')
      ->addWhere('id', '=', $objectRef->contact_id)
      ->execute()
      ->first()['created_date'];
    // Drop the timestamp from the createdDate.
    $createdDateArray = explode(' ', $createdDate);
    $formattedCreatedDate = $createdDateArray[0];
    // Return early if this is not a new Contact.
    if ($formattedCreatedDate !== $today) {
      return;
    }
    // Get the id for the "Billing" location type.
    $billingLocationId = \Civi\Api4\LocationType::get(FALSE)
      ->addSelect('id')
      ->addWhere('name', '=', 'Billing')
      ->execute()
      ->first()['id'] ?? NULL;
    // Get the location type of the Contact's other address.
    $contactAddressType = \Civi\Api4\Address::get(FALSE)
      ->addSelect('location_type_id')
      ->addWhere('contact_id', '=', $objectRef->contact_id)
      ->execute()
      ->first()['location_type_id'];
    // If the Contact's other address is of type "Billing", change the value of $onlyOtherIsBilling.
    if ($billingLocationId === $contactAddressType) {
      $onlyOtherIsBilling = TRUE;
    }
    // If the only other address is of type "Billing", set the "User Entered/Online" address as primary.
    if ($onlyOtherIsBilling) {
      \Civi\Api4\Address::update(FALSE)
        ->addValue('is_primary', TRUE)
        ->addWhere('id', '=', $objectId)
        ->execute();
    }
  }
}
