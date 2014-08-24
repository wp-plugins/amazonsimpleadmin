<?php
/**
 * Amazon Simple Admin Widget 
 *
 * @author Timo Reith
 */
class WP_Widget_AmazonSimpleAdmin extends WP_Widget {

    /**
     * wpdb object
     * @var object
     */
    protected $_db;

    /**
     * Asa Core object
     * @var AmazonSimpleAdmin
     */
    protected $_asa;

    /**
     * Asa Collection object
     * @var AsaCollection
     */
    protected $_collection;
    
    
    /**
     * Constructor
     */
    function WP_Widget_AmazonSimpleAdmin() 
    {
        global $wpdb, $asa;
        $this->_db = $wpdb;
        
        require_once(dirname(__FILE__) . '/AsaCollection.php');
        $this->_collection = new AsaCollection($this->_db);
        
        $this->_asa = $asa;
        
        $control_ops = array('width' => 300, 'height' => 350);

        $css_class_outer = 'AmazonSimpleAdmin_widget';
        if (get_option('_asa_custom_widget_class') != '') {
            $css_class_outer = get_option('_asa_custom_widget_class');
        }
        $widget_ops = array('classname' => $css_class_outer, 'description' => 'Integrates Asa collections to your sidebar');
        parent::WP_Widget(false, $name = 'AmazonSimpleAdmin', $widget_ops, $control_ops);
    }

    /**
     * Render the frontend output
     * @param unknown_type $args
     * @param unknown_type $instance
     */
    function widget($args, $instance) 
    {
        extract($args);
        $title = apply_filters( 'widget_title', empty($instance['title']) ? '' : $instance['title'], $instance );

        $asa_collection_id        = $instance['asa_collection_id'];
        $asa_collection_items     = (int)$instance['asa_collection_items'];
        $asa_collection_sort_type = $instance['asa_collection_sort_type'];
        $asa_collection_template  = $instance['asa_collection_template'];
        $class_inner              = strip_tags($instance['asa_widget_class_inner']);
        if (empty($class_inner)) {
            $class_inner = 'AmazonSimpleAdmin_widget_inner';
        }
        
        if ((int)$asa_collection_sort_type == 2) {
            $asa_collection_sort_type = 'random';
        } else {
            $asa_collection_sort_type = 'latest';
        }

        $items = ', items='. $asa_collection_items;
        
        $content = '[asa_collection '. $asa_collection_template .', type='. 
            $asa_collection_sort_type . $items .']'. 
            $this->_collection->getLabel($asa_collection_id) . '[/asa_collection]';

        echo $before_widget;
        if ( !empty( $title ) ) { echo $before_title . $title . $after_title; } ?>

            <div class="<?php echo $class_inner; ?>">
            <?php echo $this->_asa->parseContent($content); ?>
            </div>
        
        <?php
        echo $after_widget;
    }

    /**
     * 
     * Enter description here ...
     * @param unknown_type $new_instance
     * @param unknown_type $old_instance
     */
    function update($new_instance, $old_instance) 
    {
        $instance = $old_instance;
        
        $instance['title']                    = strip_tags($new_instance['title']);
        $instance['asa_collection_id']        = strip_tags($new_instance['asa_collection_id']);
        $instance['asa_collection_items']     = strip_tags($new_instance['asa_collection_items']);
        $instance['asa_collection_sort_type'] = strip_tags($new_instance['asa_collection_sort_type']);
        $instance['asa_collection_template']  = strip_tags($new_instance['asa_collection_template']);
        $instance['asa_widget_class_inner']   = strip_tags($new_instance['asa_widget_class_inner']);
        return $instance;
    }

    /**
     * Displays the widget form
     * @param unknown_type $instance
     */
    function form($instance) 
    {
        $collections = $this->_collection->getAll();
        
        $instance = wp_parse_args( (array) $instance, array(
            'title'                    => '', 
            'asa_collection_id'        => '0',
            'asa_collection_items'     => '0',
            'asa_collection_sort_type' => '1',
            'asa_collection_template'  => 'widget',
        ));
        
        $sort_types = array();
        $sort_types[1] = __('Latest added on top', 'asa1');
        $sort_types[2] = __('Random', 'asa1');
        
        $templates = $this->_asa->getAllTemplates();
        
        $asa_items                = strip_tags($instance['asa_collection_items']);
        $title                    = strip_tags($instance['title']);
        $class_inner              = strip_tags($instance['asa_widget_class_inner']);
?>
        <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'asa1'); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></p>
        
        <p>
            <label for="<?php echo $this->get_field_id('asa_collection_id'); ?>"><?php _e('Collection', 'asa1'); ?>: </label>
                <select size="1" name="<?php echo $this->get_field_name('asa_collection_id'); ?>" id="<?php echo $this->get_field_id('asa_collection_id'); ?>" class="widefat">
                    <option value="0" <?php if (0 == $instance['asa_collection_id']) echo "selected='selected' "; ?> >- <?php _e('Choose collection', 'asa1'); ?> -</option>
<?php
                if (count($collections) > 0) {
                    foreach($collections as $k => $v) {
                        $selected = ($k == $instance['asa_collection_id']) ? ' selected="selected"' : '';
                        echo '<option value="'. $k .'"'. $selected .'>'. $v .'</option>'. PHP_EOL; 
                    }
                }
?>
                </select>
        </p>
        
        <p><label for="<?php echo $this->get_field_id('asa_collection_items'); ?>"><?php _e('Number of items (0 = all)', 'asa1'); ?>:</label>
        <input size="3" maxlength="2" id="<?php echo $this->get_field_id('asa_collection_items'); ?>" name="<?php echo $this->get_field_name('asa_collection_items'); ?>" type="text" value="<?php echo esc_attr($asa_items); ?>" /></p>
        
        <p>
            <label for="<?php echo $this->get_field_id('asa_collection_sort_type'); ?>"><?php _e('Sort type', 'asa1'); ?>: </label>
                <select size="1" name="<?php echo $this->get_field_name('asa_collection_sort_type'); ?>" id="<?php echo $this->get_field_id('asa_collection_sort_type'); ?>" class="widefat">
<?php
                foreach($sort_types as $k => $v) {
                    $selected = ($k == $instance['asa_collection_sort_type']) ? ' selected="selected"' : '';
                    echo '<option value="'. $k .'"'. $selected .'>'. $v .'</option>'. PHP_EOL; 
                }
?>
                </select>
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('asa_collection_template'); ?>"><?php _e('Template', 'asa1'); ?>: </label>
                <select size="1" name="<?php echo $this->get_field_name('asa_collection_template'); ?>" id="<?php echo $this->get_field_id('asa_collection_template'); ?>" class="widefat">
<?php
                foreach($templates as $template) {
                    $selected = ($template == $instance['asa_collection_template']) ? ' selected="selected"' : '';
                    echo '<option value="'. $template .'"'. $selected .'>'. $template .'</option>'. PHP_EOL; 
                }
?>
                </select>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('asa_widget_class_inner'); ?>"><?php _e('Custom CSS class for the inner container', 'asa1'); ?>: </label>
            <input class="widefat" id="<?php echo $this->get_field_id('asa_widget_class_inner'); ?>" name="<?php echo $this->get_field_name('asa_widget_class_inner'); ?>" type="text" value="<?php echo esc_attr($class_inner); ?>" />
        </p>
<?php
    }
}

add_action('widgets_init', create_function('', 'return register_widget("WP_Widget_AmazonSimpleAdmin");'));
?>