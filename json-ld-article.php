<?php 
/*
Plugin Name:    JSON-LD for Article
Description:    JSON-LD for Article is simply the easiest solution to add valid
                schema.org microdata as a JSON-LD script to your blog posts or articles.
Version:        0.1
Author:         Mikko Piippo, Tomi Lattu
Plugin URI:     http://pluginland.com


JSON-LD is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

JSON-LD for Aricle is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with JSON-LD for Aricle; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

/**
 * @author Mikko Piippo, Tomi Lattu
 * @since 0.1
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2 or later
 */


/**
 * Echoes Markup to your footer.
 * @author Mikko Piippo, Tomi Lattu
 * @since 0.1
 */
function add_markup() {

    //
    // Get the data needed for building the JSON-LD
    //
    if (is_single()) {
        $articletitle=get_the_title();
        $articleauthor=get_the_author();
        $articlepublished=get_the_date('Y-n-j');
        $articlepublisher=get_bloginfo('name');
        $articleurl=get_permalink();
        $articlesection=get_the_category()[0]->cat_name;
        $articlemodified=get_the_modified_date();
        $articlecommentcount=get_comments_number();

        if (has_post_thumbnail()) {
            $thumbnailurl=wp_get_attachment_url(get_post_thumbnail_id());
        }

        $author = array(
            '@type' => 'Person',
            'name'  => $articleauthor);

        $pub = array (
            '@type' => 'Organization',
            'name'  => $articlepublisher);

        $arr = array(
            '@context' => 'http://schema.org',
            '@type'    => 'Article',
            'headline'     => $articletitle,
            'author'   => $author,
            'datePublished' => $articlepublished,
            'articleSection'  => $articlesection,
            'url'      => $articleurl,
            'image'  => $thumbnailurl,
            'publisher' =>$pub);

        echo '<script type="application/ld+json">'
            . json_encode($arr,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT)
            . '</script>';
    } //end if single

} // end function
add_action ('wp_footer','add_markup');

