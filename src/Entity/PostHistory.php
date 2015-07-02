<?php
namespace Drupal\post\Entity;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\post\PostHistoryInterface;
use Drupal\user\UserInterface;

/**
 * Defines the CategoryLog entity.
 *
 *
 * @ContentEntityType(
 *   id = "post_history",
 *   label = @Translation("Post History entity"),
 *   base_table = "post_history",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "ip",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class PostHistory extends ContentEntityBase implements PostHistoryInterface {


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


        $fields['post_data_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Post Data ID'))
            ->setDescription(t('The post data id of the Entity'))
            ->setSetting('target_type', 'post_data');

        $fields['ip'] = BaseFieldDefinition::create('string')
            ->setLabel(t('IP'))
            ->setDescription(t('IP of the user'))
            ->setSettings(array(
                'default_value' => '',
                'max_length' => 15,
            ));

        $fields['browser_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Browser ID'))
            ->setDescription(t('Browser ID of the client'))
            ->setSettings(array(
                'default_value' => '',
                'max_length' => 32,
            ));

        $fields['mode'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Mode'))
            ->setDescription(t('mode of the history. vote good, vote bad, report'))
            ->setSettings(array(
                'default_value' => '',
                'max_length' => 32,
            ));

        return $fields;
    }
}
