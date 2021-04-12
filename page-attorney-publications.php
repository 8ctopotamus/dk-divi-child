<?php
/*
Template Name: Attorney Publications
*/

get_header();

$post_id              = get_the_ID();
$is_page_builder_used = et_pb_is_pagebuilder_used( $post_id );
$container_tag        = 'product' === get_post_type( $post_id ) ? 'div' : 'article'; ?>

    <div id="main-content">

<?php if ( ! $is_page_builder_used ) : ?>

	<div class="container">
		<div id="content-area" class="clearfix">
			<div id="left-area">

<?php endif; ?>

			<?php while ( have_posts() ) : the_post(); ?>

				<<?php echo $container_tag; ?> id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

				<?php if ( ! $is_page_builder_used ) : ?>

					<h1 class="main_title"><?php the_title(); ?></h1>
				<?php
					$thumb = '';

					$width = (int) apply_filters( 'et_pb_index_blog_image_width', 1080 );

					$height = (int) apply_filters( 'et_pb_index_blog_image_height', 675 );
					$classtext = 'et_featured_image';
					$titletext = get_the_title();
					$alttext = get_post_meta( get_post_thumbnail_id(), '_wp_attachment_image_alt', true );
					$thumbnail = get_thumbnail( $width, $height, $classtext, $alttext, $titletext, false, 'Blogimage' );
					$thumb = $thumbnail["thumb"];

					if ( 'on' === et_get_option( 'divi_page_thumbnails', 'false' ) && '' !== $thumb )
						print_thumbnail( $thumb, $thumbnail["use_timthumb"], $titletext, $width, $height );
				?>

				<?php endif; ?>

					<div class="entry-content">
					<?php
						the_content();


						
			
						//assign the GET parameter from home page to a variable			
						if (isset($_GET['topic'])) {
								$theTopic = $_GET['topic'];
							$theTopic = str_replace("-", " ", $theTopic);
							$theTopic = str_replace("_", "-", $theTopic);
							//echo 'you clicked: ' . $theTopic . '<br />';
						} else {
								//Handle the case where there is no parameter
						}
						//get the post object of the Practice Area post corresponding to the page title specified in the GET parameter variable			
						$selectedTopic = get_page_by_title( $theTopic, OBJECT, 'practice_areas' );
						$topicID = $selectedTopic->ID;			
						//echo 'the id is: ' . $topicID;
			
						echo '<h1>' . $theTopic . '</h1>';
						
						//show posts that have the clicked Practice Area in related practice areas custom field
						$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
						$args = array(
								'posts_per_page'	=> 10,
								'post_type'			=> 'publications',
							'paged'				=>	$paged,
							'meta_query' => array(
								array(
									'key' => 'related_practice_areas', // name of custom field
									'value' => $topicID,
									'compare' => 'LIKE',
								)
							)
						);
						$the_query = new WP_Query( $args ); ?>
			
						<?php if ( $the_query->have_posts() ) : ?>
			
							<?php while ( $the_query->have_posts() ) : $the_query->the_post(); ?>
						
										<?php 
										$isPhoto = get_the_post_thumbnail();
										if($isPhoto) {
											echo '<article class="group featured">';
										} else {
											echo '<article class="group">';
										} ?>				
										<div class="featuredImage"><?php the_post_thumbnail(); ?></div>
										<div class="blogContent">
											<h2 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
											<div class="entry-meta"><?php the_date(); ?></div>
											<?php the_excerpt(); ?>
											<a href="<?php the_permalink(); ?>" class="readFull">Read More</a>
										</div>
									</article>
				
							<?php endwhile; 
					
							$theNext = get_next_posts_link(  __( 'OLDER POSTS' ), $the_query->max_num_pages ); 
							$thePrev = get_previous_posts_link( __( 'NEWER POSTS' ) ); 
							?>		
							<div class="nav-links">
								<div class="nav-previous"><?php echo $theNext; ?></div>
								<div class="nav-next"><?php echo $thePrev; ?></div>
							</div>
				
							<?php wp_reset_postdata(); ?>
			
					<?php else : ?>
						<p><?php esc_html_e( 'Sorry, no posts matched your criteria.' ); ?></p>
					<?php endif;


						if ( ! $is_page_builder_used )
							wp_link_pages( array( 'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'Divi' ), 'after' => '</div>' ) );
					?>
					</div> <!-- .entry-content -->

				<?php
					if ( ! $is_page_builder_used && comments_open() && 'on' === et_get_option( 'divi_show_pagescomments', 'false' ) ) comments_template( '', true );
				?>

				</<?php echo $container_tag; ?>> <!-- .et_pb_post -->

			<?php endwhile; ?>

<?php if ( ! $is_page_builder_used ) : ?>

			</div> <!-- #left-area -->

			<?php get_sidebar(); ?>
		</div> <!-- #content-area -->
	</div> <!-- .container -->

<?php endif; ?>

</div> <!-- #main-content -->

<?php

get_footer();
