<?php

function dk_divi_child_enqueue_styles() { 
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );

    wp_register_script( 'attorney_tabs', get_stylesheet_directory_uri() . '/js/attorney-tabs.js', '', '', true );

    if ( is_singular('attorney') ) {
        wp_enqueue_script( 'attorney_tabs' );
    }
}
add_action( 'wp_enqueue_scripts', 'dk_divi_child_enqueue_styles' );







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
    if ( !is_singular('attorney') ) return $content;
    $tabs = render_attorney_tabs();
    return $tabs .= $content;
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











