<?php
namespace Drupal\post\Entity;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\library\Library;
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



    public static function getQueryOnConds($conds)
    {
        $config = null;
        $db = \Drupal::entityQuery('post_data');
        if ( isset($conds['post_config_name']) ) {
            $config = PostConfig::loadByName($conds['post_config_name']);
            $db->condition('config_id', $config->id());
        }
        else {
            // is it error? or if it's a full search for all the forum?
        }


        if ( isset($conds['q']) && $conds['q'] ) {
            if ( isset($conds['qn']) && $conds['qn'] ) {
                $user = user_load_by_name($conds['q']);
                if ( $user ) {
                    $uid = $user->id();
                    $db->condition('user_id', $uid);
                }
            }
            else {
                if ( isset($conds['qt']) && $conds['qt'] || isset($conds['qc']) && $conds['qc'] ) {

                }
                else { // if there is no option selected on title and content filter.
                    $conds['qt'] = 'y';
                    $conds['qc'] = 'y';
                }
                $words = explode(' ', $conds['q'], 2);
                foreach( $words as $word ) {
                    $or = $db->orConditionGroup();
                    if ( isset($conds['qt']) || $conds['qt'] ) $or->condition('title', $word, 'CONTAINS');
                    if ( isset($conds['qc']) || $conds['qc']) $or->condition('content_stripped__value', $word, 'CONTAINS');
                    $db->condition($or);
                }
            }
        }
        else {
            $db->condition('parent_id', 0);
        }

        return $db;
    }
    /**
     * Returns the searched of posts
     *
     * @param $conds - is the condition array
     *
     * @return static[]
     */
    public static function search($conds) {
        $db = self::getQueryOnConds($conds);
        $page_no = Library::getPageNo();
        $start = ($page_no-1) * $conds['no_of_items_per_page'];
        $db->range($start, $conds['no_of_items_per_page']);
        $db->sort('id', 'DESC');
        $ids = $db->execute();
        return self::loadMultiple($ids);
    }


    /**
     * Returns the number of posts
     *
     * @param $conds - is the condition array
     *
     * @return int
     */
    public static function count($conds) {

        $db = self::getQueryOnConds($conds);
        $db->count();
        $no = $db->execute();
        return $no;
    }

    public static function submitPost() {
        $request = \Drupal::request();
        $id = $request->get('id');
        $config_name = $request->get('post_config_name');
        $p = [];
        $p['title'] = $request->get('title');
        $p['content'] = $request->get('content');
        $p['content_stripped'] = strip_tags($request->get('content'));

        if ( $id && is_numeric($id) ) {
            $id = self::update($id, $p);
        }
        else if ( $config_name ) {
            $config = PostConfig::loadByName($config_name);
            $p['config_id'] = $config->id();
            $id = self::insert($p);
        }
        else {
            return Library::error(-9006, "No forum config or forum post id.");
        }

        return $id;
    }

    private static function update($id, $p) {
        $post = self::load($id);
        foreach( $p as $k => $v ) {
            $post->set($k, $v);
        }
        $post->save();
        return $post->id();
    }

    /**
     * @param $config_name
     * @param $p
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
        $post->set('no_of_view',  0);
        $post->set('parent_id',  0);

        if ( isset($p['user_id']) ) { }
        else if ( isset($p['username']) ) {
            $user = user_load_by_name($p['username']);
            $post->set('user_id', $user->id());
            unset($p['username']);
        }
        else {
            $post->set('user_id', Library::myUid());
        }

        foreach( $p as $k => $v ) {
            $post->set($k, $v);
        }
        $post->save();
        return $post->id();
    }

    public static function collection($conds) {
        $list = [];
        if ( ! isset($conds['no_of_items_per_page']) ) $conds['no_of_items_per_page'] = 10;
        if ( ! isset($conds['no_of_pages_in_navigation_bar']) ) $conds['no_of_pages_in_navigation_bar'] = 10;
        if ( isset($conds['post_config_name'])) {
            $list['config'] = PostConfig::loadByName($conds['post_config_name']);
        }
        else {
            Library::error(-9100, "No post_config_name provided");
            return [];
        }

        $list['posts'] = PostData::search($conds);
        $list['no_of_posts'] = PostData::count($conds);
        $list['navigation'] = Library::paging(
            Library::getPageNo(),
            $list['no_of_posts'],
            $conds['no_of_items_per_page'],
            null,
            $conds['no_of_pages_in_navigation_bar'],
            null,
            "/post/" . $conds['post_config_name']
        );
        return $list;
    }

    public static function view($id) {
        $post = PostData::load($id);
        $no = $post->get('no_of_view')->value;
        $post->set('no_of_view', $no + 1);
        $post->save();
        return $post;
    }

    public static function comments($id,$depth=0) {
        $comments = \Drupal::entityManager()->getStorage('post_data')->loadByProperties(['parent_id'=>$id]);
        $rows = [];
        foreach( $comments as $c ) {
            $c->depth = $depth;
            $rows[] = $c;
            $returns = self::comments( $c->id(), $depth + 1 );
            if( $returns ) $rows = array_merge($rows,$returns);
        }
        return $rows;
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
            ->setReadOnly(TRUE);

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




        $fields['parent_id'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Parent ID'))
            ->setDescription(t('The parent entity id of the Entity'))
            ->setDefaultValue(0);



        $fields['no_of_view'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('No of view'))
            ->setDescription(t('The no of view of the Entity'))
            ->setDefaultValue(0);


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


        return $fields;
    }




}
