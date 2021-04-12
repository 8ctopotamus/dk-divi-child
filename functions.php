<?php

function dk_divi_child_enqueue_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );

    // 	page pdfs
    wp_register_script( 'jsPDF', '//cdnjs.cloudflare.com/ajax/libs/jspdf/1.3.4/jspdf.min.js', '', '', true );
    wp_register_script( 'html2canvas', 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/0.5.0-alpha2/html2canvas.min.js', '', '', true );
    wp_register_script( 'filesaver', 'https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.0/FileSaver.min.js', '', '', true );
    wp_register_script( 'print_page', get_stylesheet_directory_uri() . '/js/print-page.js', '', '', true );

    // single attorney pdfs
    wp_register_script( 'pdfmake', '//cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.27/pdfmake.min.js', '', '', true );
    wp_register_script( 'vfs_fonts', '//cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.27/vfs_fonts.js', '', '', true );
    wp_register_script( 'single_attorney', get_stylesheet_directory_uri() . '/js/single-attorney.js', '', '', true );
	wp_register_script( 'single_publication', get_stylesheet_directory_uri() . '/js/single-publication.js', array('jquery'), '', true );
    wp_register_script( 'dk_homepage_flyouts', get_stylesheet_directory_uri() . '/js/homepage-flyouts.js', '', '', true );

    if (is_front_page()) {
        wp_enqueue_script( 'dk_homepage_flyouts' );
    }

    if ( is_singular('attorney') ) {
        wp_enqueue_script( 'pdfmake' );
        wp_enqueue_script( 'vfs_fonts' );
        wp_localize_script( 'single_attorney', 'wp_data', array(
            'SITE_URL' => site_url(),
            'attorney_name' => get_field('attorney_name'),
            'attorney_title' => get_field('title'),
        ));
        wp_enqueue_script( 'single_attorney' );
    } else {
        wp_enqueue_script( 'jsPDF' );
        wp_enqueue_script( 'html2canvas' );
        wp_enqueue_script( 'filesaver' );
        wp_enqueue_script( 'print_page' );    
    }
	
// 	if ( is_singular('publications') ) {
// 		wp_enqueue_script( 'single_publication' );
// 	}
	
}
add_action( 'wp_enqueue_scripts', 'dk_divi_child_enqueue_styles' );


// Allow publishing of future posts and publications
// https://wordpress.stackexchange.com/questions/30178/make-future-posts-visible-to-the-public-not-just-within-wp-query
function setup_future_hook() {
    // Replace native future_post function with replacement
    remove_action('future_post','_future_post_hook');
    add_action('future_post','publish_future_post_now');

    remove_action('future_publications','_future_publications_hook');
    add_action('future_publications','publish_future_post_now');
}

function publish_future_post_now($id) {
    // Set new post's post_status to "publish" rather than "future."
    wp_publish_post($id);
}

add_action('init', 'setup_future_hook');



function custom_remove_default_et_pb_custom_search() {
	remove_action( 'pre_get_posts', 'et_pb_custom_search' );
	add_action( 'pre_get_posts', 'custom_et_pb_custom_search' );
}
add_action( 'wp_loaded', 'custom_remove_default_et_pb_custom_search' );



function custom_et_pb_custom_search( $query = false ) {
	if ( is_admin() || ! is_a( $query, 'WP_Query' ) || ! $query->is_search ) {
		return;
	}

	if ( isset( $_GET['et_pb_searchform_submit'] ) ) {
		$postTypes = array();
        
		if ( ! isset($_GET['et_pb_include_posts'] ) && ! isset( $_GET['et_pb_include_pages'] ) ) {
            $postTypes = array( 'post' );
        }

		if ( isset( $_GET['et_pb_include_pages'] ) ) {
            $postTypes = array( 'page' );
        }

		if ( isset( $_GET['et_pb_include_posts'] ) ) {
            $postTypes[] = 'post';
        } 

		/* BEGIN Add custom post types */
		$postTypes[] = 'publications';
		/* END Add custom post types */

		$query->set( 'post_type', $postTypes );

		if ( ! empty( $_GET['et_pb_search_cat'] ) ) {
			$categories_array = explode( ',', $_GET['et_pb_search_cat'] );
			$query->set( 'category__not_in', $categories_array );
		}

		if ( isset( $_GET['et-posts-count'] ) ) {
			$query->set( 'posts_per_page', (int) $_GET['et-posts-count'] );
		}
	}
}


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
   		
		
	  if (is_category() || is_subcategory()) {
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
        $query->set( 'orderby', 'title' );
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
				case 'Madison':
					$office = ';;10 East Doty Street, Suite 800;Madison;WI;53703;USA';
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


// Banner Image at top of Posts and Publications
function dk_banner_image( $content ) {
    $finalContent = $content;
    $bannerImage = get_field('banner_image');
    if ( (is_singular('post') || is_singular('publications')) && $bannerImage ) {
        $bannerHTML = $bannerImage 
            ? '<style>.et_pb_title_featured_container {display:none;}</style>
               <img src="'. $bannerImage['url'] .'" alt="'. $bannerImage['alt'] .'" class="banner-image"/>'
            : '';
        $finalContent = $bannerHTML . $finalContent;
    } 
	

    return $finalContent;
}
add_filter( 'the_content', 'dk_banner_image' );


//remove dots at end of post excerpt
function new_excerpt_more( $more ) {
	return '...';
}
add_filter('excerpt_more', 'new_excerpt_more');



/**
 *  Create a custom excerpt string from the first paragraph of the content.
 *
 *  @param   integer  $id       The id of the post
 *  @return  string   $excerpt  The excerpt string
 */
function wp_first_paragraph_excerpt( $id=null ) {
    // Set $id to the current post by default
    if( !$id ) {
        global $post;
        $id = get_the_id();
    }

    // Get the post content
    $content = get_post_field( 'post_content', $id );
    $content = apply_filters( 'the_content', strip_shortcodes( $content ) );

    // Remove all tags, except paragraphs
    $excerpt = strip_tags( $content, '<p></p>' );

    // Remove empty paragraph tags
    $excerpt = force_balance_tags( $excerpt );
    $excerpt = preg_replace( '#<p>\s*+(<br\s*/*>)?\s*</p>#i', '', $excerpt );
    $excerpt = preg_replace( '~\s?<p>(\s|&nbsp;)+</p>\s?~', '', $excerpt );

    // Get the first paragraph
    $excerpt = substr( $excerpt, 0, strpos( $excerpt, '</p>' ) + 4 );

    // Remove remaining paragraph tags
    $excerpt = strip_tags( $excerpt );

    return $excerpt;
}




add_filter('the_content', function($content) {
	$newsPage = is_page('News');
	$singleAttyNews = is_page('Single Attorney News');
	$singlePubs = is_page('Single Attorney Publications');
	$pubCat = is_category('publications-category');
	
	if ($singleAttyNews || $singlePubs || $newsPage || $pubCat) {
		$html = '';
		//assign the GET parameter from home page to a variable			
		if (isset($_GET['topic'])) {
			$theTopic = $_GET['topic'];
			$theTopic = str_replace("-", " ", $theTopic);
			$theTopic = str_replace("_", "-", $theTopic);
		}
		//get the post object of the Practice Area post corresponding to the page title specified in the GET parameter variable		
		$selectedTopic = get_page_by_title( $theTopic, OBJECT, 'attorney' );
		if ($newsPage) {
			$selectedTopic = get_page_by_title( $theTopic, OBJECT, 'practice_areas' );	
		}
	 	$topicID = $selectedTopic->ID;		
		
// 		echo 'the id is: ' . $topicID;
		
		// determine page title to display		
		$thisPageTitle = get_field('attorney_name', $topicID );
		if ($singleAttyNews) {
			$html .= '<h1 class="newsTitle">' . $thisPageTitle . ' In the News</h1>';
		} else if ($thisPageTitle) {
			$html .= '<h1 class="newsTitle">' . $thisPageTitle . ' Publications</h1>';
		} else if ($pubCat) {
			$html .= '<h1 class="newsTitle">' . $theTopic . ' Publications</h1>';
		} else if ($newsPage){
			$html .= '<h1 class="newsTitle">' . $theTopic .' News </h1>';
			
		}
		
		// determine post-type and meta-key
		$post_type = 'post';
		$meta_key = false;
		if ($singlePubs) {
			$post_type = 'publications';
			$meta_key = 'related_attorneys';
		} else if ($singleAttyNews) {
			$meta_key = 'related_attorneys';
		} else if ($newsPage) {
			$meta_key = 'related_practice_areas';
		}
		
		//show posts that have the clicked Practice Area in related practice areas custom field
		$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
		$args = array(
			'posts_per_page'	=> 10,
			'post_type'			=>  $post_type,
			'paged'				=>	$paged,
// 			'meta_key'			=> 'display_date',
// 			'orderby'			=> 'meta_value_num',
			'order'				=> 'DESC',
			'meta_query' => array(
				array(
					'key' => $meta_key, // name of custom field 
					'value' => $topicID,
					'compare' => 'LIKE',
				)
			)
		);
		$the_query = new WP_Query( $args ); 
		if ( $the_query->have_posts() ) :
			while ( $the_query->have_posts() ) : $the_query->the_post();
				$post = get_post();
		
				$isPhoto = get_the_post_thumbnail() ;
				
                $str = wpautop( strip_shortcodes(get_the_content()) );
				$str = substr( $str, 0, strpos( $str, '</p>' ));
                $str = strip_tags($str, '<a><strong><em>');
                
                if ($str === '') {
                    $str = preg_replace('/\[\/?et_pb.*?\]/', '', $post->post_content); // strip divi shortcodes - https://gist.github.com/jfarsen/beab40491848dc161436eaa6a86b95f2
                }

				$excerpt = '<p>' . wp_trim_words( $str, 55, '...' ) . '</p>';
            
				if($isPhoto) {
					$html .= '<article class="group featured et_pb_post articleGrid"> ';
				} else {
					$html .= '<article class="group et_pb_post">';
				}
		
 				$html .= '<div class="featuredImage">' . $isPhoto . '</div>';
				$html.= '<div class="post-container">';
				$html .= '<h2 class="entry-title1"><a class="" href="'. get_the_permalink() . '">' . get_the_title() . '</a></h2>';
				$html .= '<div class="entry-meta">'. get_the_date(). '</div>';
				$html .= '<div class=excerpt-text>'. $excerpt . '<div>';
				$html .= '<div class="read-text"><a href="' . get_the_permalink() . '" class="readFull post-content-inner more-link">Read More</a></div>';
				$html.= '</div>';
				$html .= '</article>';
		
			endwhile;
		
			wp_reset_postdata(); 
        endif;
        
        $html .= wp_pagenavi( array( 'query' => $the_query, 'echo' => false ) ); 
        wp_reset_query();

		$content = $content . $html;	
	}	
	return $content;
}, 100);


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

// function render_attorney_tabs() {
//     $fields = [
//         get_field_object('main_bio'),
//         get_field_object('notable_representations'),
//         get_field_object('leadership')
//     ];
//     $tabsHTML = '';
//     $tabContentHTML = '';
//     foreach($fields as $field) {
//         if ($field && $field['value'] !== false) {
//             $label = $field['label'];
//             $slug = $field['name'];
//             $tabsHTML .= '<button class="tablinks">' . $label . '</button>';
//             if ( $slug === 'notable_representations' ) {
//                 $layout = $slug === 'notable_representations';
//                 array_map('create_notables_layout', $field['value']);
//                 $layout = implode(' ', $layout);
// 				$h2Label = $label === 'Notable Representations' ? 'Notable Representations' : $label;
//                $tabContentHTML .= '
//                     <section id="' . $label . '" class="tabcontent">
//                         <h2>' . $h2Label . '</h2>
//                         '.$field['value'].'
//                     </section>
//                 ';
//             } else if ($slug === 'leadership' ){
// 				$layout = $slug === 'leadership';
// 				array_map('create_leadership_layout', $field['value']);
// 				$layout = implode(' ', $layout);
// 				$h2Label = $label === 'Leadership' ? 'Leadership' : $label;			
//                 $tabContentHTML .= '
//                     <section id="' . $label . '" class="tabcontent">
//                         <h2>' . $h2Label . '</h2>
//                         '.$field['value'].'
//                     </section>
//                 ';
// 			} else {
// 				$h2Label = $label === 'Main Bio' ? 'Biography' : $label;			
//                 $tabContentHTML .= '
//                     <section id="' . $label . '" class="tabcontent">
//                         <h2>' . $h2Label . '</h2>
//                         '.$field['value'].'
//                     </section>
//                 ';
//             }
//         }
//     }
	
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
                $tabContentHTML .= '<section id="' . $label . '" class="tabcontent">
					<h2 class="main-title">' . $label . '</h2>
					' . $layout . '
				</section>';
            } else {
				$h2Label = $label === 'Main Bio' ? 'Biography' : $label;			
                $tabContentHTML .= '
                    <section id="' . $label . '" class="tabcontent">
                        <h2>' . $h2Label . '</h2>
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
		$html .= '<h3 class="secondary-title">' . $section['section_heading'] . '</h3>';
    }
    if ($section['acf_fc_layout'] === 'representation_item') {
        $html .= '<p class="repItem"><strong>' . $section['title'] . '</strong> ' . $section['description'] . '</p>';
    }
    return $html;
}

function create_leadership_layout($section) {
    $html = '';
    if ($section['acf_fc_layout'] === 'section_title') {
        $html .= '<h3 class="secondary-title">' . $section['title'] . '</h3>';
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
                $html .= 'title="' . $cat->cat_name . '">' . $cat->cat_name . '</a>&nbsp;';
                if ($thecount > 0) {
                    $html .= $spacer;
                }
                $thecount--;
                return $html;
            }
        }
    }
}
 
function dk_home_flyouts_func( $atts ) {
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





// shows different categories in the sidebar
function cats_sidebar() {
    $html = '';
    if ( is_tax('event_categories') ) {
      $html .= '<h3>Practice Areas</h3>';
      $html .= '<ul class="publicationCatList">';
      //turn the category title into a slug corresponding with a practice area post slug, and display the link to that practice area post
      $current_category = single_cat_title("", false);
      $current_category_lower = strtolower($current_category);
      $theSlug = str_replace(' ', '-', $current_category_lower);
      $mypost = get_page_by_path( $theSlug, '', 'practice_areas');
      $myLink = get_post_permalink( $mypost->ID );

      $html .= '<li><a href="'.$myLink.'">'.$current_category.'</a></li>';
      $html .= '</ul>';
      $html .= '<h3>Industries</h3>';
      $html .= '<ul class="publicationCatList">';
      $listIndustries = [192,198,200,209,202,212,213,216,217]; //array to store industries posts
      foreach( $listIndustries as $postID):
          $p = get_post($postID);
          $html .= '<li><a href="' . site_url() . '/' . $p->post_type . '/' . $p->post_name . '">'. $p->post_title .'</a></li>';
      endforeach;
      wp_reset_postdata();
      $html .= '</ul>';
      return $html;
    }
    // business
    else if( is_category(14) ) {
       $html .= '<h3>Practice Areas</h3>';
       $html .= '<ul class="publicationCatList">';
       $businessPracticeAreas = get_field('define_practice_areas_for_business_news_page','option');
       foreach ($businessPracticeAreas as $post) {
          //get the post title and convert spaces to hypens to use in URL GET parameter
          $realTitle = $post->post_title;
          $realTitle = str_replace("-", "_", $realTitle);
          $realTitle = str_replace(" ", "-", $realTitle);
          $html .= '<li><a href="' . site_url() . '/news/?topic='. $realTitle . '">' . $post->post_title . '</a></li>';
        }
        $html .= '</ul>';
        $html .= '<h3>Industries</h3>';
        $html .= '<ul class="publicationCatList">';
        $businessIndustries = get_field('define_industries_for_business_news_page','option');
        foreach ($businessIndustries as $post) {
          //get the post title and convert spaces to hypens to use in URL GET parameter
          $realTitle = get_the_title();
          $realTitle = str_replace("-", "_", $realTitle);
          $realTitle = str_replace(" ", "-", $realTitle);
          $html .= '<li><a href="' . site_url() . '/news/?topic=' . $realTitle. '">'. $post->post_title .'</a></li>';
        }
        $html .= '</ul>';
        return $html;
    } //end is category 14, business

    // individual
    else if( is_category(15) ) {
        $html .= '<h3>Practice Areas</h3>';
        $html .= '<ul class="publicationCatList">';
        $individualPracticeAreas = get_field('define_practice_areas_for_individual_news_page','option');
        foreach ($individualPracticeAreas as $post) {
            //get the post title and convert spaces to hypens to use in URL GET parameter
            $realTitle = get_the_title();
            $realTitle = str_replace("-", "_", $realTitle);
            $realTitle = str_replace(" ", "-", $realTitle);
            $html .= '<li><a href="' . site_url() . '/news/?topic=' . $realTitle. '">'. $post->post_title .'</a></li>';
        }
        $html .= '</ul>';
        return $html;
    } //end is category 15, individual

    // public sector
    else if( is_category(16) ) {
        $html .= '<h3>Practice Areas</h3>';
        $html .= '<ul class="publicationCatList">';
        //wp_query wouldnt work, iterate through array of practice area post ids in category to get the post titles to show in list and generate GET parameter for news page
        $municipalPracticeAreas = get_field('define_practice_areas_for_public_sector_news_page','option');;
        foreach ($municipalPracticeAreas as $post) {
            //get the post title and convert spaces to hypens to use in URL GET parameter
            $realTitle = get_the_title();
            $realTitle = str_replace("-", "_", $realTitle);
            $realTitle = str_replace(" ", "-", $realTitle);
            $html .= '<li><a href="' . site_url() . '/news/?topic=' . $realTitle. '">'. $post->post_title .'</a></li>';
        }

        $html .= '</ul>';
        return $html;
    } //end is category 16, public, municipal
    
    // in-the-news
    else if ( is_category(8) ) {
        $html .= '<h3>Practice Areas</h3>';
        $html .= '<ul class="publicationCatList">';
        $areaPosts = get_field('define_practice_areas','option');
        if( $areaPosts ):
            foreach( $areaPosts as $post):
//                 setup_postdata($post);
                //use the post title to create a hyperlink to the news category for this practice area
                $postTitle = $post->post_title;
                
                $slugNoCommaOrDash = str_replace(array(',', '/'), "", $postTitle); 
                $postTitleNameLowercase = strtolower($slugNoCommaOrDash);
                $doubleDash = str_replace(" ", "-",  $postTitleNameLowercase);
                $theSlug = str_replace("--", "-",  $doubleDash);
   
               $html .= '<li><a href="'. site_url() .'/event-category/'. $theSlug .'-news">'. $post->post_title .'</a></li>'; 
            endforeach;
        wp_reset_postdata();
        $html .= '</ul>';
    
        $areaPosts = get_field('define_industries', 'options');
        $html .= '<h3>Industries</h3>';
        $html .= '<ul class="publicationCatList">';
        if( $areaPosts ):
            foreach( $areaPosts as $post):
                setup_postdata($post);
            
                $postTitle = $post->post_title;

                $slugNoCommaOrDash = str_replace(array(',', '/'), "", $postTitle); 
                 $postTitleNameLowercase = strtolower($slugNoCommaOrDash);
                 $doubleDash = str_replace(" ", "-",  $postTitleNameLowercase);
                 $theSlug = str_replace("--", "-",  $doubleDash);
    
    
                 $html .= '<li><a href="'. site_url() .'/event-category/' . $theSlug. '-news">'. $post->post_title .'</a></li>';
            endforeach;
        wp_reset_postdata();
        $html .= '</ul>';
        endif;
    
        endif;
        return $html;
    } // end cat 8
    
    // news & events
    else if( is_category(9) ) {
        $html .= '<h3>Practice Areas</h3>';
        $html .= '<ul class="publicationCatList">';

        $areaPosts = get_field('define_practice_areas','option');

        if( $areaPosts ):
            foreach( $areaPosts as $post):
                //use the post title to create a hyperlink to the news category for this $postTitle = $post->post_title;
                
                $postTitle = $post->post_title;
                
                $slugNoCommaOrDash = str_replace(array(',', '/'), "", $postTitle); 
                $postTitleNameLowercase = strtolower($slugNoCommaOrDash);
                $doubleDash = str_replace(" ", "-",  $postTitleNameLowercase);
                $theSlug = str_replace("--", "-",  $doubleDash);
   
               $html .= '<li><a href="'. site_url() .'/event-category/'. $theSlug .'-news">'. $post->post_title .'</a></li>'; 
            endforeach;
        wp_reset_postdata();
        $html .= '</ul>';
        endif;
        $html .= '<h3>Industries</h3>';
        $html .= '<ul class="publicationCatList">';
        $areaPosts = get_field('define_industries', 'options');
        if( $areaPosts ):
            foreach( $areaPosts as $post):
                setup_postdata($post);
            $postTitle = $post->post_title; 

            $slugNoCommaOrDash = str_replace(array(',', '/'), "", $postTitle); 
             $postTitleNameLowercase = strtolower($slugNoCommaOrDash);
             $doubleDash = str_replace(" ", "-",  $postTitleNameLowercase);
             $theSlug = str_replace("--", "-",  $doubleDash);


           $html .= '<li><a href="'. site_url() .'/event-category/' . $theSlug. '-news">'. $post->post_title .'</a></li>';
            endforeach;
        wp_reset_postdata();
        $html .= '</ul>';
        endif;
        return $html;
    } //end is category 9, news & events
	
	 else if( is_category(10) ) {
        $html .= '<h3>Practice Areas</h3>';
        $html .= '<ul class="publicationCatList">';

        $areaPosts = get_field('define_practice_areas','option');

        if( $areaPosts ):
            foreach( $areaPosts as $post):
                //use the post title to create a hyperlink to the news category for this practice area
                $postTitle = $post->post_title;
                
                $slugNoCommaOrDash = str_replace(array(',', '/'), "", $postTitle); 
                $postTitleNameLowercase = strtolower($slugNoCommaOrDash);
                $doubleDash = str_replace(" ", "-",  $postTitleNameLowercase);
                $theSlug = str_replace("--", "-",  $doubleDash);
   
               $html .= '<li><a href="'. site_url() .'/event-category/'. $theSlug .'-news">'. $post->post_title .'</a></li>'; 
            endforeach;
        wp_reset_postdata();
        $html .= '</ul>';
        endif;
        $html .= '<h3>Industries</h3>';
        $html .= '<ul class="publicationCatList">';
        $areaPosts = get_field('define_industries', 'options');
        if( $areaPosts ):
            foreach( $areaPosts as $post):
                setup_postdata($post);

                $postTitle = $post->post_title;

            $slugNoCommaOrDash = str_replace(array(',', '/'), "", $postTitle); 
             $postTitleNameLowercase = strtolower($slugNoCommaOrDash);
             $doubleDash = str_replace(" ", "-",  $postTitleNameLowercase);
             $theSlug = str_replace("--", "-",  $doubleDash);


             $html .= '<li><a href="'. site_url() .'/event-category/' . $theSlug. '-news">'. $post->post_title .'</a></li>';
            endforeach;
        wp_reset_postdata();
        $html .= '</ul>';
        endif;
        return $html;
    } //end is category 9, news & events

	//detect publications
	
	if (is_post_type_archive("publications")) {
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
            if ( ! in_array(get_the_ID(), [192, 198, 200, 202, 209, 213, 217])) { 
               
                $postTitle = get_post_field( 'post_title', get_the_ID() );
                
                 $slugNoCommaOrDash = str_replace(array(',', '/'), "", $postTitle); 
                 $postTitleNameLowercase = strtolower($slugNoCommaOrDash);
                 $doubleDash = str_replace(" ", "-",  $postTitleNameLowercase);
                 $theSlug = str_replace("--", "-",  $doubleDash);
    
                $html .= '<li><a href="'. site_url() .'/publications-category/'. $theSlug .'">'. get_the_title().'</a></li>'; 
            }
		}
		$html .= '</ul>';
	}
	wp_reset_postdata();

	if( $relatedIndustries ): 
		$html .= '<h3>Industries</h3>';
		$html .= '<ul class="related">';
		foreach( $relatedIndustries as $post):

            $postTitle = $post->post_title;

            $slugNoCommaOrDash = str_replace(array(',', '/'), "", $postTitle); 
             $postTitleNameLowercase = strtolower($slugNoCommaOrDash);
             $doubleDash = str_replace(" ", "-",  $postTitleNameLowercase);
             $theSlug = str_replace("--", "-",  $doubleDash);


           $html .= '<li><a href="'. site_url() .'/publications-category/'. $theSlug .'">'. $post->post_title.'</a></li>';

            
		endforeach; 
		$html .= '</ul>';
	endif;

   $html .= '</div>';
   return $html; 
	}


    //end pubs
	
    // default
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
			$html .= '<li><a href="'. site_url() .'/publications-category/'. $postSlug .'">'. get_the_title().'</a></li>';
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






// Start - shortcode for leadership page acf fields
function dk_leadership_shortcode_func ($atts) {
	$html= '';
    $html .= '<div class="profileCenter">';
    
    $html .= '<div class="leader-grid">';
        $html .= '<div class="leader">';
        $html .='<h2 class="heading">Firm President</h2>';
        $post_object = get_field('firm_president');
        if( $post_object ):
        // override $post
            $post = $post_object;
            setup_postdata( $post ); 
            $post->ACF=get_fields($post->ID);
        
            $html .='<p><a href="' . get_site_url() . '/attorney/' .  $post->post_name . '"> ' . $post->ACF['attorney_name'] . ' </a></p>';
            $html .='<p><a href="mailto:' . $post->ACF['email_address'] . '">' . $post->ACF['email_address']  .'</a></p>';
            $html .='<p>T: ' .  $post->ACF['phone'] . '</p>';

            wp_reset_postdata();
            endif; 
        $html .= '</div>'; 

        // <!-- leader -->
        $html .= '<div class="leader">';
        $html .= '<h2 class="heading">Executive Director</h2>';

        $post_object = get_field('executive_director');
        if( $post_object ):
        // override $post
            $post = $post_object;
            setup_postdata( $post );
            $post->ACF=get_fields($post->ID);

            $html .='<p><a href="' . get_site_url() . '/attorney/' .  $post->post_name . '"> ' . $post->ACF['attorney_name'] . ' </a></p>';
            $html .='<p><a href="mailto:' . $post->ACF['email_address'] . '">' . $post->ACF['email_address']  .'</a></p>';
            $html .='<p>T: ' .  $post->ACF['phone'] . '</p>';

            wp_reset_postdata(); 
            endif;
        $html .='</div>'; 
    $html .='</div>'; //leader-grid


    $post_objects = get_field('members_of_the_board');

    if( $post_objects ):
        $html .='<h2 class="heading">Members of the Board</h2>';
        $html .= '<div class="leader-grid">';
        foreach( $post_objects as $post):
            setup_postdata($post);
            $post->ACF=get_fields($post->ID);
            $html .= '<div class="leader">';
            $html .='<p><a href="' . get_site_url() . '/attorney/' .  $post->post_name . '"> ' . $post->ACF['attorney_name'] . ' </a></p>';
            $html .='<p><a href="mailto:' . $post->ACF['email_address'] . '">' . $post->ACF['email_address']  .'</a></p>';
            $html .='<p>T: ' .  $post->ACF['phone'] . '</p>';
            $html .= '</div>';
        endforeach;
        wp_reset_postdata();
        $html .= '</div>'; // leader-grid
        endif;

                        
    // check if the repeater field has rows of data

    if( have_rows('team_heads') ): 

        $html .= '<h2 class="heading">Team Heads (Practice Area Leaders)</h2>';
        $html .= '<div class="leader-grid">';        
        // loop through the rows of data
        while ( have_rows('team_heads') ) : the_row();

        $html .= '<div class="leader">';
        //show practice area
        $post_object = get_sub_field('practice_area');
        if( $post_object ):
            // override $post
            $post = $post_object;
            setup_postdata( $post );
            $html .= '<p>' . $post->post_title . '</p>';

            wp_reset_postdata();

        endif;

        //show attorney data
        $post_object = get_sub_field('attorney');
        if( $post_object ):
        // override $post
            $post = $post_object;
            setup_postdata( $post );
            $post->ACF=get_fields($post->ID);

            $html .='<p><a href="' . get_site_url() . '/attorney/' .  $post->post_name . '"> ' . $post->ACF['attorney_name'] . ' </a></p>';
            $html .='<p><a href="mailto:' . $post->ACF['email_address'] . '">' . $post->ACF['email_address']  .'</a></p>';
            $html .='<p>T: ' .  $post->ACF['phone'] . '</p>';

            wp_reset_postdata();
            endif;
            $html .= '</div>';

            endwhile;
            else :
            // no rows found
    endif;


    $html .= '</div>';// leader-grid

    // check if the repeater field has rows of data
    if( have_rows('individual_practice_chairs') ): 
        $html .= '<h2 class="heading">Individual Practice Chairs</h2>';
        $html .= '<div class="leader-grid">';     
        // loop through the rows of data
        while ( have_rows('individual_practice_chairs') ) : the_row();

            $html .= '<div class="leader">';
            //show practice area
            $post_object = get_sub_field('practice_area');
            if( $post_object ):
                // override $post
                $post = $post_object;
                setup_postdata( $post );

                $html .= '<p>' . $post->post_title . '</p>';

                wp_reset_postdata();
            endif;

            //show attorney data
            $post_object = get_sub_field('attorney');
            if( $post_object ):
            // override $post
                $post = $post_object;
                setup_postdata( $post );
                $post->ACF=get_fields($post->ID);

                $html .='<p><a href="' . get_site_url() . '/attorney/' .  $post->post_name . '"> ' . $post->ACF['attorney_name'] . ' </a></p>';
                $html .='<p><a href="mailto:' . $post->ACF['email_address'] . '">' . $post->ACF['email_address']  .'</a></p>';
                $html .='<p>T: ' .  $post->ACF['phone'] . '</p>';

                wp_reset_postdata();
                endif;
            $html .= '</div>';

            endwhile;
        else :
        // no rows found
        endif;

    $html .='</div>'; // leader-grid

    // check if the repeater field has rows of data
    if( have_rows('administrative_directors') ): 
        $html .= '<h2 class="heading">Administrative Directors</h2>';
        $html .= '<div class="leader-grid">';     
        // loop through the rows of data
        while ( have_rows('administrative_directors') ) : the_row();
			$html .= '<div class="leader">';
			//show title 
			$html .='<p>' .  get_sub_field('title') . '</p>';
			 //show attorney data
			$post_object = get_sub_field('attorney');
			if( $post_object ):
				// override $post
				$post = $post_object;
				setup_postdata( $post );
				$post->ACF=get_fields($post->ID);
				$html .='<p><a href="' . get_site_url() . '/attorney/' .  $post->post_name . '"> ' . $post->ACF['attorney_name'] . ' </a></p>';
        		$html .='<p><a href="mailto:' . $post->ACF['email_address'] . '">' . $post->ACF['email_address']  .'</a></p>';
        		$html .='<p>T: ' .  $post->ACF['phone'] . '</p>';
				wp_reset_postdata();
			endif;
			$html .= '</div>';

   		endwhile;
    else :
    // no rows found
    endif;
    $html .= '</div>';
//     endwhile; 
	
	// End of the loop. 

    $html .='</div>';
	return $html;
// <!-- profileCenter -->      
}
add_shortcode( 'leadershipFields', 'dk_leadership_shortcode_func' );



// End - shortcode for leadership page
