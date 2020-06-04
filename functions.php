<?php

function dk_divi_child_enqueue_styles() { 
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );

    wp_register_script( 'attorney_tabs', get_stylesheet_directory_uri() . '/js/attorney-tabs.js', '', '', true );

    if ( is_singular('attorney') ) {
        wp_enqueue_script( 'attorney_tabs' );
    }
}
add_action( 'wp_enqueue_scripts', 'dk_divi_child_enqueue_styles' );


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