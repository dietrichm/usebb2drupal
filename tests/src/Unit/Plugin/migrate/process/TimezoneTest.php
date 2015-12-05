<?php

/**
 * @file
 * Contains \Drupal\Tests\usebb2drupal\Unit\Plugin\migrate\process\TimezoneTest.
 */

namespace Drupal\Tests\usebb2drupal\Unit\Plugin\migrate\process;

use Drupal\usebb2drupal\Plugin\migrate\process\Timezone;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests the Timezone plugin.
 *
 * @group usebb2drupal
 */
class TimezoneTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->plugin = new Timezone([], 'usebb_timezone', []);
  }

  /**
   * Data provider for testTimezone().
   */
  public function timezoneProvider() {
    return [
      ['0', 'UTC'],
      [0, 'UTC'],
      ['4.5', 'Asia/Kabul'],
      [4.5, 'Asia/Kabul'],
      ['-3', 'America/Araguaina'],
    ];
  }

  /**
   * Test translating UseBB timezone setting to matching timezone.
   *
   * @dataProvider timezoneProvider
   */
  public function testTimezone($offset, $timezone) {
    $this->assertEquals($timezone, $this->plugin->transform($offset, $this->migrateExecutable, $this->row, 'destinationproperty'));
  }

}
