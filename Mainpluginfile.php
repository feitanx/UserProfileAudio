<?php
/*
Plugin Name: User Voice Note Profile
Description: Allows users to upload or select a voice note introduction to their profile and use a short code the play it.
Version: 1.1
Author: Victor M
*/

// Create upload form on user profile
add_action('show_user_profile', 'voice_note_profile_field');
add_action('edit_user_profile', 'voice_note_profile_field');

function voice_note_profile_field($user) {
    $voice_note_url = esc_url(get_the_author_meta('voice_note_url', $user->ID));
    ?>
    <h3>Voice Note Introduction</h3>
    <table class="form-table">
        <tr>
            <th><label for="voice_note">Upload or Select Voice Note (MP3 only)</label></th>
            <td>
                <?php if ($voice_note_url): ?>
                    <p>Current: <a href="<?php echo $voice_note_url; ?>" target="_blank">Listen</a></p>
                <?php endif; ?>
                <input type="file" name="voice_note" accept="audio/mpeg">
                <br><br>
                <label for="voice_note_library">OR choose from Media Library:</label>
                <input type="text" name="voice_note_library" id="voice_note_library" value="<?php echo $voice_note_url; ?>" style="width: 80%;">
                <button class="button select-voice-note">Select from Media Library</button>
            </td>
        </tr>
    </table>
    <script>
    jQuery(document).ready(function($){
        $('.select-voice-note').on('click', function(e){
            e.preventDefault();
            var frame = wp.media({
                title: 'Select or Upload Voice Note',
                button: { text: 'Use this audio' },
                multiple: false
            });
            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                $('#voice_note_library').val(attachment.url);
            });
            frame.open();
        });
    });
    </script>
    <?php
}

// Save uploaded or selected voice note
add_action('personal_options_update', 'save_voice_note_profile_field');
add_action('edit_user_profile_update', 'save_voice_note_profile_field');

function save_voice_note_profile_field($user_id) {
    if (!current_user_can('edit_user', $user_id)) return false;

    if (!empty($_FILES['voice_note']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        $uploaded = media_handle_upload('voice_note', 0);

        if (!is_wp_error($uploaded)) {
            update_user_meta($user_id, 'voice_note_url', wp_get_attachment_url($uploaded));
        }
    } elseif (!empty($_POST['voice_note_library'])) {
        update_user_meta($user_id, 'voice_note_url', esc_url_raw($_POST['voice_note_library']));
    }
}

// Show voice note on the user's public profile (e.g., author.php or shortcode)
add_shortcode('user_voice_note', 'display_user_voice_note');
function display_user_voice_note($atts) {
    $atts = shortcode_atts([ 'user_id' => get_current_user_id() ], $atts);
    $url = get_user_meta($atts['user_id'], 'voice_note_url', true);

    if ($url) {
        return '<audio controls><source src="' . esc_url($url) . '" type="audio/mpeg">Your browser does not support the audio element.</audio>';
    } else {
        return '<p>No voice note uploaded.</p>';
    }
}
