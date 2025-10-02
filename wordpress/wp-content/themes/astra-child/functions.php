<?php
// Fichier functions.php du thème enfant
?>
<?php
function astra_child_enqueue_styles() {
    // Charge le CSS parent Astra
    wp_enqueue_style('astra-parent', get_template_directory_uri() . '/style.css');
    // Charge ton CSS enfant
    wp_enqueue_style('astra-child', get_stylesheet_directory_uri() . '/style.css', array('astra-parent'));
}
add_action('wp_enqueue_scripts', 'astra_child_enqueue_styles');
?>
