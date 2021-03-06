<?php
namespace Drupal\post\Entity;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\file\Entity\File;
use Drupal\library\Library;
use Drupal\library\Member;
use Drupal\post\Post;
use Drupal\post\PostDataInterface;
use Drupal\user\UserInterface;

/**
 *
 *
 *
 *
 * @ContentEntityType(
 *   id = "post_data",
 *   label = @Translation("Post Data entity"),
 *   base_table = "post_data",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class PostData extends ContentEntityBase implements PostDataInterface {



    /**
     * Returns true if the post exists Or return false with error information.
     *
     * @param $id
     * @return bool
     *
     *      - true if exists.
     *      - false if not exists.
     *
     */
    public static function exist($id) {
        $post = PostData::load($id);
        if ( $post ) return true;
        else return false;
    }


    /**
     *
     * @param $id
     * @return mixed
     *
     */
    public static function getRoot($id) {
        $paths = self::loadParents($id);
        $reversed = array_reverse($paths);
        return reset($reversed);
    }

    public static function getRootID($id) {
        $root = self::getRoot($id);
        return $root->id();
    }
    public static function loadParents($id) {
        $entity = self::load($id);
        $rows = [];
        if ( $entity ) {
            $rows[] = $entity;
            $pid = $entity->parent_id->value;
            if ( $pid ) {
                $returns = self::loadParents($pid);
                $rows = array_merge($rows, $returns);
            }
        }
        return $rows;
    }


    /**
     * @param $conds
     *
     * @return \Drupal\Core\Database\Query\Select
     *
     * @refer buildguide : https://docs.google.com/document/d/1koxonGQl20ER7HZqUfHd6L53YXT5fPlJxCEwrhRqsN4/edit#heading=h.iijrsjoqv867
     *
     *
     *
     */
    public static function getQueryOnConds($conds)
    {

        $config = null;
        $and = [];
        if ( ! empty($conds['post_config_name']) ) {
            $config = PostConfig::loadByName($conds['post_config_name']);
            if ( empty($config) ) {
                // @note This is an error But it is already handled in getSearchOptions()
            }
            else {
                $and[] = 'config_id=' . $config->id();
            }
        }

        if ( ! isset($conds['original_only']) ) $and[] = 'parent_id=0';
        else if ($conds['original_only']) $and[] = 'parent_id=0';

        if ( isset($conds['fid_of_first_image']) && $conds['fid_of_first_image'] === true ) $and[] = 'fid_of_first_image>0';

        if ( isset($conds['domain']) ) {
            if ( is_string($conds['domain'])) $and[] = "domain='$conds[domain]'";
            else if ( is_array($conds['domain'])) {
                $ors = [];
                foreach( $conds['domain'] as $d ) {
                    $ors[] = "domain='$d'";
                }
                $and[] = '(' . implode(' OR ', $ors) . ')';
            }
        }


        if ( ! empty($conds['q']) ) {
            if ( ! empty($conds['qn']) ) {
                $user = user_load_by_name($conds['q']);
                if ( $user ) $ands[] = 'user_id=' . $user->id();
            }
            else if ( ! empty($conds['qt']) || ! empty($conds['qc']) ) {
                $words = explode(' ', $conds['q'], 2);
                foreach( $words as $word ) {
                    $or = [];
                    if ( ! empty($conds['qt']) ) $or[] = "`title` LIKE '%$word%'";
                    if ( ! empty($conds['qc']) ) $or[] = "content_stripped__value LIKE '%$word%'";
                    $and[] = '(' . implode(' OR ', $or) .')';
                }
            }
            else {
                // no filtering
                $words = explode(' ', $conds['q'], 2);
                foreach( $words as $word ) {
                    $or = [];
                    $or[] = "`title` LIKE '%$word%'";
                    $or[] = "content_stripped__value LIKE '%$word%'";
                    $and[] = '(' . implode(' OR ', $or) .')';
                }
            }

        }

        if ( $and ) return "WHERE " . implode(' AND ', $and);
        else return null;



    }
    /**
     * Returns the searched of posts
     *
     * @param $conds - is the condition array
     *
     *      'order_field'       - is the order field
     *      'order_direction'   - ASC or DESC
     *      'offset'            - is the beginning record to extract
     *      'limit'             - is the number of posts
     *      'post_config_name'  - is the post group name(id)
     *      'original_only'     - is the original(root) post only. If true, it only return root posts. if it is false, including all comments.
     *                          ==> If it is not set, it is set to true.
     *                          ==> So, it must be explicitly set to false to search comments.
     *      'fid_of_first_image'    - if set true, it return only those posts that has image. it returns the first fid of attached images.
     *      'domain'            - is the domain to extract. it can be string or array.
     *      'q'                 - is the search query
     *      'qt'                - if set, it searches title
     *      'qc'                - if set, it searches comment
     *      'qn'                - if set, it searches name
     *                          ==> if q is set and qt,qc,qn is empty, then it searches title and comments.
     *                          ==> if qn is set, qt and qc is ignored
     *
     *
     *
     *
     * @return static[]
     *
     *
     *
     */
    public static function search($conds) {
        $where = self::getQueryOnConds($conds);

        // default
        if ( empty($conds['order_field']) ) $conds['order_field'] = 'id';
        if ( empty($conds['order_direction']) ) $conds['order_direction'] = 'DESC';
        if ( empty($conds['offset']) ) $conds['offset'] = 0;
        if ( empty($conds['limit']) ) $conds['limit'] = 10;

        $q = "SELECT `id` FROM post_data $where ORDER BY `$conds[order_field]` $conds[order_direction] LIMIT $conds[offset],$conds[limit]";
        $result = db_query($q);
        Library::log("PostData::search : $q");

        $rows = $result->fetchAll(\PDO::FETCH_NUM);
        if ( $rows ) {
            $ids = [];
            foreach($rows as $row) {
                $ids[] = $row[0];
            }
            return self::loadMultiple($ids);
        }
        return false;

        /**
         * @deprecated
         *
        $db = self::getQueryOnConds($conds);
        $db->fields(null, ['id']);
        $page_no = Library::getPageNo();
        $start = ($page_no-1) * $conds['no_of_items_per_page'];
        $db->range($start, $conds['no_of_items_per_page']);
        $db->orderBy('id', 'DESC');
        $result = $db->execute();
        Library::log("search() begins");
        Library::log( $result->getQueryString() );
        $rows = $result->fetchAll(\PDO::FETCH_NUM);
        if ( $rows ) {
            $ids = [];
            foreach($rows as $row) {
                $ids[] = $row[0];
            }
            return self::loadMultiple($ids);
        }
        return false;
         *
         */
    }


    /**
     * Returns the number of posts
     *
     * @param $conds - is the condition array
     *
     * @return int
     */
    public static function count($conds) {

        $where = self::getQueryOnConds($conds);
        $result = db_query("SELECT COUNT(*) FROM post_data $where");
        $count = $result->fetchField();
        return $count;



        /**
         * @deprecated code
         *
        $db = self::getQueryOnConds($conds);
        $db->addExpression('COUNT(*)');
        $result = $db->execute();
        $no = $result->fetchField();
        return $no;
        */

    }

    public static function submitPost() {
        $request = \Drupal::request();
        $id = $request->get('id');
        $config_name = $request->get('post_config_name');
        $p = [];
        $p['title'] = $request->get('title');
        $p['content'] = $request->get('content');

        if ( $id && is_numeric($id) ) {
            $id = self::update($id, $p);
            if ( $id == -1 ) {
                return "Wrong post id.";
            }
        }
        else if ( $config_name ) {
            $config = PostConfig::loadByName($config_name);
            $p['config_id'] = $config->id();
            $id = self::insert($p);
        }
        else {
            return "Wrong post id.";
        }

        return $id;
    }

    private static function update($id, $p) {
        $post = self::load($id);
        if ( ! $post ) return -1;
        foreach( $p as $k => $v ) {
            $post->set($k, $v);
            if ( $k == 'content' ) {
                $post->set('content_stripped', strip_tags($v));
            }
        }

        $post->set('ip',  Library::clientIP());
        $post->set('browser_id', Library::getBrowserID());

        $post->save();
        self::updateUploadedFiles($post->id());
        return $post->id();
    }

    /**
     *
     * This inserts a post or a comment.
     *
     *
     * @param $p - post information
     *
     * @return int|mixed|null|string
     *
     * @code
            $p = [
     *      'config_id' => 1,
            'username' => 'firefox',
            'title' => "이것은 제목입니다. $i",
            'content' => "<h1>이것은 내용!</h1>그럼",
            ];
            PostData::insert('freetalk', $p);
     * @endcode
     *
     * @code How To Add A Comment
            $request = \Drupal::request();
            $parent_id = $request->get('parent_id');
            $parent = PostData::load($parent_id);
            $p = [];
            $p['config_id'] = $parent['config_id'];
            $p['parent_id'] = $parent['id'];
            $p['content'] = $request->get('content');
            PostData::insert($p);
     * @endcode
     *
     *
     */
    public static function insert($p) {
        $post = self::create();

        $post->set('root_id',  0);
        $post->set('parent_id',  0);
        $post->set('no_of_view',  0);
        $post->set('no_of_comment',  0);
        $post->set('vote_good',  0);
        $post->set('vote_bad',  0);
        $post->set('secret',  false);
        $post->set('blind',  false);
        $post->set('deleted',  false);

        $post->set('ip',  Library::clientIP());
        $post->set('browser_id', Library::getBrowserID());


        $post->set('domain', Library::domainNameWithoutWWW());
        $post->set('user_agent', Library::userAgent());

        // post config
        if ( isset($p['post_config_name']) ) {
            $config = PostConfig::loadByName($p['post_config_name']);
            $p['config_id'] = $config->id();
            unset($p['post_config_name']);
        }

        // user id
        if ( isset($p['user_id']) ) { }
        else if ( isset($p['username']) ) {
            $user = user_load_by_name($p['username']);
            if ( $user ) {
                $post->set('user_id', $user->id());
            }
            else $post->set('user_id', 0);
            unset($p['username']);
        }
        else {
            $post->set('user_id', Library::myUid());
        }


        // content
        if ( isset($p['content']) ) $p['content_stripped'] = strip_tags($p['content']);

        foreach( $p as $k => $v ) {
            $post->set($k, $v);
        }



        /**
         * This is a comment.
         */
        if ( ! empty($p['parent_id']) ) {
            $root_id = self::getRootID($p['parent_id']);
            $post->set('root_id', $root_id);
        }
        $post->save();

        if ( ! empty($root_id) ) {
            self::updateRoot($root_id);
        }

        self::updateUploadedFiles($post->id());

        return $post->id();
    }

    /**
     * @param $conds
     * @return array
     *      - list['config'] - is the config of the post if it is not seach
     *      - list['no_of_posts'] - is the number of posts
     *      - list['posts'] - is the posts
     *      - list['navigation'] - is the navigation bar
     */
    public static function collection($conds) {
        $list = [];

        if ( Post::isSearchPage() ) {
            $path = '/post/search';
        }
        else {
            $list['config'] = PostConfig::loadByName($conds['post_config_name']);
            $path = "/post/" . $conds['post_config_name'];
        }
        $list['posts'] = PostData::search($conds);
        $list['no_of_posts'] = PostData::count($conds);

        $list['navigation'] = Library::paging(
            Library::getPageNo(),
            $list['no_of_posts'],
            $conds['no_of_items_per_page'],
            null,
            $conds['no_of_pages_in_navigation_bar'],
            ["&lt;&lt;", "Previous <span class='bno'>(n)</span>", "Next <span class='bno'>(n)</span>", "&gt;&gt;"],
            $path
        );
        return $list;
    }

    public static function view($id) {
        $post = PostData::load($id);
        $no = $post->get('no_of_view')->value;		
		if( $post->user_id->target_id != Library::myUid() ) $post->set('no_of_view', $no + 1);		
        $post->save();
        return $post;
    }

    /**
     * Returns all comments of a Post ID.
     * @param $id
     * @param int $depth
     * @return array
     */
    public static function comments($id,$depth=0) {
        $comments = \Drupal::entityManager()->getStorage('post_data')->loadByProperties(['parent_id'=>$id]);
        $rows = [];
        foreach( $comments as $c ) {
            $c->depth = $depth;
            $c->files = self::files($c->id());
			$c->post_member = Member::load( $c->user_id->target_id );
            $rows[] = $c;
            $returns = self::comments( $c->id(), $depth + 1 );
            if( $returns ) $rows = array_merge($rows,$returns);
        }
        return $rows;
    }
    public static function getChildren($id) {
        return self::comments($id);
    }

    /**
     *
     * This only marks as 'deleted' for one(1) post or comment.
     *
     * @Attention This does not delete a post or comment. it marks as deleted.
     *
     * @param $id
     * @return bool
     */
    public static function deletePost($id) {
        $post = self::load($id);
        if ( empty($post) ) return "Post does not exists by that ID - $id in PostData::deletePost()";
        $post->set('title','');
        $post->set('content','');
        $post->set('content_stripped','');
        $post->set('deleted', true);
        $post->save();
        self::deleteFiles($id);
        PostData::deleteThreadIfAllDeleted($id);
        return 0;
    }

    /**
     *
     * This deletes all comments and the original post only if all comments and original post is deleted.
     *
     * @param $id - is a post id or comment id. If it is a comment id, it looks up for the post id and check if all comments are deleted.
     *
     *
     */
    public static function deleteThreadIfAllDeleted($id) {
        Library::log("deleteThread($id) begins");
        $root = self::getRoot($id);
        if ( $root->deleted->value == 0 ) return;
        $children = self::getChildren($root->id());
        $deleted_all = true;
        foreach( $children as $child ) {
            if ( $child->deleted->value == 0 ) {
                Library::log($child->id->value . " is not deleted.");
                $deleted_all = false;
                break;
            }
        }
        if ( $deleted_all ) {
            Library::log("ALL deleted...!!");
            self::forceDeleteThread($root->id());
        }
    }

    /**
     *
     *
     * @param $id - is a post id to delete the whole thread. It may be a comment id but never tested.
     */
    public static function forceDeleteThread($id) {
        Library::log("deleteThreadComplete($id) begins");
        $children = self::getChildren($id);
        foreach( $children as $child ) {
            self::deletePostComplete($child->id());
        }
        self::deletePostComplete($id);
    }

    /**
     * This actually DELETE a file. It completely remove the record of the post or comment from post_data table.
     * It also delete related files of each post(comment)
     * @param $id
     */
    private static function deletePostComplete($id) {
        Library::log("deletePostComplete($id) begins");
        $post = self::load($id);
        $post->delete();
        self::deleteFiles($id);
    }

    /**
     * @param $id
     * @todo delete files.
     */
    private static function deleteFiles($id) {
        $files = self::files($id);
        foreach( $files as $file ) {
            //\Drupal::service('file.usage')->delete($file);
            $file->delete();
        }
    }

    public static function updateRoot($root_id) {
        Library::log("updateRoot($root_id) begin");
        $no_of_comment = self::countComment($root_id);
        Library::log("no_of_comment: $no_of_comment");
        self::load($root_id)->set('no_of_comment', $no_of_comment)->save();
    }

    private static function countComment($root_id) {
        return \Drupal::entityQuery('post_data')
            ->count()
            ->condition('root_id', $root_id)
            ->execute();
    }

    public static function vote($id, $mode) {
        Library::log("vote($id, $mode) begins");
        if ( ! Library::login() ) return ['error'=>'Please login first.'];
        $browser_id = Library::getBrowserID();
        $ip = Library::clientIP();
        $uid = Library::myUid();
        if ( $re = PostHistory::checkVoteAlready($id, $uid, $browser_id, $ip, $mode) ) return ['error'=>"You voted on this post already. ($re)"];
        $post = self::load($id);
        $no = 0;
        if ( $mode == 'good' ) {
            $no = $post->get('vote_good')->value;
            Library::log($no);
            $post->set('vote_good', ++ $no);
        }
        else if ( $mode == 'bad' ) {
            $no = $post->get('vote_bad')->value;
            $post->set('vote_bad', ++ $no);
        }
        $post->save();
        $ph = PostHistory::create();
        $ph->set('user_id', $uid);
        $ph->set('post_data_id', $post->id());
        $ph->set('ip', $ip);
        $ph->set('browser_id', $browser_id);
        $ph->set('mode', $mode);
        $ph->save();
        Library::log("return: $no");
        return $no;
    }

    public static function report($post_data_id, $user_id) {
        Library::log("report($post_data_id, $user_id) begins");
        if ( ! Library::login() ) return ['error'=>'Please login first.'];
        $browser_id = Library::getBrowserID();
        $ip = Library::clientIP();
        if ( $re = PostHistory::checkReportAlready($post_data_id, $user_id, $browser_id, $ip) ) return ['error'=>"You reported this post already. ($re)"];
        $post = self::load($post_data_id);
        $no = $post->get('report')->value;
        $no = $no + 1;
        $post->set('report', $no);
        $post->save();

        $ph = PostHistory::create();
        $ph->set('user_id', $user_id);
        $ph->set('post_data_id', $post_data_id);
        $ph->set('ip', $ip);
        $ph->set('browser_id', $browser_id);
        $ph->set('mode', 'report');
        $ph->save();
        Library::log("return: $no");
        return ['message'=>'You have reported this post successfully.'];
    }

    /**
     * @param $post_data_id
     *
     * 1. set file_managed.status to 1
     * 2. delete files in 'fid_delete'
     * 3. set post_data.fid_of_first_image
     */
    private static function updateUploadedFiles($post_data_id) {

        $request = \Drupal::request();
        $ids = $request->get('fid');

        if ( $ids ) {
            $fids = explode(',', $ids);
            foreach( $fids as $fid ) {
                if ( empty($fid) || ! is_numeric($fid) ) continue;
                $file = File::load($fid);
                if ( $file ) {
                    $file->set('status', 1)->save();
                    db_update('file_usage')
                        ->fields(['id'=>$post_data_id])
                        ->condition('fid', $fid)
                        ->execute();
                }
            }
        }

        /**
         * @refer https://docs.google.com/document/d/1koxonGQl20ER7HZqUfHd6L53YXT5fPlJxCEwrhRqsN4/edit#heading=h.dy4d1ae82vq4
         */
        $ids = $request->get('fid_delete');
        if ( $ids ) {
            $fids = explode(',', $ids);
            foreach( $fids as $fid ) {
                Library::file_delete($fid);
            }
        }


        //
        $files = self::files($post_data_id);
        $count = count($files);
        Library::log("number of files in $post_data_id : $count");
        foreach( $files as $file ) {
            Library::log("type : " . $file->getMimeType());
            if ( strpos($file->getMimeType(), 'image') !== false ) {
                self::load($post_data_id)->set('fid_of_first_image', $file->id())->save();
                break;
            }
        }

    }


    /**
     * Returns all files of the post/comment.
     *
     * @param $id - id of post/comment
     * @return array
     */
    public static function files($id) {
        return Library::files_by_module_id('post', $id);
    }




    /**
     * {@inheritdoc}
     */
    public function getCreatedTime() {
        return $this->get('created')->value;
    }

    /**
     * {@inheritdoc}
     */
    public function getChangedTime() {
        return $this->get('changed')->value;
    }

    /**
     * {@inheritdoc}
     */
    public function getOwner() {
        return $this->get('user_id')->entity;
    }

    /**
     * {@inheritdoc}
     */
    public function getOwnerId() {
        return $this->get('user_id')->target_id;
    }

    /**
     * {@inheritdoc}
     */
    public function setOwnerId($uid) {
        $this->set('user_id', $uid);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setOwner(UserInterface $account) {
        $this->set('user_id', $account->id());
        return $this;
    }


    public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
        $fields['id'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('ID'))
            ->setDescription(t('The ID of the  entity.'))
            ->setReadOnly(TRUE)
            ->setSetting('unsigned', TRUE);

        $fields['uuid'] = BaseFieldDefinition::create('uuid')
            ->setLabel(t('UUID'))
            ->setDescription(t('The UUID of the  entity.'))
            ->setReadOnly(TRUE);

        $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Drupal User ID'))
            ->setDescription(t('The Drupal User ID who owns the forum.'))
            ->setSetting('target_type', 'user');

        $fields['langcode'] = BaseFieldDefinition::create('language')
            ->setLabel(t('Language code'))
            ->setDescription(t('The language code of entity.'));

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Created'))
            ->setDescription(t('The time that the entity was created.'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Changed'))
            ->setDescription(t('The time that the entity was last edited.'));


        $fields['config_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Config ID'))
            ->setDescription(t('The config id of the Entity'))
            ->setSetting('target_type', 'post_config');



        $fields['root_id'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Root ID'))
            ->setDescription(t('The root id of the post. This field is used to indicate to which original post that comment post belongs to.'))
            ->setSettings(array(
                'default_value' => 0,
                ))
            ->setSetting('unsigned', TRUE);


        $fields['parent_id'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Parent ID'))
            ->setDescription(t('The parent entity id of the Entity'))
            ->setDefaultValue(0)
            ->setSetting('unsigned', TRUE);



        $fields['no_of_view'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('No of view'))
            ->setDescription(t('The no of view of the Entity'))
            ->setDefaultValue(0)
            ->setSetting('unsigned', TRUE);

        $fields['no_of_comment'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('No of comment'))
            ->setDescription(t('The no of comment of the Entity'))
            ->setDefaultValue(0)
            ->setSetting('unsigned', TRUE);

        $fields['fid_of_first_image'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('FID of first image'))
            ->setDescription(t('The file id of file_usage of first image of the post'))
            ->setSetting('target_type', 'file');


        $fields['vote_good'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Vote Good'))
            ->setDescription(t('Vote Good of the Entity'))
            ->setDefaultValue('0')
            ->setSetting('unsigned', TRUE);

        $fields['vote_bad'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Vote Bad'))
            ->setDescription(t('Vote Bad of the Entity'))
            ->setDefaultValue(0)
            ->setSetting('unsigned', TRUE);

        $fields['secret'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Secret'))
            ->setDescription(t('The status of secret of the Entity'))
            ->setDefaultValue(false);

        $fields['blind'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Blind'))
            ->setDescription(t('The status of blind of the Entity'))
            ->setDefaultValue(false);


        $fields['block'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Block'))
            ->setDescription(t('The status of block of the Entity'))
            ->setDefaultValue(false);



        $fields['reason'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Reason'))
            ->setDescription(t('Reason for blind or block'))
            ->setSettings(array(
                'default_value' => '',
                'max_length' => 512,
            ));


        $fields['deleted'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Deleted'))
            ->setDescription(t('The status of deleted of the Entity'))
            ->setDefaultValue(false);

        $fields['report'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Report'))
            ->setDescription(t('Report of the post'))
            ->setDefaultValue(0)
            ->setSetting('unsigned', TRUE);

        $fields['browser_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Web Browser ID'))
            ->setDescription(t('Web Browser ID of the user(client)'))
            ->setSettings(array(
                'default_value' => '',
                'max_length' => 32,
            ));

        $fields['ip'] = BaseFieldDefinition::create('string')
            ->setLabel(t('IP'))
            ->setDescription(t('Client IP'))
            ->setSettings(array(
                'default_value' => '',
                'max_length' => 15,
            ));



        $fields['domain'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Domain'))
            ->setDescription(t('Domain of the site that this post was created'))
            ->setSettings(array(
                'default_value' => '',
                'max_length' => 64,
            ));

        $fields['user_agent'] = BaseFieldDefinition::create('string')
            ->setLabel(t('User Agent'))
            ->setDescription(t('User agent of the browser'))
            ->setSettings(array(
                'default_value' => '',
                'max_length' => 255,
            ));


        $fields['shortcut'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Shortcut'))
            ->setDescription(t('shortcut for the post'))
            ->setSettings(array(
                'default_value' => '',
                'max_length' => 128,
            ));









        $fields['title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Title'))
            ->setDescription(t('Title of the entity.'))
            ->setSettings(array(
                'default_value' => '',
                'max_length' => 512,
            ));

        $fields['content'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Content'))
            ->setDescription(t('Content of the entity.'));

        $fields['content_stripped'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Stripped Content'))
            ->setDescription(t('Stripped Content of the entity.'));


        $fields['title_private'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Private Title'))
            ->setDescription(t('Private Title of the entity.'))
            ->setSettings(array(
                'default_value' => '',
                'max_length' => 512,
            ));

        $fields['content_private'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Private Content'))
            ->setDescription(t('Private Content of the entity.'));

        $fields['content_stripped_private'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Private Stripped Content'))
            ->setDescription(t('Private Stripped Content of the entity.'));



        $fields['country'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Country'))
            ->setDescription(t('Country code'))
            ->setSettings(array(
                'default_value' => '',
                'max_length' => 64,
            ));


        $fields['province'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Province'))
            ->setDescription(t('Province'))
            ->setSettings(array(
                'default_value' => '',
                'max_length' => 64,
            ));

        $fields['city'] = BaseFieldDefinition::create('string')
            ->setLabel(t('City'))
            ->setDescription(t('City'))
            ->setSettings(array(
                'default_value' => '',
                'max_length' => 64,
            ));


        for( $i=1; $i<=5; $i++ ) {
            $fields["link_$i"] = BaseFieldDefinition::create('string')
                ->setLabel(t("Link $i"))
                ->setDescription(t('Link for the post'))
                ->setSettings(array(
                    'default_value' => '',
                    'max_length' => 512,
                ));
        }


        for( $i=1; $i<=10; $i++ ) {
            $fields["category_$i"] = BaseFieldDefinition::create('entity_reference')
                ->setLabel(t('Category $i'))
                ->setDescription(t('The category that this post belongs to'))
                ->setSetting('target_type', 'library_category');
        }

        for( $i=1; $i<=20; $i++ ) {
            $fields["int_$i"] = BaseFieldDefinition::create('integer')
                ->setLabel(t("Int $i"))
                ->setDescription(t('Extra int field for the post'))
                ->setDefaultValue(0);
        }


        for( $i=1; $i<=20; $i++ ) {
            $fields["char_$i"] = BaseFieldDefinition::create('string')
                ->setLabel(t("Char $i"))
                ->setDescription(t('Extra char field for the post'))
                ->setSettings(array(
                    'default_value' => '',
                    'max_length' => 1,
                ));
        }


        for( $i=1; $i<=20; $i++ ) {
            $fields["varchar_$i"] = BaseFieldDefinition::create('string')
                ->setLabel(t("Varchar $i"))
                ->setDescription(t('Extra string field for the post'))
                ->setSettings(array(
                    'default_value' => '',
                    'max_length' => 255,
                ));
        }


        for( $i=1; $i<=10; $i++ ) {
            $fields["text_$i"] = BaseFieldDefinition::create('text_long')
                ->setLabel(t("Text $i"))
                ->setDescription(t('Extra long text field of the post.'));
        }


        return $fields;
    }




}
