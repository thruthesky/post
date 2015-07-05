<?php
namespace Drupal\post\Entity;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\library\Library;
use Drupal\post\PostConfigInterface;
use Drupal\user\UserInterface;

/**
 * Defines the CategoryLog entity.
 *
 *
 * @ContentEntityType(
 *   id = "post_config",
 *   label = @Translation("Post Config entity"),
 *   base_table = "post_config",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class PostConfig extends ContentEntityBase implements PostConfigInterface {


    public static function loadByName($name)
    {
        $entities = \Drupal::entityManager()->getStorage('post_config')->loadByProperties(['name'=>$name]);
        $config = $entities ? reset($entities) : null;
        if ( $config ) self::setDefault($config);
        return $config;
    }
    public static function loadById($id) {
        $config = self::load($id);
        if ( $config ) self::setDefault($config);
        return $config;
    }

    public static function setDefault(PostConfig &$config)
    {
        if ( empty( $config->get('widget_list')->value ) ) {
            $config->set('widget_list', 'default');
            $config->save();
        }
        if ( empty( $config->get('widget_view')->value ) ) {
            $config->set('widget_view', 'default');
            $config->save();
        }
        if ( empty( $config->get('widget_edit')->value ) ) {
            $config->set('widget_edit', 'default');
            $config->save();
        }
        if ( empty( $config->get('widget_comment')->value ) ) {
            $config->set('widget_comment', 'default');
            $config->save();
        }
        if ( empty( $config->get('widget_search_box')->value ) ) {
            $config->set('widget_search_box', 'default');
            $config->save();
        }

    }

    public static function createForum() {
        $name = \Drupal::request()->get('name');
        if ( empty($name) ) {
            return Library::error(-1234,'Input name');
        }
        $config = PostConfig::loadByName($name);
        if ( $config ) {
            return Library::error(-9005, "Forum name exists.");
        }
        else {
            $config = PostConfig::create();
            $config->set('name', $name);
            return $config->save();
        }
    }
    public static function update() {
        $request = \Drupal::request();
        $name = $request->get('name');
        if ( self::validateName($name) ) return Library::error(-9108, "Wrong forum name");
        $id = $request->get('id');
        $config = self::load($id);
        if ( empty($config) ) {
            return Library::error(-9107, "There is no forum by that ID.");
        }
        $config->set('name', $name);
        $config->set('title', $request->get('title'));
        $config->set('description', $request->get('description'));
        $config->set('no_of_items_per_page', $request->get('no_of_items_per_page',0));
        $config->set('no_of_pages_in_navigation_bar', $request->get('no_of_pages_in_navigation_bar', 0));
        $config->set('list_under_view', $request->get('list_under_view', 'N'));
        $config->set('widget_list', $request->get('widget_list'));
        $config->set('widget_view', $request->get('widget_view'));
        $config->set('widget_edit', $request->get('widget_edit'));
        $config->set('widget_comment', $request->get('widget_comment'));
        $config->set('widget_search_box', $request->get('widget_search_box'));
        $config->save();
        return $config;
    }
    /**
     * Returns true if there is error.
     * @param $name - configuration name
     * @return bool -
     */
    private static function validateName($name) {
        $re = preg_match("/^[a-zA-Z0-9\-_]+$/", $name);
        if ( $re ) return false;
        else return true;
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

        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Name'))
            ->setDescription(t('Name of Forum.'))
            ->setSettings(array(
                'default_value' => '',
                'max_length' => 128,
            ));


        $fields['title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Title'))
            ->setDescription(t('Title of Forum.'))
            ->setSettings(array(
                'default_value' => '',
                'max_length' => 255,
            ));




        $fields['description'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Description'))
            ->setDescription(t('Description of Forum.'))
            ->setSettings(array(
                'default_value' => '',
                'max_length' => 2048,
            ));



        $fields["no_of_items_per_page"] = BaseFieldDefinition::create('integer')
            ->setLabel(t("no_of_items_per_page"))
            ->setDescription(t('no_of_items_per_page for the forum'))
            ->setDefaultValue(0);

        $fields["no_of_pages_in_navigation_bar"] = BaseFieldDefinition::create('integer')
            ->setLabel(t("no_of_pages_in_navigation_bar"))
            ->setDescription(t('no_of_pages_in_navigation_bar for the forum'))
            ->setDefaultValue(0);


        $fields['list_under_view'] = BaseFieldDefinition::create('string')
            ->setLabel(t('List under view'))
            ->setDescription(t('List under view option'))
            ->setSettings(array(
                'default_value' => 'N',
                'max_length' => 1,
            ));

        $fields['widget_list'] = BaseFieldDefinition::create('string')
            ->setLabel(t('List widget'))
            ->setDescription(t('List widget of Forum.'))
            ->setSettings(array(
                'default_value' => '',
                'max_length' => 64,
            ));

        $fields['widget_view'] = BaseFieldDefinition::create('string')
            ->setLabel(t('View widget'))
            ->setDescription(t('View widget of Forum.'))
            ->setSettings(array(
                'default_value' => '',
                'max_length' => 64,
            ));

        $fields['widget_edit'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Edit widget'))
            ->setDescription(t('Edit widget of Forum.'))
            ->setSettings(array(
                'default_value' => '',
                'max_length' => 64,
            ));

        $fields['widget_comment'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Comment widget'))
            ->setDescription(t('Comment widget of Forum.'))
            ->setSettings(array(
                'default_value' => '',
                'max_length' => 64,
            ));
        /*
        $fields['widget_search'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Search widget'))
            ->setDescription(t('Search widget of Forum.'))
            ->setSettings(array(
                'default_value' => '',
                'max_length' => 64,
            ));
        */

        $fields['widget_search_box'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Search Box widget'))
            ->setDescription(t('Search Box widget of Forum.'))
            ->setSettings(array(
                'default_value' => '',
                'max_length' => 64,
            ));
        return $fields;
    }
}
