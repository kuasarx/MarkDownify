<?php
/**
 * MarkDownify
 *
 * A PHP library for parsing Markdown into HTML code.
 *
 * @package MarkDownify
 * @version 1.0.0
 * @author Juan Camacho
 * @link https://github.com/kuasarx/MarkDownify
 * @license MIT
 * 
 * -----------------------------------------------------------------------
 * 
 * Developer: Juan Camacho
 * Email: kuasarx@gmail.com
 * 
 * -----------------------------------------------------------------------
 */

namespace MarkDownify;

class MarkDownify
{
    private $text;

    public function __construct($text)
    {
        $this->text = $text;
    }

    public function parse()
    {
        $text = $this->text;

        $text = $this->convertNewlines($text);
        $text = $this->convertHeaders($text);
        $text = $this->convertEmphasis($text);
        $text = $this->convertStrikethrough($text);
        $text = $this->convertCodeBlocks($text);
        $text = $this->convertInlineCode($text);
        $text = $this->convertTaskLists($text);
        $text = $this->convertTables($text);
        $text = $this->convertFootnotes($text);
        $text = $this->convertLinks($text);
        $text = $this->convertImages($text);
        $text = $this->convertHorizontalRules($text);
        $text = $this->convertBlockquotes($text);
        $text = $this->convertOrderedLists($text);
        $text = $this->convertLists($text);
        $text = $this->convertDefinitionLists($text);
        $text = $this->convertHighlight($text);
        $text = $this->convertSubscriptSuperscript($text);
        $text = $this->convertSpecialSymbols($text);
        $text = $this->convertEmojis($text);

        return $text;
    }

    private function convertNewlines($text)
    {
        // Convert newlines to <br>
        return preg_replace('/\R/', "<br>\n", $text);
    }

    private function convertHeaders($text)
    {
        // Convert headers with optional IDs
        return preg_replace_callback('/^(#{1,6})\s*([^#]*?)(?:\s+\{#([^}]+)\})?\s*#*\n+/m', function ($matches) {
            $level = strlen($matches[1]);
            $heading = trim($matches[2]);
            $id = !empty($matches[3]) ? ' id="' . $matches[3] . '"' : '';
            return "<h$level$id>$heading</h$level>\n\n";
        }, $text);
    }

    private function convertEmphasis($text)
    {
        // Convert emphasis and strong emphasis
        $text = preg_replace('/(\*\*|__)(?=\S)(.+?)(?<=\S)\1/', '<strong>$2</strong>', $text);
        $text = preg_replace('/(\*|_)(?=\S)(.+?)(?<=\S)\1/', '<em>$2</em>', $text);

        return $text;
    }

    private function convertStrikethrough($text)
    {
        // Convert strikethrough
        return preg_replace('/~~(.*?)~~/', '<del>$1</del>', $text);
    }

    private function convertCodeBlocks($text)
    {
        // Convert fenced code blocks with optional language
        return preg_replace_callback('/```(.*?)\n(.*?)```/s', function ($matches) {
            $language = $matches[1];
            $code = htmlspecialchars($matches[2]);
            return "<pre><code class=\"language-$language\">$code</code></pre>";
        }, $text);
    }

    private function convertInlineCode($text)
    {
        // Convert inline code
        return preg_replace('/`([^`\n]+)`/', '<code>$1</code>', $text);
    }

    private function convertTaskLists($text)
    {
        // Convert task lists
        return preg_replace_callback('/^- \[(x| )\](.*)$/m', function ($matches) {
            $checked = $matches[1] === 'x' ? 'checked' : '';
            $item = trim($matches[2]);
            return "<input type=\"checkbox\" $checked disabled> $item";
        }, $text);
    }

    private function convertTables($text) {
        $lines = explode("\n", $text);
        $insideTable = false;
        $result = "";
        foreach($lines as $line) {
            if(strpos($line, "|") !== false) {
                if(!$insideTable) {
                    // Start of a table
                    $insideTable = true;
                    $result .= "<table>\n";
                }
                if(strpos($line, "---") !== false)
                    $result .= "<thead>\n";
                else if(strpos($line, "</thead>") !== false)
                    $result .= "<tbody>\n";
                else {
                    // It's a row
                    $line = str_replace("|", "<td>", $line);
                    $line = "<tr><td>" . $line . "</td></tr>\n";
                    $result .= $line;
                }
            } else {
                if($insideTable) {
                    // End of a table
                    $insideTable = false;
                    $result .= "</table>\n";
                }
                // Append non-table line
                $result .= $line . "\n";
            }
        }
        return $result;
    }

    private function convertFootnotes($text)
    {
        // Convert footnotes
        $text = preg_replace_callback('/\[\^(\d+)\]:\s*(.*?)\s*$/m', function ($matches) {
            $index = $matches[1];
            $content = $matches[2];
            return "<sup id=\"fnref:$index\"><a href=\"#fn:$index\" class=\"footnote-ref\">[$index]</a></sup>";
        }, $text);

        $text = preg_replace_callback('/^\[\^(\d+)\]:\s*(.*?)\s*$/m', function ($matches) {
            $index = $matches[1];
            $content = $matches[2];
            return "<div id=\"fn:$index\" class=\"footnote\"><sup>$index</sup> $content</div>";
        }, $text);

        return $text;
    }

    private function convertLinks($text)
    {
        // Convert links
        return preg_replace_callback('/\[(.*?)\]\((.*?)\)/', function ($matches) {
            $text = $matches[1];
            $url = $matches[2];
            return "<a href=\"$url\">$text</a>";
        }, $text);
    }

    private function convertImages($text)
    {
        // Convert images
        return preg_replace_callback('/!\[(.*?)\]\((.*?)\)/', function ($matches) {
            $altText = $matches[1];
            $imageUrl = $matches[2];
            return "<img src=\"$imageUrl\" alt=\"$altText\">";
        }, $text);
    }

    private function convertHorizontalRules($text)
    {
        // Convert horizontal rules
        $hr = "<hr>\n";
        $text = preg_replace('/^(?:\s*([-_*]){3,}\s*)+$/m', $hr, $text);

        return $text;
    }

    private function convertBlockquotes($text)
    {
        // Convert blockquotes
        return preg_replace_callback('/^>(\s*>)*\s*(.*?)\s*$/m', function ($matches) {
            $quoteLevel = substr_count($matches[0], '>');
            $content = trim($matches[count($matches) - 1]);
            $blockquote = '<blockquote>' . $content . '</blockquote>';

            if ($quoteLevel > 1) {
                $blockquote = $this->addNestedBlockquote($blockquote, $quoteLevel - 1);
            }

            return $blockquote;
        }, $text);
    }

    private function addNestedBlockquote($content, $quoteLevel)
    {
        for ($i = 0; $i < $quoteLevel; $i++) {
            $content = '<blockquote>' . $content . '</blockquote>';
        }

        return $content;
    }


    private function convertOrderedLists($text)
    {
        // Convert unordered lists
        $text = preg_replace('/^(\s*)[*+-]\s*(.*?)$/m', '$1<li>$2</li>', $text);
        $text = preg_replace('/(<li>.*?<\/li>)(?:\n(?![*\-+]).+)+/s', '<ul>$1</ul>', $text);

        // Convert ordered lists
        $text = preg_replace('/^(\s*)\d+\.\s*(.*?)$/m', '$1<li>$2</li>', $text);
        $text = preg_replace('/(<li>.*?<\/li>)(?:\n(?!\d\.).+)+/s', '<ol>$1</ol>', $text);

        return $text;
    }
    private function convertLists($text)
    {
        $lines = explode("\n", str_replace("\r", "", $text));

        // Pattern for markdown lists, $matches[1] = indentation, $matches[2] = item
        $pattern = '/^( *)[\*\-]\s(.*)/';

        $htmlList = '';

        // Keep track of number of indents in previous line
        $prevSpaces = 0;
        foreach ($lines as $line) {
            // If line matches markdown list pattern.
            if (preg_match($pattern, $line, $matches)) {
                $numSpaces = strlen($matches[1]);
                $listItem = $matches[2];

                if ($numSpaces > $prevSpaces) {
                    // If more indents, start a new list
                    $htmlList .= "\n<ul>\n<li>{$listItem}";
                } elseif ($numSpaces < $prevSpaces) {
                    // If fewer indents, close last list and start new list item
                    $htmlList .= "\n</li>\n</ul>\n<li>{$listItem}";
                } else {
                    // If same number of indents, just start new list item
                    $htmlList .= "\n</li>\n<li>{$listItem}";
                }

                $prevSpaces = $numSpaces;
            } else {
                // If line doesn't match list pattern, append as plain text
                $htmlList .= "\n{$line}";
            }
        }
        // After going through all lines, close any open lists
        $htmlList .= str_repeat("</li>\n</ul>", $prevSpaces / 2);

        $text = $htmlList;

        return $text;
    }

    private function convertDefinitionLists($text)
    {
        // Convert definition lists
        $text = preg_replace('/^(\S.*?)\s*:\s*(.*?)$/m', '<dt>$1</dt><dd>$2</dd>', $text);
        $text = preg_replace('/(<dt>.*?<\/dt>)(?:\n(?!\S).+)+/s', '<dl>$1</dl>', $text);

        return $text;
    }

    private function convertHighlight($text)
    {
        // Convert highlight
        return preg_replace('/===(.*?)===/', '<mark>$1</mark>', $text);
    }

    private function convertSubscriptSuperscript($text)
    {
        // Convert subscript and superscript
        $text = preg_replace('/\^\^(\S.*?)\^\^/', '<sup>$1</sup>', $text);
        $text = preg_replace('/~(\S.*?)~/', '<sub>$1</sub>', $text);

        return $text;
    }

    private function convertSpecialSymbols($text)
    {
        // Convert special symbols
        $text = preg_replace('/\(c\)/i', '&copy;', $text);
        $text = preg_replace('/\(r\)/i', '&reg;', $text);
        $text = preg_replace('/\(tm\)/i', '&trade;', $text);
        $text = preg_replace('/\(p\)/i', '&pound;', $text);
        $text = preg_replace('/\+-/', '&plusmn;', $text);

        return $text;
    }

    private function convertEmojis($text)
    {
        // Convert emojis (full support)
        $emojiMap = [
            ":grinning:" => "&#x1F600;",
            ":grin:" => "&#x1F601;",
            ":joy:" => "&#x1F602;",
            ":smiley:" => "&#x1F603;",
            ":smile:" => "&#x1F604;",
            ":sweat_smile:" => "&#x1F605;",
            ":laughing:" => "&#x1F606;",
            ":innocent:" => "&#x1F607;",
            ":wink:" => "&#x1F609;",
            ":blush:" => "&#x1F60A;",
            ":slightly_smiling_face:" => "&#x1F642;",
            ":upside_down_face:" => "&#x1F643;",
            ":relaxed:" => "&#x263A;",
            ":heart_eyes:" => "&#x1F60D;",
            ":kissing_heart:" => "&#x1F618;",
            ":kissing:" => "&#x1F617;",
            ":kissing_smiling_eyes:" => "&#x1F619;",
            ":kissing_closed_eyes:" => "&#x1F61A;",
            ":yum:" => "&#x1F60B;",
            ":stuck_out_tongue:" => "&#x1F61B;",
            ":stuck_out_tongue_winking_eye:" => "&#x1F61C;",
            ":zany_face:" => "&#x1F92A;",
            ":face_with_raised_eyebrow:" => "&#x1F928;",
            ":face_with_monocle:" => "&#x1F9D0;",
            ":nerd_face:" => "&#x1F913;",
            ":sunglasses:" => "&#x1F60E;",
            ":star_struck:" => "&#x1F929;",
            ":partying_face:" => "&#x1F973;",
            ":smirk:" => "&#x1F60F;",
            ":unamused:" => "&#x1F612;",
            ":disappointed:" => "&#x1F61E;",
            ":pensive:" => "&#x1F614;",
            ":worried:" => "&#x1F61F;",
            ":confused:" => "&#x1F615;",
            ":slightly_frowning_face:" => "&#x1F641;",
            ":frowning_face:" => "&#x2639;",
            ":persevere:" => "&#x1F623;",
            ":confounded:" => "&#x1F616;",
            ":tired_face:" => "&#x1F62B;",
            ":weary:" => "&#x1F629;",
            ":cry:" => "&#x1F622;",
            ":sob:" => "&#x1F62D;",
            ":triumph:" => "&#x1F624;",
            ":angry:" => "&#x1F620;",
            ":rage:" => "&#x1F621;",
            ":sleepy:" => "&#x1F62A;",
            ":mask:" => "&#x1F637;",
            ":face_with_thermometer:" => "&#x1F912;",
            ":face_with_head_bandage:" => "&#x1F915;",
            ":nauseated_face:" => "&#x1F922;",
            ":face_vomiting:" => "&#x1F92E;",
            ":sneezing_face:" => "&#x1F927;",
            ":hot_face:" => "&#x1F975;",
            ":cold_face:" => "&#x1F976;",
            ":woozy_face:" => "&#x1F974;",
            ":dizzy_face:" => "&#x1F635;",
            ":exploding_head:" => "&#x1F92F;",
            ":cowboy_hat_face:" => "&#x1F920;",
            ":partly_sunny_face:" => "&#x26C5;",
            ":clown_face:" => "&#x1F921;",
            ":lying_face:" => "&#x1F925;",
            ":shushing_face:" => "&#x1F92B;",
            ":face_with_symbols_on_mouth:" => "&#x1F92C;",
            ":face_with_hand_over_mouth:" => "&#x1F92D;",
            ":serious_face_with_symbols_covering_mouth:" => "&#x1F92F;",
            ":hugging_face:" => "&#x1F917;",
            ":thinking_face:" => "&#x1F914;",
            ":zipper_mouth_face:" => "&#x1F910;",
            ":face_with_raised_eyebrow_tongue:" => "&#x1F928;&#x200D;&#x1F61D;",
            ":neutral_face:" => "&#x1F610;",
            ":expressionless:" => "&#x1F611;",
            ":no_mouth:" => "&#x1F636;",
            ":smiling_imp:" => "&#x1F608;",
            ":imp:" => "&#x1F47F;",
            ":skull:" => "&#x1F480;",
            ":skull_and_crossbones:" => "&#x2620;",
            ":hankey:" => "&#x1F4A9;",
            ":ghost:" => "&#x1F47B;",
            ":alien:" => "&#x1F47D;",
            ":space_invader:" => "&#x1F47E;",
            ":robot:" => "&#x1F916;",
            ":poop:" => "&#x1F4A9;",
            ":smiley_cat:" => "&#x1F63A;",
            ":smile_cat:" => "&#x1F638;",
            ":joy_cat:" => "&#x1F639;",
            ":heart_eyes_cat:" => "&#x1F63B;",
            ":smirk_cat:" => "&#x1F63C;",
            ":kissing_cat:" => "&#x1F63D;",
            ":scream_cat:" => "&#x1F640;",
            ":crying_cat_face:" => "&#x1F63F;",
            ":pouting_cat:" => "&#x1F63E;",
            ":palms_up_together:" => "&#x1F932;",
            ":open_hands:" => "&#x1F450;",
            ":raised_hands:" => "&#x1F64C;",
            ":clap:" => "&#x1F44F;",
            ":handshake:" => "&#x1F91D;",
            ":thumbsup:" => "&#x1F44D;",
            ":thumbsdown:" => "&#x1F44E;",
            ":punch:" => "&#x1F44A;",
            ":fist:" => "&#x270A;",
            ":left_facing_fist:" => "&#x1F91B;",
            ":right_facing_fist:" => "&#x1F91C;",
            ":fingers_crossed:" => "&#x1F91E;",
            ":v:" => "&#x270C;",
            ":ok_hand:" => "&#x1F44C;",
            ":raised_hand:" => "&#x270B;",
            ":muscle:" => "&#x1F4AA;",
            ":pray:" => "&#x1F64F;",
            ":foot:" => "&#x1F9B6;",
            ":leg:" => "&#x1F9B5;",
            ":mechanical_arm:" => "&#x1F9BE;",
            ":mechanical_leg:" => "&#x1F9BF;",
            ":handshake_hidden:" => "&#x1F91A;",
            ":writing_hand:" => "&#x270D;",
            ":nail_care:" => "&#x1F485;",
            ":lips:" => "&#x1F444;",
            ":tongue:" => "&#x1F445;",
            ":ear:" => "&#x1F442;",
            ":nose:" => "&#x1F443;",
            ":eye:" => "&#x1F441;",
            ":eyes:" => "&#x1F440;",
            ":brain:" => "&#x1F9E0;",
            ":bust_in_silhouette:" => "&#x1F464;",
            ":busts_in_silhouette:" => "&#x1F465;",
            ":speaking_head:" => "&#x1F5E3;",
            ":baby:" => "&#x1F476;",
            ":child:" => "&#x1F9D2;",
            ":boy:" => "&#x1F466;",
            ":girl:" => "&#x1F467;",
            ":adult:" => "&#x1F9D1;",
            ":man:" => "&#x1F468;",
            ":woman:" => "&#x1F469;",
            ":blonde_woman:" => "&#x1F471;",
            ":blonde_man:" => "&#x1F471;&zwj;&#x2642;&#xFE0F;",
            ":bearded_person:" => "&#x1F9D4;",
            ":older_adult:" => "&#x1F9D3;",
            ":older_man:" => "&#x1F474;",
            ":older_woman:" => "&#x1F475;",
            ":man_with_gua_pi_mao:" => "&#x1F472;",
            ":woman_with_headscarf:" => "&#x1F9D5;",
            ":woman_with_turban:" => "&#x1F473;",
            ":man_with_turban:" => "&#x1F473;",
            ":policewoman:" => "&#x1F46E;",
            ":policeman:" => "&#x1F46E;",
            ":construction_worker_woman:" => "&#x1F477;",
            ":construction_worker_man:" => "&#x1F477;",
            ":guardswoman:" => "&#x1F482;",
            ":guardsman:" => "&#x1F482;",
            ":female_detective:" => "&#x1F575;",
            ":male_detective:" => "&#x1F575;&zwj;&#x2642;&#xFE0F;",
            ":woman_health_worker:" => "&#x1F469;&#x200D;&#x2695;&#xFE0F;",
            ":man_health_worker:" => "&#x1F468;&#x200D;&#x2695;&#xFE0F;",
            ":woman_farmer:" => "&#x1F469;&#x200D;&#x1F33E;",
            ":man_farmer:" => "&#x1F468;&#x200D;&#x1F33E;",
            ":woman_cook:" => "&#x1F469;&#x200D;&#x1F373;",
            ":man_cook:" => "&#x1F468;&#x200D;&#x1F373;",
            ":woman_student:" => "&#x1F469;&#x200D;&#x1F393;",
            ":man_student:" => "&#x1F468;&#x200D;&#x1F393;",
            ":woman_singer:" => "&#x1F469;&#x200D;&#x1F3A4;",
            ":man_singer:" => "&#x1F468;&#x200D;&#x1F3A4;",
            ":woman_teacher:" => "&#x1F469;&#x200D;&#x1F3EB;",
            ":man_teacher:" => "&#x1F468;&#x200D;&#x1F3EB;",
            ":woman_factory_worker:" => "&#x1F469;&#x200D;&#x1F3ED;",
            ":man_factory_worker:" => "&#x1F468;&#x200D;&#x1F3ED;",
            ":woman_technologist:" => "&#x1F469;&#x200D;&#x1F4BB;",
            ":man_technologist:" => "&#x1F468;&#x200D;&#x1F4BB;",
            ":woman_office_worker:" => "&#x1F469;&#x200D;&#x1F4BC;",
            ":man_office_worker:" => "&#x1F468;&#x200D;&#x1F4BC;",
            ":woman_mechanic:" => "&#x1F469;&#x200D;&#x1F527;",
            ":man_mechanic:" => "&#x1F468;&#x200D;&#x1F527;",
            ":woman_scientist:" => "&#x1F469;&#x200D;&#x1F52C;",
            ":man_scientist:" => "&#x1F468;&#x200D;&#x1F52C;",
            ":woman_artist:" => "&#x1F469;&#x200D;&#x1F3A8;",
            ":man_artist:" => "&#x1F468;&#x200D;&#x1F3A8;",
            ":woman_firefighter:" => "&#x1F469;&#x200D;&#x1F692;",
            ":man_firefighter:" => "&#x1F468;&#x200D;&#x1F692;",
            ":woman_pilot:" => "&#x1F469;&#x200D;&#x2708;&#xFE0F;",
            ":man_pilot:" => "&#x1F468;&#x200D;&#x2708;&#xFE0F;",
            ":woman_astronaut:" => "&#x1F469;&#x200D;&#x1F680;",
            ":man_astronaut:" => "&#x1F468;&#x200D;&#x1F680;",
            ":woman_judge:" => "&#x1F469;&#x200D;&#x2696;&#xFE0F;",
            ":man_judge:" => "&#x1F468;&#x200D;&#x2696;&#xFE0F;",
            ":woman_superhero:" => "&#x1F9B8;&#x200D;&#x2640;&#xFE0F;",
            ":man_superhero:" => "&#x1F9B8;&#x200D;&#x2642;&#xFE0F;",
            ":woman_supervillain:" => "&#x1F9B9;&#x200D;&#x2640;&#xFE0F;",
            ":man_supervillain:" => "&#x1F9B9;&#x200D;&#x2642;&#xFE0F;",
            ":woman_skeleton:" => "&#x1F480;&zwj;&#x2640;&#xFE0F;",
            ":man_skeleton:" => "&#x1F480;&zwj;&#x2642;&#xFE0F;",
            ":woman_elf:" => "&#x1F9DD;&#x200D;&#x2640;&#xFE0F;",
            ":man_elf:" => "&#x1F9DD;&#x200D;&#x2642;&#xFE0F;",
            ":woman_vampire:" => "&#x1F9DB;&#x200D;&#x2640;&#xFE0F;",
            ":man_vampire:" => "&#x1F9DB;&#x200D;&#x2642;&#xFE0F;",
            ":woman_zombie:" => "&#x1F9DF;&#x200D;&#x2640;&#xFE0F;",
            ":man_zombie:" => "&#x1F9DF;&#x200D;&#x2642;&#xFE0F;",
            ":woman_genie:" => "&#x1F9DE;&#x200D;&#x2640;&#xFE0F;",
            ":man_genie:" => "&#x1F9DE;&#x200D;&#x2642;&#xFE0F;",
            ":mermaid:" => "&#x1F9DC;&#x200D;&#x2640;&#xFE0F;",
            ":merman:" => "&#x1F9DC;&#x200D;&#x2642;&#xFE0F;",
            ":woman_fairy:" => "&#x1F9DA;&#x200D;&#x2640;&#xFE0F;",
            ":man_fairy:" => "&#x1F9DA;&#x200D;&#x2642;&#xFE0F;",
            ":angel:" => "&#x1F47C;",
            ":pregnant_woman:" => "&#x1F930;",
            ":breastfeeding:" => "&#x1F931;",
            ":princess:" => "&#x1F478;",
            ":prince:" => "&#x1F934;",
            ":bride_with_veil:" => "&#x1F470;",
            ":man_in_tuxedo:" => "&#x1F935;",
            ":running_woman:" => "&#x1F3C3;",
            ":running_man:" => "&#x1F3C3;&#x200D;&#x2642;&#xFE0F;",
            ":walking_woman:" => "&#x1F6B6;",
            ":walking_man:" => "&#x1F6B6;&#x200D;&#x2642;&#xFE0F;",
            ":dancer:" => "&#x1F483;",
            ":man_dancing:" => "&#x1F57A;",
            ":dancing_women:" => "&#x1F46F;",
            ":dancing_men:" => "&#x1F46F;&#x200D;&#x2642;&#xFE0F;",
            ":couple:" => "&#x1F46B;",
            ":two_women_holding_hands:" => "&#x1F46D;",
            ":two_men_holding_hands:" => "&#x1F46C;",
            ":couple_with_heart_woman_man:" => "&#x1F491;",
            ":couple_with_heart_woman_woman:" => "&#x1F469;&zwj;&#x2764;&#xFE0F;&zwj;&#x1F469;",
            ":couple_with_heart_man_man:" => "&#x1F468;&zwj;&#x2764;&#xFE0F;&zwj;&#x1F468;",
            ":couplekiss_man_woman:" => "&#x1F48F;",
            ":couplekiss_woman_woman:" => "&#x1F469;&zwj;&#x2764;&#xFE0F;&zwj;&#x1F48B;&zwj;&#x1F469;",
            ":couplekiss_man_man:" => "&#x1F468;&zwj;&#x2764;&#xFE0F;&zwj;&#x1F48B;&zwj;&#x1F468;",
            ":family_man_woman_boy:" => "&#x1F46A;",
            ":family_man_woman_girl:" => "&#x1F468;&#x200D;&#x1F469;&#x200D;&#x1F467;",
            ":family_man_woman_girl_boy:" => "&#x1F468;&#x200D;&#x1F469;&#x200D;&#x1F467;&#x200D;&#x1F466;",
            ":family_man_woman_boy_boy:" => "&#x1F468;&#x200D;&#x1F469;&#x200D;&#x1F466;&#x200D;&#x1F466;",
            ":family_man_woman_girl_girl:" => "&#x1F468;&#x200D;&#x1F469;&#x200D;&#x1F467;&#x200D;&#x1F467;",
            ":family_woman_woman_boy:" => "&#x1F469;&#x200D;&#x1F469;&#x200D;&#x1F466;",
            ":family_woman_woman_girl:" => "&#x1F469;&#x200D;&#x1F469;&#x200D;&#x1F467;",
            ":family_woman_woman_girl_boy:" => "&#x1F469;&#x200D;&#x1F469;&#x200D;&#x1F467;&#x200D;&#x1F466;",
            ":family_woman_woman_boy_boy:" => "&#x1F469;&#x200D;&#x1F469;&#x200D;&#x1F466;&#x200D;&#x1F466;",
            ":family_woman_woman_girl_girl:" => "&#x1F469;&#x200D;&#x1F469;&#x200D;&#x1F467;&#x200D;&#x1F467;",
            ":family_man_man_boy:" => "&#x1F468;&#x200D;&#x1F468;&#x200D;&#x1F466;",
            ":family_man_man_girl:" => "&#x1F468;&#x200D;&#x1F468;&#x200D;&#x1F467;",
            ":family_man_man_girl_boy:" => "&#x1F468;&#x200D;&#x1F468;&#x200D;&#x1F467;&#x200D;&#x1F466;",
            ":family_man_man_boy_boy:" => "&#x1F468;&#x200D;&#x1F468;&#x200D;&#x1F466;&#x200D;&#x1F466;",
            ":family_man_man_girl_girl:" => "&#x1F468;&#x200D;&#x1F468;&#x200D;&#x1F467;&#x200D;&#x1F467;",
            ":family_woman_boy:" => "&#x1F469;&#x200D;&#x1F466;",
            ":family_woman_girl:" => "&#x1F469;&#x200D;&#x1F467;",
            ":family_woman_girl_boy:" => "&#x1F469;&#x200D;&#x1F467;&#x200D;&#x1F466;",
            ":family_woman_boy_boy:" => "&#x1F469;&#x200D;&#x1F466;&#x200D;&#x1F466;",
            ":family_woman_girl_girl:" => "&#x1F469;&#x200D;&#x1F467;&#x200D;&#x1F467;",
            ":family_man_boy:" => "&#x1F468;&#x200D;&#x1F466;",
            ":family_man_girl:" => "&#x1F468;&#x200D;&#x1F467;",
            ":family_man_girl_boy:" => "&#x1F468;&#x200D;&#x1F467;&#x200D;&#x1F466;",
            ":family_man_boy_boy:" => "&#x1F468;&#x200D;&#x1F466;&#x200D;&#x1F466;",
            ":family_man_girl_girl:" => "&#x1F468;&#x200D;&#x1F467;&#x200D;&#x1F467;",
            ":yarn:" => "&#x1F9F6;",
            ":thread:" => "&#x1F9F5;",
            ":coat:" => "&#x1F9E5;",
            ":lab_coat:" => "&#x1F97C;",
            ":safety_vest:" => "&#x1F9BA;",
            ":womans_clothes:" => "&#x1F45A;",
            ":tshirt:" => "&#x1F455;",
            ":jeans:" => "&#x1F456;",
            ":necktie:" => "&#x1F454;",
            ":dress:" => "&#x1F457;",
            ":bikini:" => "&#x1F459;",
            ":kimono:" => "&#x1F458;",
            ":lipstick:" => "&#x1F484;",
            ":kiss:" => "&#x1F48B;",
            ":footprints:" => "&#x1F463;",
            ":flat_shoe:" => "&#x1F97F;",
            ":high_heel:" => "&#x1F460;",
            ":sandal:" => "&#x1F461;",
            ":boot:" => "&#x1F462;",
            ":mans_shoe:" => "&#x1F45E;",
            ":athletic_shoe:" => "&#x1F45F;",
            ":hiking_boot:" => "&#x1F97E;",
            ":socks:" => "&#x1F9E6;",
            ":gloves:" => "&#x1F9E4;",
            ":scarf:" => "&#x1F9E3;",
            ":tophat:" => "&#x1F3A9;",
            ":billed_cap:" => "&#x1F9E2;",
            ":womans_hat:" => "&#x1F452;",
            ":mortar_board:" => "&#x1F393;",
            ":helmet_with_white_cross:" => "&#x26D1;",
            ":crown:" => "&#x1F451;",
            ":ring:" => "&#x1F48D;",
            ":pouch:" => "&#x1F45D;",
            ":purse:" => "&#x1F45B;",
            ":handbag:" => "&#x1F45C;",
            ":briefcase:" => "&#x1F4BC;",
            ":school_satchel:" => "&#x1F392;",
            ":luggage:" => "&#x1F9F3;",
            ":eyeglasses:" => "&#x1F453;",
            ":dark_sunglasses:" => "&#x1F576;",
            ":goggles:" => "&#x1F97D;",
            ":closed_umbrella:" => "&#x1F302;",
            ":dog:" => "&#x1F436;",
            ":cat:" => "&#x1F431;",
            ":mouse:" => "&#x1F42D;",
            ":hamster:" => "&#x1F439;",
            ":rabbit:" => "&#x1F430;",
            ":fox_face:" => "&#x1F98A;",
            ":bear:" => "&#x1F43B;",
            ":panda_face:" => "&#x1F43C;",
            ":koala:" => "&#x1F428;",
            ":tiger:" => "&#x1F42F;",
            ":lion:" => "&#x1F981;",
            ":cow:" => "&#x1F42E;",
            ":pig:" => "&#x1F437;",
            ":pig_nose:" => "&#x1F43D;",
            ":frog:" => "&#x1F438;",
            ":monkey_face:" => "&#x1F435;",
            ":see_no_evil:" => "&#x1F648;",
            ":hear_no_evil:" => "&#x1F649;",
            ":speak_no_evil:" => "&#x1F64A;",
            ":monkey:" => "&#x1F412;",
            ":chicken:" => "&#x1F414;",
            ":penguin:" => "&#x1F427;",
            ":bird:" => "&#x1F426;",
            ":baby_chick:" => "&#x1F424;",
            ":hatching_chick:" => "&#x1F423;",
            ":hatched_chick:" => "&#x1F425;",
            ":duck:" => "&#x1F986;",
            ":eagle:" => "&#x1F985;",
            ":owl:" => "&#x1F989;",
            ":bat:" => "&#x1F987;",
            ":wolf:" => "&#x1F43A;",
            ":boar:" => "&#x1F417;",
            ":horse:" => "&#x1F434;",
            ":unicorn_face:" => "&#x1F984;",
            ":honeybee:" => "&#x1F41D;",
            ":bug:" => "&#x1F41B;",
            ":butterfly:" => "&#x1F98B;",
            ":snail:" => "&#x1F40C;",
            ":beetle:" => "&#x1F41E;",
            ":ant:" => "&#x1F41C;",
            ":spider:" => "&#x1F577;",
            ":scorpion:" => "&#x1F982;",
            ":crab:" => "&#x1F980;",
            ":snake:" => "&#x1F40D;",
            ":lizard:" => "&#x1F98E;",
            ":t-rex:" => "&#x1F996;",
            ":sauropod:" => "&#x1F995;",
            ":turtle:" => "&#x1F422;",
            ":tropical_fish:" => "&#x1F420;",
            ":fish:" => "&#x1F41F;",
            ":blowfish:" => "&#x1F421;",
            ":dolphin:" => "&#x1F42C;",
            ":shark:" => "&#x1F988;",
            ":whale:" => "&#x1F433;",
            ":whale2:" => "&#x1F40B;",
            ":crocodile:" => "&#x1F40A;",
            ":leopard:" => "&#x1F406;",
            ":tiger2:" => "&#x1F405;",
            ":water_buffalo:" => "&#x1F403;",
            ":ox:" => "&#x1F402;",
            ":cow2:" => "&#x1F404;",
            ":deer:" => "&#x1F98C;",
            ":dromedary_camel:" => "&#x1F42A;",
            ":camel:" => "&#x1F42B;",
            ":elephant:" => "&#x1F418;",
            ":rhinoceros:" => "&#x1F98F;",
            ":gorilla:" => "&#x1F98D;",
            ":racehorse:" => "&#x1F40E;",
            ":pig2:" => "&#x1F416;",
            ":goat:" => "&#x1F410;",
            ":ram:" => "&#x1F40F;",
            ":sheep:" => "&#x1F411;",
            ":dog2:" => "&#x1F415;",
            ":poodle:" => "&#x1F429;",
            ":cat2:" => "&#x1F408;",
            ":rooster:" => "&#x1F413;",
            ":turkey:" => "&#x1F983;",
            ":dove:" => "&#x1F54A;",
            ":rabbit2:" => "&#x1F407;",
            ":mouse2:" => "&#x1F401;",
            ":rat:" => "&#x1F400;",
            ":chipmunk:" => "&#x1F43F;",
            ":feet:" => "&#x1F43E;",
            ":paw_prints:" => "&#x1F43E;",
            ":dragon:" => "&#x1F409;",
            ":dragon_face:" => "&#x1F432;",
            ":cactus:" => "&#x1F335;",
            ":christmas_tree:" => "&#x1F384;",
            ":evergreen_tree:" => "&#x1F332;",
            ":deciduous_tree:" => "&#x1F333;",
            ":palm_tree:" => "&#x1F334;",
            ":seedling:" => "&#x1F331;",
            ":herb:" => "&#x1F33F;",
            ":shamrock:" => "&#x2618;",
            ":four_leaf_clover:" => "&#x1F340;",
            ":bamboo:" => "&#x1F38D;",
            ":tanabata_tree:" => "&#x1F38B;",
            ":leaves:" => "&#x1F343;",
            ":fallen_leaf:" => "&#x1F342;",
            ":maple_leaf:" => "&#x1F341;",
            ":ear_of_rice:" => "&#x1F33E;",
            ":hibiscus:" => "&#x1F33A;",
            ":sunflower:" => "&#x1F33B;",
            ":rose:" => "&#x1F339;",
            ":wilted_flower:" => "&#x1F940;",
            ":tulip:" => "&#x1F337;",
            ":blossom:" => "&#x1F33C;",
            ":cherry_blossom:" => "&#x1F338;",
            ":bouquet:" => "&#x1F490;",
            ":mushroom:" => "&#x1F344;",
            ":chestnut:" => "&#x1F330;",
            ":jack_o_lantern:" => "&#x1F383;",
            ":shell:" => "&#x1F41A;",
            ":spider_web:" => "&#x1F578;",
            ":earth_americas:" => "&#x1F30E;",
            ":earth_africa:" => "&#x1F30D;",
            ":earth_asia:" => "&#x1F30F;",
            ":full_moon:" => "&#x1F315;",
            ":waning_gibbous_moon:" => "&#x1F316;",
            ":last_quarter_moon:" => "&#x1F317;",
            ":waning_crescent_moon:" => "&#x1F318;",
            ":new_moon:" => "&#x1F311;",
            ":waxing_crescent_moon:" => "&#x1F312;",
            ":first_quarter_moon:" => "&#x1F313;",
            ":waxing_gibbous_moon:" => "&#x1F314;",
            ":new_moon_with_face:" => "&#x1F31A;",
            ":full_moon_with_face:" => "&#x1F31D;",
            ":first_quarter_moon_with_face:" => "&#x1F31B;",
            ":last_quarter_moon_with_face:" => "&#x1F31C;",
            ":sun_with_face:" => "&#x1F31E;",
            ":crescent_moon:" => "&#x1F319;",
            ":star:" => "&#x2B50;",
            ":star2:" => "&#x1F31F;",
            ":dizzy:" => "&#x1F4AB;",
            ":sparkles:" => "&#x2728;",
            ":comet:" => "&#x2604;",
            ":sunny:" => "&#x2600;",
            ":sun_behind_small_cloud:" => "&#x1F324;",
            ":partly_sunny:" => "&#x26C5;",
            ":sun_behind_large_cloud:" => "&#x1F325;",
            ":sun_behind_rain_cloud:" => "&#x1F326;",
            ":cloud:" => "&#x2601;",
            ":cloud_with_rain:" => "&#x1F327;",
            ":cloud_with_lightning_and_rain:" => "&#x26C8;",
            ":cloud_with_lightning:" => "&#x1F329;",
            ":zap:" => "&#x26A1;",
            ":fire:" => "&#x1F525;",
            ":boom:" => "&#x1F4A5;",
            ":snowflake:" => "&#x2744;",
            ":cloud_with_snow:" => "&#x1F328;",
            ":snowman:" => "&#x26C4;",
            ":snowman_without_snow:" => "&#x26C4;&#xFE0F;",
            ":wind_face:" => "&#x1F32C;",
            ":dash:" => "&#x1F4A8;",
            ":tornado:" => "&#x1F32A;",
            ":fog:" => "&#x1F32B;",
            ":open_umbrella:" => "&#x2602;",
            ":umbrella:" => "&#x2602;",
            ":droplet:" => "&#x1F4A7;",
            ":sweat_drops:" => "&#x1F4A6;",
            ":ocean:" => "&#x1F30A;",
            ":green_apple:" => "&#x1F34F;",
            ":apple:" => "&#x1F34E;",
            ":pear:" => "&#x1F350;",
            ":tangerine:" => "&#x1F34A;",
            ":lemon:" => "&#x1F34B;",
            ":banana:" => "&#x1F34C;",
            ":watermelon:" => "&#x1F349;",
            ":grapes:" => "&#x1F347;",
            ":strawberry:" => "&#x1F353;",
            ":melon:" => "&#x1F348;",
            ":cherries:" => "&#x1F352;",
            ":peach:" => "&#x1F351;",
            ":pineapple:" => "&#x1F34D;",
            ":coconut:" => "&#x1F965;",
            ":kiwi_fruit:" => "&#x1F95D;",
            ":mango:" => "&#x1F96D;",
            ":avocado:" => "&#x1F951;",
            ":broccoli:" => "&#x1F966;",
            ":tomato:" => "&#x1F345;",
            ":eggplant:" => "&#x1F346;",
            ":cucumber:" => "&#x1F952;",
            ":carrot:" => "&#x1F955;",
            ":hot_pepper:" => "&#x1F336;",
            ":potato:" => "&#x1F954;",
            ":corn:" => "&#x1F33D;",
            ":leafy_green:" => "&#x1F96C;",
            ":sweet_potato:" => "&#x1F360;",
            ":peanuts:" => "&#x1F95C;",
            ":honey_pot:" => "&#x1F36F;",
            ":croissant:" => "&#x1F950;",
            ":bread:" => "&#x1F35E;",
            ":baguette_bread:" => "&#x1F956;",
            ":bagel:" => "&#x1F96F;",
            ":pretzel:" => "&#x1F968;",
            ":cheese:" => "&#x1F9C0;",
            ":egg:" => "&#x1F95A;",
            ":bacon:" => "&#x1F953;",
            ":steak:" => "&#x1F969;",
            ":pancakes:" => "&#x1F95E;",
            ":poultry_leg:" => "&#x1F357;",
            ":meat_on_bone:" => "&#x1F356;",
            ":bone:" => "&#x1F9B4;",
            ":fried_shrimp:" => "&#x1F364;",
            ":fried_egg:" => "&#x1F373;",
            ":hamburger:" => "&#x1F354;",
            ":fries:" => "&#x1F35F;",
            ":stuffed_flatbread:" => "&#x1F959;",
            ":hotdog:" => "&#x1F32D;",
            ":pizza:" => "&#x1F355;",
            ":sandwich:" => "&#x1F96A;",
            ":canned_food:" => "&#x1F96B;",
            ":spaghetti:" => "&#x1F35D;",
            ":taco:" => "&#x1F32E;",
            ":burrito:" => "&#x1F32F;",
            ":green_salad:" => "&#x1F957;",
            ":shallow_pan_of_food:" => "&#x1F958;",
            ":ramen:" => "&#x1F35C;",
            ":stew:" => "&#x1F372;",
            ":fish_cake:" => "&#x1F365;",
            ":sushi:" => "&#x1F363;",
            ":bento:" => "&#x1F371;",
            ":curry:" => "&#x1F35B;",
            ":rice_ball:" => "&#x1F359;",
            ":rice:" => "&#x1F35A;",
            ":rice_cracker:" => "&#x1F358;",
            ":oden:" => "&#x1F362;",
            ":dango:" => "&#x1F361;",
            ":shaved_ice:" => "&#x1F367;",
            ":ice_cream:" => "&#x1F368;",
            ":icecream:" => "&#x1F366;",
            ":cake:" => "&#x1F370;",
            ":birthday:" => "&#x1F382;",
            ":custard:" => "&#x1F36E;",
            ":lollipop:" => "&#x1F36D;",
            ":candy:" => "&#x1F36C;",
            ":chocolate_bar:" => "&#x1F36B;",
            ":popcorn:" => "&#x1F37F;",
            ":doughnut:" => "&#x1F369;",
            ":cookie:" => "&#x1F36A;",
            ":milk_glass:" => "&#x1F95B;",
            ":baby_bottle:" => "&#x1F37C;",
            ":coffee:" => "&#x2615;",
            ":tea:" => "&#x1F375;",
            ":sake:" => "&#x1F376;",
            ":champagne:" => "&#x1F37E;",
            ":wine_glass:" => "&#x1F377;",
            ":cocktail:" => "&#x1F378;",
            ":tropical_drink:" => "&#x1F379;",
            ":beer:" => "&#x1F37A;",
            ":beers:" => "&#x1F37B;",
            ":clinking_glasses:" => "&#x1F942;",
            ":tumbler_glass:" => "&#x1F943;",
            ":cup_with_straw:" => "&#x1F964;",
            ":bubble_tea:" => "&#x1F9CB;",
            ":beverage_box:" => "&#x1F9C3;",
            ":mate:" => "&#x1F9C9;",
            ":ice_cube:" => "&#x1F9CA;",
            ":chopsticks:" => "&#x1F962;",
            ":plate_with_cutlery:" => "&#x1F37D;",
            ":fork_and_knife:" => "&#x1F374;",
            ":spoon:" => "&#x1F944;",
            ":hocho:" => "&#x1F52A;",
            ":amphora:" => "&#x1F3FA;",":grinning:" => "&#x1F600;",
            ":grin:" => "&#x1F601;",
            ":joy:" => "&#x1F602;",
            ":smiley:" => "&#x1F603;",
            ":smile:" => "&#x1F604;",
            ":sweat_smile:" => "&#x1F605;",
            ":laughing:" => "&#x1F606;",
            ":innocent:" => "&#x1F607;",
            ":wink:" => "&#x1F609;",
            ":blush:" => "&#x1F60A;",
            ":slightly_smiling_face:" => "&#x1F642;",
            ":upside_down_face:" => "&#x1F643;",
            ":relaxed:" => "&#x263A;",
            ":heart_eyes:" => "&#x1F60D;",
            ":kissing_heart:" => "&#x1F618;",
            ":kissing:" => "&#x1F617;",
            ":kissing_smiling_eyes:" => "&#x1F619;",
            ":kissing_closed_eyes:" => "&#x1F61A;",
            ":yum:" => "&#x1F60B;",
            ":stuck_out_tongue:" => "&#x1F61B;",
            ":stuck_out_tongue_winking_eye:" => "&#x1F61C;",
            ":zany_face:" => "&#x1F92A;",
            ":face_with_raised_eyebrow:" => "&#x1F928;",
            ":face_with_monocle:" => "&#x1F9D0;",
            ":nerd_face:" => "&#x1F913;",
            ":sunglasses:" => "&#x1F60E;",
            ":star_struck:" => "&#x1F929;",
            ":partying_face:" => "&#x1F973;",
            ":smirk:" => "&#x1F60F;",
            ":unamused:" => "&#x1F612;",
            ":disappointed:" => "&#x1F61E;",
            ":pensive:" => "&#x1F614;",
            ":worried:" => "&#x1F61F;",
            ":confused:" => "&#x1F615;",
            ":slightly_frowning_face:" => "&#x1F641;",
            ":frowning_face:" => "&#x2639;",
            ":persevere:" => "&#x1F623;",
            ":confounded:" => "&#x1F616;",
            ":tired_face:" => "&#x1F62B;",
            ":weary:" => "&#x1F629;",
            ":cry:" => "&#x1F622;",
            ":sob:" => "&#x1F62D;",
            ":triumph:" => "&#x1F624;",
            ":angry:" => "&#x1F620;",
            ":rage:" => "&#x1F621;",
            ":sleepy:" => "&#x1F62A;",
            ":mask:" => "&#x1F637;",
            ":face_with_thermometer:" => "&#x1F912;",
            ":face_with_head_bandage:" => "&#x1F915;",
            ":nauseated_face:" => "&#x1F922;",
            ":face_vomiting:" => "&#x1F92E;",
            ":sneezing_face:" => "&#x1F927;",
            ":hot_face:" => "&#x1F975;",
            ":cold_face:" => "&#x1F976;",
            ":woozy_face:" => "&#x1F974;",
            ":dizzy_face:" => "&#x1F635;",
            ":exploding_head:" => "&#x1F92F;",
            ":cowboy_hat_face:" => "&#x1F920;",
            ":partly_sunny_face:" => "&#x26C5;",
            ":clown_face:" => "&#x1F921;",
            ":lying_face:" => "&#x1F925;",
            ":shushing_face:" => "&#x1F92B;",
            ":face_with_symbols_on_mouth:" => "&#x1F92C;",
            ":face_with_hand_over_mouth:" => "&#x1F92D;",
            ":serious_face_with_symbols_covering_mouth:" => "&#x1F92F;",
            ":hugging_face:" => "&#x1F917;",
            ":thinking_face:" => "&#x1F914;",
            ":zipper_mouth_face:" => "&#x1F910;",
            ":face_with_raised_eyebrow_tongue:" => "&#x1F928;&#x200D;&#x1F61D;",
            ":neutral_face:" => "&#x1F610;",
            ":expressionless:" => "&#x1F611;",
            ":no_mouth:" => "&#x1F636;",
            ":smiling_imp:" => "&#x1F608;",
            ":imp:" => "&#x1F47F;",
            ":skull:" => "&#x1F480;",
            ":skull_and_crossbones:" => "&#x2620;",
            ":hankey:" => "&#x1F4A9;",
            ":ghost:" => "&#x1F47B;",
            ":alien:" => "&#x1F47D;",
            ":space_invader:" => "&#x1F47E;",
            ":robot:" => "&#x1F916;",
            ":poop:" => "&#x1F4A9;",
            ":smiley_cat:" => "&#x1F63A;",
            ":smile_cat:" => "&#x1F638;",
            ":joy_cat:" => "&#x1F639;",
            ":heart_eyes_cat:" => "&#x1F63B;",
            ":smirk_cat:" => "&#x1F63C;",
            ":kissing_cat:" => "&#x1F63D;",
            ":scream_cat:" => "&#x1F640;",
            ":crying_cat_face:" => "&#x1F63F;",
            ":pouting_cat:" => "&#x1F63E;",
            ":palms_up_together:" => "&#x1F932;",
            ":open_hands:" => "&#x1F450;",
            ":raised_hands:" => "&#x1F64C;",
            ":clap:" => "&#x1F44F;",
            ":handshake:" => "&#x1F91D;",
            ":thumbsup:" => "&#x1F44D;",
            ":thumbsdown:" => "&#x1F44E;",
            ":punch:" => "&#x1F44A;",
            ":fist:" => "&#x270A;",
            ":left_facing_fist:" => "&#x1F91B;",
            ":right_facing_fist:" => "&#x1F91C;",
            ":fingers_crossed:" => "&#x1F91E;",
            ":v:" => "&#x270C;",
            ":ok_hand:" => "&#x1F44C;",
            ":raised_hand:" => "&#x270B;",
            ":muscle:" => "&#x1F4AA;",
            ":pray:" => "&#x1F64F;",
            ":foot:" => "&#x1F9B6;",
            ":leg:" => "&#x1F9B5;",
            ":mechanical_arm:" => "&#x1F9BE;",
            ":mechanical_leg:" => "&#x1F9BF;",
            ":handshake_hidden:" => "&#x1F91A;",
            ":writing_hand:" => "&#x270D;",
            ":nail_care:" => "&#x1F485;",
            ":lips:" => "&#x1F444;",
            ":tongue:" => "&#x1F445;",
            ":ear:" => "&#x1F442;",
            ":nose:" => "&#x1F443;",
            ":eye:" => "&#x1F441;",
            ":eyes:" => "&#x1F440;",
            ":brain:" => "&#x1F9E0;",
            ":bust_in_silhouette:" => "&#x1F464;",
            ":busts_in_silhouette:" => "&#x1F465;",
            ":speaking_head:" => "&#x1F5E3;",
            ":baby:" => "&#x1F476;",
            ":child:" => "&#x1F9D2;",
            ":boy:" => "&#x1F466;",
            ":girl:" => "&#x1F467;",
            ":adult:" => "&#x1F9D1;",
            ":man:" => "&#x1F468;",
            ":woman:" => "&#x1F469;",
            ":blonde_woman:" => "&#x1F471;",
            ":blonde_man:" => "&#x1F471;&zwj;&#x2642;&#xFE0F;",
            ":bearded_person:" => "&#x1F9D4;",
            ":older_adult:" => "&#x1F9D3;",
            ":older_man:" => "&#x1F474;",
            ":older_woman:" => "&#x1F475;",
            ":man_with_gua_pi_mao:" => "&#x1F472;",
            ":woman_with_headscarf:" => "&#x1F9D5;",
            ":woman_with_turban:" => "&#x1F473;",
            ":man_with_turban:" => "&#x1F473;",
            ":policewoman:" => "&#x1F46E;",
            ":policeman:" => "&#x1F46E;",
            ":construction_worker_woman:" => "&#x1F477;",
            ":construction_worker_man:" => "&#x1F477;",
            ":guardswoman:" => "&#x1F482;",
            ":guardsman:" => "&#x1F482;",
            ":female_detective:" => "&#x1F575;",
            ":male_detective:" => "&#x1F575;&zwj;&#x2642;&#xFE0F;",
            ":woman_health_worker:" => "&#x1F469;&#x200D;&#x2695;&#xFE0F;",
            ":man_health_worker:" => "&#x1F468;&#x200D;&#x2695;&#xFE0F;",
            ":woman_farmer:" => "&#x1F469;&#x200D;&#x1F33E;",
            ":man_farmer:" => "&#x1F468;&#x200D;&#x1F33E;",
            ":woman_cook:" => "&#x1F469;&#x200D;&#x1F373;",
            ":man_cook:" => "&#x1F468;&#x200D;&#x1F373;",
            ":woman_student:" => "&#x1F469;&#x200D;&#x1F393;",
            ":man_student:" => "&#x1F468;&#x200D;&#x1F393;",
            ":woman_singer:" => "&#x1F469;&#x200D;&#x1F3A4;",
            ":man_singer:" => "&#x1F468;&#x200D;&#x1F3A4;",
            ":woman_teacher:" => "&#x1F469;&#x200D;&#x1F3EB;",
            ":man_teacher:" => "&#x1F468;&#x200D;&#x1F3EB;",
            ":woman_factory_worker:" => "&#x1F469;&#x200D;&#x1F3ED;",
            ":man_factory_worker:" => "&#x1F468;&#x200D;&#x1F3ED;",
            ":woman_technologist:" => "&#x1F469;&#x200D;&#x1F4BB;",
            ":man_technologist:" => "&#x1F468;&#x200D;&#x1F4BB;",
            ":woman_office_worker:" => "&#x1F469;&#x200D;&#x1F4BC;",
            ":man_office_worker:" => "&#x1F468;&#x200D;&#x1F4BC;",
            ":woman_mechanic:" => "&#x1F469;&#x200D;&#x1F527;",
            ":man_mechanic:" => "&#x1F468;&#x200D;&#x1F527;",
            ":woman_scientist:" => "&#x1F469;&#x200D;&#x1F52C;",
            ":man_scientist:" => "&#x1F468;&#x200D;&#x1F52C;",
            ":woman_artist:" => "&#x1F469;&#x200D;&#x1F3A8;",
            ":man_artist:" => "&#x1F468;&#x200D;&#x1F3A8;",
            ":woman_firefighter:" => "&#x1F469;&#x200D;&#x1F692;",
            ":man_firefighter:" => "&#x1F468;&#x200D;&#x1F692;",
            ":woman_pilot:" => "&#x1F469;&#x200D;&#x2708;&#xFE0F;",
            ":man_pilot:" => "&#x1F468;&#x200D;&#x2708;&#xFE0F;",
            ":woman_astronaut:" => "&#x1F469;&#x200D;&#x1F680;",
            ":man_astronaut:" => "&#x1F468;&#x200D;&#x1F680;",
            ":woman_judge:" => "&#x1F469;&#x200D;&#x2696;&#xFE0F;",
            ":man_judge:" => "&#x1F468;&#x200D;&#x2696;&#xFE0F;",
            ":woman_superhero:" => "&#x1F9B8;&#x200D;&#x2640;&#xFE0F;",
            ":man_superhero:" => "&#x1F9B8;&#x200D;&#x2642;&#xFE0F;",
            ":woman_supervillain:" => "&#x1F9B9;&#x200D;&#x2640;&#xFE0F;",
            ":man_supervillain:" => "&#x1F9B9;&#x200D;&#x2642;&#xFE0F;",
            ":woman_skeleton:" => "&#x1F480;&zwj;&#x2640;&#xFE0F;",
            ":man_skeleton:" => "&#x1F480;&zwj;&#x2642;&#xFE0F;",
            ":woman_elf:" => "&#x1F9DD;&#x200D;&#x2640;&#xFE0F;",
            ":man_elf:" => "&#x1F9DD;&#x200D;&#x2642;&#xFE0F;",
            ":woman_vampire:" => "&#x1F9DB;&#x200D;&#x2640;&#xFE0F;",
            ":man_vampire:" => "&#x1F9DB;&#x200D;&#x2642;&#xFE0F;",
            ":woman_zombie:" => "&#x1F9DF;&#x200D;&#x2640;&#xFE0F;",
            ":man_zombie:" => "&#x1F9DF;&#x200D;&#x2642;&#xFE0F;",
            ":woman_genie:" => "&#x1F9DE;&#x200D;&#x2640;&#xFE0F;",
            ":man_genie:" => "&#x1F9DE;&#x200D;&#x2642;&#xFE0F;",
            ":mermaid:" => "&#x1F9DC;&#x200D;&#x2640;&#xFE0F;",
            ":merman:" => "&#x1F9DC;&#x200D;&#x2642;&#xFE0F;",
            ":woman_fairy:" => "&#x1F9DA;&#x200D;&#x2640;&#xFE0F;",
            ":man_fairy:" => "&#x1F9DA;&#x200D;&#x2642;&#xFE0F;",
            ":angel:" => "&#x1F47C;",
            ":pregnant_woman:" => "&#x1F930;",
            ":breastfeeding:" => "&#x1F931;",
            ":princess:" => "&#x1F478;",
            ":prince:" => "&#x1F934;",
            ":bride_with_veil:" => "&#x1F470;",
            ":man_in_tuxedo:" => "&#x1F935;",
            ":running_woman:" => "&#x1F3C3;",
            ":running_man:" => "&#x1F3C3;&#x200D;&#x2642;&#xFE0F;",
            ":walking_woman:" => "&#x1F6B6;",
            ":walking_man:" => "&#x1F6B6;&#x200D;&#x2642;&#xFE0F;",
            ":dancer:" => "&#x1F483;",
            ":man_dancing:" => "&#x1F57A;",
            ":dancing_women:" => "&#x1F46F;",
            ":dancing_men:" => "&#x1F46F;&#x200D;&#x2642;&#xFE0F;",
            ":couple:" => "&#x1F46B;",
            ":two_women_holding_hands:" => "&#x1F46D;",
            ":two_men_holding_hands:" => "&#x1F46C;",
            ":couple_with_heart_woman_man:" => "&#x1F491;",
            ":couple_with_heart_woman_woman:" => "&#x1F469;&zwj;&#x2764;&#xFE0F;&zwj;&#x1F469;",
            ":couple_with_heart_man_man:" => "&#x1F468;&zwj;&#x2764;&#xFE0F;&zwj;&#x1F468;",
            ":couplekiss_man_woman:" => "&#x1F48F;",
            ":couplekiss_woman_woman:" => "&#x1F469;&zwj;&#x2764;&#xFE0F;&zwj;&#x1F48B;&zwj;&#x1F469;",
            ":couplekiss_man_man:" => "&#x1F468;&zwj;&#x2764;&#xFE0F;&zwj;&#x1F48B;&zwj;&#x1F468;",
            ":family_man_woman_boy:" => "&#x1F46A;",
            ":family_man_woman_girl:" => "&#x1F468;&#x200D;&#x1F469;&#x200D;&#x1F467;",
            ":family_man_woman_girl_boy:" => "&#x1F468;&#x200D;&#x1F469;&#x200D;&#x1F467;&#x200D;&#x1F466;",
            ":family_man_woman_boy_boy:" => "&#x1F468;&#x200D;&#x1F469;&#x200D;&#x1F466;&#x200D;&#x1F466;",
            ":family_man_woman_girl_girl:" => "&#x1F468;&#x200D;&#x1F469;&#x200D;&#x1F467;&#x200D;&#x1F467;",
            ":family_woman_woman_boy:" => "&#x1F469;&#x200D;&#x1F469;&#x200D;&#x1F466;",
            ":family_woman_woman_girl:" => "&#x1F469;&#x200D;&#x1F469;&#x200D;&#x1F467;",
            ":family_woman_woman_girl_boy:" => "&#x1F469;&#x200D;&#x1F469;&#x200D;&#x1F467;&#x200D;&#x1F466;",
            ":family_woman_woman_boy_boy:" => "&#x1F469;&#x200D;&#x1F469;&#x200D;&#x1F466;&#x200D;&#x1F466;",
            ":family_woman_woman_girl_girl:" => "&#x1F469;&#x200D;&#x1F469;&#x200D;&#x1F467;&#x200D;&#x1F467;",
            ":family_man_man_boy:" => "&#x1F468;&#x200D;&#x1F468;&#x200D;&#x1F466;",
            ":family_man_man_girl:" => "&#x1F468;&#x200D;&#x1F468;&#x200D;&#x1F467;",
            ":family_man_man_girl_boy:" => "&#x1F468;&#x200D;&#x1F468;&#x200D;&#x1F467;&#x200D;&#x1F466;",
            ":family_man_man_boy_boy:" => "&#x1F468;&#x200D;&#x1F468;&#x200D;&#x1F466;&#x200D;&#x1F466;",
            ":family_man_man_girl_girl:" => "&#x1F468;&#x200D;&#x1F468;&#x200D;&#x1F467;&#x200D;&#x1F467;",
            ":family_woman_boy:" => "&#x1F469;&#x200D;&#x1F466;",
            ":family_woman_girl:" => "&#x1F469;&#x200D;&#x1F467;",
            ":family_woman_girl_boy:" => "&#x1F469;&#x200D;&#x1F467;&#x200D;&#x1F466;",
            ":family_woman_boy_boy:" => "&#x1F469;&#x200D;&#x1F466;&#x200D;&#x1F466;",
            ":family_woman_girl_girl:" => "&#x1F469;&#x200D;&#x1F467;&#x200D;&#x1F467;",
            ":family_man_boy:" => "&#x1F468;&#x200D;&#x1F466;",
            ":family_man_girl:" => "&#x1F468;&#x200D;&#x1F467;",
            ":family_man_girl_boy:" => "&#x1F468;&#x200D;&#x1F467;&#x200D;&#x1F466;",
            ":family_man_boy_boy:" => "&#x1F468;&#x200D;&#x1F466;&#x200D;&#x1F466;",
            ":family_man_girl_girl:" => "&#x1F468;&#x200D;&#x1F467;&#x200D;&#x1F467;",
            ":yarn:" => "&#x1F9F6;",
            ":thread:" => "&#x1F9F5;",
            ":coat:" => "&#x1F9E5;",
            ":lab_coat:" => "&#x1F97C;",
            ":safety_vest:" => "&#x1F9BA;",
            ":womans_clothes:" => "&#x1F45A;",
            ":tshirt:" => "&#x1F455;",
            ":jeans:" => "&#x1F456;",
            ":necktie:" => "&#x1F454;",
            ":dress:" => "&#x1F457;",
            ":bikini:" => "&#x1F459;",
            ":kimono:" => "&#x1F458;",
            ":lipstick:" => "&#x1F484;",
            ":kiss:" => "&#x1F48B;",
            ":footprints:" => "&#x1F463;",
            ":flat_shoe:" => "&#x1F97F;",
            ":high_heel:" => "&#x1F460;",
            ":sandal:" => "&#x1F461;",
            ":boot:" => "&#x1F462;",
            ":mans_shoe:" => "&#x1F45E;",
            ":athletic_shoe:" => "&#x1F45F;",
            ":hiking_boot:" => "&#x1F97E;",
            ":socks:" => "&#x1F9E6;",
            ":gloves:" => "&#x1F9E4;",
            ":scarf:" => "&#x1F9E3;",
            ":tophat:" => "&#x1F3A9;",
            ":billed_cap:" => "&#x1F9E2;",
            ":womans_hat:" => "&#x1F452;",
            ":mortar_board:" => "&#x1F393;",
            ":helmet_with_white_cross:" => "&#x26D1;",
            ":crown:" => "&#x1F451;",
            ":ring:" => "&#x1F48D;",
            ":pouch:" => "&#x1F45D;",
            ":purse:" => "&#x1F45B;",
            ":handbag:" => "&#x1F45C;",
            ":briefcase:" => "&#x1F4BC;",
            ":school_satchel:" => "&#x1F392;",
            ":luggage:" => "&#x1F9F3;",
            ":eyeglasses:" => "&#x1F453;",
            ":dark_sunglasses:" => "&#x1F576;",
            ":goggles:" => "&#x1F97D;",
            ":closed_umbrella:" => "&#x1F302;",
            ":dog:" => "&#x1F436;",
            ":cat:" => "&#x1F431;",
            ":mouse:" => "&#x1F42D;",
            ":hamster:" => "&#x1F439;",
            ":rabbit:" => "&#x1F430;",
            ":fox_face:" => "&#x1F98A;",
            ":bear:" => "&#x1F43B;",
            ":panda_face:" => "&#x1F43C;",
            ":koala:" => "&#x1F428;",
            ":tiger:" => "&#x1F42F;",
            ":lion:" => "&#x1F981;",
            ":cow:" => "&#x1F42E;",
            ":pig:" => "&#x1F437;",
            ":pig_nose:" => "&#x1F43D;",
            ":frog:" => "&#x1F438;",
            ":monkey_face:" => "&#x1F435;",
            ":see_no_evil:" => "&#x1F648;",
            ":hear_no_evil:" => "&#x1F649;",
            ":speak_no_evil:" => "&#x1F64A;",
            ":monkey:" => "&#x1F412;",
            ":chicken:" => "&#x1F414;",
            ":penguin:" => "&#x1F427;",
            ":bird:" => "&#x1F426;",
            ":baby_chick:" => "&#x1F424;",
            ":hatching_chick:" => "&#x1F423;",
            ":hatched_chick:" => "&#x1F425;",
            ":duck:" => "&#x1F986;",
            ":eagle:" => "&#x1F985;",
            ":owl:" => "&#x1F989;",
            ":bat:" => "&#x1F987;",
            ":wolf:" => "&#x1F43A;",
            ":boar:" => "&#x1F417;",
            ":horse:" => "&#x1F434;",
            ":unicorn_face:" => "&#x1F984;",
            ":honeybee:" => "&#x1F41D;",
            ":bug:" => "&#x1F41B;",
            ":butterfly:" => "&#x1F98B;",
            ":snail:" => "&#x1F40C;",
            ":beetle:" => "&#x1F41E;",
            ":ant:" => "&#x1F41C;",
            ":spider:" => "&#x1F577;",
            ":scorpion:" => "&#x1F982;",
            ":crab:" => "&#x1F980;",
            ":snake:" => "&#x1F40D;",
            ":lizard:" => "&#x1F98E;",
            ":t-rex:" => "&#x1F996;",
            ":sauropod:" => "&#x1F995;",
            ":turtle:" => "&#x1F422;",
            ":tropical_fish:" => "&#x1F420;",
            ":fish:" => "&#x1F41F;",
            ":blowfish:" => "&#x1F421;",
            ":dolphin:" => "&#x1F42C;",
            ":shark:" => "&#x1F988;",
            ":whale:" => "&#x1F433;",
            ":whale2:" => "&#x1F40B;",
            ":crocodile:" => "&#x1F40A;",
            ":leopard:" => "&#x1F406;",
            ":tiger2:" => "&#x1F405;",
            ":water_buffalo:" => "&#x1F403;",
            ":ox:" => "&#x1F402;",
            ":cow2:" => "&#x1F404;",
            ":deer:" => "&#x1F98C;",
            ":dromedary_camel:" => "&#x1F42A;",
            ":camel:" => "&#x1F42B;",
            ":elephant:" => "&#x1F418;",
            ":rhinoceros:" => "&#x1F98F;",
            ":gorilla:" => "&#x1F98D;",
            ":racehorse:" => "&#x1F40E;",
            ":pig2:" => "&#x1F416;",
            ":goat:" => "&#x1F410;",
            ":ram:" => "&#x1F40F;",
            ":sheep:" => "&#x1F411;",
            ":dog2:" => "&#x1F415;",
            ":poodle:" => "&#x1F429;",
            ":cat2:" => "&#x1F408;",
            ":rooster:" => "&#x1F413;",
            ":turkey:" => "&#x1F983;",
            ":dove:" => "&#x1F54A;",
            ":rabbit2:" => "&#x1F407;",
            ":mouse2:" => "&#x1F401;",
            ":rat:" => "&#x1F400;",
            ":chipmunk:" => "&#x1F43F;",
            ":feet:" => "&#x1F43E;",
            ":paw_prints:" => "&#x1F43E;",
            ":dragon:" => "&#x1F409;",
            ":dragon_face:" => "&#x1F432;",
            ":cactus:" => "&#x1F335;",
            ":christmas_tree:" => "&#x1F384;",
            ":evergreen_tree:" => "&#x1F332;",
            ":deciduous_tree:" => "&#x1F333;",
            ":palm_tree:" => "&#x1F334;",
            ":seedling:" => "&#x1F331;",
            ":herb:" => "&#x1F33F;",
            ":shamrock:" => "&#x2618;",
            ":four_leaf_clover:" => "&#x1F340;",
            ":bamboo:" => "&#x1F38D;",
            ":tanabata_tree:" => "&#x1F38B;",
            ":leaves:" => "&#x1F343;",
            ":fallen_leaf:" => "&#x1F342;",
            ":maple_leaf:" => "&#x1F341;",
            ":ear_of_rice:" => "&#x1F33E;",
            ":hibiscus:" => "&#x1F33A;",
            ":sunflower:" => "&#x1F33B;",
            ":rose:" => "&#x1F339;",
            ":wilted_flower:" => "&#x1F940;",
            ":tulip:" => "&#x1F337;",
            ":blossom:" => "&#x1F33C;",
            ":cherry_blossom:" => "&#x1F338;",
            ":bouquet:" => "&#x1F490;",
            ":mushroom:" => "&#x1F344;",
            ":chestnut:" => "&#x1F330;",
            ":jack_o_lantern:" => "&#x1F383;",
            ":shell:" => "&#x1F41A;",
            ":spider_web:" => "&#x1F578;",
            ":earth_americas:" => "&#x1F30E;",
            ":earth_africa:" => "&#x1F30D;",
            ":earth_asia:" => "&#x1F30F;",
            ":full_moon:" => "&#x1F315;",
            ":waning_gibbous_moon:" => "&#x1F316;",
            ":last_quarter_moon:" => "&#x1F317;",
            ":waning_crescent_moon:" => "&#x1F318;",
            ":new_moon:" => "&#x1F311;",
            ":waxing_crescent_moon:" => "&#x1F312;",
            ":first_quarter_moon:" => "&#x1F313;",
            ":waxing_gibbous_moon:" => "&#x1F314;",
            ":new_moon_with_face:" => "&#x1F31A;",
            ":full_moon_with_face:" => "&#x1F31D;",
            ":first_quarter_moon_with_face:" => "&#x1F31B;",
            ":last_quarter_moon_with_face:" => "&#x1F31C;",
            ":sun_with_face:" => "&#x1F31E;",
            ":crescent_moon:" => "&#x1F319;",
            ":star:" => "&#x2B50;",
            ":star2:" => "&#x1F31F;",
            ":dizzy:" => "&#x1F4AB;",
            ":sparkles:" => "&#x2728;",
            ":comet:" => "&#x2604;",
            ":sunny:" => "&#x2600;",
            ":sun_behind_small_cloud:" => "&#x1F324;",
            ":partly_sunny:" => "&#x26C5;",
            ":sun_behind_large_cloud:" => "&#x1F325;",
            ":sun_behind_rain_cloud:" => "&#x1F326;",
            ":cloud:" => "&#x2601;",
            ":cloud_with_rain:" => "&#x1F327;",
            ":cloud_with_lightning_and_rain:" => "&#x26C8;",
            ":cloud_with_lightning:" => "&#x1F329;",
            ":zap:" => "&#x26A1;",
            ":fire:" => "&#x1F525;",
            ":boom:" => "&#x1F4A5;",
            ":snowflake:" => "&#x2744;",
            ":cloud_with_snow:" => "&#x1F328;",
            ":snowman:" => "&#x26C4;",
            ":snowman_without_snow:" => "&#x26C4;&#xFE0F;",
            ":wind_face:" => "&#x1F32C;",
            ":dash:" => "&#x1F4A8;",
            ":tornado:" => "&#x1F32A;",
            ":fog:" => "&#x1F32B;",
            ":open_umbrella:" => "&#x2602;",
            ":umbrella:" => "&#x2602;",
            ":droplet:" => "&#x1F4A7;",
            ":sweat_drops:" => "&#x1F4A6;",
            ":ocean:" => "&#x1F30A;",
            ":green_apple:" => "&#x1F34F;",
            ":apple:" => "&#x1F34E;",
            ":pear:" => "&#x1F350;",
            ":tangerine:" => "&#x1F34A;",
            ":lemon:" => "&#x1F34B;",
            ":banana:" => "&#x1F34C;",
            ":watermelon:" => "&#x1F349;",
            ":grapes:" => "&#x1F347;",
            ":strawberry:" => "&#x1F353;",
            ":melon:" => "&#x1F348;",
            ":cherries:" => "&#x1F352;",
            ":peach:" => "&#x1F351;",
            ":pineapple:" => "&#x1F34D;",
            ":coconut:" => "&#x1F965;",
            ":kiwi_fruit:" => "&#x1F95D;",
            ":mango:" => "&#x1F96D;",
            ":avocado:" => "&#x1F951;",
            ":broccoli:" => "&#x1F966;",
            ":tomato:" => "&#x1F345;",
            ":eggplant:" => "&#x1F346;",
            ":cucumber:" => "&#x1F952;",
            ":carrot:" => "&#x1F955;",
            ":hot_pepper:" => "&#x1F336;",
            ":potato:" => "&#x1F954;",
            ":corn:" => "&#x1F33D;",
            ":leafy_green:" => "&#x1F96C;",
            ":sweet_potato:" => "&#x1F360;",
            ":peanuts:" => "&#x1F95C;",
            ":honey_pot:" => "&#x1F36F;",
            ":croissant:" => "&#x1F950;",
            ":bread:" => "&#x1F35E;",
            ":baguette_bread:" => "&#x1F956;",
            ":bagel:" => "&#x1F96F;",
            ":pretzel:" => "&#x1F968;",
            ":cheese:" => "&#x1F9C0;",
            ":egg:" => "&#x1F95A;",
            ":bacon:" => "&#x1F953;",
            ":steak:" => "&#x1F969;",
            ":pancakes:" => "&#x1F95E;",
            ":poultry_leg:" => "&#x1F357;",
            ":meat_on_bone:" => "&#x1F356;",
            ":bone:" => "&#x1F9B4;",
            ":fried_shrimp:" => "&#x1F364;",
            ":fried_egg:" => "&#x1F373;",
            ":hamburger:" => "&#x1F354;",
            ":fries:" => "&#x1F35F;",
            ":stuffed_flatbread:" => "&#x1F959;",
            ":hotdog:" => "&#x1F32D;",
            ":pizza:" => "&#x1F355;",
            ":sandwich:" => "&#x1F96A;",
            ":canned_food:" => "&#x1F96B;",
            ":spaghetti:" => "&#x1F35D;",
            ":taco:" => "&#x1F32E;",
            ":burrito:" => "&#x1F32F;",
            ":green_salad:" => "&#x1F957;",
            ":shallow_pan_of_food:" => "&#x1F958;",
            ":ramen:" => "&#x1F35C;",
            ":stew:" => "&#x1F372;",
            ":fish_cake:" => "&#x1F365;",
            ":sushi:" => "&#x1F363;",
            ":bento:" => "&#x1F371;",
            ":curry:" => "&#x1F35B;",
            ":rice_ball:" => "&#x1F359;",
            ":rice:" => "&#x1F35A;",
            ":rice_cracker:" => "&#x1F358;",
            ":oden:" => "&#x1F362;",
            ":dango:" => "&#x1F361;",
            ":shaved_ice:" => "&#x1F367;",
            ":ice_cream:" => "&#x1F368;",
            ":icecream:" => "&#x1F366;",
            ":cake:" => "&#x1F370;",
            ":birthday:" => "&#x1F382;",
            ":custard:" => "&#x1F36E;",
            ":lollipop:" => "&#x1F36D;",
            ":candy:" => "&#x1F36C;",
            ":chocolate_bar:" => "&#x1F36B;",
            ":popcorn:" => "&#x1F37F;",
            ":doughnut:" => "&#x1F369;",
            ":cookie:" => "&#x1F36A;",
            ":milk_glass:" => "&#x1F95B;",
            ":baby_bottle:" => "&#x1F37C;",
            ":coffee:" => "&#x2615;",
            ":tea:" => "&#x1F375;",
            ":sake:" => "&#x1F376;",
            ":champagne:" => "&#x1F37E;",
            ":wine_glass:" => "&#x1F377;",
            ":cocktail:" => "&#x1F378;",
            ":tropical_drink:" => "&#x1F379;",
            ":beer:" => "&#x1F37A;",
            ":beers:" => "&#x1F37B;",
            ":clinking_glasses:" => "&#x1F942;",
            ":tumbler_glass:" => "&#x1F943;",
            ":cup_with_straw:" => "&#x1F964;",
            ":bubble_tea:" => "&#x1F9CB;",
            ":beverage_box:" => "&#x1F9C3;",
            ":mate:" => "&#x1F9C9;",
            ":ice_cube:" => "&#x1F9CA;",
            ":chopsticks:" => "&#x1F962;",
            ":plate_with_cutlery:" => "&#x1F37D;",
            ":fork_and_knife:" => "&#x1F374;",
            ":spoon:" => "&#x1F944;",
            ":hocho:" => "&#x1F52A;",
            ":amphora:" => "&#x1F3FA;",
                // Add more emojis as needed
        ];

        foreach ($emojiMap as $emojiCode => $emojiHtml) {
            $text = str_replace($emojiCode, "<span class=\"emoji\">$emojiHtml</span>", $text);
        }

        return $text;
    }
}
