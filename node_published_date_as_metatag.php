<?php 



/***********************************************************************************************************
   The following functions are used to create a node_published_date meta tag for basic pages and panel pages 

************************************************************************************************************/

/**
* Dependencies : 
* Metatag Module : https://www.drupal.org/project/metatag
* Publication Date Module : https://www.drupal.org/project/publication_date
**/


/**
 * Implements hook_metatag_config_default_alter().
 * Add following functions to module.inc file (only required to run once)
 */
function hook_metatag_config_default_alter(&$config) {
  if (!empty($config['global'])) {
    $config['global']->config['node_published_date'] = array('value' => 0);
  }
}

/**
 * Implements hook_metatag_info().
 * @author University of Ottawa Web Team
 * Add following functions to module.inc file (only required to run once)
 */
function hook_metatag_info() {
  $info = array();

    $info['tags']['node_published_date'] = array(
        'label' => t('Published Date for Node'),
        'description' => t("The published/changed date for the Node, this takes whichever is the latest"),
        'class' => 'DrupalTextMetaTag',
        'context' => array('global'),
        'group' => 'advanced',
        'weight' => 224,
    );
  return $info;
}



/**
 * Implements hook_panels_prerender_panes_alter()
 * @author University of Ottawa Web Team
 * Used to get the latest published date information for the content snippets and nodes that the panel is composed of
 * The date of the component that was updated most recently is used 
 * @param $pane_info
 */

function hook_panels_prerender_panes_alter($pane_info) {
    // check if the panel published date cache has expired 
    $expired = uottawa_is_panel_published_date_cache_expired();
    // if expired, get the panel published date again 
    if($expired){
        //get all the panes 
        $panes = $pane_info->prepared["panes"];
        $date = 0;
        // loop throght all the panes for the current page 
        foreach ($panes as $pane){
            //get panel type 
            $pane_type = $pane->type;
            // get panel configuration
            $pane_config= $pane->configuration;
            // Get Node Published Dates
            if($pane_type=="node" && isset($pane_config["nid"])){
                $node_changed_date = node_last_changed($pane_config["nid"]);
                //if this date is greater
                if($node_changed_date > $date){
                    $date = $node_changed_date;
                }
            }

            // Get Content Snippet Published Dates
            if($pane_type=="snippet" && isset($pane_config["csid"])){
                $cs_info = content_snippets_load($pane_config["csid"]);
                //if this date is greater
                if($cs_info->changed > $date){
                    $date = $cs_info->changed;
                }
            }
        }

        //Calling the caching function to set date
        // this value will be used by the hook_metatag_metatags_view_alter to create the meta tag
        uottawa_set_panel_published_date_cache($date);
    }

}

/**
 * Alter metatags before being cached.
 * @author University of Ottawa Web Team
 * This hook is invoked prior to the meta tags for a given page are cached.
 *
 * @param array $output
 *   All of the meta tags to be output for this page in their raw format. This
 *   is a heavily nested array.
 * @param string $instance
 *   An identifier for the current page's page type, typically a combination
 *   of the entity name and bundle name, e.g. "node:story".
 * @param array $options
 *   All of the options used to generate the meta tags.
 */

function hook_metatag_metatags_view_alter(&$output, $instance, $options) {
    //check if the node_published_date exists in the meta outputs
    if (isset($output['node_published_date']['#attached']['drupal_add_html_head'][0][0]['#value'])) {
        if(isset($options["entity"])){
            //get the default value of the node_published_date meta that by default has value [node:changed]
            $changed_date =  $options["entity"]->changed;
            // convert the string to date object
            $display_date = $changed_date;
            //default value of the published date
            $published_date_ts = 0;
            //check if the published date value exists for this node
            if(isset($options["entity"]->published_at){
                //set the published date variable
                $published_date_ts = $options["entity"]->published_at;
            }

            //check if $published_date is greater than $changed_date (Future Publication Date)
            if($published_date_ts > $changed_date){
                // replace the changed date with publication date
                $display_date = $published_date_ts;
            }

            if($display_date){
                //modify the output with the desired display date
                $output['node_published_date']['#attached']['drupal_add_html_head'][0][0]['#value'] = $display_date;
            }
        }

        //This checks if the current page is panel page and has a valid cache object 
        $cache_id = str_replace(array("/", "-"), '_', request_path())."_panel_published_date";
        $panel_cache_date = cache_get($cache_id) ? cache_get($cache_id) : 0;

        if($panel_cache_date){
            $output['node_published_date']['#attached']['drupal_add_html_head'][0][0]['#value'] = $panel_cache_date->data;
        }

    }
}

/**
 * Custom Function to create the date cache for Panel pages
 * @author University of Ottawa Web Team
 * @param $date
 */
function uottawa_set_panel_published_date_cache($date){
    //cache id is based on the request_path() to make sure it is unique 
    $cache_id = str_replace(array("/", "-"), '_', request_path())."_panel_published_date";
    $old_cache_date = cache_get($cache_id) ? cache_get($cache_id) : 0;
    //update only if the date has changed or the old date has been deleted 
    if((is_object($old_cache_date) && $date > $old_cache_date->data ) || (!is_object($old_cache_date) && $old_cache_date == 0)) {
        cache_set($cache_id, $date, "cache", time()+(86400)*7);
    }
}

/**
 * Custom Function to check if the panel published date cache is 7 days old
 * @return bool
 */
function uottawa_is_panel_published_date_cache_expired(){
    //cache id is based on the request_path() to make sure it is unique 
    $cache_id = str_replace(array("/", "-"), '_', request_path())."_panel_published_date";
    $old_cache_date = cache_get($cache_id) ? cache_get($cache_id) : 0;
    $cur_time = time();
    if(is_object($old_cache_date)){
        if(time() < $old_cache_date->expire){
            return FALSE;
        }
    }
    return TRUE;
}

?>