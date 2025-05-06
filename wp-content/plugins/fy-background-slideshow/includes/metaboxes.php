<?php
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook === 'post-new.php' || $hook === 'post.php') {
        global $post;
        if ($post->post_type === 'bg_slideshow') {
            wp_enqueue_style('fybs-backend', plugin_dir_url(__DIR__) . 'style.css');
            wp_enqueue_media(); // <-- nécessaire pour la médiathèque
            wp_enqueue_script('jquery-ui-sortable'); // <-- nécessaire pour le tri des images
        }
    }
});

add_action('add_meta_boxes', function() {
    add_meta_box('fybs_metabox', 'Paramètres du diaporama', 'fybs_render_metabox', 'bg_slideshow', 'normal', 'default');
});

function fybs_render_metabox($post) {
    $class = get_post_meta($post->ID, '_fybs_class', true);
    $images = get_post_meta($post->ID, '_fybs_images', true);
    $transition = get_post_meta($post->ID, '_fybs_transition', true);
    $duration = get_post_meta($post->ID, '_fybs_duration', true);
    wp_nonce_field('fybs_save_metabox', 'fybs_nonce');
    ?>
    <p><label>Classe CSS cible :</label><br>
    <input type="text" name="fybs_class" value="<?php echo esc_attr($class); ?>" style="width:100%;" /></p>
    
    <p><label>Images du diaporama :</label><br>
    <input type="hidden" name="fybs_images" id="fybs_images" value="<?php echo esc_attr($images); ?>" />
    <button type="button" class="button" id="fybs_select_images">Choisir des images</button>
    <div id="fybs_preview" style="margin-top:10px; padding:10px; border:1px solid #ddd; min-height:100px;">
        <!-- Les images seront insérées ici -->
    </div></p>

    <p><label>Effet de transition :</label><br>
    <select name="fybs_transition">
        <option value="fade" <?php selected($transition, 'fade'); ?>>Fondu</option>
        <option value="slide-left" <?php selected($transition, 'slide-left'); ?>>Glissement gauche</option>
        <option value="slide-top" <?php selected($transition, 'slide-top'); ?>>Glissement haut</option>
    </select></p>

    <p><label>Durée entre chaque image (en ms) :</label><br>
    <input type="number" name="fybs_duration" value="<?php echo esc_attr($duration ?: 5000); ?>" /></p>
    
    <script>
    jQuery(document).ready(function($) {
        let frame;
        const images = '<?php echo esc_js($images); ?>'; // Récupérer les IDs des images enregistrées

        // Si des images existent, les afficher dans l'ordre des IDs enregistrés
        if (images) {
            const imageIds = images.split(','); // Séparer les IDs d'images
            let promises = [];

            // Pour chaque ID d'image, effectuer une requête AJAX pour récupérer l'URL
            imageIds.forEach(function(id) {
                promises.push(
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        data: {
                            action: 'get_attachment_url',
                            attachment_id: id
                        },
                        success: function(response) {
                            const imgPreview = '<div class="fybs-image-item" data-id="'+id+'" id="img-'+id+'"><img src="'+response+'"> <span class="fybs-delete">&#10005;</span></div>';
                            $('#fybs_preview').append(imgPreview);
                        }
                    })
                );
            });

            // Une fois toutes les requêtes AJAX terminées, réorganiser les images
            $.when.apply($, promises).done(function() {
                // Réorganiser les images dans l'ordre
                const sortedImages = $('#fybs_preview .fybs-image-item').sort(function(a, b) {
                    const aId = $(a).data('id');
                    const bId = $(b).data('id');
                    return imageIds.indexOf(aId.toString()) - imageIds.indexOf(bId.toString());
                });
                $('#fybs_preview').html(sortedImages); // Réafficher les images dans l'ordre correct
            });
        }

        // Sélection des images via la médiathèque
        $('#fybs_select_images').on('click', function(e) {
            e.preventDefault();
            if (frame) { frame.open(); return; }
            frame = wp.media({
                title: 'Choisir des images',
                multiple: true,
                library: { type: 'image' },
                button: { text: 'Utiliser ces images' }
            });
            frame.on('select', function() {
                const attachments = frame.state().get('selection').toJSON();
                const currentIds = $('#fybs_images').val().split(',').filter(Boolean); // Récupérer les ids déjà existants
                const newIds = attachments.map(a => a.id); // Récupérer les nouveaux ids sélectionnés
                const allIds = currentIds.concat(newIds); // Ajouter les nouveaux ids aux existants
                const ids = allIds.join(','); // Créer la chaîne d'ids séparée par des virgules

                const previews = attachments.map(a => {
                    return '<div class="fybs-image-item" data-id="'+a.id+'" id="img-'+a.id+'"><img src="'+a.sizes.thumbnail.url+'"> <span class="fybs-delete">&#10005;</span></div>';
                });

                $('#fybs_images').val(ids); // Mettre à jour la valeur des ids
                $('#fybs_preview').append(previews.join('')); // Ajouter les nouvelles images à l'aperçu
                makeImagesSortable(); // Reinitialiser la fonctionnalité sortable
            });
            frame.open();
        });

        // Fonction pour rendre les images déplaçables
        function makeImagesSortable() {
            $('#fybs_preview').sortable({
                items: '.fybs-image-item',
                tolerance: 'pointer',
                axis: 'x',
                scroll: true,
                start: function() {
                    $('#fybs_preview').addClass('sortable-active');
                },
                stop: function() {
                    $('#fybs_preview').removeClass('sortable-active');
                },
                update: function(event, ui) {
                    const sortedIds = $('#fybs_preview .fybs-image-item').map(function() {
                        return $(this).data('id');
                    }).get().join(',');
                    $('#fybs_images').val(sortedIds);
                }
            });
        }

        // Initialiser la fonctionnalité sortable
        makeImagesSortable();

        // Suppression d'une image
        $('#fybs_preview').on('click', '.fybs-delete', function() {
            const item = $(this).closest('.fybs-image-item');
            item.remove();
            const updatedIds = $('#fybs_preview .fybs-image-item').map(function() {
                return $(this).data('id');
            }).get().join(',');
            $('#fybs_images').val(updatedIds);
        });
    });
    </script>
    <?php
}

// Récupérer l'URL de l'image par son ID
add_action('wp_ajax_get_attachment_url', function() {
    if (isset($_GET['attachment_id'])) {
        $attachment_id = $_GET['attachment_id'];
        $url = wp_get_attachment_url($attachment_id);
        echo $url;
    }
    wp_die(); // Fin de la requête AJAX
});

add_action('save_post',  function($post_id) {
    if (!isset($_POST['fybs_nonce']) || !wp_verify_nonce($_POST['fybs_nonce'], 'fybs_save_metabox')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    // Enregistrer la classe CSS
    update_post_meta($post_id, '_fybs_class', sanitize_text_field($_POST['fybs_class']));

    // Enregistrer les images du diaporama
    if (isset($_POST['fybs_images'])) {
        $ids = preg_replace('/[^0-9,]/', '', $_POST['fybs_images']); // Nettoie les IDs
        update_post_meta($post_id, '_fybs_images', $ids);
    }

    // Enregistrer la transition
    update_post_meta($post_id, '_fybs_transition', sanitize_text_field($_POST['fybs_transition']));

    // Enregistrer la durée
    update_post_meta($post_id, '_fybs_duration', intval($_POST['fybs_duration']));
});