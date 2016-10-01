<?php

namespace Drupal\Tests\usebb2drupal\Unit\Plugin\migrate\process;

use Drupal\usebb2drupal\Plugin\migrate\process\CombineFields;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests the CombineFields plugin.
 *
 * @group usebb2drupal
 */
class CombineFieldsTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->plugin = new CombineFields([
      'fields' => [
        'foo' => 'FooTool',
        'bar' => 'BarTool',
        'baz' => 'BazTool',
      ],
    ], 'usebb_combine_fields', []);
  }

  /**
   * Test combination of fields.
   */
  public function testCombineFields() {
    $this->row->method('getSourceProperty')->will($this->returnValueMap([
      ['foo', 'myname123'],
      ['bar', 'MrNice'],
      ['baz', 'coolguy'],
    ]));

    $value = $this->plugin->transform(NULL, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertEquals([
      ['value' => 'FooTool: myname123'],
      ['value' => 'BarTool: MrNice'],
      ['value' => 'BazTool: coolguy'],
    ], $value);
  }

  /**
   * Test ignoring of missing or empty fields.
   */
  public function testIgnoreMissingFields() {
    $this->row->method('getSourceProperty')->will($this->returnValueMap([
      ['foo', ''],
      ['baz', 'coolguy'],
    ]));

    $value = $this->plugin->transform(NULL, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertEquals([
      ['value' => 'BazTool: coolguy'],
    ], $value);
  }

}
