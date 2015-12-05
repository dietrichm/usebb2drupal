<?php

/**
 * @file
 * Contains \Drupal\Tests\usebb2drupal\Unit\Plugin\migrate\process\WebsiteTest.
 */

namespace Drupal\Tests\usebb2drupal\Unit\Plugin\migrate\process;

use Drupal\usebb2drupal\Plugin\migrate\process\Website;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests the Website plugin.
 *
 * @group usebb2drupal
 */
class WebsiteTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->plugin = new Website([], 'usebb_website', []);
  }

  /**
   * Data provider for testWebsite().
   */
  public function websiteProvider() {
    return [
      ['https://www.drupal.org/node/2428583#comment-10639284',
        [
          'uri' => 'https://www.drupal.org/node/2428583#comment-10639284',
          'title' => 'drupal.org',
          'options' => [],
        ],
      ],
      ['https://drupal.org/node/2428583',
        [
          'uri' => 'https://drupal.org/node/2428583',
          'title' => 'drupal.org',
          'options' => [],
        ],
      ],
      ['http://testing.example.org/123',
        [
          'uri' => 'http://testing.example.org/123',
          'title' => 'testing.example.org',
          'options' => [],
        ],
      ],
    ];
  }

  /**
   * Test the translation of website URLs into URI and title.
   *
   * @dataProvider websiteProvider
   */
  public function testWebsite($url, $value) {
    $this->assertEquals($value, $this->plugin->transform($url, $this->migrateExecutable, $this->row, 'destinationproperty'));
  }

}
