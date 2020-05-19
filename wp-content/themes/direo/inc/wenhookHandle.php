<?php 
add_action("wp_ajax_add_webhook", "add_webhook");
add_action("wp_ajax_nopriv_add_webhook", "add_webhook");
add_action("wp_ajax_get_webhook", "get_webhook");
add_action("wp_ajax_nopriv_get_webhook", "get_webhook");
add_action("wp_ajax_update_webhook", "update_webhook");
add_action("wp_ajax_nopriv_update_webhook", "update_webhook");
add_action("wp_ajax_delete_webhook", "delete_webhook");
add_action("wp_ajax_nopriv_delete_webhook", "delete_webhook");

function add_webhook() {
    $status = 200;
    $msg = "OK";
    $data = null;
    // nonce check for an extra layer of security, the function will exit if it fails
    if ( !wp_verify_nonce( $_REQUEST['nonce'], "add_webhook")) {
        $status = 400;
        $msg = "Bad request";
    } else {
        $webhook_url = $_REQUEST['webhook_url'];
    }
    echo wp_json_encode([
        'status' => $status,
        'msg'    => $msg,
        'data'   => $data,
    ]);
    die;
}
function get_webhook() {}
function update_webhook() {}
function delete_webhook() {}