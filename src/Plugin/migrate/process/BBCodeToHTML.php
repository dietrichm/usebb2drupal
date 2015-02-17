<?php

/**
 * @file
 * Contains \Drupal\usebb2drupal\Plugin\migrate\process\BBCodeToHTML.
 */

namespace Drupal\usebb2drupal\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\Component\Utility\Random;
use Drupal\Component\Utility\String;

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
  public function transform($value, MigrateExecutable $migrate_executable, Row $row, $destination_property) {
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
      $string = String::checkPlain($string);
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
      $string = _filter_autop($string);
      $string = str_replace("\r", "", $string);
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

}
