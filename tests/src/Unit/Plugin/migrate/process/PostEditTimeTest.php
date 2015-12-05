<?php

/**
 * @file
 * Contains \Drupal\Tests\usebb2drupal\Unit\Plugin\migrate\process\PostEditTimeTest.
 */

namespace Drupal\Tests\usebb2drupal\Unit\Plugin\migrate\process;

use Drupal\usebb2drupal\Plugin\migrate\process\PostEditTime;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests the PostEditTime plugin.
 *
 * @group usebb2drupal
 */
class PostEditTimeTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->plugin = new PostEditTime([], 'usebb_post_edit_time', []);
  }

  /**
   * Test return of passed edit time value and ignoring of post time.
   */
  public function testPostEditTime() {
    $this->row->method('hasSourceProperty')->will($this->returnValueMap([
      ['post_time', TRUE],
    ]));
    $this->row->method('getSourceProperty')->will($this->returnValueMap([
      ['post_time', '1449319993'],
    ]));

    $value = $this->plugin->transform('1449319413', $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertEquals('1449319413', $value);
  }

  /**
   * Test return of empty edit time value.
   */
  public function testEmptyPostEditTime() {
    $value = $this->plugin->transform(NULL, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertEquals(NULL, $value);
  }

  /**
   * Test return of post_time for empty edit time value.
   */
  public function testPostTimeEditTime() {
    $this->row->method('hasSourceProperty')->will($this->returnValueMap([
      ['post_time', TRUE],
    ]));
    $this->row->method('getSourceProperty')->will($this->returnValueMap([
      ['post_time', '1449319993'],
    ]));

    $value = $this->plugin->transform(NULL, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertEquals('1449319993', $value);
  }

}
