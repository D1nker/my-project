<?php

/**
 * Classe Poll_Widget
 */
class Poll_Widget extends WP_Widget
{
    /**
     * Constructeur
     */
    public function __construct()
    {
        parent::__construct('poll', __('Sondage'), array('description' => __('Module de sondage')));
    }

    /**
     * Affichage du widget
     */
    public function widget($args, $instance)
    {
        echo $args['before_widget'];
        echo $args['before_title'];
        echo apply_filters('widget_title', $instance['title']);
        echo $args['after_title'];

        /** @var $wpdb wpdb */
        global $wpdb;
        $options = $wpdb->get_results("SELECT o.*, IFNULL(r.total,0) as total FROM wp_poll_options o LEFT JOIN wp_poll_results r on o.id=r.option_id");

        if (!isset($_COOKIE['voted']) && !isset($_POST['poll_vote'])): ?>
            <p><?php echo get_option('poll_question') ?></p>
            <form action="" method="post">
                <?php foreach ($options as $option): ?>
                    <p>
                        <label for="poll_<?php echo $option->id ?>"><?php echo $option->label ?></label>
                        <input id="poll_<?php echo $option->id ?>" name="poll_vote" value="<?php echo $option->id ?>" type="radio"/>
                    </p>
                <?php endforeach; ?>
                <input type="submit"/>
            </form>
        <?php else:?>
            <p><?php _e('Résultats :') ?></p>
            <?php foreach ($options as $option): ?>
                <p><?php echo $option->label ?> : <?php echo $option->total ?> <?php _e('vote(s)'); ?></p>
            <?php endforeach ?>
        <?php endif;

        echo $args['after_widget'];
    }

    /**
     * Affichage du formulaire dans l'administration
     */
    public function form($instance)
    {
        $title = isset($instance['title']) ? $instance['title'] : '';
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
        </p>
    <?php
    }
}