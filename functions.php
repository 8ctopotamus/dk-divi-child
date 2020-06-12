<?php

function dk_divi_child_enqueue_styles() { 
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );

    wp_register_script( 'attorney_tabs', get_stylesheet_directory_uri() . '/js/attorney-tabs.js', '', '', true );
    wp_register_script( 'dk_homepage_flyouts', get_stylesheet_directory_uri() . '/js/homepage-flyouts.js', '', '', true );

    if (is_front_page()) {
        wp_enqueue_script( 'dk_homepage_flyouts' );
    }

    if ( is_singular('attorney') ) {
        wp_enqueue_script( 'attorney_tabs' );
    }
}
add_action( 'wp_enqueue_scripts', 'dk_divi_child_enqueue_styles' );





//set attorneys custom post archive to show all posts
function set_posts_per_page_attorney( $query ) {
    if ( !is_admin() && $query->is_main_query() && is_post_type_archive( 'attorney' ) ) {
      $query->set( 'posts_per_page', '-1' );
    }
    // letter archive
    if ( !is_admin() && $query->is_main_query() && is_tax( 'letter' ) ) {
      $query->set( 'posts_per_page', '-1' );
    }
  }
  add_action( 'pre_get_posts', 'set_posts_per_page_attorney' );



//set letter taxonomy to display results in alphabetical order
add_action('parse_query', 'pmg_ex_sort_posts');
function pmg_ex_sort_posts($q) {
    if(!$q->is_main_query() || is_admin())
        return;
    if(
        !is_post_type_archive('attorney') &&
        !is_tax(array('letter', 'attorney'))
    ) return;
    $q->set('orderby', 'title');
    $q->set('order', 'ASC');
}

/* allow .vcf files */
function jberg_enable_vcard_upload( $mime_types=array() ){
  	$mime_types['vcf'] = 'text/x-vcard';
	$mime_types['vcard'] = 'text/x-vcard';
  	return $mime_types;
}
add_filter('upload_mimes', 'jberg_enable_vcard_upload' );

//add options page for ACF field group
if( function_exists('acf_add_options_page') ) {
	acf_add_options_page('Define Practice Areas & Industries');
}

function get_vcard_link($post_id, $force_generate = false) {
	$vcard_link = get_post_meta($post_id, 'vcard_link', true);
	if (!$vcard_link || $force_generate) {
		$full_name = get_field('attorney_name', $post_id);
		$name =  preg_replace_callback('/^(.+)\s(\w+)$/', function($matchs){
			return $matchs[2].';'.$matchs[1];
		}, $full_name);
		$offices = get_field('office_location', $post_id);
		$office = '';
		if (!empty($offices)) {
			switch($offices[0]->post_title) {
				case 'Brookfield':
					$office = ';;300 North Corporate Drive, Suite 150;Brookfield;WI;53045;USA';
					break;
				case 'Milwaukee':
					$office = ';;111 E. Kilbourn Avenue, Suite 1400;Milwaukee;WI;53202-6613;USA';
					break;
				default:
					$office = ';;318 S. Washington Street, Suite 300;Green Bay;WI;54301;USA';
					break;
			}
		}
		$vcard = 'BEGIN:VCARD'.PHP_EOL;
		$vcard .= 'VERSION:2.1'.PHP_EOL;
		$vcard .= 'N:'.$name.PHP_EOL;
		$vcard .= 'FN:'.$full_name.PHP_EOL;
		$vcard .= 'ORG:Davis & Kuelthau'.PHP_EOL;
		$vcard .= 'ADR:WORK:'.$office.PHP_EOL;
		$vcard .= 'TITLE:'.get_field('title', $post_id).PHP_EOL;
		$vcard .= 'TEL;WORK;VOICE:'.get_field('phone', $post_id).PHP_EOL;
		$vcard .= 'TEL;WORK;FAX:'.get_field('fax', $post_id).PHP_EOL;
		$vcard .= 'URL;TYPE=WORK:http://www.dkattorneys.com'.PHP_EOL;
		$vcard .= 'EMAIL;PREF;INTERNET:'.get_field('email_address', $post_id).PHP_EOL;
		$vcard .= 'END:VCARD';
		$upload_dir = wp_upload_dir();
		$file_name = preg_replace('/\W/', '', $full_name) . '.vcf';
		$file = $upload_dir['path'].'/'.$file_name;
		$vcard_link = $upload_dir['url'].'/'.$file_name;
		if (file_put_contents($file, $vcard, FILE_TEXT)) {
			update_post_meta($post_id, 'vcard_link', $vcard_link);
		}
	}
	return $vcard_link;
}







/**
 * Single Attorney Tabs
 */
function dk_divi_attorney_tabs( $content ) {
    if ( !is_singular('attorney') ) {
        return render_attorney_tabs() . $content;
    }
    return $content;
}
add_filter( 'the_content', 'dk_divi_attorney_tabs' );

function render_attorney_tabs() {
    $fields = [
        get_field_object('main_bio'),
        get_field_object('notable_representations'),
        get_field_object('leadership')
    ];
    $tabsHTML = '';
    $tabContentHTML = '';
    foreach($fields as $field) {
        if ($field) {
            $label = $field['label'];
            $slug = $field['name'];
            $tabsHTML .= '<button class="tablinks">' . $label . '</button>';
            if ($slug === 'notable_representations' || $slug === 'leadership') {
                $layout = $slug === 'notable_representations'
                    ? array_map('create_notables_layout', $field['value'])
                    : array_map('create_leadership_layout', $field['value']);
                $layout = implode(' ', $layout);
                $tabContentHTML .= '<section id="' . $label . '" class="tabcontent">' . $layout . '</section>';
            } else {
                $tabContentHTML .= '
                    <section id="' . $label . '" class="tabcontent">
                        <h2>' . $label . '</h2>
                        '.$field['value'].'
                    </section>
                ';
            }
        }
    }

    return "<div class='dk-tabs'><div class='tab'>$tabsHTML</div> $tabContentHTML</div>";
}

function create_notables_layout($section) {
    $html = '';
    if ($section['acf_fc_layout'] === 'section_heading') {
        $html .= '<h3>' . $section['section_heading'] . '</h3>';
    }
    if ($section['acf_fc_layout'] === 'representation_item') {
        $html .= '<p class="repItem"><strong>' . $section['title'] . ':</strong> ' . $section['description'] . '</p>';
    }
    return $html;
}

function create_leadership_layout($section) {
    $html = '';
    if ($section['acf_fc_layout'] === 'section_title') {
        $html .= '<h3>' . $section['title'] . '</h3>';
    }
    if ($section['acf_fc_layout'] === 'leadership_item') {
        $html .= '<p class="repItem">' . $section['leadership_item_text'] . '</p>';
    }
    return $html;
}











/** 
 * [dk-flyouts]
 */ 
function exclude_post_categories($excl='', $spacer=' ') {
    $categories = get_the_category($post->ID);
    if (!empty($categories)) {
        $exclude = $excl;
        $exclude = explode(",", $exclude);
        $thecount = count(get_the_category()) - count($exclude);
        foreach ($categories as $cat) {
            $html = '';
            if (!in_array($cat->cat_ID, $exclude)) {
                $html .= '<a href="' . get_category_link($cat->cat_ID) . '" ';
                $html .= 'title="' . $cat->cat_name . '">' . $cat->cat_name . '</a>';
                if ($thecount > 0) {
                    $html .= $spacer;
                }
                $thecount--;
                return $html;
            }
        }
    }
}
 
function dk_home_flyouts_func( $atts ){
    $html = '';
    $html .= '
    <div class="sectors group">
        <div class="box">
            <a href="#" class="boxHeading business" id="businessButton">Business</a>
            <div class="postList">';
            
            $args = array(
                'post_type' => array('publications','post'),
                'posts_per_page' => 3,
                'tax_query' => array(
                    array(
                        'taxonomy' => 'category',
                        'terms'    => 14,
                    ),
                ),
                'meta_query' => array(
                    array(
                        'key' => 'exclude_this_post_from_the_home_page',
                        'value' => 0,
                        'compare' => 'IN',
                    ),
                ),
            );
            $the_query = new WP_Query( $args );
                
            // get corona cat ids					
            $args = array('child_of' => 114);
            $categories = get_categories( $args );
            $coronaCats = [];
            foreach($categories as $category) { 
                array_push($coronaCats, $category->term_id);
            }
            $coronaCats = implode(',', $coronaCats);

            if ( $the_query->have_posts() ) {
                while ( $the_query->have_posts() ) {
                    $the_query->the_post();
                        $html .= '<div class="boxPost">
                            <a href="' . get_the_permalink() . '">' . get_the_title() . '</a><br />
                            <span>';
                                if( get_field('display_date') ){
                                    $date = get_field('display_date', false, false);
                                    $date = new DateTime($date);
                                    $html .= $date->format('F j, Y');
                                } else {
                                    $html .= get_the_date();
                                }
                                $html .= ' - ' . exclude_post_categories("14,15,16,$coronaCats");
                                //add publications link if post is a Publication
                                if( 'publications' == get_post_type() ) {
                                    $html .= '<a href="' . site_url() . '/publications/">Publications</a>';
                                }
                            $html .= '</span>
                        </div>';
                }
                wp_reset_postdata();
            }             
            $html .= '<a href="<?php echo site_url(); ?>/categories/business/" class="readMore">Read More >></a>
            </div><!-- postList -->
            
            <div class="newsByPracticeArea static" id="businessAreas">
                <p class="newsTitle">Business News and Services by Practice Area <a href="#" data-id="businessAreas" class="closeButton"></a></p>
                <div class="inner-news group">';
                    $posts = get_field('define_practice_areas_home','option');
                    //get number of posts to split into columns
                    $count = count($posts); //number of posts
                    $postsPerColumn = $count / 3; //number of posts divided by 3 columns
                    $postsPerColumn = ceil($postsPerColumn);
                    $columnNumber = 1; //starting value
                    $postNumber = 1; //starting value
                    $colClass = '';
                    if( $count < 5 ) { //set this class if there is only one column
                        $colClass = ' centered';
                    }
                    if( $posts ):
                            $html .= '<p>Practice Areas</p>';
                            $html .= '<div class="column' . $colClass . '">';
                            foreach( $posts as $post):
                                setup_postdata($post);
                                if( $count < 5 ) { //if less than 5 posts, put them all into one column
                                    $html .= '<a href="' . site_url() . '/' . 
                                    '/' . $post->post_type . '/'. $post->post_name .'">' . $post->post_title . '</a>';
                                }
                                if( $count > 5 ) { //if more than 5 posts, divide them into 3 columns
                                    //if postNumber is higher than postsPerColumn, reset to 1 and increment to next column;
                                    if( $postNumber > $postsPerColumn ) {
                                        $postNumber = 1;
                                        $columnNumber++;
                                        $html .=  '</div><!-- column -->';
                                        $html .=  '<div class="column">';
                                    }
                                    $html .= '<a href="' . site_url() . '/' . 
                                    '/' . $post->post_type . '/'. $post->post_name .'">' . $post->post_title . '</a>';
                            }
                            $postNumber++;
                            endforeach;
                        wp_reset_postdata();
                    endif;
                    $html .= '
                    </div><!-- column -->
                    <div class="clear"></div>';

                    $posts = get_field('define_industries_home','option');

                    //get number of posts to split into columns
                    $count = count($posts); //number of posts
                    $postsPerColumn = $count / 3; //number of posts divided by 3 columns
                    $postsPerColumn = ceil($postsPerColumn);
                    $columnNumber = 1; //starting value
                    $postNumber = 1; //starting value
                    $colClass = '';
                    if( $count < 5 ) { //set this class if there is only one column
                        $colClass = ' centered';
                    }

                    if( $posts ):
                            $html .=  '<p class="marginTop">Industries</p>';
                            $html .=  '<div class="column' . $colClass . '">';
                            foreach( $posts as $post):
                                if( $count < 5 ) { //if less than 5 posts, put them all into one column
                                    $html .= '<a href="' . site_url() . '/' . 
                                    '/' . $post->post_type . '/'. $post->post_name .'">' . $post->post_title . '</a>';
                                }
                                if( $count > 5 ) { //if more than 5 posts, divide them into 3 columns
                                    //if postNumber is higher than postsPerColumn, reset to 1 and increment to next column;
                                    if( $postNumber > $postsPerColumn ) {
                                        $postNumber = 1;
                                        $columnNumber++;
                                        $html .=  '</div><!-- column -->';
                                        $html .=  '<div class="column">';
                                    }
                                    $html .= '<a href="' . site_url() . '/' . 
                                    '/' . $post->post_type . '/'. $post->post_name .'">' . $post->post_title . '</a>';
                                }
                                $postNumber++;
                            endforeach;
                        wp_reset_postdata();
                    endif;
                    $html .= '</div><!-- column -->

                </div><!-- inner-news -->
            </div><!-- newsByPracticeArea -->

        </div><!-- box -->

        <div class="box">
            <a href="#" class="boxHeading public" id="publicButton">Public Sector</a>
            <div class="postList">';

            // query to get last 3 posts in category
            $args = array(
                'post_type' => array('publications','post'),
                'posts_per_page' => 3,
                'tax_query' => array(
                        array(
                            'taxonomy' => 'category',
                            'terms'    => 16,
                        ),
                ),
                'meta_query' => array(
                    array(
                        'key' => 'exclude_this_post_from_the_home_page',
                        'value' => 0,
                        'compare' => 'IN',
                    ),
                ),
                'meta_key' => 'display_date', // name of custom field
                'orderby' => 'meta_value_num',
                'order' => 'DESC',
            );
            $the_query = new WP_Query( $args );

            if ( $the_query->have_posts() ) {

                while ( $the_query->have_posts() ) {
                    $the_query->the_post();
                        $html .= '<div class="boxPost">
                            <a href="' . get_the_permalink() . '">' . get_the_title() . '</a><br />
                            <span>';
                                if( get_field('display_date') ) {
                                    $date = get_field('display_date', false, false);
                                    $date = new DateTime($date);
                                    $html .= $date->format('F j, Y');
                                } else {
                                    $html .= get_the_date();
                                }
                                $html .= ' - ' . exclude_post_categories("14,15,16,$coronaCats");
                                //add publications link if post is a Publication
                                if ( 'publications' == get_post_type() ) {
                                    $html .= '<a href="' . site_url() . '/publications/">Publications</a>';
                                }
                            $html .= '</span>
                        </div>';
                }
                wp_reset_postdata();
            } 
            $html .= '<a href="' . site_url() . '/categories/public-sector/" class="readMore">Read More >></a>
            </div><!-- postList -->

            <div class="newsByPracticeArea static" id="publicAreas">
                <p class="newsTitle">Public Sector News and Services by Practice Area <a href="#" data-id="publicAreas" class="closeButton"></a></p>
                <div class="inner-news">';
                    $posts = get_field('define_practice_areas_for_public_sector_on_home','option');
                    //get number of posts to split into columns
                    $count = count($posts); //number of posts
                    $postsPerColumn = $count / 3; //number of posts divided by 3 columns
                    $postsPerColumn = ceil($postsPerColumn);
                    $columnNumber = 1; //starting value
                    $postNumber = 1; //starting value
                    $colClass = '';
                    if( $count < 5 ) { //set this class if there is only one column
                        $colClass = ' centered';
                    }
                    if( $posts ):
                            $html .= '<p>Practice Areas</p>';
                            $html .= '<div class="column' . $colClass . '">';
                            foreach( $posts as $post):
                                setup_postdata($post);
                                if( $count < 5 ) { //if less than 5 posts, put them all into one column
                                    $html .= '<a href="' . site_url() . '/' . 
                                    '/' . $post->post_type . '/'. $post->post_name .'">' . $post->post_title . '</a>';
                                }
                                if( $count > 5 ) { //if more than 5 posts, divide them into 3 columns
                                    //if postNumber is higher than postsPerColumn, reset to 1 and increment to next column;
                                    if( $postNumber > $postsPerColumn ) {
                                        $postNumber = 1;
                                        $columnNumber++;
                                        $html .= '</div><!-- column -->';
                                        $html .= '<div class="column">';
                                    }
                                    $html .= '<a href="' . site_url() . '/' . 
                                    '/' . $post->post_type . '/'. $post->post_name .'">' . $post->post_title . '</a>';
                            }
                            $postNumber++;
                            endforeach;
                        wp_reset_postdata();
                    endif;
                    $html .= '</div><!-- column -->
                    <div class="clear"></div>';
                        $posts = get_field('define_industries_for_public_sector_on_home','option');
                        //get number of posts to split into columns
                        $count = count($posts); //number of posts
                        $postsPerColumn = $count / 3; //number of posts divided by 3 columns
                        $postsPerColumn = ceil($postsPerColumn);
                        $columnNumber = 1; //starting value
                        $postNumber = 1; //starting value
                        $colClass = '';
                        if( $count < 5 ) { //set this class if there is only one column
                            $colClass = ' centered';
                        }

                        if( $posts ):
                            $html .= '<p class="marginTop">Industries</p>';
                            $html .= '<div class="column' . $colClass . '">';
                            foreach( $posts as $post):
                                setup_postdata($post);
                                if( $count < 5 ) { //if less than 5 posts, put them all into one column
                                    $html .= '<a href="' . site_url() . '/' . 
                                    '/' . $post->post_type . '/'. $post->post_name .'">' . $post->post_title . '</a>';
                                }
                                if( $count > 5 ) { //if more than 5 posts, divide them into 3 columns
                                    //if postNumber is higher than postsPerColumn, reset to 1 and increment to next column;
                                    if( $postNumber > $postsPerColumn ) {
                                        $postNumber = 1;
                                        $columnNumber++;
                                        $html .= '</div><!-- column -->';
                                        $html .= '<div class="column">';
                                    }
                                    $html .= '<a href="' . site_url() . '/' . 
                                    '/' . $post->post_type . '/'. $post->post_name .'">' . $post->post_title . '</a>';
                                }
                                $postNumber++;
                            endforeach;
                        wp_reset_postdata();
                    endif;
                    $html .= '</div><!-- column -->
                </div><!-- inner-news -->
            </div><!-- newsByPracticeArea -->

        </div><!-- box -->

        <div class="box last">
            <a href="#" class="boxHeading individual" id="individualButton">Individual</a>
            <div class="postList">';
                // query to get last 3 posts in category
                $args = array(
                    'posts_per_page' => 3,
                    'post_type' => array('publications','post'),
                    'tax_query' => array(
                            array(
                                'taxonomy' => 'category',
                                'terms'    => 15,
                            ),
                    ),
                    'meta_query' => array(
                            array(
                                'key' => 'exclude_this_post_from_the_home_page',
                                'value' => 0,
                                'compare' => 'IN',
                            ),
                    ),
                );
                $the_query = new WP_Query( $args );

                if ( $the_query->have_posts() ) {
                    while ( $the_query->have_posts() ) {
                        $the_query->the_post();
                            $html .= '<div class="boxPost">
                                <a href="' . get_the_permalink() . '">' . get_the_title() . '</a><br />
                                <span>';
                                    if( get_field('display_date') ){
                                        $date = get_field('display_date', false, false);
                                        $date = new DateTime($date);
                                        $html .= $date->format('F j, Y');
                                    } else {
                                        $html .= get_the_date();
                                    }
                                    $html .= ' - ' . exclude_post_categories("14,15,16,$coronaCats");
                                    //add publications link if post is a Publication
                                    if( 'publications' == get_post_type() ) {
                                        $html .= '<a href="' . site_url() . '/publications/">Publications</a>';
                                    }
                                $html .= '</span>
                            </div>';
                    }
                    wp_reset_postdata();
                }
                $html .= '
                    <a href="<?php echo site_url(); ?>/categories/individual/" class="readMore">Read More >></a>
                </div><!-- postList -->

            <div class="newsByPracticeArea static" id="individualAreas">
                <p class="newsTitle">Individual News and Services by Practice Area <a href="#" data-id="individualAreas" class="closeButton"></a></p>
                <div class="inner-news">';
                    $posts = get_field('define_practice_areas_for_individual_on_home','option');

                    //get number of posts to split into columns
                    $count = count($posts); //number of posts
                    $postsPerColumn = $count / 3; //number of posts divided by 3 columns
                    $postsPerColumn = ceil($postsPerColumn);
                    $columnNumber = 1; //starting value
                    $postNumber = 1; //starting value
                    $colClass = '';
                    if( $count < 5 ) { //set this class if there is only one column
                        $colClass = ' centered';
                    }
                    if( $posts ):
                        $html .= '<p>Practice Areas</p>';
                        $html .= '<div class="column' . $colClass . '">';
                            foreach( $posts as $post):
                                if( $count < 5 ) { //if less than 5 posts, put them all into one column
                                    $html .= '<a href="' . site_url() . '/' . 
                                    '/' . $post->post_type . '/'. $post->post_name .'">' . $post->post_title . '</a>';
                                }
                                if( $count > 5 ) { //if more than 5 posts, divide them into 3 columns
                                    //if postNumber is higher than postsPerColumn, reset to 1 and increment to next column;
                                    if( $postNumber > $postsPerColumn ) {
                                        $postNumber = 1;
                                        $columnNumber++;
                                        $html .= '</div><!-- column -->';
                                        $html .= '<div class="column">';
                                    }
                                    $html .= '<a href="' . site_url() . '/' . 
                                    '/' . $post->post_type . '/'. $post->post_name .'">' . $post->post_title . '</a>';
                                }
                                $postNumber++;
                            endforeach;
                        wp_reset_postdata();
                    endif;
                    $html .= '</div><!-- column -->
                    <div class="clear"></div>';

                    $posts = get_field('define_industries_for_individual_on_home','option');
                    if ($posts !== '') {
                        //get number of posts to split into columns
                        $count = count($posts); //number of posts
                        $postsPerColumn = $count / 3; //number of posts divided by 3 columns
                        $postsPerColumn = ceil($postsPerColumn);
                        $columnNumber = 1; //starting value
                        $postNumber = 1; //starting value
                        $colClass = '';
                        if( $count < 5 ) { //set this class if there is only one column
                            $colClass = ' centered';
                        }
                        if( $posts ):
                            $html .= '<p class="marginTop">Industries</p>';
                            $html .= '<div class="column' . $colClass . '">';
                            foreach( $posts as $post):
                                setup_postdata($post);
                                if( $count < 5 ) { //if less than 5 posts, put them all into one column
                                    $html .= '<a href="' . site_url() . '/' . 
                                    '/' . $post->post_type . '/'. $post->post_name .'">' . $post->post_title . '</a>';
                                }
                                if( $count > 5 ) { //if more than 5 posts, divide them into 3 columns
                                    //if postNumber is higher than postsPerColumn, reset to 1 and increment to next column;
                                    if( $postNumber > $postsPerColumn ) {
                                        $postNumber = 1;
                                        $columnNumber++;
                                        $html .= '</div><!-- column -->';
                                        $html .= '<div class="column">';
                                    }
                                    $html .= '<a href="' . site_url() . '/' . 
                                    '/' . $post->post_type . '/'. $post->post_name .'">' . $post->post_title . '</a>';
                                }
                                $postNumber++;
                            endforeach;
                            wp_reset_postdata();
                        endif;
                    }
                    $html .= '</div><!-- column -->
                </div><!-- inner-news -->
            </div><!-- newsByPracticeArea -->
        </div><!-- box -->
    </div>';

    return $html;
}

add_shortcode( 'dk-flyouts', 'dk_home_flyouts_func' );






// [coronavirus-posts]
function coronavirus_posts_func( $atts ){
	$html = '';
	$coronaCats = get_categories(
		array( 'parent' => 114 )
	);
	foreach ($coronaCats as $cat) {
		$html .= '<h3>' . $cat->name . '</h3>';
		$query = new WP_Query([
			'post_type' => array('post', 'publications'),
			'cat'=> $cat->term_id,
			'posts_per_page' => -1,
		]);				
		if ( $query->have_posts() ) {
			$html .= '<ul style="list-style: none;padding-left: 0; margin-left: 0;">';
			while ( $query->have_posts() ) {
				$query->the_post();
				$thumb = get_the_post_thumbnail( get_the_ID(), 'thumbnail', ['style' => 'margin-right: 25px; min-width: 150px;'] );
				$html .= '<li style="display: flex; align-items: center;">';
				$html .= !empty($thumb) ? $thumb : null;
				$html .= '<h4><a href="' . get_permalink() . '" style="font-size: 1rem;">' . get_the_title() . '</a><br/>'.get_the_date('F j, Y').'</h4>';
				$html .= '</li>';
			}
			$html .= '</ul>';
		}
		wp_reset_postdata();
	}
	return $html;
}
add_shortcode( 'coronavirus-posts', 'coronavirus_posts_func' );