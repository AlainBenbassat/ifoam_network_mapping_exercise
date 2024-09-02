<?php

class NmeHelper {
  private $expirationDate = '2030-10-31'; // last date the form can be filled in

  public $allowedGroups = [
    'sep-1' => 'For the European Parliament they include:',
    270 => 'AGRI (Agriculture & Rural Development) - Committee members and substitutes',
    271 => 'ENVI (Environment, Public Health & Food Safety) - Committee members and substitutes',
    428 => 'IMCO (Internal Market & Consumer Protection) - Committee members and substitutes',
    272 => 'ITRE (Industry, Research & Energy) - Committee members and substitutes',
    'sep-2' => 'For the European Commission they include:',
    490 => 'Vice-President - Cabinet of Commissioner Frans TIMMERMANS',
    485 => 'AGRI (Agriculture & Rural Development) - Cabinet of Commissioner Janusz WOJCIECHOWSKI',
    487 => 'ENVI (Environment, Oceans & Fisheries) - Cabinet of Commissioner Virginijus SINKEVICIUS',
    486 => 'RTD (Innovation, Research, Culture, Education & Youth) - Cabinet of Commissioner Mariya GABRIEL',
    488 => 'SANTE (Health & Food Safety) - Cabinet of Commissioner Stella KYRIAKIDES',
  ];

  public $context;

  public $cid;
  public $cs;
  public $groupId;
  public $contactId;
  public $contactName;
  public $includeCountry;

  public $currentUserName;
  public $currentUserContactId;

  public $relationshipBasic;
  public $relationshipMedium;
  public $relationshipStrong;

  public $agreeDisagreeQuestions = [
    '46' => 'Organic',
    '36' => 'CAP (includes RDPs, etc)',
    '51' => 'Climate change',
    '26' => 'GMOs',
    '59' => 'Organic inputs',
    '23' => 'the Organic regulation',
    '41' => 'the EU\'s Research policy',
    '31' => 'Seed',
  ];

  public function __construct($context) {
    civicrm_initialize();

    // get query string params
    $this->getQueryStringParams();

    // make sure the contact is allow to view this page
    $this->checkUserAllowed();

    if ($context == 'main') {
      $this->context = $context;
    }
    elseif ($context == 'group_contacts') {
      $this->context = $context;

      // make sure the specified group is allowed
      $this->checkGroupAllowed();
    }
    elseif ($context == 'contact_details') {
      $this->context = $context;

      // make sure the specified group and contact are allowed
      $this->checkGroupAllowed();
      $this->checkContactAllowed();

      $this->fillRelationshipTypeIds();
    }
    else {
      throw new Exception('Unknown context');
    }
  }

  public function getGroupContacts($groupId) {
    // check if this is a smart group
    $sql = "select saved_search_id from civicrm_group where id = $groupId";
    $isSmartGroup = CRM_Core_DAO::singleValueQuery($sql);
    if ($isSmartGroup) {
      // refresh the cache
      CRM_Contact_BAO_GroupContactCache::check([$groupId]);
      $groupContactTable = 'civicrm_group_contact_cache';
      $groupContactStatus = '';
    }
    else {
      $groupContactTable = 'civicrm_group_contact';
      $groupContactStatus = " gc.status = 'Added' and ";
    }

    $sql = "
      select
        c.id,
        ifnull(ctry.name, '') country,
        c.last_name,
        c.first_name
      from
        $groupContactTable gc
      inner join
        civicrm_contact c on c.id = gc.contact_id
      left outer join
        civicrm_value_mep_committee_membership_33 mep on mep.entity_id = c.id
      left outer join
        civicrm_country ctry on mep.country_of_origin_274 = ctry.id
      where
        $groupContactStatus
        gc.group_id = $groupId
      and
        c.is_deleted = 0
      order by
        ifnull(ctry.name, ''),
        c.sort_name
    ";
    $dao = CRM_Core_DAO::executeQuery($sql);

    return $dao;
  }

  public function addNoteToContact($note) {
    civicrm_api3('Note', 'create', [
      'entity_table' => 'civicrm_contact',
      'entity_id' => $this->contactId,
      'note' => $note,
      'contact_id' => $this->currentUserContactId,
      'subject' => 'comment',
      'modified_date' => date('Y-m-d'),
    ]);
  }

  public function getContactNameFromId($contactId) {
    $sql = "select display_name from civicrm_contact where id = $contactId";
    return CRM_Core_DAO::singleValueQuery($sql);
  }

  public function updateRelationshipLevel($relationshipLevel) {
    // delete the current relationships
    $sql = "
      delete from
        civicrm_relationship
      where
        contact_id_a = {$this->currentUserContactId}
      and
        contact_id_b = {$this->contactId}
      and
        relationship_type_id in ({$this->relationshipBasic}, {$this->relationshipMedium}, {$this->relationshipStrong})
    ";
    CRM_Core_DAO::executeQuery($sql);

    // invert the id's
    $sql = "
      delete from
        civicrm_relationship
      where
        contact_id_a = {$this->contactId}
      and
        contact_id_b = {$this->currentUserContactId}
      and
        relationship_type_id in ({$this->relationshipBasic}, {$this->relationshipMedium}, {$this->relationshipStrong})
    ";
    CRM_Core_DAO::executeQuery($sql);

    // create the relationship
    if ($relationshipLevel > 0) {
      civicrm_api3('Relationship', 'create', [
        'contact_id_a' => $this->currentUserContactId,
        'contact_id_b' => $this->contactId,
        'relationship_type_id' => $relationshipLevel,
        'is_active' => 1,
      ]);
    }
  }

  public function getTagId($questionKey, $tagName) {
    $sql = "select id from civicrm_tag t where t.parent_id  = $questionKey and name like '% $tagName'";
    $tagId = CRM_Core_DAO::singleValueQuery($sql);
    if (!$tagId) {
      throw new Exception("Could not find tag id, $tagName (parent tag = $questionKey)");
    }

    return $tagId;
  }

  public function updateAgreeDisagree($tagIdToSet, $tagsToUnset) {
    // unset the tags
    $sql = "delete from civicrm_entity_tag where entity_table = 'civicrm_contact' and entity_id = {$this->contactId} and tag_id in (" . implode(',', $tagsToUnset) . ")";
    CRM_Core_DAO::executeQuery($sql);

    // add the new tag (we use sql because the api requires the tag name instead of the id)
    $sql = "insert ignore into civicrm_entity_tag (entity_table, entity_id, tag_id) values ('civicrm_contact', %1, %2)";
    $sqlParams = [
      1 => [$this->contactId, 'Integer'],
      2 => [$tagIdToSet, 'Integer'],
    ];
    CRM_Core_DAO::executeQuery($sql, $sqlParams);
  }

  public function getDefaultTagId($disagreeId, $neutralId, $agreeId) {
    // see what the default value is for the selected contact (i.e. an assigned tag)
    $sql = "select ifnull(max(tag_id), 0) from civicrm_entity_tag where entity_table = 'civicrm_contact' and entity_id = {$this->contactId} and tag_id  in ($disagreeId, $neutralId, $agreeId)";
    return CRM_Core_DAO::singleValueQuery($sql);
  }

  private function checkUserAllowed() {
    $allowed = FALSE;
    $errorMessage = 'You are not allowed to access this page.';

    // see if the contact is logged in
    // check the date
    if (date('Y-m-d') > $this->expirationDate) {
      $errorMessage = "You cannot fill in the form anymore. The expiration date was {$this->expirationDate}.";
    }
    elseif (is_user_logged_in()) {
      // check the roles
      $user = wp_get_current_user();

      foreach ($user->roles as $role) {
        if ($role == 'administrator' || $role == 'contributor' || $role == 'subscriber') {
          $allowed = TRUE;

          // lookup the name of the user
          $sql = "select c.id, c.first_name, c.last_name from civicrm_contact c inner join civicrm_uf_match uf on uf.contact_id = c.id where uf_id = {$user->ID} and c.is_deleted = 0";
          $dao = CRM_Core_DAO::executeQuery($sql);
          if ($dao->fetch()) {
            $this->currentUserName = $dao->first_name . ' ' . $dao->last_name;
            $this->currentUserContactId = $dao->id;
          }

          break;
        }
      }
    }
    else {
      // check the contact id and checksum
      $isValidUser = CRM_Contact_BAO_Contact_Utils::validChecksum($this->cid, $this->cs);
      if (!$isValidUser) {
        $errorMessage = 'Cannot identify user.';
      }
      else {
        $allowed = TRUE;

        // lookup the name of the user
        $sql = "select c.id, c.display_name from civicrm_contact c where c.id = {$this->cid} and c.is_deleted = 0";
        $dao = CRM_Core_DAO::executeQuery($sql);
        if ($dao->fetch()) {
          $this->currentUserName = $dao->display_name;
          $this->currentUserContactId = $dao->id;
        }
      }
    }

    if ($allowed == FALSE) {
      throw new Exception($errorMessage);
    }
  }

  private function checkGroupAllowed() {
    $allowed = FALSE;

    if (!$this->groupId) {
      // no group id in query string
    }
    else {
      // make sure the group id is an allowed one
      if (array_key_exists($this->groupId, $this->allowedGroups)) {
        // OK
        $allowed = TRUE;
      }
    }

    if ($allowed == FALSE) {
      throw new Exception('Not a valid group.');
    }
  }

  private function checkContactAllowed() {
    // check if this is a smart group
    $sql = "select saved_search_id from civicrm_group where id = {$this->groupId}";
    $isSmartGroup = CRM_Core_DAO::singleValueQuery($sql);
    if ($isSmartGroup) {
      // refresh the cache
      CRM_Contact_BAO_GroupContactCache::check([$this->groupId]);
      $groupContactTable = 'civicrm_group_contact_cache';
      $groupContactStatus = '';
    }
    else {
      $groupContactTable = 'civicrm_group_contact';
      $groupContactStatus = " gc.status = 'Added' and ";
    }

    $sql = "
      select
        c.display_name
      from
        $groupContactTable gc
      inner join
        civicrm_contact c on c.id = gc.contact_id
      where
        $groupContactStatus
        gc.group_id = {$this->groupId}
      and
        c.is_deleted = 0
      and
        c.id = {$this->contactId}
    ";
    $this->contactName = CRM_Core_DAO::singleValueQuery($sql);

    if ($this->contactName) {
      // OK, found contact
    }
    else {
      throw new Exception('Not a valid contact.');
    }
  }

  private function fillRelationshipTypeIds() {
    $sql = "select id from civicrm_relationship_type where name_a_b = 'Basic relationship'";
    $this->relationshipBasic = CRM_Core_DAO::singleValueQuery($sql);

    $sql = "select id from civicrm_relationship_type where name_a_b = 'Medium relationship'";
    $this->relationshipMedium = CRM_Core_DAO::singleValueQuery($sql);

    $sql = "select id from civicrm_relationship_type where name_a_b = 'Strong relationship'";
    $this->relationshipStrong = CRM_Core_DAO::singleValueQuery($sql);


  }

  private function getQueryStringParams() {
    // get the checksum (if applicable)
    $this->cs = CRM_Utils_Request::retrieve('cs', 'String');

    // get the cid and alternatives  (if applicable)
    $this->cid = CRM_Utils_Request::retrieve('cid', 'Integer');
    if ($this->cid == 0) {
      $this->cid = CRM_Utils_Request::retrieve('cid1', 'Integer');
    }

    // get the group id and the contact id
    $this->groupId = CRM_Utils_Request::retrieve('group_id', 'Integer');
    $this->contactId = CRM_Utils_Request::retrieve('contact_id', 'Integer');

    // include country?
    $this->includeCountry = CRM_Utils_Request::retrieve('include_country', 'String');
  }
}
