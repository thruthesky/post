<?php
namespace Drupal\post\Controller;
use Drupal\Core\Controller\ControllerBase;
use Drupal\library\Library;
use Drupal\post\Entity\PostConfig;
use Drupal\post\Entity\PostData;
use Drupal\post\Post;
use Symfony\Component\HttpFoundation\RedirectResponse;


class PostController extends ControllerBase {
    public static function postModuleIndex() {
        if ( $submit = \Drupal::request()->get('submit') ) self::$submit();

        $configs = PostConfig::loadMultiple();

        return [
            '#theme' => 'post.index',
            '#data' => [
                'configs' => $configs,
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
        $conds = post::getSearchOptions($post_config_name);
        if ( Library::getError() ) {
            $list = [];
        }
        else $list = PostData::collection($conds);
        $render_array = [
            '#theme' => 'post.list',
            '#data' => [
                'list' => $list,
            ],
        ];
        $render_array['#attached']['library'][] = 'post/list';
        return $render_array;
    }


    public static function postErrorPage() {
        $render_array = [
            '#theme' => 'post.error',
            '#data' => [
            ],
        ];
        //$render_array['#attached']['library'][] = 'post/error';
        return $render_array;
    }



    public static function postEdit($post_config_name=null, $id=null)
    {
        if ( \Drupal::request()->get('submit') == 'post' ) {
            $id = PostData::submitPost();
            if ( $id ) {
                $post = PostData::load($id);
                $config = $post->get('config_id')->entity;
                $config_name = $config->label();

                if ( $post->parent_id->value ) {
                    $root_id = PostData::getRootID($id);
                    return new RedirectResponse("/post/$config_name/$root_id#$id");
                }
                else {
                    return new RedirectResponse("/post/$config_name/$id?" . $post->label());
                }



                // return self::postListPage($config->label());
            }
        }
        return self::postEditPage($post_config_name, $id);
    }


    private static function postEditPage($post_config_name, $id) {
        if ( is_numeric($id) ) {
            $post = PostData::load($id);
            $config = $post->get('config_id')->entity;
        }
        else {
            $config = PostConfig::loadByName($post_config_name);
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


    public static function postView($config_post_name, $id)
    {
        if ( ! Post::exist($id) ) return self::postErrorPage();
        /**
         * Redirects to the root post if $id is a comment.
         */
        if ( $id ) {
            $root_id = PostData::getRootID($id);
            if ( $id != $root_id ) {
                return new RedirectResponse("/post/$config_post_name/$root_id#$id");
            }
        }

        $post = PostData::view($id);
        $config = $post->get('config_id')->entity;
        $conds = post::getSearchOptions($config->label());
        $list = PostData::collection($conds);
        $comments = PostData::comments($id);

        $render_array = [
            '#theme' => 'post.view',
            '#data' => [
                'config' => $config,
                'post' => $post,
                'comments' => $comments,
                'list' => $list,
            ]
        ];
        $render_array['#attached']['library'][] = 'post/view';
        return $render_array;
    }

    public function postConfig($post_config_name)
    {
        $config = null;
        if ( Library::isFromSubmit() ) {
            if ( $re = PostConfig::update() ) {
                if ( Library::isError($re) ) {
                    $config = PostConfig::loadByName($post_config_name);
                }
                else {
                    $config = $re;
                }
            }
        }
        else {
            $config = PostConfig::loadByName($post_config_name);
        }
        $widgets = [];
        if ( empty($config) ) Library::error(-91006, "No forum exists by that name - $post_config_name");
        else {
            $widgets['list'] = Post::getWidgetSelectBox('list', $config->get('widget_list')->value);
            $widgets['search_box'] = Post::getWidgetSelectBox('search_box', $config->get('widget_search_box')->value);
            $widgets['view'] = Post::getWidgetSelectBox('view', $config->get('widget_view')->value);
            $widgets['edit'] = Post::getWidgetSelectBox('edit', $config->get('widget_edit')->value);
            $widgets['comment'] = Post::getWidgetSelectBox('comment', $config->get('widget_comment')->value);
            $widgets['search'] = Post::getWidgetSelectBox('search', $config->get('widget_search')->value);
        }
        return [
            '#theme' => 'post.config',
            '#data' => [
                'config' => $config,
                'widgets' => $widgets,
            ]
        ];
    }

    public static function postCommentSubmit()
    {
        $request = \Drupal::request();
        $parent_id = $request->get('parent_id');
        $parent = PostData::load($parent_id);
        $p = [];

        $p['config_id'] = $parent->get('config_id')->entity->id();
        $p['parent_id'] = $parent->id();
        $p['content'] = $request->get('content');

        $id = PostData::insert($p);

        $config_name = $parent->get('config_id')->entity->label();
        $root_id = PostData::getRootID($parent_id);
        return new RedirectResponse("/post/$config_name/$root_id#$id");
    }

    public static function postSearch()
    {

        $conds = post::getSearchOptions();
        $list = PostData::collection($conds);
        $render_array = [
            '#theme' => 'post.search',
            '#data' => [
                'list'=>$list
            ]
        ];
        $render_array['#attached']['library'][] = 'post/search';
        return $render_array;
    }

    public static function postAdmin()
    {
        if ( Library::isFromSubmit() ) Library::saveFormSubmit('post_global_config');

        $post_global_config = Library::getGroupConfig('post_global_config');

        return [
            '#theme' => 'post.admin',
            '#data' => [
                'post_global_config' =>  $post_global_config,
            ]
        ];
    }


    /**
     *
     * Delete a post or all of the thread.
     *
     * @param null $post_config_name
     * @param null $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     */
    public static function postDelete($post_config_name=null, $id=null)
    {
        if ( ! Post::exist($id) ) return self::postErrorPage();
        PostData::deletePost($id);
        $post = PostData::load($id);
        if ( $post ) {
            $config = $post->get('config_id')->entity;
            $config_name = $config->label();
            if ( $post->parent_id->value ) {
                $root_id = PostData::getRootID($id);
                return new RedirectResponse("/post/$config_name/$root_id#$id");
            }
            else {
                return new RedirectResponse("/post/$config_name/$id?" . $post->label());
            }
        }
        return new RedirectResponse("/post/$post_config_name");
    }

    /**
     * @todo Let only admin do this!!
     * @param $post_config_name
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public static function postForceDelete($post_config_name, $id) {
        if ( Library::isAdmin() ) {
            PostData::forceDeleteThread($id);
            return new RedirectResponse("/post/$post_config_name");
        }
        else {
            Library::error(-94040, "You are not admin. Only admin can force-delete a whole thread.");
            return self::postErrorPage();
        }
    }

}
