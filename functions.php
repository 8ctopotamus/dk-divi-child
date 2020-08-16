<?php

function dk_divi_child_enqueue_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
    wp_register_script( 'pdfmake', '//cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.27/pdfmake.min.js', '', '', true );
    wp_register_script( 'vfs_fonts', '//cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.27/vfs_fonts.js', '', '', true );
    wp_register_script( 'single_attorney', get_stylesheet_directory_uri() . '/js/single-attorney.js', '', '', true );
    wp_register_script( 'dk_homepage_flyouts', get_stylesheet_directory_uri() . '/js/homepage-flyouts.js', '', '', true );

    if (is_front_page()) {
        wp_enqueue_script( 'dk_homepage_flyouts' );
    }

    if ( is_singular('attorney') ) {
        wp_enqueue_script( 'pdfmake' );
        wp_enqueue_script( 'vfs_fonts' );
        wp_localize_script( 'single_attorney', 'wp_data', array(
            'permalink' => get_the_permalink(),
            'attorney_name' => get_field('attorney_name'),
            'attorney_title' => get_field('title'),
        ));
        wp_enqueue_script( 'single_attorney' );
    }
}
add_action( 'wp_enqueue_scripts', 'dk_divi_child_enqueue_styles' );




function is_subcategory($return_boolean=true) {
    $result = false;
    if (is_category()) {
        $this_category = get_queried_object();
        if (0 != $this_category->parent) // Category has a parent
            $result = $return_boolean ? true : $this_category;
    }
    return $result;
}

//set attorneys custom post archive to show all posts
function set_posts_per_page_attorney( $query ) {
    
    if ( !is_admin() && $query->is_main_query() ) {
   
		
	  if (is_subcategory()) {
		  $query->set('post_type', array('post', 'publications'));
	  }
		
    }
    // letter archive
    if ( !is_admin() && $query->is_main_query() && is_tax( 'letter' ) ) {
      $query->set( 'posts_per_page', '-1' );
    }
	// attorneys archive
	if ( !is_admin() && $query->is_main_query() && is_post_type_archive( 'attorney' ) ) {
		$query->set( 'posts_per_page', '-1' );
	  }
	
  }
  add_action( 'pre_get_posts', 'set_posts_per_page_attorney', 100 );


//Modify the main query    
function custom_archive_query($query){
   if(is_admin() || !$query->is_main_query()){
      return;
   }
   $cpts = array("research","documents","booklets");
   if(is_post_type_archive($cpts)){
      $query->set('post_type', $cpts);
      return;
   }
}
add_action('pre_get_posts', 'custom_archive_query');


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
    if ( is_singular('attorney') ) {
        return render_attorney_tabs() . $content ;
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
        if ($field && $field['value'] !== false) {
            $label = $field['label'];
            $slug = $field['name'];
            $tabsHTML .= '<button class="tablinks">' . $label . '</button>';
            if ( $slug === 'notable_representations' || $slug === 'leadership' ) {
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

    $htmlForPDF = '<div id="attorney-pdf-markup"></div>';

    return "<div class='dk-tabs'><div class='tab'>$tabsHTML</div> $tabContentHTML</div>" . $htmlForPDF;
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
            $html .= '<a href="' . site_url() . '/categories/business/" class="readMore">Read More >></a>
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
                    <a href="' . site_url() . '/categories/individual/" class="readMore">Read More >></a>
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






//create a custom taxonomy for publications
add_action( 'init', 'create_topics_hierarchical_taxonomy', 0 );

function create_topics_hierarchical_taxonomy() {
  $labels = array(
    'name' => _x( 'Publication Categories', 'taxonomy general name' ),
    'singular_name' => _x( 'Publication Category', 'taxonomy singular name' ),
    'search_items' =>  __( 'Search Publication Categories' ),
    'all_items' => __( 'All Publication Categories' ),
    'parent_item' => __( 'Parent Publication Category' ),
    'parent_item_colon' => __( 'Parent Publication Category:' ),
    'edit_item' => __( 'Edit Publication Category' ),
    'update_item' => __( 'Update Publication Category' ),
    'add_new_item' => __( 'Add New Publication Category' ),
    'new_item_name' => __( 'New Publication Category Name' ),
    'menu_name' => __( 'Publication Category' ),
  );

  register_taxonomy('publication_categories',array('publications'), array(
    'hierarchical' => true,
    'labels' => $labels,
    'show_ui' => true,
    'show_admin_column' => true,
    'query_var' => true,
    'rewrite' => array( 'slug' => 'publications-category' ),
	'show_in_rest'          => true,
    'rest_base'             => 'publications-category',	  
  ));

}

//create a custom taxonomy for posts, to categorize Events by practice area
add_action( 'init', 'create_events_hierarchical_taxonomy', 0 );

function create_events_hierarchical_taxonomy() {
  $labels = array(
    'name' => _x( 'Event Categories', 'taxonomy general name' ),
    'singular_name' => _x( 'Event Category', 'taxonomy singular name' ),
    'search_items' =>  __( 'Search Event Categories' ),
    'all_items' => __( 'All Event Categories' ),
    'parent_item' => __( 'Parent Event Category' ),
    'parent_item_colon' => __( 'Parent Event Category:' ),
    'edit_item' => __( 'Edit Event Category' ),
    'update_item' => __( 'Update Event Category' ),
    'add_new_item' => __( 'Add New Event Category' ),
    'new_item_name' => __( 'New Event Category Name' ),
    'menu_name' => __( 'Event Category' ),
  );

  register_taxonomy('event_categories',array('post'), array(
    'hierarchical' => true,
    'labels' => $labels,
    'show_ui' => true,
    'show_admin_column' => true,
    'query_var' => true,
    'rewrite' => array( 'slug' => 'event-category' ),
	  'show_in_rest'          => true,
    'rest_base'             => 'event-category',
  ));

}










function coronaSidebar() {
    $html = '<div class="corona-sidebar">';
        $currentcat = get_queried_object();
       $html .= '<h2>Coronavirus Resources</h2>';
        // if ($currentcat->parent == 114) {
            $coronaCats = get_categories( array( 'parent' => 114 ) );
            foreach ($coronaCats as $cat) {
                $html .= '<h5>';
                $html .='<a href=" '. site_url('/categories/coronavirus/' .$cat->slug).'">';
                            $html .= $cat->name;
                            $html .='</a>';
                    $html .='</h5>'; 
             }
             $html .='</div>';
       return $html; 
    }
add_shortcode('corona-sidebar', 'coronaSidebar');



function cats_sidebar() {
	$industriesIds = [192, 198, 200, 202, 209, 213, 217];
	$relatedIndustries = [];
	$html = '<div class="cats-sidebar">';
	$currentCat = get_queried_object();
	$catSlug = $currentCat->post_name;
	$args = [
		'post_type' => 'practice_areas',
		'category_name' => $catSlug,
		'posts_per_page' => -1,
		'order' => 'ASC',
		'orderby' => 'title',
	];
	$the_query = new WP_Query( $args );
	if ( $the_query->have_posts() ) {
		$html .= '<h3>Practice Areas</h3>';
		$html .= '<ul class="publicationCatList">';
		while ( $the_query->have_posts() ) {
			$the_query->the_post();
			if ( in_array( get_the_ID(), $industriesIds) ) {
				array_push($relatedIndustries, get_post( get_the_ID() ));
			}
			$postSlug = get_post_field( 'post_name', get_the_ID() );					
			$html .= '<li><a href="'. site_url() .'/event-category/'. $postSlug .'-news">'. get_the_title().'</a></li>';
		}
		$html .= '</ul>';
	}
	wp_reset_postdata();

	if( $relatedIndustries ): 
		$html .= '<h3>Industries</h3>';
		$html .= '<ul class="related">';
		foreach( $relatedIndustries as $post):
			$html .= '<li><a href="'. site_url() .'/'. $post->post_name .'/'. $post->post_name .'">'. $post->post_title.'</a></li>';
		endforeach; 
		$html .= '</ul>';
	endif;

   $html .= '</div>';
   return $html; 
}
add_shortcode('cats-sidebar', 'cats_sidebar');





