<?php

declare(strict_types=1);

namespace Doclify\Dom;

/**
* Doclify StructuredText
*/
class StructuredText
{
  public static function serialize($item)
  {
    switch ($item->type) {
      case 'doc':
        return static::serializeItems($item->content ?? []);
      case 'paragraph':
        return '<p>' . static::serializeItems($item->content ?? []) . '</p>';
      case 'heading':
        return "<h{$item->attrs->level}>" . static::serializeItems($item->content ?? []) . "</h{$item->attrs->level}>";
      case 'heading':
        return "<h{$item->attrs->level}>" . static::serializeItems($item->content ?? []) . "</h{$item->attrs->level}>";
      case 'image':
        return "<p><img src=\"{$item->attrs->url}\"></p>";
      case 'text':
        if (!isset($item->marks)) {
          return \htmlspecialchars($item->text);
        }

        $html = \htmlspecialchars($item->text);

        foreach ($item->marks as $mark) {
          if ($mark->type === 'bold') {
            $html = "<strong>$html</strong>";
          } else if ($mark->type === 'italic') {
            $html = "<em>$html</em>";
          } else if ($mark->type === 'underline') {
            $html = "<span style=\"text-decoration:underline\">$html</span>";
          } else if ($mark->type === 'link') {
            $target = isset($mark->attrs->target) && $mark->attrs->target ? " target=\"{$mark->attrs->target}\" rel=\"noopener\"" : '';
            $html = "<a href=\"{$mark->attrs->href}\"$target>$html</a>";
          }
        }

        return $html;
      case 'bullet_list':
        return '<ul>' . static::serializeItems($item->content ?? []) . '</ul>';
      case 'ordered_list':
        return '<ol>' . static::serializeItems($item->content ?? []) . '</ol>';
      case 'list_item':
        return '<li>' . static::serializeItems($item->content ?? []) . '</li>';
      case 'hard_break':
        return '<br>';
      case 'table':
        return '<table><tbody>' . static::serializeItems($item->content ?? []) . '</tbody></table>';
      case 'table_row':
        return '<tr>' . static::serializeItems($item->content ?? []) . '</tr>';
      case 'table_header':
      case 'table_cell':
        $tag = $item->type === 'table_header' ? 'th' : 'td';
        $attrs = [];

        foreach ($item->attrs as $attr => $value) {
          if ($value) {
            $attrs[] = "$attr=\"$value\"";
          }
        }

        $attrString = \join(' ', $attrs);

        return "<$tag $attrString>" . static::serializeItems($item->content ?? []) . "</$tag>";

      default:
        return '';
    }
  }

  public static function serializeItems($items): string
  {
    return \join('', \array_map([static::class, 'serialize'], (array) $items));
  }

  public static function asHtml($document): string
  {
    if (!$document) {
      return '';
    }

    return static::serialize($document);
  }
}
