<?php
namespace Drupal\post;
use Drupal\library\Library;
use Drupal\post\Entity\PostConfig;
use Drupal\post\Entity\PostData;
use Symfony\Component\Yaml\Yaml;


/**
 *
 *
 */

class Post {

    public static function isPostPage() {
        $request = \Drupal::request();
        $uri = $request->getRequestUri();
        if ( strpos( $uri, '/post') !== FALSE ) {
            return TRUE;
        }
        else return FALSE;
    }

    public static function isAdminPage() {
        $request = \Drupal::request();
        $uri = $request->getRequestUri();
        if ( strpos( $uri, '/post/admin') !== FALSE ) {
            return TRUE;
        }
        else return FALSE;
    }


    public static function isSearchPage() {
        $request = \Drupal::request();
        $uri = $request->getRequestUri();
        if ( strpos( $uri, '/post/search') !== FALSE ) {
            return TRUE;
        }
        else return FALSE;
    }



    public static function emptyEntity($entity_type)
    {
        $entities = \Drupal::entityManager()->getStorage($entity_type)->loadMultiple();
        if ( $entities ) {
            foreach ( $entities as $entitiy ) {
                $entitiy->delete();
            }
        }
    }

    public static function emptyData() {

        //self::emptyEntity('post_config');
        //self::emptyEntity('post_data');

        db_truncate('post_config')->execute();
        db_truncate('post_data')->execute();
        db_truncate('post_history')->execute();

    }

    public static function getSearchQuery() {
        return \Drupal::request()->get('q');
    }
    public static function getSearchFieldName() {
        return \Drupal::request()->get('qn');
    }
    public static function getSearchFieldTitle() {
        return \Drupal::request()->get('qt');
    }
    public static function getSearchFieldContent() {
        return \Drupal::request()->get('qc');
    }

    /**
     * Returns search option
     *
     * @param null $post_config_name
     * @return array
     *
     *
     */
    public static function getSearchOptions($post_config_name=null) {

        $request = \Drupal::request();
        $g = Post::getGlobalConfig();


        $conds = [
            'q' => Post::getSearchQuery(),
            'qn' => Post::getSearchFieldName(),
            'qt' => Post::getSearchFieldTitle(),
            'qc' => Post::getSearchFieldContent()
        ];


        if ( ! empty($g['search_domain_group']) ) {
            $yml = Yaml::parse($g['search_domain_group']);

            $domain = Library::domainNameWithoutWWW();
            if ( isset($yml[$domain]) ) {
                $conds['domain'] = $yml[$domain];
            }
            else if ( isset($yml['domain']) && $yml['domain'] == 'default' ) {
                $conds['domain'] = $domain;
            }
        }

        // di($conds['domain']);


        if ( empty($post_config_name) ) $post_config_name = $request->get('post_config_name');

        if ( $post_config_name ) {
            $config = PostConfig::loadByName($post_config_name);
            if ( empty($config) ) {
                return [-91017, "Forum name $post_config_name does not exists."];
            }
            else {
                $conds['post_config_name'] = $config->label();
                $conds['no_of_items_per_page'] = $config->no_of_items_per_page->value;
                $conds['no_of_pages_in_navigation_bar'] = $config->no_of_pages_in_navigation_bar->value;
            }
        }

        if ( empty($conds['no_of_items_per_page']) ) $conds['no_of_items_per_page'] = $g['no_of_items_per_page'];
        if ( empty($conds['no_of_pages_in_navigation_bar']) ) $conds['no_of_pages_in_navigation_bar'] = $g['no_of_pages_in_navigation_bar'];


	
	// Sets 10 as default even if its 
        if ( empty($conds['no_of_items_per_page']) ) $conds['no_of_items_per_page'] = 10;
        if ( empty($conds['no_of_pages_in_navigation_bar']) ) $conds['no_of_pages_in_navigation_bar'] = 10;



        /**
         * @note Use 'offset' and 'limit' to do the navigation or block extraction.
         *          Do not use no_of_items_per_page.
         */
        $page_no = Library::getPageNo();
        $conds['offset'] = ($page_no-1) * $conds['no_of_items_per_page'];
        $conds['limit'] = $conds['no_of_items_per_page'];

        return $conds;
    }

    public static function getWidgets($widget) {
        $widgets = [];
        foreach(  glob(DIR_POST_TEMPLATES . "/widgets/$widget.*", GLOB_ONLYDIR ) as $filename ) {
            list($trash, $ex ) = explode("/widgets/$widget.", $filename);
            $ex = str_replace(".html.twig", '', $ex);
            $widgets[$ex] = $ex;
        }
        return $widgets;
    }

    public static function getWidgetSelectBox($widget, $default=null) {
        $widgets = self::getWidgets($widget);
        $twig = DIR_POST_TEMPLATES . "/elements/select.html.twig";
        $template = @file_get_contents($twig);
        $name = "widget_$widget";
        $markup = \Drupal::service('twig')->renderInline($template, ['name'=>$name, 'options'=>$widgets, 'default'=>$default]);
        return $markup;
    }



    public static function getGlobalConfig() {
        $g = Library::getGroupConfig('post_global_config');
        if ( empty($g['widget_search']) ) $g['widget_search'] = 'default';
        if ( empty($g['no_of_pages_in_navigation_bar']) ) $g['no_of_pages_in_navigation_bar'] = 10;
        if ( empty($g['no_of_items_per_page']) ) $g['no_of_items_per_page'] = 10;
        return $g;
    }

    public static function checkPermission($post) {
        if ( empty($post) ) return false;
        if ( Library::isAdmin() ) return true;
        if ( $post->user_id->target_id == Library::myUid() ) return true;
        return false;
    }

    public static function filesByType($files) {
        if ( empty($files) ) return false;
        $ret = [];
        foreach( $files as $file ) {
            $ret[ $file->type ][] = $file;
        }
        return $ret;
    }

    /**
     * @return bool
     *  - true if admin
     */
    public static function isAdmin() {
        if ( Library::isAdmin() ) return true;
        else if ( Library::isSiteAdmin() ) return TRUE;
        else return false;
    }




}
