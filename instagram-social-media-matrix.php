<?php
/*
Plugin Name: Instagram Social Media Matrix
Plugin URI: https://github.com/mdperez86/instagram-social-media-matrix
Description: A WordPress widget for showing Instagram photos with social media links.
Version: 1.0.1
Author: Maikel David Perez Gomez
Author URI: https://www.linkedin.com/in/mdperez86
Text Domain: instagram-social-media-matrix
Domain Path: /assets/languages/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Copyright © 2013 Maikel David Perez Gomez

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
*/

function wpiw_widget() {
	register_widget('ISMM_Widget');
}

add_action('widgets_init', 'wpiw_widget');

define('CLIENT_ID', '506cde34fd1f4004b7faa9a44b460b61');
define('CLIENT_SECRET', '5c971b39499f4ff18c5e7e07d973b1ca');
define('AUTHORIZE_URI', 'https://api.instagram.com/oauth/authorize');
define('RECENT_URI', 'https://api.instagram.com/v1/users/self/media/recent');

class ISMM_Widget extends WP_Widget {

  /**
   * Sets up the widgets name etc
   */
  public function __construct() {
    parent::__construct(
      'ismm_widget',
      esc_html__('Instagram Social Media Matrix', 'text_domain'),
      array('description' => esc_html__('A WordPress widget for showing Instagram photos with social media links.', 'text_domain'))
    );
  }

  private function get_redirect_url() {
    $path = '/wp-admin/widgets.php';
    return site_url($path);
  }

  private function hasError($response) {
    return is_wp_error($response) || $response['response']['code'] >= 400 || $response['response']['code'] < 200;
  }

  private function get_recent($access_token) {
    $url = RECENT_URI . '?access_token=' . $access_token;
    $response = wp_remote_get($url);
    if ($this->hasError($response)) {
      return null;
    }
    return $response['body'];
  }

  private function get_authorize_url() {
    return AUTHORIZE_URI . '?client_id=' . CLIENT_ID . '&redirect_uri=' . $this->get_redirect_url() . '&response_type=token';
  }

  private function push_image(array &$images, $item) {
    if (!empty($item['images']['standard_resolution']['url'])) {
      array_push($images, array(
        'type' => 'image',
        'url' => $item['images']['standard_resolution']['url'],
        'thumbnail' => $item['images']['low_resolution']['url'],
      ));
    }
  }

  private function get_images_from_body($body) {
    $images = array();
    if (empty($body)) return $images;
    $json = json_decode($body, true);
    foreach ($json['data'] as $data) {
      if ($data['type'] == 'carousel') {
        foreach ($data['carousel_media'] as $media) {
          $this->push_image($images, $media);
        }
      } else {
        $this->push_image($images, $data);
      }
    }
    return $images;
  }

  private function merge_socials_and_images($instance) {
    $items = array();
    if (!empty($instance['access_token'])) {
      $access_token = $instance['access_token'];
      $body = $this->get_recent($access_token);
      $images = $this->get_images_from_body($body);
      $images = array_slice($images, 0, 9);
      if ($instance['random_images']) {
        shuffle($images);
      }
      array_push($items, array(
        'type' => 'social',
        'class' => 'fa fa-2x fa-twitter',
        'url' => $instance['twitter'],
      ));
      $items = array_merge($items, array_slice($images, 0, 3));
      array_push($items, array(
        'type' => 'social',
        'class' => 'fa fa-2x fa-instagram',
        'url' => $instance['instagram'],
      ));
      $items = array_merge($items, array_slice($images, 3, 1));
      array_push($items, array(
        'type' => 'phrase',
        'content' => $instance['phrase'],
      ));
      $items = array_merge($items, array_slice($images, 4, 2));
      array_push($items, array(
        'type' => 'social',
        'class' => 'fa fa-2x fa-facebook',
        'url' => $instance['facebook'],
      ));
      $items = array_merge($items, array_slice($images, 6, 2));
      array_push($items, array(
        'type' => 'social',
        'class' => 'fa fa-2x fa-youtube-play',
        'url' => $instance['youtube'],
      ));
      $items = array_merge($items, array_slice($images, 8, 1));
    }
    return $items;
  }

  /**
   * Outputs the content of the widget
   *
   * @param array $args
   * @param array $instance
   */
  public function widget($args, $instance) {
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css', array(), '4.7.0');
    wp_enqueue_style('lightbox2', 'https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.10.0/css/lightbox.min.css', array(), '2.10.0');
    wp_enqueue_script('lightbox2', 'https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.10.0/js/lightbox.min.js', array('jquery'), '2.10.0');
    wp_enqueue_style('ismm-styles', plugin_dir_url(__FILE__) . './css/styles.css', array(), '1.0.0');
    wp_enqueue_script('ismm-script', plugin_dir_url(__FILE__) . './js/index.js', array('jquery'), '1.0.0');
    $items = $this->merge_socials_and_images($instance);
    ?>
      <div class="instagram-social-media-matrix">
      <?php foreach ($items as $item) { ?>
        <?php if ($item['type'] === 'image') { ?>
          <a href="<?php echo $item['url']; ?>" data-lightbox="<?php echo esc_attr($this->get_field_name('roadtrip')); ?>" class="media media-<?php echo $item['type']; ?>" target="_blank">
            <div style="background-image: url('<?php echo $item['thumbnail']; ?>');"></div>
          </a>
        <?php } else if ($item['type'] === 'social') { ?>
          <a href="<?php echo $item['url']; ?>" class="media media-<?php echo $item['type']; ?>" target="_blank">
            <i class="<?php echo $item['class']; ?>"></i>
          </a>
        <?php } else if ($item['type'] === 'phrase') { ?>
          <div class="media media-<?php echo $item['type']; ?>">
            <?php echo $item['content']; ?>
          </div>
        <?php } ?>
      <?php } ?>
      </div>
    <?php
  }

  /**
   * Outputs the options form on admin
   *
   * @param array $instance The widget options
   */
  public function form($instance) {
    if (!empty($instance['access_token'])) {
      $access_token = $instance['access_token'];
    } else {
      $access_token = $_GET['access_token'];
      if (!empty($access_token)) {
        $instance['access_token'] = $access_token;
        ?>
        <script>
          setTimeout(function () {
            var id = '#<?php echo esc_attr($this->get_field_id('savewidget')); ?>';
            var submit = $(id);
            if (submit[0]) {
              submit.click();
            }
          }, 3000);
        </script>
      <?php
      }
    }
    $twitter = !empty($instance['twitter']) ? $instance['twitter'] : '';
    $facebook = !empty($instance['facebook']) ? $instance['facebook'] : '';
    $instagram = !empty($instance['instagram']) ? $instance['instagram'] : '';
    $youtube = !empty($instance['youtube']) ? $instance['youtube'] : '';
    $phrase = !empty($instance['phrase']) ? $instance['phrase'] : '';
    $random_images = !empty($instance['random_images']) ? $instance['random_images'] : '';
    ?>
    <?php if (!$access_token) { ?>
      <script>
        if (window.location.hash && window.location.hash.indexOf('#access_token=') !== -1) {
          var accessToken = window.location.hash.replace('#access_token=', '');
          window.location.href = '<?php echo site_url('/wp-admin/widgets.php'); ?>?access_token=' + accessToken;
        }
      </script>
      <p>
        <strong>
          <?php esc_attr_e('Before you continuous, you must authorize Instagram to share your images in this website.', 'text_domain'); ?>
        </strong>
      </p>
      <p>
        <a class="button button-primary" href="<?php echo $this->get_authorize_url(); ?>">
        <?php esc_attr_e('Aurhotize Instagram', 'text_domain'); ?>
        </a>
      </p>
    <?php } else { ?>
      <input class="widefat" id="<?php echo esc_attr($this->get_field_id('access_token')); ?>" name="<?php echo esc_attr($this->get_field_name('access_token')); ?>" type="hidden" value="<?php echo esc_attr($access_token); ?>">
      <p>
        <label for="<?php echo esc_attr($this->get_field_id('twitter')); ?>"><?php esc_attr_e('Twitter', 'text_domain'); ?></label> 
        <input class="widefat" id="<?php echo esc_attr($this->get_field_id('twitter')); ?>" name="<?php echo esc_attr($this->get_field_name('twitter')); ?>" type="text" value="<?php echo esc_attr($twitter); ?>">
      </p>
      <p>
        <label for="<?php echo esc_attr($this->get_field_id('facebook')); ?>"><?php esc_attr_e('Facebook', 'text_domain'); ?></label> 
        <input class="widefat" id="<?php echo esc_attr($this->get_field_id('facebook')); ?>" name="<?php echo esc_attr($this->get_field_name('facebook')); ?>" type="text" value="<?php echo esc_attr($facebook); ?>">
      </p>
      <p>
        <label for="<?php echo esc_attr($this->get_field_id('instagram')); ?>"><?php esc_attr_e('Instagram', 'text_domain'); ?></label> 
        <input class="widefat" id="<?php echo esc_attr($this->get_field_id('instagram')); ?>" name="<?php echo esc_attr($this->get_field_name('instagram')); ?>" type="text" value="<?php echo esc_attr($instagram); ?>">
      </p>
      <p>
        <label for="<?php echo esc_attr($this->get_field_id('youtube')); ?>"><?php esc_attr_e('Youtube', 'text_domain'); ?></label> 
        <input class="widefat" id="<?php echo esc_attr($this->get_field_id('youtube')); ?>" name="<?php echo esc_attr($this->get_field_name('youtube')); ?>" type="text" value="<?php echo esc_attr($youtube); ?>">
      </p>
      <p>
        <label for="<?php echo esc_attr($this->get_field_id('phrase')); ?>"><?php esc_attr_e('Phrase', 'text_domain'); ?></label> 
        <textarea class="widefat" id="<?php echo esc_attr($this->get_field_id('phrase')); ?>" name="<?php echo esc_attr($this->get_field_name('phrase')); ?>"><?php echo esc_attr($phrase); ?></textarea>
      </p>
      <p>
        <label for="<?php echo esc_attr($this->get_field_id('random_images')); ?>">
          <input class="widefat" id="<?php echo esc_attr($this->get_field_id('random_images')); ?>" name="<?php echo esc_attr($this->get_field_name('random_images')); ?>" type="checkbox" <?php if ($random_images) { ?> checked="checked"<?php } ?>>
          <?php esc_attr_e('Random images', 'text_domain'); ?>
        </label>
      </p>
      <?php } ?>
    <?php
  }

  /**
   * Processing widget options on save
   *
   * @param array $new_instance The new options
   * @param array $old_instance The previous options
   *
   * @return array
   */
  public function update($new_instance, $old_instance) {
    $new_instance['random_images'] = array_key_exists('random_images', $new_instance);
    $merge_instance = array_merge($old_instance, $new_instance);
    $instance = array();
    foreach ($merge_instance as $key => $value) {
      $instance[$key] = !empty($value) ? sanitize_text_field($value) : '';
    }
		return $instance;
  }

}