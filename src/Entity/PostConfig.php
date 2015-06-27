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
        return $entities ? reset($entities) : null;
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
                'max_length' => 255,
            ));


        return $fields;
    }



}
