
/**
 * Implements hook_panels_prerender_panes_alter()
 * Used to get the latest published date information for the content snippets and nodes that the panel is composed of
 * @param $pane_info
 */

function uottawa_core_panels_prerender_panes_alter($pane_info) {
    $expired = uottawa_core_is_panel_published_date_cache_expired();
    if($expired){
        $panes = $pane_info->prepared["panes"];
        $date = 0;
        foreach ($panes as $pane){
            $pane_type = $pane->type;
            $pane_config= $pane->configuration;
            // Get Node Published Dates
            if($pane_type=="node" && isset($pane_config["nid"])){
                $node_changed_date = node_last_changed($pane_config["nid"]);
                if($node_changed_date > $date){
                    $date = $node_changed_date;
                }
            }

            // Get Content Snippet Published Dates
            if($pane_type=="snippet" && isset($pane_config["csid"])){
                $cs_info = content_snippets_load($pane_config["csid"]);
                if($cs_info->changed > $date){
                    $date = $cs_info->changed;
                }
            }
        }

        //Calling the caching function to set date
        uottawa_core_set_panel_published_date_cache($date);
    }

}

/**
 * @author Winnerjit Singh Rathor <wrathor@uottawa.ca>
 * Alter metatags before being cached.
 *
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

function uottawa_core_metatag_metatags_view_alter(&$output, $instance, $options) {
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
            if(isset($options["entity"]->uottawa_publish_date["und"][0]{"value"})){
                //set the published date variable
                $published_date = $options["entity"]->uottawa_publish_date["und"][0]{"value"};
                //get date object from published date string (2019-04-08T01:00:00)
                //Note T is being escaped here using /T as T also means Timezone in dateFormat
                //Note: strtotime() does not work in this case because of presence  of / and - (Thu, 23/02/2012 - 15:18)
                //https://www.hashbangcode.com/article/how-i-learned-stop-using-strtotime-and-love-php-datetime
                $published_date_obj = DateTime::createFromFormat('Y-m-d\TH:i:s', $published_date);
                $published_date_ts = $published_date_obj->getTimestamp();
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

        $cache_id = str_replace(array("/", "-"), '_', request_path())."_panel_published_date";
        $panel_cache_date = cache_get($cache_id) ? cache_get($cache_id) : 0;

        if($panel_cache_date){
            $output['node_published_date']['#attached']['drupal_add_html_head'][0][0]['#value'] = $panel_cache_date->data;
        }

    }
}

/**
 * Function to create the date cache for Panel pages
 * @param $date
 */
function uottawa_core_set_panel_published_date_cache($date){
    $cache_id = str_replace(array("/", "-"), '_', request_path())."_panel_published_date";
    $old_cache_date = cache_get($cache_id) ? cache_get($cache_id) : 0;
    if((is_object($old_cache_date) && $date > $old_cache_date->data ) || (!is_object($old_cache_date) && $old_cache_date == 0)) {
        cache_set($cache_id, $date, "cache", time()+(86400)*7);
    }
}

/**
 * Function to check if the panel published date cache is 7 days old
 * @return bool
 */
function uottawa_core_is_panel_published_date_cache_expired(){
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