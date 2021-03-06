<?php
/*
Plugin Name:    WP-JSONLD
Description:    WP-JSONLD adds valid schema.org microdata as JSON-LD-script to your blog.
Version:        0.3.1
Author:         Benjamin Marwell
Original Author:         Mikko Piippo, Tomi Lattu
Plugin URI:     https://github.com/bmhm/wp-jsonld/


WP-JSONLD is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

WP-JSONLD for Aricle is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with WP-JSONLD for Aricle; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

namespace bmarwell\wp_jsonld;

use bmarwell\wp_jsonld\Author;
use bmarwell\wp_jsonld\BlogPosting;
use bmarwell\wp_jsonld\ImageObject;
use bmarwell\wp_jsonld\Organization;

/**
 * @author Mikko Piippo, Tomi Lattu
 * @since 0.1
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2 or later
 */
class WPJsonLD {
    private $wpJsonLdTools = null;

    public function __construct($wpJsonLdTools = null) {
        $this->wpJsonLdTools = $wpJsonLdTools;
    }

    /**
     * createBlogPosting
     *
     * @return BlogPosting
     */
    public function createBlogPosting() {
        $blogpost = new BlogPosting();

        // Basic info
        $blogpost->headline = get_the_title();
        $blogpost->datePublished = get_the_date('Y-m-d H:i:s');
        $blogpost->url = get_permalink();
        $blogpost->setId(get_permalink());

        // Addition info
        $blogpost->articleSection = get_the_category()[0]->cat_name;
        $blogpost->dateModified = get_the_modified_date('Y-m-d H:i:s');
        $blogpost->commentCount = get_comments_number();

        // Thumbnail if exists

        return $blogpost;
    }

    /**
     * createAuthorEntity - create Author Markup
     */
    public function createAuthorEntity() {
        $author = new Author();

        $author->name = get_the_author_meta('display_name');
        $auId = get_the_author_meta( 'ID' );
        $author->url = get_author_posts_url($auId);
        $author->setId(get_author_posts_url($auId));
        $author->email = get_the_author_meta('user_email');

        return $author;
    }

    /**
     * createOrganization
     *
     * Creates an organization object for the blog.
     */
    public function createOrganization() {
        $toolclass = $this->wpJsonLdTools;
        $org = new Organization();

        $org->name = get_bloginfo('name');
        $org->legalName = get_bloginfo('name');
        $org->setId(network_site_url('/'));
        $org->url = network_site_url('/');
        $org->logo = $toolclass::findPublisherLogo();

        return $org;
    }

    /**
     * createImage
     *
     * Creates an image for the post thumbnail.
     */
    public function createImage() {
        $thId = get_post_thumbnail_id();
        $img = new ImageObject();

        if (has_post_thumbnail()) {
            $img->contentUrl = wp_get_attachment_url($thId);
            $img->image = wp_get_attachment_url($thId);
            $img->setId(get_attachment_link($thId));
            $img->url = wp_get_attachment_url($thId);

            $props = wp_get_attachment_metadata($thId);
            $img->width = $props['width'];
            $img->height = $props['height'];
            $img->caption = wp_prepare_attachment_for_js($thId)['caption'];
        }

        return $img;
    }

    /**
     * createMainEntity
     *
     * @param String $type
     * @param String $id
     */
    public function createMainEntity($type = 'Article', $identifier = null) {
        return array(
            "@type" => $type,
            "@id" => $identifier);
    }

    /**
     * createArticleEntity
     *
     * @return Article
     */
    public function createArticleEntity() {
        $article = new Article();

        // Basic info
        $article->headline = get_the_title();
        $article->datePublished = get_the_date('Y-m-d H:i:s');
        $article->url = get_permalink();
        $article->setId(get_permalink());

        // Addition info
        $article->articleSection = get_the_category()[0]->cat_name;
        $article->dateModified = get_the_modified_date('Y-m-d H:i:s');
        $article->commentCount = get_comments_number();

        // Thumbnail if exists

        return $article;
    }

    public function create_jsonld_page() {
        $markup = null;
        $markup = $this->createArticleEntity();
        $markup->addContext();
        $markup->author = $this->createAuthorEntity();
        $markup->publisher = $this->createOrganization();
        $markup->image = $this->createImage();

        $markup->mainEntityOfPage = $this->createMainEntity($markup->{'@type'}, $markup->{'@id'});

        // create rating if yasr is installed.
        if (function_exists("yasr_get_visitor_votes")) {
            $visitorVotes = yasr_get_visitor_votes();

            if ($visitorVotes) {
                $markup->aggregateRating = $this->createRating();
            }
        }

        // TODO: check for pagination

        $scriptcontents = json_encode($markup, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);

        return $scriptcontents;
    }

    public function create_jsonld_author() {
        $markup = $this->createAuthorEntity();
        $markup->addContext();

        $scriptcontents = json_encode($markup, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);

        return $scriptcontents;
    }

    /**
     * Create a rating object for AggregateRating.
     *
     * @since 0.3
     * */
    public function createRating($itemUrl = null) {
        $ratingMarkup = null;
        $visitorVotes = yasr_get_visitor_votes();

        /*
         * This function should not return null,
         * but to be safe, it is tested.
         * */
        if (empty($visitorVotes)) {
            return $ratingMarkup;
        }

        foreach ($visitorVotes as $rating) {
            $visitorRating['votes_number'] = $rating->number_of_votes;
            $visitorRating['sum'] = $rating->sum_votes;
        }

        /*
         * There needs to be something to calculate from.
         * I.e. at least one rating.
         * */
        if ($visitorRating['sum'] == 0 || $visitorRating['votes_number'] == 0) {
            return $ratingMarkup;
        }

        $averageRating = $visitorRating['sum'] / $visitorRating['votes_number'];
        $averageRating = round($averageRating, 1);

        $ratingMarkup = new AggregateRating();
        $ratingMarkup->ratingValue = $averageRating;
        $ratingMarkup->ratingCount = intval($visitorRating['votes_number']);

        // if there is no item url, just remove and return.
        if ($itemUrl === null) {
            unset($ratingMarkup->{'@id'});

            return $ratingMarkup;
        }

        $ratingMarkup->{'@id'} = $itemUrl;

        return $ratingMarkup;
    }

    public function create_jsonld_blogposting() {
        $markup = $this->createBlogPosting();
        $markup->addContext();
        $markup->author = $this->createAuthorEntity();
        $markup->publisher = $this->createOrganization();
        $markup->image = $this->createImage();

        $markup->mainEntityOfPage = $this->createMainEntity($markup->{'@type'}, $markup->{'@id'});

        // create rating if yasr is installed.
        if (function_exists("yasr_get_visitor_votes")) {
            $visitorVotes = yasr_get_visitor_votes();

            if ($visitorVotes) {
                $markup->aggregateRating = $this->createRating();
            }
        }

        $scriptcontents = json_encode($markup, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);

        return $scriptcontents;
    }

    /**
     * Echoes Markup to your footer.
     * @author Mikko Piippo, Tomi Lattu
     * @since 0.1
     */
    public function addMarkup() {
        // the text markup to be inserted.
        $markup = null;

        // Get the data needed for building the JSON-LD
        if (is_single()) {
            $postid = get_the_id();

            if ( false === ( $markup = get_transient( 'wpjsonld-article_' . $postid ) ) ) {
                $markup = $this->create_jsonld_blogposting();
                set_transient('wpjsonld-article_' . $postid, $markup, 0);
            }
        } elseif (is_page()) {
            // Outside the loop, get_the_id is not working that easily.
            $page = get_page_by_title($page_name);
            $pageid = get_the_id();

            if ( false === ( $markup = get_transient( 'wpjsonld-page_' . $pageid ) ) ) {
                $markup = $this->create_jsonld_page();
                set_transient('wpjsonld-page_' . $pageid, $markup, 0);
            }
        } elseif (is_author()) {
            $auId = get_the_author_meta( 'ID' );

            if ( false === ( $markup = get_transient( 'wpjsonld-author_' . $auId ) ) ) {
                $markup = $this->create_jsonld_author();
                set_transient('wpjsonld-author_' . $auId, $markup, 0);
            }
        }

        // if markup found, insert.
        if (null !== $markup) {
            echo '<script type="application/ld+json">'
                . $markup
                . '</script>';
        }
    } // end function
}
