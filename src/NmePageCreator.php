<?php

class NmePageCreator {
  public function createPages() {
    // create the main page
    $title = 'Network Mapping Exercise';
    if (!$this->existsPage($title)) {
      $page = [
        'post_title' => $title,
        'post_content' => '[network_mapping_exercise_main_page]',
        'post_status' => 'publish',
        'post_type' => 'page',
        'post_name' => 'network_mapping_exercise',
      ];
      wp_insert_post($page);
    }

    // get the post ID of the main page
    $parentId = get_posts([
      'post_type'  => 'page',
      'title' => $title,
      'fields' => 'ids',
    ])[0];

    // create the 'group' page as sub page
    $title = 'Network Mapping Exercise - Groups';
    if (!$this->existsPage($title)) {
      $page = [
        'post_title' => $title,
        'post_content' => '[network_mapping_exercise_group_page]',
        'post_status' => 'publish',
        'post_type' => 'page',
        'post_parent' => $parentId,
        'post_name' => 'group',
      ];
      wp_insert_post($page);
    }

    // create the 'contact' page as sub page
    $title = 'Network Mapping Exercise - Contact Details';
    if (!$this->existsPage($title)) {
      $page = [
        'post_title' => $title,
        'post_content' => '[network_mapping_exercise_contact_page]',
        'post_status' => 'publish',
        'post_type' => 'page',
        'post_parent' => $parentId,
        'post_name' => 'contact',
      ];
      wp_insert_post($page);
    }
  }
  
  private function existsPage($title) {
    $pages = get_posts([
      'post_type'  => 'page',
      'title' => $title,
    ]);

    if (empty($pages)) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }
}