<?php

require_once __DIR__ . '/NmeHelper.php';

class NmeMainPage {
  public static function get() {
    $html = '<p>You are not authorised to access this page. Please contact communication@organicseurope.bio if you are a Board or Council Member.</p>';

    try {
      // make sure we are allowed to see this page
      $helper = new NmeHelper('main');

      if ($helper->currentUserContactId == 0) {
        throw new Exception('Not allowed');
      }

      $html = '<p>You are about to start the IFOAM Organics Europe Network Mapping Exercise.</p>' .
        '<p>This exercise serves to map your relationships with the members of the European Parliament and the European Commission as well as their attitude towards organic farming and opinion on the topics we are working on.</p>' .
        '<p>It will help us to better defend your interests. By getting a more detailed overview of our network, we will be able to better target key policymakers, and our communication to them. It will also help us to better target our communication efforts to make sure we reach our Vision 2030.</p>' .
        '<p>To be as efficient as possible, we have limited the selection to the European Parliament Committees and Commission Cabinets that are most important to our work.</p>';

      // add the user name
      $html = '<p>Welcome ' . $helper->currentUserName . ',</p>' . $html;

      $inUL = FALSE;
      $includeCountry = 'no';
      foreach ($helper->allowedGroups as $groupId => $groupName) {
        if (strpos($groupId, 'sep') === 0) {
          // this is a section
          // check if we have to close the previous ul
          if ($inUL) {
            $html .= "</ul>";
          }
          $html .= "<h2>$groupName</h2>";
          $html .= "<ul>";
          $inUL = TRUE;

          if (strpos($groupName, 'European Parliament') === FALSE) {
            $includeCountry = 'no';
          }
          else {
            $includeCountry = 'yes';
          }
        }
        elseif (strpos($groupId, '-') === 0) {
          // this group is not complete yet, so no hyperlink
          $html .= "<li>$groupName [NOT UPDATED YET]</li>";
        }
        else {
          // this is an actual group
          $html .= "<li><a href=\"group?group_id=$groupId" . '&cid=' . $helper->cid . '&cs=' . $helper->cs . "&include_country=$includeCountry\">$groupName</a></li>";
        }
      }
      $html .= "</ul>";

      // outro text
      $html .= '<p>When clicking on one of these groups, you will see an overview with all members of the respective Committee or Cabinet.</p>' .
        '<p>Please take your time to go through the members of the Committees and Cabinets to provide us with your valuable information.</p>' .
        '<p>Your time and effort are very much appreciated!</p>' .
        '<p><strong>Your IFOAM Organics Europe Team</strong></p>';
    }
    catch (Exception $e) {
      $myErrors = new WP_Error('required', $e->getMessage());
    }

    return $html;
  }
}