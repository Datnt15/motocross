<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
add_action("wp_ajax_add_webhook", "add_webhook");
add_action("wp_ajax_nopriv_add_webhook", "add_webhook");
add_action("wp_ajax_get_webhook", "get_webhook");
add_action("wp_ajax_nopriv_get_webhook", "get_webhook");
add_action("wp_ajax_update_webhook", "update_webhook");
add_action("wp_ajax_nopriv_update_webhook", "update_webhook");
add_action("wp_ajax_delete_webhook", "delete_webhook");
add_action("wp_ajax_nopriv_delete_webhook", "delete_webhook");

function add_webhook() {
    $webhook = new WEBHOOK();
    $webhook->addWebhook();
    wp_die();
}

function get_webhook() {
    $webhook = new WEBHOOK();
    $webhook->paging();
    wp_die();
}
function delete_webhook() {
    $webhook = new WEBHOOK();
    $webhook->delete();
    wp_die();
}
function update_webhook() {
    $webhook = new WEBHOOK();
    $webhook->update();
    wp_die();
}
class WEBHOOK{
    private $status = 200;
    private $msg = "OK";
    private $data = null;
    private $url;
    private $id;
    private $model;
    private $apiKey;
    private $postType = "webhook_url";
    function __construct(){
        global $wpdb;
        $this->url    = isset($_REQUEST['webhook_url']) ? $_REQUEST['webhook_url'] : null;
        $this->id     = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : null;
        $this->model  = $wpdb;
        $this->apiKey = md5(time());
    }

    public function addWebhook() {
        if ($this->checkValidWebhookUrl()) {
            $id = wp_insert_post([
                "post_title"   => $this->url,
                "post_type"    => $this->postType,
                "post_excerpt" => $this->apiKey
            ]);
            if ($id) {
                $this->data = $id;
            } else {
                $this->status = 400;
                $this->msg = "Server error, please try later";
            }
        }
    }

    public function paging() {
        if(!$this->checkNonce()) return false;
        $page    = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ?  intval($_REQUEST['page']) : 1;
        $size    = isset($_REQUEST['size']) && !empty($_REQUEST['size']) ?  intval($_REQUEST['size']) : 20;
        $orderBy = isset($_REQUEST['order-by']) && !empty($_REQUEST['order-by']) ?  $_REQUEST['order-by'] : "id";
        $s       = isset($_REQUEST['s']) && !empty($_REQUEST['s']) ?  trim($_REQUEST['s']) : "";
        if (!in_array($orderBy, ['id', 'webhook_url', 'api_key'])) {
            $orderBy = 'id';
        }
        $sort = isset($_REQUEST['sort']) && !empty($_REQUEST['sort']) ?  $_REQUEST['sort'] : "DESC";
        if (!in_array($sort, ['DESC', 'ASC'])) {
            $sort = 'DESC';
        }
        if ($page < 1) {
            $page = 1;
        }
        $offset = ($page - 1) * $size;
        $where  = ["post_type='" . $this->postType . "'"];
        if (!empty($s)) {
            $where[] = "post_title like '%$s%'";
        }
        $where      = implode(" AND ", $where);
        $sql        = "SELECT ID as id, post_title as webhook_url, post_excerpt as api_key FROM " . $this->model->posts . " WHERE $where ORDER BY $orderBy $sort LIMIT $size OFFSET $offset";
        $this->data = $this->model->get_results($sql, ARRAY_A);
    }

    public function callback($id) {
        $where = "post_type='" . $this->postType . "'";
        $tbl   = $this->model->posts;
        $sql   = "SELECT post_title as url FROM $tbl WHERE $where";
        $urls  = $this->model->get_results($sql, ARRAY_A);
        foreach ($urls as $url) {
            $URL = $url['url'];
            $query = wp_parse_url( $URL, PHP_URL_QUERY );
            if ($query) {
                $URL .= "&id=$id";
            } else {
                $URL .= "?id=$id";
            }
            file_get_contents($URL);
        }
    }

    public function delete() {
        $webhook = $this->getPostById($this->id);
        if(!empty($webhook)) {
            $isDeleted = wp_delete_post($this->id);
            if (!$isDeleted) {
                $this->status = 400;
                $this->msg    = "Something went wrong";
            }
        } else {
            $this->status = 404;
            $this->msg    = "Object not found";
        }
    }

    public function update() {
        $webhook = $this->getPostById($this->id);
        if(!empty($webhook)) {
            $isUpdated = wp_update_post([
                "ID"           => $this->id,
                "post_title"   => $this->url,
                "post_type"    => $this->postType,
            ]);
            if (!$isUpdated) {
                $this->status = 400;
                $this->msg = "Server error, please try later";
            }
        } else {
            $this->status = 404;
            $this->msg    = "Object not found";
        }
    }
    
    private function checkNonce() {
        if (!wp_verify_nonce( $_REQUEST['nonce'], "webhook_nonce")) {
            $this->status = 400;
            $this->msg = "Bad request. Missing nonce!";
            return false;
        } else {
            return true;
        }
    }

    private function checkValidWebhookUrl() {
        if (!$this->checkNonce()) {
            return false;
        }
        if (!preg_match(
            "/^(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/",
            $this->url)
        ) {
            $this->msg = "Webhook url is not valid";
            $this->status = 400;
            return false;
        }
        if ($this->checkPostExist()) {
            $this->msg = "Webhook is already existed";
            $this->status = 409;
            return false;
        }
        return true;
    }

    private function getPostById() {
        $sql = "Select * from " . $this->model->posts . " where post_type='" . $this->postType . "' AND ID = $this->id";
        return $this->model->get_results($sql, ARRAY_A);
    }

    private function checkPostExist() {
        $sql = "Select * from " . $this->model->posts . " where post_type='" . $this->postType . "' AND post_title='$this->url'";
        if ($this->id != null) {
            $sql .= " AND ID <> $this->id";
        }
        return !!!empty($this->model->get_results($sql, ARRAY_A));
    }

    function __destruct(){
        echo wp_json_encode([
            'status' => $this->status,
            'msg'    => $this->msg,
            'data'   => $this->data,
        ]);
    }
}
