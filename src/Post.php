<?php
namespace Drupal\post;


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
}