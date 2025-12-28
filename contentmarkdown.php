<?php
/**
 * @version    1.0.2
 * @package    contentmarkdown (plugin)
 * @author     Mattes H. mattes_h@gmx.net
 * @copyright  Copyright (c) 2023 Mattes H.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 */

//kill direct access
defined('_JEXEC') || die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
require(JPATH_SITE.'/plugins/content/contentmarkdown/parsedown/Parsedown.php');

class PlgContentContentMarkdown extends CMSPlugin
{
    public function onContentPrepare($context, &$article, &$params, $page = 0)
    {
		$Parsedown = new Parsedown();
		$article_text = $article->text;
		$tinymce_on = false;
		if(preg_match('/<p>\s*{markdown/', $article_text, $th)) {
			// echo "Found TineMCE";
			$tinymce_on = true;
			preg_match_all('/<p>{markdown.*?\/markdown}<\/p>/s', $article_text, $matches);
		} else {
			preg_match_all('/{markdown.*?\/markdown}/s', $article_text, $matches);
		}
		// echo "Converting markdown...";
		
        //for each match
        foreach ($matches[0] as $orig_value) {
			$value = $orig_value;
			// test for TintyMCE and remove bad formatting
			if($tinymce_on) {
				if(file_exists(JPATH_SITE.'/tmp/before')) {
					unlink(JPATH_SITE.'/tmp/before');
					unlink(JPATH_SITE.'/tmp/after');
				}
				file_put_contents(JPATH_SITE.'/tmp/before', $value);
				$value = preg_replace('/ /', ' ', $value);
				$value = preg_replace('/<br \/>/', "\r\n", $value);
				$value = preg_replace('/<\/p>/', "\r\n", $value);
				$value = preg_replace('/<[^>]*>/', '', $value);
				$value = html_entity_decode($value);
				file_put_contents(JPATH_SITE.'/tmp/after', $value);
			}
            //find the body
            preg_match('/(?<={markdown)(.*?})(.*?)(?={\/markdown})/s', $value, $tagMatch);
			$content = $tagMatch[2];
			
			// attributes for configuration
			$attribPatterns = 'inline|class';
			$use_inline = false;
			$class = "";
            preg_match_all('/('.$attribPatterns.')="(.*?)"/s', $tagMatch[1], $attribMatches);
			$cntAttribs = count($attribMatches[1]);
			for ($i = 0; $i < $cntAttribs; ++$i) {
				if ($attribMatches[1][$i] == 'inline') {
					$use_inline = ($attribMatches[2][$i] === '1');
					// echo "inline=".$use_inline;
				} else 
				if ($attribMatches[1][$i] == 'class') {
					$class = $attribMatches[2][$i];
				}				
			}
			if($use_inline) {
				$output = $Parsedown->line($content);
			} else {
				$output = $Parsedown->text($content);
			}
			if ($class) {
				// add div class around
				$output = "<div class=\"" . $class . "\">" . $output . "</div>";
			}
			$article->text = str_replace($orig_value, $output, $article_text);
        } // for markdown
    }
}
