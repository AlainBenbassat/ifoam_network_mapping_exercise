<?php

require_once __DIR__ . '/NmeHelper.php';

class NmeGroupPage {
  public static function get() {
    try {
      // make sure we are allowed to see this page
      $helper = new NmeHelper('group_contacts');

      $html = '<h2>' . $helper->allowedGroups[$helper->groupId] . '</h2>';
      if ($helper->includeCountry == 'yes') {
        $html .=
          '<p>As you can see below, the members of the Committee are sorted by country (first column) and name (second column). You can either search for a member by<p>'
          . '<ul>'
          . '<li><strong>country</strong>: scroll through the list to the country you are searching for or press "Ctrl + F" (on Windows) or "CMD+F" (on Mac) to search for the country you want to find,</li>'
          . '<li><strong>name</strong>: scroll through the list to the name you are searching for or press "Ctrl + F" (on Windows) or "CMD+F" (on Mac) to search for the name you want to find.</li>'
          . '</ul>';
      }
      else {
        $html .=
          '<p>As you can see below, the members of the Cabinet are sorted by name. You can either search for a member by<p>'
          . '<ul>'
          . '<li>scrolling through the list to the name you are searching for or</li>'
          . '<li>pressing "Ctrl + F" (on Windows) or "CMD+F" (on Mac) to search for the name you want to find.</li>'
          . '</ul>';
      }

      $html .=
        '<p>When clicking on the name of a member (in green), you will be asked to indicate the following information:<p>'
        . '<ul>'
        . '<li>your relationship level (if applicable),</li>'
        . '<li>their attitude towards organic (if known),</li>'
        . '<li>their opinion on the topics we are working on (if known).</li>'
        . '</ul>';

      $html .= '<p>You can also open the member in a new tab by right-clicking on the name and choosing "Open Link in New Tab".</p>';
      $html .= '<p>Please fill in the information for everyone you know:</p>';

      $dao = $helper->getGroupContacts($helper->groupId);

      $html .= '<ul>';

      while ($dao->fetch()) {
        if ($helper->includeCountry == 'yes' && !empty($dao->country)) {
          $html .= $dao->country . ' - ';
        }

        $html .= '<li><a href="../contact?group_id=' . $helper->groupId . '&contact_id=' . $dao->id . '&cid=' . $helper->cid . '&cs=' . $helper->cs . '&include_country=' . $helper->includeCountry . '">'
          . $dao->last_name . ', ' . $dao->first_name . '</a></li>';
      }

      $html .= '</ul>';

      $html .= '<p><br><a href="..">&lt; Back</p>';
    }
    catch (Exception $e) {
      $myErrors = new WP_Error('required', $e->getMessage());
    }

    return $html;

  }
}