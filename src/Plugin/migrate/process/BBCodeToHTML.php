<?php

namespace Drupal\usebb2drupal\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\Component\Utility\Random;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Html;

/**
 * Convert BBCode to HTML the way UseBB does.
 *
 * @MigrateProcessPlugin(
 *   id = "usebb_bbcode_to_html"
 * )
 */
class BBCodeToHTML extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $enable_bbcode = $row->hasSourceProperty('enable_bbcode') ? $row->getSourceProperty('enable_bbcode') : TRUE;
    $enable_html = $row->hasSourceProperty('enable_html') ? $row->getSourceProperty('enable_html') : FALSE;
    return $this->applyBbcode($value, $enable_bbcode, $enable_html);
  }

  /**
   * Markup function from UseBB 1. Transform BBCode to HTML.
   *
   * @param string $string
   *   String to convert.
   * @param bool $bbcode
   *   Whether to convert BBCode.
   * @param bool $html
   *   Whether to keep HTML enabled.
   *
   * @return string
   *   Converted string.
   */
  protected function applyBbcode($string, $bbcode, $html) {
    static $random;

    $quote_format = '<blockquote>%s</blockquote>';
    $quote_format_named = "<blockquote><p><strong>%s</strong></p>\n\n%s</blockquote>";
    $code_format = '<pre><code>%s</code></pre>';

    $string = preg_replace('#(script|about|applet|activex|chrome):#is', '\\1&#058;', $string);

    // Needed by some BBCode regexps and smilies.
    $string = ' ' . $string . ' ';

    if (!$html) {
      $string = SafeMarkup::checkPlain($string);
      if (strpos($string, '&') !== FALSE) {
        $string = preg_replace(array('#&amp;\#([0-9]+)#', '#&\#?[a-z0-9]+$#'), array('&#\\1', ''), $string);
      }
    }

    if ($bbcode) {
      $string = ' ' . $this->prepareBbcode($string) . ' ';

      // Protect from infinite loops.
      // The while loop to parse nested quote tags has the sad side-effect of
      // entering an infinite loop when the parsed text contains $0 or \0.
      if ($random == NULL) {
        $random_gen = new Random();
        $random = str_replace(array('$', "\\"), array('', ''), $random_gen->string(25));
      }

      $string = str_replace(array('$', "\\"), array('&#36;' . $random, '&#92;' . $random), $string);

      // Parse quote tags.
      // Might seem a bit difficultly done, but trimming doesn't work the usual
      // way.
      while (preg_match("#\[quote\](.*?)\[/quote\]#is", $string, $matches)) {
        $string = preg_replace("#\[quote\]" . preg_quote($matches[1], '#') . "\[/quote\]#is", sprintf($quote_format, ' ' . trim($matches[1])) . ' ', $string);
        unset($matches);
      }
      while (preg_match("#\[quote=(.*?)\](.*?)\[/quote\]#is", $string, $matches)) {
        $string = preg_replace("#\[quote=" . preg_quote($matches[1], '#') . "\]" . preg_quote($matches[2], '#') . "\[/quote\]#is", sprintf($quote_format_named, $matches[1], ' ' . trim($matches[2]) . ' '), $string);
        unset($matches);
      }

      // Undo the dirty fixing.
      $string = str_replace(array('&#36;' . $random, '&#92;' . $random), array('$', "\\"), $string);

      // Parse code tags.
      preg_match_all("#\[code\](.*?)\[/code\]#is", $string, $matches);
      foreach ($matches[1] as $oldpart) {
        $newpart = preg_replace(array(
          '#<img src="[^"]+" alt="([^"]+)" />#',
          "#\n#",
          "#\r#",
        ), array(
          '\\1',
          '<br />',
          '',
        ), $oldpart);
        $string = str_replace('[code]' . $oldpart . '[/code]', '[code]' . $newpart . '[/code]', $string);
      }
      $string = preg_replace("#\[code\](.*?)\[/code\]#is", sprintf($code_format, '\\1'), $string);

      // Parse URL's and e-mail addresses enclosed in special characters.
      $ignore_chars = "([^a-z0-9/]|&\#?[a-z0-9]+;)*?";
      for ($i = 0; $i < 2; $i++) {
        $string = preg_replace(array(
          "#([\s]" . $ignore_chars . ")([\w]+?://[\w\#\$%&~/\.\-;:=,\?@\[\]\+\\\\\'!\(\)\*]*?)(" . $ignore_chars . "[\s])#is",
          "#([\s]" . $ignore_chars . ")(www\.[\w\#\$%&~/\.\-;:=,\?@\[\]\+\\\\\'!\(\)\*]*?)(" . $ignore_chars . "[\s])#is",
          "#([\s]" . $ignore_chars . ")([a-z0-9&\-_\.\+]+?@[\w\-]+\.([\w\-\.]+\.)?[\w]+)(" . $ignore_chars . "[\s])#is",
        ), array(
          '\\1<a href="\\3">\\3</a>\\4',
          '\\1<a href="http://\\3">\\3</a>\\4',
          '\\1<a href="mailto:\\2">\\3</a>\\5',
        ), $string);
      }

      // All kinds of BBCode regexps.
      $links = TRUE;
      $regexps = array(
        // [b]text[/b]
        "#\[b\](.*?)\[/b\]#is" => '<strong>\\1</strong>',
        // [i]text[/i]
        "#\[i\](.*?)\[/i\]#is" => '<em>\\1</em>',
        // [u]text[/u]
        "#\[u\](.*?)\[/u\]#is" => '<u>\\1</u>',
        // [s]text[/s]
        "#\[s\](.*?)\[/s\]#is" => '<s>\\1</s>',
        // [img]image[/img]
        "#\[img\]([\w]+?://[\w\#\$%&~/\.\-;:=,\?@\[\]\+\\\\\'!\(\)\*]*?)\[/img\]#is" => $links ? '<img src="\\1" />' : '\\1',
        // www.usebb.net
        "#([\s])(www\.[\w\#\$%&~/\.\-;:=,\?@\[\]\+\\\\\'!\(\)\*]*?)#is" => $links ? '\\1<a href="http://\\2">\\2</a>\\3' : '\\1\\2\\3',
        // ftp.usebb.net
        "#([\s])(ftp\.[\w\#\$%&~/\.\-;:=,\?@\[\]\+\\\\\'!\(\)\*]*?)([\s])#is" => $links ? '\\1<a href="ftp://\\2">\\2</a>\\3' : '\\1\\2\\3',
        // [url]http://www.usebb.net[/url]
        "#\[url\]([\w]+?://[\w\#\$%&~/\.\-;:=,\?@\[\]\+\\\\\'!\(\)\*]*?)\[/url\]#is" => $links ? '<a href="\\1">\\1</a>' : '\\1',
        // [url=http://www.usebb.net]UseBB[/url]
        "#\[url=([\w]+?://[\w\#\$%&~/\.\-;:=,\?@\[\]\+\\\\\'!\(\)\*]*?)\](.*?)\[/url\]#is" => $links ? '<a href="\\1">\\2</a>' : '\\2 [\\1]',
        // [mailto]somebody@nonexistent.com[/mailto]
        "#\[mailto\]([a-z0-9&\-_\.\+]+?@[\w\-]+\.([\w\-\.]+\.)?[\w]+)\[/mailto\]#is" => $links ? '<a href="mailto:\\1">\\1</a>' : '\\1',
        // [mailto=somebody@nonexistent.com]mail me[/mailto]
        "#\[mailto=([a-z0-9&\-_\.\+]+?@[\w\-]+\.([\w\-\.]+\.)?[\w]+)\](.*?)\[/mailto\]#is" => $links ? '<a href="mailto:\\1">\\3</a>' : '\\3 [\\1]',
        // [color=red]text[/color]
        "#\[color=([\#a-z0-9]+)\](.*?)\[/color\]#is" => '\\2',
        // [google=keyword]text[/google]
        "#\[google=(.*?)\](.*?)\[/google\]#is" => '<a href="http://www.google.com/search?q=\\1">\\2</a>',
      );

      // Now parse those regexps.
      foreach ($regexps as $find => $replace) {
        $string = preg_replace($find, $replace, $string);
      }
      $string = preg_replace_callback("#\[size=([0-9]*?)\](.*?)\[/size\]#is", function ($m) {
        $pt = (int) $m[1];
        $text = $m[2];
        return $pt > 11 ? sprintf('<h2>%s</h2>', $text) : $text;
      }, $string);

      // Remove tags from attributes.
      if (strpos($string, '<') !== FALSE) {
        preg_match_all('#[a-z]+="[^"]*<[^>]*>[^"]*"#', $string, $matches);
        foreach ($matches[0] as $match) {
          $string = str_replace($match, strip_tags($match), $string);
        }
      }
    }
    $string = trim($string);

    if (!$html) {
      $string = str_replace("\r", "", $string);
      $string = trim(Html::normalize(self::applyParagraphs($string)));
      $string = preg_replace(['#<p>\s+#', '#\s+</p>#'], ['<p>', '</p>'], $string);
    }

    return $string;
  }

  /**
   * Prepare string for parsing BBCode.
   *
   * @param string $string
   *   String to cleanup BBCode.
   *
   * @return string
   *   Cleaned up BBCode string.
   */
  protected function prepareBbcode($string) {
    $string = trim($string);
    $existing_tags = array(
      'code',
      'b',
      'i',
      'u',
      's',
      'img',
      'url',
      'mailto',
      'color',
      'size',
      'google',
      'quote',
    );

    // BBCode tags start with an alphabetic character, eventually followed by
    // non [ and ] characters.
    $parts = array_reverse(preg_split('#(\[/?[a-z][^\[\]]*\])#i', $string, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY));

    $open_tags = $open_parameters = array();
    $new_string = '';

    while (count($parts)) {
      $part = array_pop($parts);
      $matches = array();

      // Add open tag.
      if (preg_match('#^\[([a-z]+)(=[^\]]*)?\]$#i', $part, $matches)) {
        $matches[1] = strtolower($matches[1]);

        // Transform tags.
        if (end($open_tags) == 'code') {
          $new_string .= str_replace(array('[', ']'), array('&#91;', '&#93;'), $part);
          continue;
        }

        // Is already open.
        if ($matches[1] != 'quote' && in_array($matches[1], $open_tags)) {
          continue;
        }

        // Only add this if it exists.
        if (in_array($matches[1], $existing_tags)) {
          array_push($open_tags, $matches[1]);
          array_push($open_parameters, isset($matches[2]) ? $matches[2] : '');
        }

        $new_string .= $part;
        continue;
      }

      // Add close tag.
      if (preg_match('#^\[/([a-z]+)\]$#i', $part, $matches)) {
        $matches[1] = strtolower($matches[1]);

        // Transform tags.
        if (end($open_tags) == 'code' && $matches[1] != 'code') {
          $new_string .= str_replace(array('[', ']'), array('&#91;', '&#93;'), $part);
          continue;
        }

        // Unexisting tag.
        if (!in_array($matches[1], $existing_tags)) {
          $new_string .= $part;
          continue;
        }

        // Is current open tag.
        if (end($open_tags) == $matches[1]) {
          array_pop($open_tags);
          array_pop($open_parameters);
          $new_string .= $part;
          continue;
        }

        // Is other open tag.
        if (in_array($matches[1], $open_tags)) {
          $to_reopen_tags = $to_reopen_parameters = array();
          while ($open_tag = array_pop($open_tags)) {
            $open_parameter = array_pop($open_parameters);
            $new_string .= '[/' . $open_tag . ']';
            if ($open_tag == $matches[1]) {
              break;
            }
            array_push($to_reopen_tags, $open_tag);
            array_push($to_reopen_parameters, $open_parameter);
          }

          $to_reopen_tags = array_reverse($to_reopen_tags);
          $to_reopen_parameters = array_reverse($to_reopen_parameters);

          while ($open_tag = array_pop($to_reopen_tags)) {
            $open_parameter = array_pop($to_reopen_parameters);
            $new_string .= '[' . $open_tag . $open_parameter . ']';
            array_push($open_tags, $open_tag);
            array_push($open_parameters, $open_parameter);
          }
        }
      }
      else {
        // Plain text.
        $new_string .= $part;
      }
    }

    // Close opened tags.
    while ($open_tag = array_pop($open_tags)) {
      $open_parameter = array_pop($open_parameters);
      $new_string .= '[/' . $open_tag . $open_parameter . ']';
    }

    // Remove empties.
    foreach ($existing_tags as $existing_tag) {
      $new_string = preg_replace('#\[(' . $existing_tag . ')([^\]]+)?\]\[/(\1)\]#i', '', $new_string);
    }

    return $new_string;
  }

  /**
   * Converts line breaks into <p> and <br> in an intelligent fashion.
   *
   * Taken from core filter.module _filter_autop().
   */
  protected static function applyParagraphs($text) {
    // All block level tags
    $block = '(?:table|thead|tfoot|caption|col|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|option|form|map|area|blockquote|address|math|input|p|h[1-6]|fieldset|legend|hr|article|aside|details|figcaption|figure|footer|header|hgroup|menu|nav|section|summary)';

    // Split at opening and closing PRE, SCRIPT, STYLE, OBJECT, IFRAME tags
    // and comments. We don't apply any processing to the contents of these tags
    // to avoid messing up code. We look for matched pairs and allow basic
    // nesting. For example:
    // "processed <pre> ignored <script> ignored </script> ignored </pre> processed"
    $chunks = preg_split('@(<!--.*?-->|</?(?:pre|script|style|object|iframe|!--)[^>]*>)@i', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
    // Note: PHP ensures the array consists of alternating delimiters and literals
    // and begins and ends with a literal (inserting NULL as required).
    $ignore = FALSE;
    $ignoretag = '';
    $output = '';
    foreach ($chunks as $i => $chunk) {
      if ($i % 2) {
        $comment = (substr($chunk, 0, 4) == '<!--');
        if ($comment) {
          // Nothing to do, this is a comment.
          $output .= $chunk;
          continue;
        }
        // Opening or closing tag?
        $open = ($chunk[1] != '/');
        list($tag) = preg_split('/[ >]/', substr($chunk, 2 - $open), 2);
        if (!$ignore) {
          if ($open) {
            $ignore = TRUE;
            $ignoretag = $tag;
          }
        }
        // Only allow a matching tag to close it.
        elseif (!$open && $ignoretag == $tag) {
          $ignore = FALSE;
          $ignoretag = '';
        }
      }
      elseif (!$ignore) {
        $chunk = preg_replace('|\n*$|', '', $chunk) . "\n\n"; // just to make things a little easier, pad the end
        $chunk = preg_replace('|<br />\s*<br />|', "\n\n", $chunk);
        $chunk = preg_replace('!(<' . $block . '[^>]*>)!', "\n$1", $chunk); // Space things out a little
        $chunk = preg_replace('!(</' . $block . '>)!', "$1\n\n", $chunk); // Space things out a little
        $chunk = preg_replace("/\n\n+/", "\n\n", $chunk); // take care of duplicates
        $chunk = preg_replace('/^\n|\n\s*\n$/', '', $chunk);
        $chunk = '<p>' . preg_replace('/\n\s*\n\n?(.)/', "</p>\n<p>$1", $chunk) . "</p>\n"; // make paragraphs, including one at the end
        $chunk = preg_replace("|<p>(<li.+?)</p>|", "$1", $chunk); // problem with nested lists
        $chunk = preg_replace('|<p><blockquote([^>]*)>|i', "<blockquote$1><p>", $chunk);
        $chunk = str_replace('</blockquote></p>', '</p></blockquote>', $chunk);
        $chunk = preg_replace('|<p>\s*</p>\n?|', '', $chunk); // under certain strange conditions it could create a P of entirely whitespace
        $chunk = preg_replace('!<p>\s*(</?' . $block . '[^>]*>)!', "$1", $chunk);
        $chunk = preg_replace('!(</?' . $block . '[^>]*>)\s*</p>!', "$1", $chunk);
        $chunk = preg_replace('|(?<!<br />)\s*\n|', "<br />\n", $chunk); // make line breaks
        $chunk = preg_replace('!(</?' . $block . '[^>]*>)\s*<br />!', "$1", $chunk);
        $chunk = preg_replace('!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)>)!', '$1', $chunk);
        $chunk = preg_replace('/&([^#])(?![A-Za-z0-9]{1,8};)/', '&amp;$1', $chunk);
      }
      $output .= $chunk;
    }
    return $output;
  }

}
