<?php
/**
 * The template for displaying Attorney post archive pages
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package gotheme
 */

get_header(); ?>
<div class="inner-content group">
	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">

		<?php
		if ( have_posts() ) : ?>

			<header class="page-header">
				<h1>Attorneys & Professionals</h1>
			</header><!-- .page-header -->
			
			<div id="attorney-archive-description"><?php the_field('description', 29); ?></div>
			
			<div class="alphaFilter">
				<?php
				$terms = get_terms( array(
    				'taxonomy' => 'letter',
				) );

    			foreach ( $terms as $term ) { ?>
					<a href="<?php echo get_term_link($term); ?>"><?php echo $term->name; ?></a>
    			<?php }

				?>
			</div>
			
			<div id="filterControls">
				<?php echo do_shortcode('[searchandfilter id="498"]'); ?>
			</div>

			<div class="attorney-listing headings">
					<div class="list-photo"></div>
					<div class="list-name">NAME</div>
					<div class="list-practice">PRACTICE AREAS</div>
					<div class="list-location">LOCATION</div>
					<div class="list-phone">PHONE</div>
					<div class="list-vcard">vCard</div>
			</div>
			<?php
			/* Start the Loop */
			while ( have_posts() ) : the_post(); ?>

				<div class="attorney-listing">
					
					<div class="list-photo"><a href="<?php the_permalink(); ?>"><img src="<?php the_field('thumbnail_image'); ?>"></a></div>
					
					<div class="list-name">
						<a href="<?php the_permalink(); ?>"><?php the_field('attorney_name'); ?></a>
						<p><?php the_field('title'); ?></p>
					</div><!-- list name -->
					
					<div class="list-practice">
						<?php				
						$post_objects = get_field('practice_areas');
						if( $post_objects ): ?>
    						<ul class="practice results">
    						<?php foreach( $post_objects as $post): 
								setup_postdata($post); ?>
								<li><?php the_title(); ?></li>
    						<?php endforeach; ?>
    						</ul>
    						<?php wp_reset_postdata(); // reset the $post object so the rest of the page works correctly 
						endif; ?>
					</div><!-- list practice -->
					
					<div class="list-location">
						<?php				
						$post_objects = get_field('office_location');
						if( $post_objects ): ?>
    						<?php foreach( $post_objects as $post): 
								setup_postdata($post); ?>
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
    						<?php endforeach; ?>
    						<?php wp_reset_postdata(); // reset the $post object so the rest of the page works correctly 
						endif; ?>					
					</div><!-- list location -->
					<?php
						//remove non-digit characters from phone field for tel link
						$phone = get_field('phone');
						$theNumber = preg_replace('~[-.]~', '', $phone);
					?>
					<div class="list-phone"><a href="tel:<?php echo $theNumber; ?>"><?php the_field('phone'); ?></a></div>
					<div class="list-vcard"><a href="<?php the_field('add_vcard'); ?>">vCard</a></div>
				</div>
			<?php endwhile; ?>
			
			<div class="nav prev"><?php previous_posts_link('See Previous'); ?></div>
			<div class="nav next"><?php next_posts_link('See More'); ?></div>
		<?php
		else :

			get_template_part( 'template-parts/content', 'none' );

		endif; ?>

		</main><!-- #main -->
	</div><!-- #primary -->
</div><!-- inner-content group -->

<?php
get_footer();
