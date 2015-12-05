<?php

/**
 * @file
 * Contains \Drupal\Tests\usebb2drupal\Unit\Plugin\migrate\process\BBCodeToHTMLTest.
 */

namespace Drupal\Tests\usebb2drupal\Unit\Plugin\migrate\process;

use Drupal\usebb2drupal\Plugin\migrate\process\BBCodeToHTML;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;
use Drupal\Component\Utility\Html;

/**
 * Tests the BBCodeToHTML plugin.
 *
 * @group usebb2drupal
 */
class BBCodeToHTMLTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->plugin = new BBCodeToHTML([], 'usebb_bbcode_to_html', []);
  }

  /**
   * Data provider for testBbcodeToHtml().
   */
  public function bbcodeToHtmlProvider() {
    return [
      [TRUE, FALSE, 'Contains [b]some bold text[/b], [i]italic and [u]underlined[/u][/i]. With closing tags in [b][i][s]right[/s] wrong order[/b][/i].
A newline.

[quote]Let me just quote this:
Single line break.

Double line break.[/quote]

[quote=Myself]Let me just quote myself:

[quote]Let me just quote this:
Single line break.

Double line break.[/quote][/quote]

[code]In code [b]no[/b] tags are parsed.[/code]

[url=http://www.example.com][img]http://www.usebb.net/gfx/site_logo.png[/img][/url]

[color=red]Colors are stripped :-( but [size=14]size[/size] is not.[/color]
Unless it\'s [size=10]too small[/size].', '<p>Contains <strong>some bold text</strong>, <em>italic and <u>underlined</u></em>. With closing tags in <strong><em><s>right</s> wrong order</em></strong>.<br>
A newline.</p>
<blockquote><p>Let me just quote this:<br>
Single line break.</p>
<p>Double line break.</p></blockquote>
<blockquote><p><strong>Myself</strong></p>
<p>Let me just quote myself:</p>
<blockquote><p>Let me just quote this:<br>
Single line break.</p>
<p>Double line break.</p></blockquote>
</blockquote>
<pre><code>In code [b]no[/b] tags are parsed.</code></pre><p><a href="http://www.example.com"><img src="http://www.usebb.net/gfx/site_logo.png" /></a></p>
<p>Colors are stripped :-( but<br></p><h2>size</h2>
<p>is not.<br>
Unless it\'s too small.</p>'],
      [FALSE, FALSE, 'Contains [b]some bold text[/b], [i]italic and [u]underlined[/u][/i]. With closing tags in [b][i][s]right[/s] wrong order[/b][/i].
A newline.

[quote]Let me just quote this:
Single line break.

Double line break.[/quote]

[quote=Myself]Let me just quote myself:

[quote]Let me just quote this:
Single line break.

Double line break.[/quote][/quote]

[code]In code [b]no[/b] tags are parsed.[/code]

[url=http://www.example.com][img]http://www.usebb.net/gfx/site_logo.png[/img][/url]

[color=red]Colors are stripped :-( but [size=14]size[/size] is not.[/color]
Unless it\'s [size=10]too small[/size].', '<p>Contains [b]some bold text[/b], [i]italic and [u]underlined[/u][/i]. With closing tags in [b][i][s]right[/s] wrong order[/b][/i].<br />
A newline.</p>
<p>[quote]Let me just quote this:<br />
Single line break.</p>
<p>Double line break.[/quote]</p>
<p>[quote=Myself]Let me just quote myself:</p>
<p>[quote]Let me just quote this:<br />
Single line break.</p>
<p>Double line break.[/quote][/quote]</p>
<p>[code]In code [b]no[/b] tags are parsed.[/code]</p>
<p>[url=http://www.example.com][img]http://www.usebb.net/gfx/site_logo.png[/img][/url]</p>
<p>[color=red]Colors are stripped :-( but [size=14]size[/size] is not.[/color]<br />
Unless it\'s [size=10]too small[/size].</p>'],
      [FALSE, TRUE, '<span class="testing">Testing</span>

<div>Yeah</div>', '<span class="testing">Testing</span>

<div>Yeah</div>'],
    ];
  }

  /**
   * Test conversion of BBCode to HTML.
   *
   * @dataProvider bbcodeToHtmlProvider
   */
  public function testBbcodeToHtml($enable_bbcode, $enable_html, $bbcode, $html) {
    $this->row->method('hasSourceProperty')->will($this->returnValueMap([
      ['enable_bbcode', TRUE],
      ['enable_html', TRUE],
    ]));
    $this->row->method('getSourceProperty')->will($this->returnValueMap([
      ['enable_bbcode', $enable_bbcode],
      ['enable_html', $enable_html],
    ]));

    $value = $this->plugin->transform($bbcode, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertEquals(Html::normalize($html), $value);
  }

}
