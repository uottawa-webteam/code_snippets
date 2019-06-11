<?php

/**
 * @file
 * Functions and hook implementations for this module.
 */

/***************************
 * INITIAL SETUP
 ***************************/

/**
 * Implements hook_menu().
 */
function uottawa_search_menu() {
    $uottawa_search_path = _uottawa_search_get_path();
    $items[$uottawa_search_path] = array(
        'title' => 'Search result',
        'page callback' => 'uottawa_search_simple_search_page',
        'access callback' => TRUE,
    );

    $advanced_enabled = variable_get('uottawa_core_allow_advanced_search', 0);
    if ($advanced_enabled) {
        $uottawa_adv_search_path = _uottawa_advanced_search_get_path();
        $items[$uottawa_adv_search_path] = array(
            'title' => t('Advanced Search result'),
            'page callback' => 'uottawa_search_advanced_search_page',
            'access callback' => TRUE,
        );
    }

    return $items;
}

/**
 * Function returning the partial path of the advanced search page
 *
 * @return string
 *   String containing the partial path
 */
function _uottawa_advanced_search_get_path() {
    return 'resultats-results-advanced';
}


/**
 * Function returning the partial path of the simple search page
 *
 * @return string
 *   String containing the partial path
 */
function _uottawa_search_get_path() {
    return 'resultats-results';
}


/**
 * Implements hook_form().
 */
function uottawa_search_simple_form($form, &$form_submit) {

    $form['search_field'] = array(
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => t('Search the University'),
        '#maxlength' => 100,
        '#prefix' => '<div class="search_field"><h2 class="uottawa_h2">' . t('Search the University') . '</h2>',
        '#suffix' => '</div>',
        '#attributes' => array('autofocus' => ''),
    );

    $form['submit'] = array(
        '#value' => t('Submit'),
        '#type' => 'submit',
    );

    $advanced_enabled = variable_get('uottawa_core_allow_advanced_search', 0);
    if ($advanced_enabled) {
        $uottawa_adv_search_path = _uottawa_advanced_search_get_path();

        $form['advanced_search_link'] = array(
            '#markup' => '<p><a class="uottawa-rtl" href="/' . $uottawa_adv_search_path . '">' . t("Advanced Search") . '</a></p>',
        );
    }

    return $form;
}

/**
 * Implements hook_FORM_validate().
 * Validation for the Simple Search Page
 */
function uottawa_search_simple_form_validate(&$form, &$form_state) {
    $str = $form_state['values']['search_field'];
    $uottawa_search_path = _uottawa_search_get_path();
    $q = _uottawa_search_encode_url_string($str, FALSE);
    if (strlen(trim($str)) < 2 || trim($str) == FALSE) {
        form_set_error('search_field', t('Search input should be at least 2 characters'));
        drupal_goto($uottawa_search_path . '/' . $q);
    }
    $form_state['redirect'] = $uottawa_search_path . '/' . $q;
}


/**
 * Implements hook_form().
 */
function uottawa_search_advanced_form($form, &$form_submit) {

    $form['all_match'] = array(
        '#type' => 'textfield',
        '#title' => t('all these words:'),
        '#maxlength' => 100,
        '#prefix' => '<h2 class="uottawa_h2">' . t("Find pages with...") . '</h2><div class="search_field"><h3>' . t('all these words:') . '</h3>',
        '#description' => t('Type the important words: tuition fees deadline'),
        '#suffix' => '</div>',
        '#attributes' => array('autofocus' => ''),
    );

    $form['exact_match'] = array(
        '#type' => 'textfield',
        '#title' => t('this exact word or phrase:'),
        '#maxlength' => 100,
        '#prefix' => '<div class="search_field"><h3>' . ('this exact word or phrase:').'</h3>',
        '#description' => t('Put exact words in quotes: "tuition fees"'),
        '#suffix' => '</div>',
        '#attributes' => array('autofocus' => ''),
    );

    $form['any_match'] = array(
        '#type' => 'textfield',
        '#title' => t('any of these words:'),
        '#maxlength' => 100,
        '#prefix' => '<div class="search_field"><h3>' . t('any of these words:') . '</h3>',
        '#description' => t('Type OR between all the words you want: fall OR winter'),
        '#suffix' => '</div>',
        '#attributes' => array('autofocus' => ''),
    );

    $form['no_match'] = array(
        '#type' => 'textfield',
        '#title' => t('any of these words:'),
        '#maxlength' => 100,
        '#prefix' => '<div class="search_field"><h3>' . t('none of these words:') . '</h3>',
        '#description' => t('Put a minus sign just before words that you don\'t want: -science, -"faculty of law"'),
        '#suffix' => '</div>',
        '#attributes' => array('autofocus' => ''),
    );

    $lang_options = array(
        'en' => t('English'),
        'fr' => t('French'),
    );
    $form['language_select'] = array(
        '#type' => 'select',
        '#title' => t('language') . ":",
        '#default_value' => _uottawa_search_get_lang_choice(),
        '#maxlength' => 100,
        '#prefix' => '<h2 class="uottawa_h2">' . t("Then narrow your results by...") . '</h2><div class="search_field"><h3>' . t('language') . ':</h3>',
        "#options" => $lang_options,
        '#description' => t('Find pages in the language that you select.'),
        '#suffix' => '</div>',
        '#attributes' => array('autofocus' => ''),
    );


    $form['last_updated'] = array(
        '#type' => 'select',
        '#title' => t('last update:'),
        '#maxlength' => 100,
        '#prefix' => '<div class="search_field"><h3>' . t('last update') . ':</h3>',
        '#options' => array(
            'any' => t('anytime'),
            'day' => t('last 24 hours'),
            'week' => t('up until a week ago'),
            'month' => t('up until a month ago'),
            'year' => t('up until a year ago'),
        ),
        '#description' => t('Find pages in the language that you select.'),
        '#suffix' => '</div>',
        '#attributes' => array('autofocus' => ''),
    );

    $form['submit'] = array(
        '#value' => t('Submit'),
        '#type' => 'submit',
    );

    $uottawa_search_path = _uottawa_search_get_path();
    $form['advanced_search_link'] = array(
        '#markup' => '<p><a class="uottawa-ltr" href="/' . $uottawa_search_path . '">' . t("Go back to simple search") . '</a></p>',
    );

    return $form;
}



/**
 * Implements hook_FORM_validate().
 * Validation for the Advanced Search Page
 */
function uottawa_search_advanced_form_validate(&$form, &$form_state) {
    $fields = _uottawa_search_get_advanced_form_field_keys();
    $uottawa_search_path = _uottawa_advanced_search_get_path();
    $q = "";
    foreach ($fields as $field) {
        $str = $form_state['values'][$field];
        $val = _uottawa_search_encode_url_string($str, FALSE);
        $field_q = ($q == "") ? $field . "=" . $val : "&" . $field . "=" . $val;
        $q = $q . $field_q;
    }

    $form_state['redirect'] = $uottawa_search_path . '/' . $q;
}


/**
 * Function that returns the SOLR key mapping for advanced search filters
 *
 * @param bool $just_keys - to return the keys only
 * @param bool $just_facets - to return just facets
 *
 * @return array
 */
function _uottawa_search_get_filter_keys_solr_map($just_keys = FALSE, $just_facets = FALSE) {
    $filter_keys = array(
        "language_select" => "lang",
        "last_updated" => "published_date",
    );

    $facet_keys = array(
        "title" => "title",
        "content" => "content",
        "desc" => "description",
        "keys" => "keywords",
        "site_name" => "site_name",
        "last_updated" => "published_date",
    );

    if ($just_facets) {
        if ($just_keys) {
            return array_keys($facet_keys);
        }
        return $facet_keys;
    }

    $combined = array_merge($filter_keys, $facet_keys);
    if ($just_keys) {
        return array_keys($combined);
    }
    return $combined;
}

/**
 * Function that returns the form field keys for advanced search page
 *
 * @param bool $no_filters
 *
 * @return array
 */
function _uottawa_search_get_advanced_form_field_keys($no_filters = FALSE) {
    $field_keys = array(
        "all_match",
        "exact_match",
        "any_match",
        "no_match",
    );

    $form_filter_keys = array(
        "language_select",
        "last_updated",
    );

    if ($no_filters) {
        return $field_keys;
    }

    return array_merge($field_keys, $form_filter_keys);
}



/*****************************************
		BUILD SOLR QUERY 
*****************************************/
		
	/**
 * Core functionality for the module.
 *
 * @param string $str
 *   String submitted by user.
 *
 * @global array $language
 *
 * @return string
 *   String containing JSON results of the query.
 */
function _uottawa_search_build_query($str, $page, $filters = array(), $is_advanced = FALSE) {
  $lang = _uottawa_search_get_lang();
  //URL for the solr handler
  $url = "";
  $query_pieces = array();
  // Check if the page is an integer <= 10
  $page = _uottawa_search_check_page($page);
  // Add parameters to send to Solr (build query)
  $query = _uottawa_search_add_parameters($str, $page, $filters, $is_advanced);
  // Set a 3 seconds timeout for solr to respond 
  $ctx = stream_context_create(array('http' => array('timeout' => 3)));
  $json = @file_get_contents($url . $query, FALSE, $ctx);
  return $json;
}




/**
 * Function for building the query string with q and others parameters.
 *
 * @param string $str
 *   The search criteria submitted by the user.
 * @param int $page
 *   The page number (optional)
 *
 * @param array $filters
 *   The filters that have been applied
 * @param bool $is_advanced
 *  To check if this is an advanced search
 * @return string
 *   String containing the built query
 */

function _uottawa_search_add_parameters($str, $page = 0, $filters = array(), $is_advanced = FALSE) {
  $filter_queries = array();
  $facet_exclude_queries = array();
  // Truncate if length > 100 (enable mb_strlen php function locally if needed)
  if (mb_strlen($str, 'UTF-8') > 100) {
    $str = _uottawa_search_truncate_string($str, 100);
  }
  $content_lang_choice = _uottawa_search_get_lang_choice();
  if ($is_advanced) {
    $advanced_params = _uottawa_search_handle_advanced_parameters($filters);
    $content_lang_choice = $advanced_params["lang"];
    $filter_queries = $advanced_params["filter_queries"];
    $facet_exclude_queries = $advanced_params["facet_exclude_queries"];
    if ($str == "") {
      $str = "*:*";
    }
  }

  $filter_queries[] = urlencode('lang:' . $content_lang_choice . ' OR lang:und');


  $parameters = array(
    'q' => $str,
    //'hl.fl' => 'content content_ext_en content_ext_fr content_ext_und url title_en title_fr title_und title',
    'fl' => '*',
    'rows' => 10,
    'start' => (10 * ($page)),
    'hl.encoder' => 'html',
    'facet' => 'on',
  );
  $parameters = array_merge($parameters, $facet_exclude_queries);

  $query = http_build_query($parameters);
  if (!empty($filter_queries)) {
    $query = $query . "&fq=" . implode("&fq=", ($filter_queries));
  }
  return   '?' . $query;
}



/**
 * Function that handles the Advanced Parameters such as Filter, Facets for SOLR query
 *
 * This based on the understanding that the SOLR facets are treated as filters once rendered.  
 * Also the chosen filter is added to the excludeTerms so they do not re-appear 
 *
 * @param $filters
 *
 * @return array
 */
function _uottawa_search_handle_advanced_parameters($filters) {

  //@TODO setting to [0} for now, would need to be changed if both language search is supported
  $content_lang_choice = $filters["lang"][0];
  if ($content_lang_choice == "") {
    $content_lang_choice = _uottawa_search_get_lang();
  }
  $filter_queries = array();
  $facet_exclude_queries = array();
  $facet_list = array("title", "content", "description", "keywords");
  
  if (!empty($filters)) {
    if (isset($filters["lang"])) {
      $content_lang_choice = $filters["lang"][0];
      unset($filters["lang"]);
    }

    foreach ($filters as $filter_key => $filter_values) {
      foreach ($filter_values as $filter_value) {
        $filter_queries[] = urlencode($filter_key . ':' . $filter_value);
        if (in_array($filter_key, $facet_list)) {
          if (isset($facet_exclude_queries["f." . $filter_key . ".facet.excludeTerms"])) {
            $facet_exclude_queries["f." . $filter_key . ".facet.excludeTerms"] = $facet_exclude_queries["f." . $filter_key . ".facet.excludeTerms"] . "," . $filter_value;
          }
          else {
            $facet_exclude_queries["f." . $filter_key . ".facet.excludeTerms"] = $filter_value;
          }
        }
      }
    }
  }

  return array(
    "filter_queries" => $filter_queries,
    "facet_exclude_queries" => $facet_exclude_queries,
    "lang" => $content_lang_choice,
  );
}


/********************************************
 *      DISPLAYING RESULTS
 *******************************************/


/**
 * Generates the Advanced Search Page
 *
 * @param string $param : Parameters
 * @param string $p : Page
 * @param string $content_lang : Language
 *
 * @return array : Search Form + Results
 */
function uottawa_search_advanced_search_page($param = '', $p = '', $content_lang = '') {

  $lang = _uottawa_search_get_lang();
  $field_keys = _uottawa_search_get_advanced_form_field_keys(TRUE);

  $filter_keys_solr_map = _uottawa_search_get_filter_keys_solr_map();


  $search_str = "";
  $filters = array();


  if ($content_lang) {
    if (in_array($content_lang, array("en", "fr"))) {
      $_SESSION['content_lang_choice'] = $content_lang;
    }
  }


  // Load Search form
  $content['form_container']['search_form'] = drupal_get_form('uottawa_search_advanced_form');
  if (!empty($param) && strlen($param) > 1) {
    $parameters = _uottawa_search_get_parameter_array($param);
    foreach ($parameters as $param_key => $param_value) {

      if (isset($filter_keys_solr_map[$param_key])) {
        $filter_value = $param_value;
        if ($param_key == "last_updated") {
          $filter_value = _uottawa_search_get_time_filter_val($param_value[0]);
        }

        if (isset($filter_keys_solr_map[$param_key]) && !empty($filter_value)) {
          $filters[$filter_keys_solr_map[$param_key]] = $filter_value;
          if (isset($content['form_container']['search_form'][$param_key])) {
            if (!in_array($param_key, $field_keys)) {
              $content['form_container']['search_form'][$param_key]['#value'] = $param_value[0];
              $content['form_container']['search_form'][$param_key]['#default_value'] = $param_value[0];
            }
          }
        }
      }

      if (in_array($param_key, $field_keys)) {
        if ($param_value != "") {
          if (isset($content['form_container']['search_form'][$param_key])) {
            if (in_array($param_key, $field_keys)) {
              $content['form_container']['search_form'][$param_key]['#value'] = $param_value;
            }
          }

          $search_str = ($search_str == "") ? $param_value : $search_str . " AND " . $param_value;
        }
      }
    }
  }

  if (!empty($param) && strlen($param) > 1) {
    $paging = (int) ($p ? trim($p) : 1);
    $query = _uottawa_search_build_query($search_str, $paging, $filters, TRUE);
    $content['results'] = _uottawa_process_results($query, $paging, $param, TRUE);
  }
  else {
    $content['results'] = '';
  }
  $page['content'] = $content;

  return $page;
}



/**
* Generates the search page.
 *
 * @param string $param
*   String submitted in search criteria input field.
 *
 * @param string $p
*   Page number requested
*
 * @param string $content_lang
* Language of the Content
*
 * @return array
 *   Returns renderable page.
 */
function uottawa_search_simple_search_page($param = '', $p = '', $content_lang = '') {

    $lang = _uottawa_search_get_lang();
    $page = array();

    if ($content_lang) {
        if (in_array($content_lang, array("en", "fr"))) {
            $_SESSION['content_lang_choice'] = $content_lang;
        }
    }

    // Load Search form
    $content['form_container']['search_form'] = drupal_get_form('uottawa_search_simple_form');
    $content['form_container']['search_form']['search_field']['#value'] = $param;

    if (!empty($param) && strlen($param) > 1) {
        $paging = (int) ($p ? trim($p) : 1);
        $query = _uottawa_search_build_query($param, $paging);
        $content['results'] = _uottawa_process_results($query, $paging, $param);
    }
    else {
        $content['results'] = '';
    }
    $page['content'] = $content;

    return $page;
}

/**
 * Function for processing results.
 *
 * @param string $decoded
 *   Results of the query in string array format.
 * @param integer $page
 *   Page result number requested.
 * @param string $param
 *   Query in string format.
 * @param bool $is_advanced
 *   Boolean to check if Search is advanced or not.
 *
 * @return array
 *   Returns the renderable results.
 */
function _uottawa_process_results($decoded, $page, $param, $is_advanced = FALSE) {

    $decodedArray = array();
    $results = array();

    // Case nothing is returned by Solr, user is redirected
    // to the google custom search engine
    if ($decoded === FALSE) {
        //fall back if there is an error in SOLR processing
    }
    else {
        $decodedArray = json_decode($decoded, TRUE);
        if ($decodedArray['response']['numFound'] > 0) {
            // markup for displaying results
        }

        elseif ($decodedArray['response']['numFound'] == 0) {
            //markup if no results are found
        }
        else {
            //markup if there is an error
        }
    }

    if(!empty($param)){
        //markup to display parameters
    }

    $facets = $decodedArray['facet_counts']["facet_fields"];

    if($facets){
        //markup for facets
    }

    if($is_advanced){
        //any special markup to be displayed if advanced page
    }

    return $results;
}




/*********************************
 * HELPER FUNCTIONS
 **********************************/


/**
 * Function returning the language content chosen by the user on which to query
 *
 * @return string
 *   String containing the language of the content to query
 */
function _uottawa_search_get_lang_choice() {
    $content_lang_choice = 'en';

    // Reset the variable session after 30 min to go back to default lang setting
    if (isset($_SESSION['LAST_SEARCH_ACTIVITY']) && (time() - $_SESSION['LAST_SEARCH_ACTIVITY'] > 1800)) {
        unset($_SESSION['content_lang_choice']);
    }
    // Update last activity time stamp
    $_SESSION['LAST_SEARCH_ACTIVITY'] = time();

    // If choice of search content language not set, take page lang as search content language
    if (!isset($_SESSION['content_lang_choice'])) {
        $content_lang_choice = _uottawa_search_get_lang();
    }
    else {
        if (@$_SESSION['content_lang_choice'] === 'fr') {
            $content_lang_choice = 'fr';
        }
    }
    return $content_lang_choice;
}


/**
 * Function for truncating search query if length > 100 characters.
 *
 * @param string $string
 *   Search criteria query string
 *
 * @param integer $max_width
 *   Maximium character allowed
 *
 * @return array
 *   Returns truncated string to the closest word under 100 character
 */
function _uottawa_search_truncate_string($string, $max_width) {
    $parts = preg_split('/([\s\n\r]+)/', $string, NULL, PREG_SPLIT_DELIM_CAPTURE);
    $parts_count = count($parts);
    $length = 0;
    $last_part = 0;
    for (; $last_part < $parts_count; ++$last_part) {
        $length += strlen($parts[$last_part]);
        if ($length > $max_width) {
            break;
        }
    }
    return implode(array_slice($parts, 0, $last_part));
}

/**
 * Function to properly encode the string
 *   Since parameters are passed by "URL parameter" instead of "query
 * parameter",
 *   '.' and '/' need to be encoded twice to not be interpreted by the browser
 *
 * @param string $str
 *   String of the query
 * @param boolean $encode_twice (optional)
 *   Set the double encoding option
 *
 * @return string
 *   Return the encoded string
 */
function _uottawa_search_encode_url_string($str, $encode_twice = TRUE) {
    // Encode string for url
    $str = rawurlencode($str);

    $str = str_replace('.', '%2E', $str);
    // Encode twice any dot (not encoded by rawurlencode) if it's for a link
    if ($encode_twice) {
        $str = str_replace('%2E', '%252E', $str);
    }

    $str = str_replace('/', '%2F', $str);
    // Encode twice any slash (not encoded by rawurlencode) if it's for a link
    if ($encode_twice) {
        $str = str_replace('%2F', '%252F', $str);
    }

    return $str;
}

/**
 * Function returning the language of the user interface
 *
 * @global array $language
 *
 * @return string
 *   String containing the language interface
 */
function _uottawa_search_get_lang() {
    global $language;
    return $language->language;
}



/**
 * Helper function for cleaning & checking page parameter integer.
 *
 * @param integer $value
 *   The integer that will need to be cleaned and check.
 * @return int|string
 *
 */

function _uottawa_search_check_page($value) {
    if (is_numeric($value)) {
        if ($value > 0 && $value <= 10) {
            return $value - 1;
        }
    }

    return 0;
}

		
?>