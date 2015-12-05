<?php

/**
 * @file
 * Contains \Drupal\Tests\usebb2drupal\Unit\Plugin\migrate\process\StringToUnicodeTest.
 */

namespace Drupal\Tests\usebb2drupal\Unit\Plugin\migrate\process;

use Drupal\usebb2drupal\Plugin\migrate\process\StringToUnicode;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests the StringToUnicode plugin.
 *
 * TODO: add test for Unicode conversion of texts.
 *
 * @group usebb2drupal
 */
class StringToUnicodeTest extends MigrateProcessTestCase {

  /**
   * Mocked UseBB Info service.
   */
  protected $info;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->info = $this->getMockBuilder('Drupal\usebb2drupal\UseBBInfo')
      ->disableOriginalConstructor()
      ->getMock();
    $this->info->method('getEncoding')->willReturn('ISO-8859-1');
    $this->plugin = new StringToUnicode([], 'usebb_string_to_unicode', [], $this->info);
  }

  /**
   * Test replacing of HTML entities by Unicode characters.
   */
  public function testReplaceEntitiesToUnicode() {
    $dirty_string = 'Testing &#964;&#945;&#1041;&#1068;&#8467;&#963;: 1<2 & 4+1>3, now 20% off!';
    $clean_string = 'Testing ταБЬℓσ: 1<2 & 4+1>3, now 20% off!';
    $value = $this->plugin->transform($dirty_string, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertEquals($clean_string, $value);
  }

}
