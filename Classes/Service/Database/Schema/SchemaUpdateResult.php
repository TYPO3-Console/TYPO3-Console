<?php
namespace Helhum\Typo3Console\Service\Database\Schema;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Mathias Brodala <mbrodala@pagemachine.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Represents a database schema update result
 */
class SchemaUpdateResult {

  /**
   * @var array $performedUpdates
   */
  protected $performedUpdates = array();

  /**
   * Returns the list of performed updates, grouped by schema update type
   *
   * @return array
   */
  public function getPerformedUpdates() {
    return $this->performedUpdates;
  }

  /**
    * Returns the list of performed update types including the count
    *
    * @return array
    */
   public function getPerformedUpdateTypes() {
     $typesCount = array();
     foreach ($this->performedUpdates as $type => $performedUpdates) {
       $typesCount[$type] = count($performedUpdates);
     }
     return $typesCount;
   }

  /**
   * Returns TRUE if updates where performed, FALSE otherwise
   *
   * @return boolean
   */
  public function hasPerformedUpdates() {
    return count($this->performedUpdates) > 0;
  }

  /**
   * Adds to the number of updates performed for a schema update type
   *
   * @param SchemaUpdateType $schemaUpdateType Schema update type
   * @param array $updates Updates performed
   */
  public function addPerformedUpdates(SchemaUpdateType $schemaUpdateType, array $updates) {
    $this->performedUpdates[(string)$schemaUpdateType] = array_merge((array)$this->performedUpdates[(string)$schemaUpdateType], $updates);
  }

  /**
   * @var array $errors
   */
  protected $errors = array();

  /**
   * @return array
   */
  public function getErrors() {
    return $this->errors;
  }

  /**
   * Adds to the list of errors occurred for a schema update type
   *
   * @param SchemaUpdateType $schemaUpdateType Schema update type
   * @param array $errors List of error messages
   */
  public function addErrors(SchemaUpdateType $schemaUpdateType, array $errors) {
    $this->errors[(string)$schemaUpdateType] = array_merge((array)$this->errors[(string)$schemaUpdateType], $errors);
  }

  /**
   * Returns TRUE if errors did occur during schema update, FALSE otherwise
   *
   * @return boolean
   */
  public function hasErrors() {
    return count($this->errors);
  }
}
