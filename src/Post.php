<?php
namespace Drupal\post;
use Drupal\post\Entity\PostConfig;
use Drupal\post\Entity\PostData;


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
        self::emptyEntity('post_config');
        self::emptyEntity('post_data');
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

    public static function getSearchCondition($post_config_name=null) {

        $conds = [
            'q' => Post::getSearchQuery(),
            'qn' => Post::getSearchFieldName(),
            'qt' => Post::getSearchFieldTitle(),
            'qc' => Post::getSearchFieldContent()
        ];

        if ( $post_config_name ) {
            $config = PostConfig::loadByName($post_config_name);
            $conds['post_config_name'] = $config->label();
        }

        if ( empty($conds['no_of_items_per_page']) ) $conds['no_of_items_per_page'] = state('post_global_config.no_of_item_in_list');
        if ( empty($conds['no_of_pages_in_navigation_bar']) ) $conds['no_of_pages_in_navigation_bar'] = state('post_global_config.no_of_page_in_navigator');


        return $conds;
    }

    public static function getWidgets($widget) {
        $widgets = [];
        foreach(  glob(DIR_POST_TEMPLATES . "/widgets/$widget.*") as $filename ) {
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

}
