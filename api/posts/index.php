<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
require __DIR__ . '/../vendor/autoload.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// $configuration = [
//     'settings' => [
//         'displayErrorDetails' => true,
//     ],
// ];
// $c = new \Slim\Container($configuration);
// $app = new \Slim\App($c);
$app = new \Slim\App();
$app->get('/', function (Request $request, Response $response, $args) {
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $size = isset($_GET['size']) ? intval($_GET['size']) : 10;
    $key  = isset($_GET['key']) ? $_GET['key'] : "";
    if (!isApiKeyExist($key)) {
        $res = [
            "status" => 403,
            "msg" => "You are not allowed to access this api",
        ];
        $response->getBody()->write(encode($res));
        return $response;
    }
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
    $total  = fetchQuery("SELECT count(ID) as total FROM `wp_posts` WHERE post_status='publish' AND post_type='at_biz_dir'");
    $fields = implode(",", $fields);
    $offset = ($page - 1) * $size;
    $data   = fetchQuery("select $fields from `wp_posts` WHERE post_status='publish' AND post_type='at_biz_dir' ORDER BY ID DESC LIMIT $size OFFSET $offset");
    $res    = [
        "status" => 200,
        "data"   => $data,
        "total"  => $total[0]->total,
    ];
    $response->getBody()->write(encode($res));
    return $response;
});

$app->get('/{id}', function (Request $request, Response $response, $args) {
    $key = isset($_GET['key']) ? $_GET['key'] : "";
    if (!isApiKeyExist($key)) {
        $res = [
            "status" => 403,
            "msg" => "You are not allowed to access this api",
        ];
        $response->getBody()->write(encode($res));
        return $response;
    }
    $id = intval($args['id']);
    if (!is_int($id)) {
        $res = [
            "status" => 400,
            "msg" => "ID must be an integer",
        ];
        $response->getBody()->write(encode($res));
        return $response;
    } else {
        $data = fetchQuery("select * from `wp_posts` WHERE ID=$id AND post_status='publish' AND post_type='at_biz_dir'");
        if (!count($data)) {
            $res = [
                "status" => 404,
                "msg" => "object not found"
            ];
        }
        else {
            $postMeta = fetchQuery("SELECT * from wp_postmeta WHERE post_id=$id");
            $customFields = fetchQuery("SELECT ID,post_title from wp_posts where post_type='atbdp_fields' AND post_status='publish'");
            $featured     = [];
            $listFieldIds = [];
            foreach ($customFields as $field) {
                $listFieldIds[] = $field->ID;
                $featured[$field->ID] = [
                    "title" => $field->post_title,
                    "value" => "",
                ];
            }
            $meta = getPostMeta($postMeta, $listFieldIds, $featured, $data[0]);
        }
        $res = [
            "status" => 200,
            "data"   => $data[0],
            "meta"   => $meta,
        ];
        $response->getBody()->write(encode($res));
        return $response;
    }
});
$app->get('/get-tracks/{offset}/{limit}', function (Request $request, Response $response, $args) {
    $key = isset($_GET['key']) ? $_GET['key'] : "";
    if (!isApiKeyExist($key)) {
        $res = [
            "status" => 403,
            "msg" => "You are not allowed to access this api",
        ];
        $response->getBody()->write(encode($res));
        return $response;
    }
    $fields = [
        'ID',
        'post_title',
        'post_date',
        'post_author',
        'post_content',
        // 'post_name',
        'guid',
    ];
    $fields       = implode(",", $fields);
    $data         = [];
    $limit        = isset($args['limit']) ? intval($args['limit']) : 10;
    $offset       = isset($args['offset']) ? intval($args['offset']) : 0;
    $posts        = fetchQuery("select $fields from `wp_posts` WHERE post_status='publish' AND post_type='at_biz_dir' ORDER BY ID DESC LIMIT $limit OFFSET $offset");
    $customFields = fetchQuery("SELECT ID, post_title from wp_posts where post_type='atbdp_fields' AND post_status='publish'");
    $featured     = [];
    $listFieldIds = [];
    foreach ($customFields as $field) {
        $listFieldIds[] = $field->ID;
        $featured[$field->ID] = [
            "title" => $field->post_title,
            "value" => "",
        ];
    }
    $ids = array_map(function ($item){
        return $item->ID;
    }, $posts);
    $ids = implode(",", $ids);
    $contactKeys  = ['_address', '_website', '_phone', '_email', '_phone2', '_fax', '_email', '_zip', '_social'];
    $addresstKeys = ['_manual_lat', '_manual_lng'];
    $listMetaKeys = array_merge(['_price', '_videourl', '_atbdp_post_views_count'], $contactKeys, $addresstKeys);
    $listMetaKeys = implode("','", $listMetaKeys);
    $listMetaKeys = empty($listMetaKeys) ? "" : "AND meta_key IN ('$listMetaKeys')";
    $metaDatas    = fetchQuery("SELECT * from wp_postmeta WHERE post_id IN ($ids) $listMetaKeys");
    $postMeta     = [];
    foreach ($metaDatas as $meta) {
        $postMeta[$meta->post_id][] = $meta;
    }
    $users = [];
    foreach ($posts as $post) {
        $aliasPost = json_decode(json_encode($post));
        unset($aliasPost->post_content);
        unset($aliasPost->post_author);
        $metaData = getPostMeta($postMeta[$post->ID], $listFieldIds, $featured, $post, $users);
        $data[] = [
            "data" => $aliasPost,
            "meta" => $metaData,
        ];
    }
    $res = [
        "status" => 200,
        "data"   => $data,
    ];
    // die(encode($res));
    $response->getBody()->write(encode($res));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();

function isApiKeyExist($key) {
    $webhook = fetchQuery("select ID from wp_posts where post_type='webhook_url' AND post_excerpt='$key'");
    return !!count($webhook);
}

function encode($data) {
    return json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
}

function getPostMeta($postMeta, $listFieldIds, $featured, $post, &$users = []) {
    $meta         = [];
    $contact      = [];
    $address      = [];
    $price        = null;
    $video        = null;
    $viewcount    = null;
    $contactKeys  = ['_address', '_website', '_phone', '_email', '_phone2', '_fax', '_email', '_zip', '_social'];
    $addresstKeys = ['_manual_lat', '_manual_lng'];
    foreach ($postMeta as $_meta) {
        if (in_array($_meta->meta_key, $contactKeys)) {
            $contact[str_replace("_", '', $_meta->meta_key)] = $_meta->meta_value;
        } else if(in_array($_meta->meta_key, $addresstKeys)){
            $address[str_replace("_manual_", '', $_meta->meta_key)] = $_meta->meta_value;
        } else if(in_array($_meta->meta_key, $listFieldIds)) {
            $featured[$_meta->meta_key]['value'] = $_meta->meta_value;
        } else if($_meta->meta_key == '_price') {
            $price = $_meta->meta_value;
        } else if($_meta->meta_key == '_videourl') {
            $video = $_meta->meta_value;
        } else if($_meta->meta_key == '_atbdp_post_views_count') {
            $viewcount = $_meta->meta_value;
        }
    }
    $meta['thumbnails'] = fetchQuery("SELECT post_title as name, guid as url FROM wp_posts where post_type='attachment' AND post_parent=".$post->ID);
    $meta['contact']    = $contact;
    $meta['location']   = $address;
    $meta['featured']   = $featured;
    $meta['price']      = $price;
    $meta['video']      = $video;
    $meta['view_count'] = $viewcount;
    // Author
    $authorId = $post->post_author;
    if (isset($users[$authorId])) {
        $meta['author'] = $users[$authorId];
    } else {
        $sqlToGetAuthor = "SELECT u.ID as id, u.user_login, u.user_nicename, u.user_email, u.display_name, u.user_registered
            FROM wp_users  u
            INNER JOIN wp_usermeta m ON m.user_id = u.ID
            WHERE u.ID=$authorId
            ORDER BY u.user_registered";
        $meta['author'] = fetchQuery($sqlToGetAuthor)[0];
        $users[$authorId] = $meta['author'];
    }
    $postContent = $post->post_content;
    $postContent = strip_tags($postContent);
    if (preg_match('/\[awesome-weather(.*?)\]/',$postContent)) {
        preg_match('/extended_url=\"(.*?)\"/', $postContent, $match);
        $meta['weather_forecast'] = [
            'url'     => $match[1],
            'content' => trim(preg_replace('/\[(.*?)\]/', '',$postContent)),
        ];
    }
    return (array) $meta;
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

function fetchQuery($query) {
    $conn = model();
    $conn->query("SET NAMES 'utf8'");
    $data = $conn->query($query);
    $res = [];
    if ($data && isset($data->num_rows) && $data->num_rows != null) {
        $i = 0;
        while($row = $data->fetch_object()){
            $res[] = $row;
        }
        $data->close();
    }
    $conn->close();
    return $res;
}