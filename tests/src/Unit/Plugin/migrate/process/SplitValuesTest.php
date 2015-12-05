<?php

/**
 * @file
 * Contains \Drupal\Tests\usebb2drupal\Unit\Plugin\migrate\process\SplitValuesTest.
 */

namespace Drupal\Tests\usebb2drupal\Unit\Plugin\migrate\process;

use Drupal\usebb2drupal\Plugin\migrate\process\SplitValues;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests the SplitValues plugin.
 *
 * @group usebb2drupal
 */
class SplitValuesTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->plugin = new SplitValues([], 'usebb_split_values', []);
  }

  /**
   * Test value splitting.
   */
  public function testSplitValues() {
    $value = $this->plugin->transform(' one,two, three , four  ,five  ', $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertEquals([
      ['value' => 'one'],
      ['value' => 'two'],
      ['value' => 'three'],
      ['value' => 'four'],
      ['value' => 'five'],
    ], $value);
  }

}
