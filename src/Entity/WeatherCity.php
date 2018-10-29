<?php

namespace Drupal\weather\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Defines the ContentWeatherCity entity.
 *
 * @ingroup weather
 *
 * @ContentEntityType(
 *   id = "weather_city",
 *   label = @Translation("Weather city entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\weather\Entity\Controller\WeatherCityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\weather\Form\WeatherCityForm",
 *       "edit" = "Drupal\weather\Form\WeatherCityForm",
 *       "delete" = "Drupal\weather\Form\WeatherCityDeleteForm",
 *     },
 *     "access" = "Drupal\weather\WeatherCityAccessControlHandler",
 *   },
 *   list_cache_contexts = { "user" },
 *   base_table = "weather_city",
 *   admin_permission = "administer weather city entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "city_id" = "id",
 *   },
 *   links = {
 *     "canonical" = "/weather_city/{weather_city}",
 *     "edit-form" = "/weather_city/{weather_city}/edit",
 *     "delete-form" = "/weather_city/{weather_city}/delete",
 *     "collection" = "/weather_city/list"
 *   },
 * )
 */
class WeatherCity extends ContentEntityBase {

  use EntityChangedTrait;

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

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Weather city entity.'))
      ->setReadOnly(TRUE);

    $fields['city_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Name'))
      ->setDescription(t('The ID of the Weather city entity.'))
      ->setSettings([
        'max_length' => 11,
      ])
      // Set no default value.
      ->setDefaultValue(NULL)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Weather city entity.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      // Set no default value.
      ->setDefaultValue(NULL)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['country'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Country code'))
      ->setDescription(t('The first name of the Country code of weather city entity.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      // Set no default value.
      ->setDefaultValue(NULL)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
