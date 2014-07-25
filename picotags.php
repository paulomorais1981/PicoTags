<?php

/**
 * Picotags
 *
 * Adds page tagging functionality to Pico.
 *
 * @author Dan Reeves
 * @link https://github.com/DanReeves/picotags/
 * @license http://danreeves.mit-license.org/
 */
class Picotags {

    public $is_tag;
    public $current_tag;

    public function request_url(&$url)
    {
        // Set is_tag to true if the first four characters of the URL are 'tag/'
        $this->is_tag = (substr($url, 0, 4) === 'tag/');
        // If the URL does start with 'tag/', grab the rest of the URL
        if ($this->is_tag) $this->current_tag = substr($url, 4);
    }

    public function before_read_file_meta(&$headers)
    {
        // Define tags variable
        $headers['tags'] = 'Tags';
    }

    public function file_meta(&$meta)
    {
        // Parses meta.tags to ensure it is an array
        if (isset($meta['tags']) && !is_array($meta['tags']) && $meta['tags'] !== '') {
            $meta['tags'] = explode(',', $meta['tags']);
        }
    }

    public function get_page_data(&$data, $page_meta)
    {
        // If tags in page_meta isn't empty
        if ($page_meta['tags'] != '') {
            // Add tags to page in pages
            $data['tags'] = explode(',', $page_meta['tags']);
        }
    }

    public function get_pages(&$pages, &$current_page, &$prev_page, &$next_page)
    {
        // If the URL starts with 'tag/' do this different logic
        if ($this->is_tag === true) {
            // Init $new_pages and $tag_list arrays
            $new_pages = array();
            $tag_list = array();
            $tag_list_sorted = array();
            // Loop through the pages
            foreach ($pages as $page) {
                // If the page has tags
                if ($page['tags'] and $page['template'] != 'category') {
                    if (!is_array($page['tags'])) {
                        $page['tags'] = explode(',', $page['tags']);
                    }
                    // Loop through the tags
                    foreach ($page['tags'] as $tag) {
                        // And add them to the tag_list array
                        $tag_list[] = $tag;
                        // If the tag matches the current_tag
                        if ($tag === $this->current_tag) {
                            // Add that page to the new_pages
                            $new_pages[] = $page;
                        }
                    }
                }
            }
            /* Sort alphabetically, case insensitive */
            natcasesort($tag_list);
            foreach ($tag_list as $key => $value) {
                $tag_list_sorted[] = $value;
            }
            // Add the tag list to the class scope, taking out duplicate or empty values
            $this->tag_list = array_unique(array_filter($tag_list));
            $this->tag_list_sorted = array_unique(array_filter($tag_list_sorted));
            // Overwrite $pages with $new_pages
            $pages = $new_pages;
        } else { // Workaround
            $new_pages = array();
            foreach ($pages as $page) {
                if (!is_array($page['tags'])) {
                    $page['tags'] = explode(',', $page['tags']);
                }
                $new_pages[] = $page;
            }
            $pages = $new_pages;
        }
    }

    public function before_render(&$twig_vars, &$twig)
    {
        if ($this->is_tag) {
            // Override 404 header
            header($_SERVER['SERVER_PROTOCOL'].' 200 OK');
            // Set page title to #TAG
            $twig_vars['meta']['title'] = "#" . $this->current_tag;
            // Return current tag and list of all tags as Twig vars
            $twig_vars['current_tag'] = $this->current_tag; /* {{ current_tag }} is a string*/
            $twig_vars['tag_list'] = $this->tag_list; /* {{ tag_list }} in an array*/
            /* For a tag list alphabetically sorted */
            $twig_vars['tag_list_sorted'] = $this->tag_list_sorted; /* {{ tag_list }} in an array*/

            /* 
                MULTICOLUMNS OUTPUT
                Change the value of $nbcol.
                In your template, for a two columns output : 
                <ul>
                    {% for tag in tag_list_0 %} OR {% for tag in tag_list_sorted_0 %}
                        <li><a href="/tag/{{ tag }}">#{{ tag }}</a></li>
                    {% endfor %}
                </ul>
                <ul>
                    {% for tag in tag_list_1 %} OR {% for tag in tag_list_sorted_1 %}
                        <li><a href="/tag/{{ tag }}">#{{ tag }}</a></li>
                    {% endfor %}
                </ul>
            */
            $nbcol = 5;
            $nbtags = sizeof($this->tag_list);
            $nbtagscol = ceil ($nbtags/$nbcol);
            $tag_list_cut = array();
            $tag_list_sorted_cut = array();
            for ($i=0;$i<$nbcol;$i++)
            {
                $this->tag_list_cut = array_slice($this->tag_list, $i*$nbtagscol, $nbtagscol);
                $twig_vars['tag_list_'.$i] = $this->tag_list_cut;
                $this->tag_list_sorted_cut = array_slice($this->tag_list, $i*$nbtagscol, $nbtagscol);
                $twig_vars['tag_list_sorted_'.$i] = $this->tag_list_sorted_cut;
            }
        }
    }

}
