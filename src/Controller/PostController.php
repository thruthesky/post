<?php
namespace Drupal\post\Controller;
use Drupal\Core\Controller\ControllerBase;
use Drupal\library\Library;
use Drupal\library\Member;
use Drupal\post\Entity\PostConfig;
use Drupal\post\Entity\PostData;
use Drupal\post\Post;
use Symfony\Component\HttpFoundation\RedirectResponse;


class PostController extends ControllerBase {
    public static function postModuleIndex() {
        $data = ['page'=>'index'];
        if ( $submit = \Drupal::request()->get('submit') ) self::$submit($data);

        $configs = PostConfig::loadMultiple();
        $data['configs'] = $configs;

        return [
            '#theme' => 'post.layout',
            '#data' => $data
        ];
    }

    public static function config_create(&$data)
    {
        PostConfig::createForum($data);
    }
    public static function postList($post_config_name)
    {
        //di('postList()');
        return self::postListPage($post_config_name);
    }

    public static function postListPage($post_config_name)
    {
        $config = PostConfig::loadByName($post_config_name);
        if ( empty($config) ) return self::errorPage("Forum not exists by that name - $post_config_name");


        $conds = post::getSearchOptions($post_config_name);
        if ( Library::isError($conds)) {
            $list = [];
        }
        else $list = PostData::collection($conds);


        $render_array = [
            '#theme' => 'post.layout',
            '#data' => [
                'page' => 'list',
                'list' => $list,
            ],
        ];
        $render_array['#attached']['library'][] = 'post/list';
        return $render_array;
    }


    /**
     * @param $error
     * @return bool
     *
     */
    public static function errorPage($error) {
        $render_array = [
            '#theme' => 'post.layout',
            '#data' => [
                'page' => 'error',
                'error' => $error
            ],
        ];
        //$render_array['#attached']['library'][] = 'post/error';
        return $render_array;
    }



    public static function postEdit($post_config_name=null, $id=null)
    {
        if ( ! Library::login() ) return self::errorPage("Please, sign in first before you create/edit a post.");
        if ( Library::isFromSubmit() ) {
            $id = PostData::submitPost();
            if ( is_numeric($id) && $id > 0 ) {
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
            else {
                return self::errorPage($id);
            }
        }
        return self::postEditPage($post_config_name, $id);
    }


    private static function postEditPage($post_config_name, $id) {
        if ( is_numeric($id) ) {
            $post = PostData::load($id);
            if ( $post ) { // is Edit?
                $config = $post->get('config_id')->entity;
                if ( Post::checkPermission($post) ) return self::getPostUploadTheme($post, $config);
                else return self::errorPage("You do not have permission to edit this post.");
            }
            else return self::errorPage("No post exists by that ID.");
        }
        else { // is New Posting
            $config = PostConfig::loadByName($post_config_name);
            return self::getPostUploadTheme(null, $config);
        }
    }

    /**
     *
     * Returns the theme information
     *
     * @param $post
     * @param $config
     * @return array
     *
     * @note if the post has files, then it add files here.
     */
    public static function getPostUploadTheme($post, PostConfig $config)
    {
        $files = null;
        $filesByType = null;
        if ( $post ) {
            $files = PostData::files($post->id());
            $filesByType = Post::filesByType($files);
        }
        $render_array = [
            '#theme' => 'post.layout',
            '#data' => [
                'page' => 'edit',
                'config' => $config,
                'post' => $post,
                'files' => $files,
                'filesByType' => $filesByType
            ]
        ];
        $render_array['#attached']['library'][] = 'post/edit';
        return $render_array;
    }


    public static function postView($config_post_name, $id)
    {
        if ( ! PostData::exist($id) ) {
            return self::errorPage("Post not found by that ID - [ $id ]. The post may be deleted. Please search for what you want.");
        }
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
        $comments = PostData::comments($id);
        $files = PostData::files($id);
        $filesByType = Post::filesByType($files);
        $post_member = Member::load( $post->user_id->target_id );

        $list = null;
        if ( $config->list_under_view->value == 'Y' ) {
            $conds = post::getSearchOptions($config->label());
            $list = PostData::collection($conds);
        }

        $render_array = [
            '#theme' => 'post.layout',
            '#data' => [
                'page' => 'view',
                'config' => $config,
                'post' => $post,
                'post_member' => $post_member,
                'files' => $files,
                'filesByType' => $filesByType,
                'comments' => $comments,
                'list' => $list,
            ]
        ];
        $render_array['#attached']['library'][] = 'post/view';
        return $render_array;
    }

    public function postConfig($post_config_name)
    {
        $data = ['page'=>'config'];
        if ( Library::isFromSubmit() ) {
            if ( $re = PostConfig::update() ) {
                if ( Library::isError($re) ) {
                    $data['error'] = Library::readError($re);
                    $data['config'] = PostConfig::loadByName($post_config_name);
                }
                else {
                    $data['config'] = $re;
                }
            }
        }
        else {
            $data['config'] = PostConfig::loadByName($post_config_name);
        }


        if ( empty( $data['config'] ) ) {
            return self::errorPage("Post Config:: No forum exists by that forum name - $post_config_name");
        }
        else {
            $config = $data['config'];
            $widgets = [];
            $widgets['list'] = Post::getWidgetSelectBox('list', $config->get('widget_list')->value);
            $widgets['search_box'] = Post::getWidgetSelectBox('search_box', $config->get('widget_search_box')->value);
            $widgets['view'] = Post::getWidgetSelectBox('view', $config->get('widget_view')->value);
            $widgets['edit'] = Post::getWidgetSelectBox('edit', $config->get('widget_edit')->value);
            $widgets['comment'] = Post::getWidgetSelectBox('comment', $config->get('widget_comment')->value);
            $data['widgets'] = $widgets;
        }
        return [
            '#theme' => 'post.layout',
            '#data' => $data,
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
        $data = ['page'=>'search'];
        $conds = post::getSearchOptions();
        $conds['original_only'] = false; // this must be placed only here.
        if ( Library::isError($conds) ) {
            $data['error'] = Library::readError($conds);
        }
        else {
            $data['list'] = PostData::collection($conds);
        }
        $render_array = [
            '#theme' => 'post.layout',
            '#data' => $data
        ];
        $render_array['#attached']['library'][] = 'post/search';
        return $render_array;
    }

    public static function postAdminGlobalConfig()
    {
        if ( Library::isFromSubmit() ) Library::saveFormSubmit('post_global_config');
        $post_global_config = Library::getGroupConfig('post_global_config');
        $search = Post::getWidgetSelectBox('search', $post_global_config['widget_search']);
        return [
            '#theme' => 'post.layout',
            '#data' => [
                'page' => 'admin-global',
                'post_global_config' =>  $post_global_config,
                'search' => $search
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
        if ( ! PostData::exist($id) ) {
            return self::errorPage("Post not found by that ID - [ $id ]. The post may be deleted. Please search for what you want.");
        }

        $post = PostData::load($id);
        if ( ! Post::checkPermission($post) ) return self::errorPage("You cannot delete this post. You do not have permission.");
        if ( $post ) { // post exist
            if ( $re = PostData::deletePost($id) ) return self::errorPage($re);
            $post = PostData::load($id);
            if ( $post ) { // marked as deleted.
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
        }
        return new RedirectResponse("/post/$post_config_name"); // completely deleted.
    }

    /**
     *
     * @todo Let only admin do this!!
     * @param $post_config_name
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public static function postForceDelete($post_config_name, $id) {
        if ( Post::isAdmin() ) {
            PostData::forceDeleteThread($id);
            return new RedirectResponse("/post/$post_config_name");
        }
        else {
            return self::errorPage("You are not admin. Only admin can force-delete a whole thread.");
        }
    }

}
