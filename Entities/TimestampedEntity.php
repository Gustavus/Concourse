<?php
/**
 * @package  Concourse
 * @subpackage  Entities
 * @author  Billy Visto
 */

namespace Gustavus\Concourse\Entities;

use DateTime;

/**
 * Entity to extend keep track of created and modified columns
 *
 * @package  Concourse
 * @subpackage  Entities
 * @author  Billy Visto
 * @MappedSuperclass
 */
abstract class TimestampedEntity
{
  /**
   * @var DateTime $created
   *
   * @Column(name="created", type="datetime")
   */
  protected $created;

  /**
   * @var DateTime $modified
   *
   * @Column(name="modified", type="datetime")
   */
  protected $modified;

  /**
   * Sets created and modified right before this object is first persisted to the database.
   *
   * @PrePersist
   * @return  void
   */
  abstract public function setCreatedValue();

  /**
   * Sets modified right before this object is changed in the database.
   *
   * @PreUpdate
   * @return  void
   */
  abstract public function setModifiedValue();

  /**
   * Gets created value
   *
   * @return DateTime
   */
  public function getCreatedValue()
  {
    return $this->created;
  }

  /**
   * Gets modified value
   *
   * @return DateTime
   */
  public function getModifiedValue()
  {
    return $this->modified;
  }
}