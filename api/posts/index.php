<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
require __DIR__ . '/../vendor/autoload.php';

$app = new \Slim\App();

$app->get('/', function (Request $request, Response $response, $args) {
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $size = isset($_GET['size']) ? intval($_GET['size']) : 10;
    $fields = [
        'ID',
        'post_title',
        'post_date',
        'comment_status',
        'post_name',
        'guid',
        'post_modified',
        'post_parent',
        'menu_order',
        'comment_count',
    ];
    $total = fetchQuery("SELECT count(ID) as total FROM `wp_posts` WHERE post_status='publish' AND post_type='at_biz_dir'");
    $fields = implode(",", $fields);
    $data = fetchQuery("select $fields from `wp_posts` WHERE post_status='publish' AND post_type='at_biz_dir' ORDER BY ID DESC", $page, $size);
    $res = [
        "status" => 200,
        "data"   => $data,
        "total"  => $total[0]->total,
    ];
    $response->getBody()->write(encode($res));
    return $response;
});
$app->get('/{id}', function (Request $request, Response $response, $args) {
    $id = $args['id'];
    $data = fetchQuery("select * from `wp_posts` WHERE ID=$id AND post_status='publish' AND post_type='at_biz_dir'");
    $postMeta = fetchQuery("SELECT * from wp_postmeta WHERE post_id=$id", 1, 10000);
    if (!count($data)) {
        $res = [
            "status" => 404,
            "msg" => "object not found"
        ];
    }
    else {
        $meta = [];
        $customFields = fetchQuery("select ID,post_title from wp_posts where post_type='atbdp_fields' AND post_status='publish'");
        $featured = [];
        $listFieldIds = [];
        foreach ($customFields as $field) {
            $listFieldIds[] = $field->ID;
            $featured[$field->ID] = [
                "title" => $field->post_title,
                "value" => "",
            ];
        }
        $contact = [];
        $address = [];
        $price = null;
        $video = null;
        $viewcount = null;
        $contactKeys = ['_address', '_website', '_phone', '_email', '_phone2', '_fax', '_email', '_zip', '_social'];
        $addresstKeys = ['_manual_lat', '_manual_lng'];
        foreach ($postMeta as $_meta) {
            if (in_array($_meta->meta_key, $contactKeys)) {
                $contact[str_replace("_", '', $_meta->meta_key)] = $_meta->meta_value;
            } else if(in_array($_meta->meta_key, $addresstKeys)){
                $address[str_replace("_manual_", '', $_meta->meta_key)] = $_meta->meta_value;
            } else if(in_array($_meta->meta_key, $listFieldIds)) {
                $featured[$_meta->meta_key]['value'] = $_meta->meta_value;
            } else if($_meta->meta_key=='_price') {
                $price = $_meta->meta_value;
            } else if($_meta->meta_key=='_videourl') {
                $video = $_meta->meta_value;
            } else if($_meta->meta_key=='_atbdp_post_views_count') {
                $viewcount = $_meta->meta_value;
            }
        }
        $meta['contact']    = $contact;
        $meta['location']   = $address;
        $meta['featured']   = $featured;
        $meta['price']      = $price;
        $meta['video']      = $video;
        $meta['view_count'] = $viewcount;
        // Author
        $authorId = $data[0]->post_author;
        $sqlToGetAuthor = "SELECT u.ID as id, u.user_login, u.user_nicename, u.user_email, u.display_name, u.user_registered
            FROM wp_users  u
            INNER JOIN wp_usermeta m ON m.user_id = u.ID
            WHERE u.ID=$authorId
            ORDER BY u.user_registered";
        $meta['author'] = fetchQuery($sqlToGetAuthor)[0];
        $postContent = $data[0]->post_content;
        $postContent = strip_tags($postContent);
        if (preg_match('/\[awesome-weather(.*?)\]/',$postContent)) {
            preg_match('/extended_url=\"(.*?)\"/', $postContent, $match);
            $meta['weather_forecast'] = [
                'url'     => $match[1],
                'content' => trim(preg_replace('/\[(.*?)\]/', '',$postContent)),
            ];
        }
    }
    $res = [
        "status" => 200,
        "data"   => $data[0],
        "meta"   => $meta,
        // "defaultMeta"   => $postMeta,
    ];
    $response->getBody()->write(encode($res));
    return $response;
});
$app->run();

function encode($data) {
    return json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
}

function model() {
    $db   = "wordpress_2";
    $pw   = "Qc06zSv9V#";
    $user = "wordpress_a";
    $host = "localhost";
    $port = 3306;
    $conn = new \mysqli($host, $user, $pw, $db, $port);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

function fetchQuery($query, $page = 1, $size = 10) {
    $offset = ($page - 1) * $size;
    $conn = model();
    $data = $conn->query("$query LIMIT $size OFFSET $offset;");
    $res = [];
    if ($data) {
        while($row = $data->fetch_object()){
            $res[] = $row;
        }
    }
    $data->close();
    return $res;
}