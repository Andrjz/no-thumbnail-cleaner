<?php
/*
Plugin Name: No Thumbnail Cleaner
Plugin URI: https://github.com/Andrjz/no-thumbnail-cleaner
Description: Scans all posts and sends to trash those without a featured image. Processes batches in configurable sizes (default 20) without saving to DB and shows real-time progress.
Version: 1.2
Author: Andrjz
Author URI: https://github.com/Andrjz
*/

if (!defined('ABSPATH')) exit;

// Admin menu
add_action('admin_menu', function() {
    add_menu_page(
        'No Thumbnail Cleaner',
        'No Thumbnail Cleaner',
        'manage_options',
        'no-thumbnail-cleaner',
        'ntc_admin_page',
        'dashicons-trash',
        80
    );
});

// Admin page
function ntc_admin_page() {
    ?>
    <div class="wrap">
        <h1>No Thumbnail Cleaner</h1>
        <p>Processes posts in configurable batches (default <strong>20</strong>, recommended) and shows real-time progress. Nothing is saved to the database.</p>
        <div id="ntc-settings" style="margin-bottom:15px;">
            <label for="ntc-batch-size"><strong>Batch size:</strong></label>
            <input type="number" id="ntc-batch-size" value="20" min="1" max="500" style="width:80px;">
            <span style="color:#888;">(default 20 – recommended)</span><br><br>

            <label for="ntc-order"><strong>Order:</strong></label>
            <select id="ntc-order" style="width:160px;">
                <option value="desc" selected>Newest to Oldest</option>
                <option value="asc">Oldest to Newest</option>
            </select>
        </div>

        <div id="ntc-status" style="margin-top:20px;padding:15px;background:#fff;border:1px solid #ccd0d4;border-radius:4px;">
            <p><strong>Status:</strong> <span id="ntc-state">Ready to start</span></p>
            <p><strong>Checked:</strong> <span id="ntc-checked">0</span></p>
            <p><strong>Deleted:</strong> <span id="ntc-deleted">0</span></p>
        </div>
        <button id="ntc-start" class="button button-primary" style="margin-top:15px;">Start Cleanup</button>
    </div>

    <script>
    let totalChecked = 0;
    let totalDeleted = 0;
    let lastId = 0;
    let processing = false;

    document.getElementById('ntc-start').addEventListener('click', function() {
        if (processing) return;
        processing = true;
        this.disabled = true;
        this.innerText = "Processing...";
        lastId = 0;
        totalChecked = 0;
        totalDeleted = 0;
        processBatch();
    });

    function processBatch() {
        const batchSize = document.getElementById('ntc-batch-size').value || 20;
        const order = document.getElementById('ntc-order').value;

        fetch(ajaxurl + '?action=ntc_process_batch&last_id=' + lastId + '&batch_size=' + batchSize + '&order=' + order)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                totalChecked += data.data.checked;
                totalDeleted += data.data.deleted;
                lastId = data.data.last_id;

                document.getElementById('ntc-state').innerText = data.data.finished ? "✅ Completed" : "🔄 Processing...";
                document.getElementById('ntc-checked').innerText = totalChecked;
                document.getElementById('ntc-deleted').innerText = totalDeleted;

                if (!data.data.finished) {
                    setTimeout(processBatch, 200);
                } else {
                    document.getElementById('ntc-start').disabled = false;
                    document.getElementById('ntc-start').innerText = "Restart Cleanup";
                    processing = false;
                }
            } else {
                document.getElementById('ntc-state').innerText = "❌ AJAX response error";
                document.getElementById('ntc-start').disabled = false;
                document.getElementById('ntc-start').innerText = "Retry";
                processing = false;
            }
        })
        .catch(err => {
            document.getElementById('ntc-state').innerText = "❌ Error: " + err;
            document.getElementById('ntc-start').disabled = false;
            document.getElementById('ntc-start').innerText = "Retry";
            processing = false;
        });
    }
    </script>
    <?php
}

// Process batch AJAX
add_action('wp_ajax_ntc_process_batch', function() {
    global $wpdb;

    $last_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;
    $batch_size = isset($_GET['batch_size']) ? max(1, intval($_GET['batch_size'])) : 20;
    $order = ($_GET['order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

    $checked = 0;
    $deleted = 0;

    if ($order === 'ASC') {
        $post_ids = $wpdb->get_col($wpdb->prepare(
            "
            SELECT p.ID FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm
              ON p.ID = pm.post_id AND pm.meta_key = '_thumbnail_id'
            WHERE p.post_type = 'post'
              AND p.post_status = 'publish'
              AND p.ID > %d
            ORDER BY p.ID ASC
            LIMIT %d
            ",
            $last_id,
            $batch_size
        ));
    } else {
        $post_ids = $wpdb->get_col($wpdb->prepare(
            "
            SELECT p.ID FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm
              ON p.ID = pm.post_id AND pm.meta_key = '_thumbnail_id'
            WHERE p.post_type = 'post'
              AND p.post_status = 'publish'
              AND p.ID < %d
            ORDER BY p.ID DESC
            LIMIT %d
            ",
            $last_id > 0 ? $last_id : PHP_INT_MAX,
            $batch_size
        ));
    }

    foreach ($post_ids as $post_id) {
        $checked++;
        if (!get_post_meta($post_id, '_thumbnail_id', true)) {
            wp_trash_post($post_id);
            $deleted++;
        }
        $last_id = $post_id;
    }

    $finished = count($post_ids) < $batch_size;

    wp_send_json_success([
        'checked' => $checked,
        'deleted' => $deleted,
        'last_id' => $last_id,
        'finished' => $finished
    ]);
});
