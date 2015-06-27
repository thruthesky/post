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


        if ( isset($conds['q']) ) {
            if ( isset($conds['qn']) ) {
                $user = user_load_by_name($conds['q']);
                if ( $user ) {
                    $uid = $user->id();
                    $db->condition('user_id', $uid);
                }
            }
            else if ( isset($conds['qt']) || isset($conds['qc']) ) {
                $words = explode(' ', $conds['q'], 2);
                foreach( $words as $word ) {
                    $or = $db->orConditionGroup();
                    if ( isset($conds['qt']) ) $or->condition('title', $word, 'CONTAINS');
                    if ( isset($conds['qc']) ) $or->condition('content_stripped__value', $word, 'CONTAINS');
                    $db->condition($or);
                }
            }
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
        $db->range(($page_no-1) * $conds['no_of_items_per_page'], $conds['no_of_items_per_page']);
        $db->sort('created', 'DESC');
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
        if ( $id && is_numeric($id) ) {
            $post = self::load($id);
            //$config = $post->get('config_id')->getEntity();
        }
        else if ( $config_name ) {
            $post = self::create();
            $config = PostConfig::loadByName($config_name);
            $post->set('config_id', $config->id());
            $post->set('user_id', Library::myUid());
        }
        else {
            return Library::error(-9006, "No forum config or forum post id.");
        }
        $post->set('title', $request->get('title'));
        $post->set('content', $request->get('content'));
        $post->set('content_stripped', strip_tags($request->get('content')));
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
            ->setDescription(t('The parent entity id of the Entity'));


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
