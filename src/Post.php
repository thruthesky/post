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

    public static function getSearchCondition($post_config_name) {
        $config = PostConfig::loadByName($post_config_name);
        $conds = [
            'post_config_name'=>$config->label(),
            'q' => Post::getSearchQuery(),
            'qn' => Post::getSearchFieldName(),
            'qt' => Post::getSearchFieldTitle(),
            'qc' => Post::getSearchFieldContent()
        ];
        return $conds;
    }

}
