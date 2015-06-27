<?php
namespace Drupal\post\Controller;
use Drupal\Core\Controller\ControllerBase;
use Drupal\library\Library;
use Drupal\post\Entity\PostConfig;
use Drupal\post\Entity\PostData;
use Drupal\post\Post;
use Drupal\post\PostConfigInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;


class PostController extends ControllerBase {
    public static function postModuleIndex() {

        if ( $submit = \Drupal::request()->get('submit') ) self::$submit();


        return [
            '#theme' => 'post.index',
            '#data' => [
                'configs' => PostConfig::loadMultiple()
            ]
        ];
    }

    public static function config_create()
    {
        PostConfig::createForum();
    }
    public static function postList($post_config_name)
    {
        return self::postListPage($post_config_name);
    }

    public static function postListPage($post_config_name)
    {


        $conds = post::getSearchCondition($post_config_name);
        $list = PostData::collection($conds);

        return [
            '#theme' => 'post.list',
            '#data' => [
                'list' => $list,
            ]
        ];
    }




    public static function postEdit($id)
    {
        if ( \Drupal::request()->get('submit') == 'post' ) {
            $id = PostData::submitPost();
            if ( $id ) {
                $post = PostData::load($id);
                $config = $post->get('config_id')->entity;

                return new RedirectResponse('/post/' . $config->label());

                // return self::postListPage($config->label());
            }
        }
        return self::postEditPage($id);
    }


    private static function postEditPage($id) {
        if ( is_numeric($id) ) {
            $post = PostData::load($id);
            $config = $post->get('config_id')->entity;
        }
        else {
            $config = PostConfig::loadByName($id);
            $post = null;
        }
        return [
            '#theme' => 'post.edit',
            '#data' => [
                'config' => $config,
                'post' => $post
            ]
        ];
    }


    public static function postView($id)
    {
        $post = PostData::load($id);
        $config = $post->get('config_id')->entity;
        $conds = post::getSearchCondition($config->label());
        $list = PostData::collection($conds);

        return [
            '#theme' => 'post.view',
            '#data' => [
                'config' => $config,
                'post' => $post,
                'list' => $list,
            ]
        ];
    }
}
