<?php

require_once __DIR__ . '/NmeHelper.php';

class NmeContactPage {
  public static function get() {
    try {
      // make sure we are allowed to see this page
      $helper = new NmeHelper('contact_details');

      $html = '<h2>' . $helper->contactName . '</h2>';
      $html .= '<p>Please fill in the following information for this member:<p>'
        . '<ul>'
        . '<li>your relationship level (if applicable),</li>'
        . '<li>their attitude towards organic (if known),</li>'
        . '<li>their opinion on the topics we are working on (if known).</li>'
        . '</ul>'
        . '<p>Please note that the information on your relationship with the member as well as the members\' opinion and attitude currently saved in our database will be ticked by default. Feel free to correct this information if necessary.</p>'
        . '<p>Once you have filled in all information, please make sure to click "Submit" at the bottom of the page to save your changes in our database. </p>'
        . '<p>In case you want to go back to the previous page without saving your changes, please click on "Cancel and go back".</p>';


      $url = 'admin-post.php' . '/?group_id=' . $helper->groupId . '&cid=' . $helper->cid . '&cs=' . $helper->cs . '&include_country=' . $helper->includeCountry;
      $html .= '<form action="' . esc_url(admin_url($url)) . '" method="post">';

      $html .= '<input type="hidden" name="action" value="ifoam_process_contact">';
      $html .= '<input type="hidden" name="contact_id" value="' . $helper->contactId . '">';
      $html .= '<input type="hidden" name="contact_name" value="' . $helper->contactName . '">';

      // question relationship level
      $html .= $helper->getCheckboxGroup('relationship_level', '<strong>How well do you know ' . $helper->contactName . '</strong>?', [
        0 => 'Not at all or no regular contact',
        $helper->relationshipBasic => 'Basic relationship (you are in contact within normal course of work)',
        $helper->relationshipMedium => 'Medium relationship (you can call them and they will take your call or call you back)',
        $helper->relationshipStrong => 'Strong relationship (you have dinner together at each other\'s house)',
      ],
      0);

      // dynamic questions
      foreach ($helper->agreeDisagreeQuestions as $questionKey => $questionTitle) {
        // get the corresponding tag id's
        if ($questionKey == 46) {
          // exception for attitude towards organic
          $disagreeId = $helper->getTagId($questionKey, 'Anti organic');
          $neutralId = $helper->getTagId($questionKey, 'Neutral/Undecided');
          $agreeId = $helper->getTagId($questionKey, 'Pro organic');

          $html .= $helper->getCheckboxGroup($questionKey, 'What is the attitude of ' . $helper->contactName . ' towards <strong>' . $questionTitle . '</strong>?', [
            0 => 'I don\'t know',
            $disagreeId => 'Anti organic',
            $neutralId => 'Neutral/Undecided',
            $agreeId => 'Pro organic',
          ],
          $helper->getDefaultTagId($disagreeId, $neutralId, $agreeId));
        }
        else {
          $disagreeId = $helper->getTagId($questionKey, 'Disagrees with us');
          $neutralId = $helper->getTagId($questionKey, 'Neutral/Undecided');
          $agreeId = $helper->getTagId($questionKey, 'Agrees with us');

          $html .= $helper->getCheckboxGroup($questionKey, 'What is the opinion of ' . $helper->contactName . ' on <strong>' . $questionTitle . '</strong>?', [
            0 => 'I don\'t know',
            $disagreeId => 'Disagrees with us',
            $neutralId => 'Neutral/Undecided',
            $agreeId => 'Agrees with us',
          ],
          $helper->getDefaultTagId($disagreeId, $neutralId, $agreeId));
        }
      }

      $html .= '<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary button-large" value="Save Changes"></p>';
      $html .= '</form>';

      $html .= '<p><br><a href="../group?group_id=' . $helper->groupId . '&cid=' . $helper->cid . '&cs=' . $helper->cs . '&include_country=' . $helper->includeCountry . '">&lt; Cancel and go back</a></p>';

    }
    catch (Exception $e) {
      $myErrors = new WP_Error('required', $e->getMessage());
    }

    return $html;
  }
}