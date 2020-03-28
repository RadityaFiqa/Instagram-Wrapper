<?php
/**
 * Version 0.0.1
 * Author Raditya Firman Syaputra
 * Email : radityafiqa4@gmail.com
 */
class Instagram
{

    public function curl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result);
        return $result;
    }

    public function curl_get_contents($url)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);

        $data = curl_exec($ch);
        curl_close($ch);

        return $data;
    }
    public function imageToBase64($image, $mimetype)
    {
        $imageData = base64_encode($this->curl_get_contents($image));
        return 'data:' . $mimetype . ';base64,' . $imageData;
    }

    public function profile($url)
    {
        $url = (strpos($url, '@')) ? str_replace("@", "", $url) : $url;
        $url = (strpos($url, 'instagram.com')) ? $url : 'https://instagram.com/' . $url;
        $url = (strpos($url, '/?')) ? $url . '&__a=1' : $url . '/?__a=1';
        $json = $this->curl($url);
        if ($json == null) {
            echo json_encode(array('status' => false, 'message' => 'username/url not_found'));
        } else {
            $timeline = [];
            if (!empty($json->graphql->user->edge_owner_to_timeline_media->edges)) {
                for ($x = 0; $x < count($json->graphql->user->edge_owner_to_timeline_media->edges); $x++) {
                    $timeline[$x]['id_post'] = $json->graphql->user->edge_owner_to_timeline_media->edges[$x]->node->id;
                    $timeline[$x]['taken_at'] = $json->graphql->user->edge_owner_to_timeline_media->edges[$x]->node->taken_at_timestamp;
                    $timeline[$x]['post_url'] = 'https://instagram.com/p/' . $json->graphql->user->edge_owner_to_timeline_media->edges[$x]->node->shortcode;
                    $timeline[$x]['display_url'] = $json->graphql->user->edge_owner_to_timeline_media->edges[$x]->node->display_url;
                    $timeline[$x]['is_video'] = $json->graphql->user->edge_owner_to_timeline_media->edges[$x]->node->is_video;
                    $timeline[$x]['caption'] = $json->graphql->user->edge_owner_to_timeline_media->edges[$x]->node->edge_media_to_caption->edges[0]->node->text;
                    $timeline[$x]['like_count'] = $json->graphql->user->edge_owner_to_timeline_media->edges[$x]->node->edge_liked_by->count;
                    $timeline[$x]['comment_count'] = $json->graphql->user->edge_owner_to_timeline_media->edges[$x]->node->edge_media_to_comment->count;
                    $timeline[$x]['image_contain'] = $json->graphql->user->edge_owner_to_timeline_media->edges[$x]->node->accessibility_caption;
                }
            }
            return json_encode(array(
                'nama' => $json->graphql->user->full_name,
                'id' => $json->graphql->user->id,
                'biography' => $json->graphql->user->biography,
                'profile_pic' => $json->graphql->user->profile_pic_url_hd,
                'following' => $json->graphql->user->edge_follow->count,
                'follower' => $json->graphql->user->edge_followed_by->count,
                'is_private' => $json->graphql->user->is_private,
                'timeline_count' => $json->graphql->user->edge_owner_to_timeline_media->count,
                'timeline' => $timeline,
            ));
        }
    }

    public function stories($url)
    {
        $url = (strpos($url, '@')) ? str_replace("@", "", $url) : $url;
        $url = (strpos($url, 'instagram.com')) ? $url : 'https://instagram.com/' . $url;
        $url = (strpos($url, '/?')) ? $url . '&__a=1' : $url . '/?__a=1';
        $get = $this->curl($url);
        if ($get == null) {
            return json_encode(array('status' => false, 'message' => 'query mismatch'));
        } else {
            $id = $get->graphql->user->id;
            $url = "https://i.instagram.com/api/v1/feed/user/" . $id . "/reel_media/";
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_POSTFIELDS => "",
                CURLOPT_HTTPHEADER => array(
                    "cache-control: no-cache",
                    "cookie: ig_did=81E9FBF3-A186-4C81-8CEB-DC7D230A8700; mid=Xn4WbQABAAGmE4_8bEnAANzU01oX; csrftoken=YsHGszbbuTUzZUzsknqlEhFr5dMoD75m; rur=ATN; ds_user_id=32312353284; sessionid=32312353284%3ANQTx4y0L4ybXMF%3A19; ds_user=mberrjaya",
                    "user-agent: Instagram 10.8.0 Android (18/4.3; 320dpi; 720x1280; Xiaomi; HM 1SW; armani; qcom; en_US)",
                ),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err) {
                return json_encode(array('status' => false, 'message' => 'curl error'));
            } else {
                $json = json_decode($response);
                if (empty($json->items)) {
                    return json_encode(array('status' => true, 'data' => ["username" => $json->user->username, "fullname" => $json->user->full_name, "is_private" => $json->user->is_private, "profile_picture" => $json->user->profile_pic_url], "stories" => "not_found"));
                } else {
                    $story = [];
                    for ($x = 0; $x < count($json->items); $x++) {
                        $story[$x]['taken_at'] = $json->items[$x]->taken_at;
                        $story[$x]['media_type'] = $json->items[$x]->media_type;
                        if ($json->items[$x]->media_type == 1) {
                            $story[$x]['url'] = $json->items[$x]->image_versions2->candidates[0]->url;
                            $story[$x]['width'] = $json->items[$x]->image_versions2->candidates[0]->width;
                            $story[$x]['height'] = $json->items[$x]->image_versions2->candidates[0]->height;
                        } else {
                            $story[$x]['url'] = $json->items[$x]->video_versions[0]->url;
                            $story[$x]['width'] = $json->items[$x]->video_versions[0]->width;
                            $story[$x]['height'] = $json->items[$x]->video_versions[0]->height;
                        }
                    }
                    return json_encode(array('status' => true, 'data' => ["username" => $json->user->username, "fullname" => $json->user->full_name, "is_private" => $json->user->is_private, "profile_picture" => $json->user->profile_pic_url], "stories" => [$story]));
                }
            }
        }
    }

    public function media($url, $base64 = false)
    {
        $url = (strpos($url, '/?')) ? $url . '&__a=1' : $url . '/?__a=1';
        $get = $this->curl($url);
        if ($get == null) {
            return json_encode(array('status' => false, 'message' => 'user private'));
        } else if ($get->graphql->shortcode_media->__typename == 'GraphVideo') {
            $url = ($base64) ? $this->imageToBase64($get->graphql->shortcode_media->video_url, 'video/mp4') : $get->graphql->shortcode_media->video_url;
            return json_encode(array('status' => true, 'media_type' => 'video', 'url' => $url));
        } else if ($get->graphql->shortcode_media->__typename == 'GraphImage') {
            $url = ($base64) ? $this->imageToBase64($get->graphql->shortcode_media->display_url, 'image/jpg') : $get->graphql->shortcode_media->display_url;
            return json_encode(array('status' => true, 'media_type' => 'photo', 'url' => $url));
        } else if ($get->graphql->shortcode_media->__typename == 'GraphSidecar') {
            $media = [];
            for ($x = 0; $x < count($get->graphql->shortcode_media->edge_sidecar_to_children->edges); $x++) {
                if ($get->graphql->shortcode_media->edge_sidecar_to_children->edges[$x]->node->__typename == "GraphVideo") {
                    $url = ($base64) ? $this->imageToBase64($get->graphql->shortcode_media->edge_sidecar_to_children->edges[$x]->node->video_url, 'video/mp4') : $get->graphql->shortcode_media->edge_sidecar_to_children->edges[$x]->node->video_url;
                    $media[$x]['url'] = $url;
                    $media[$x]['type'] = 'video';
                } else {
                    $url = ($base64) ? $this->imageToBase64($get->graphql->shortcode_media->edge_sidecar_to_children->edges[$x]->node->display_url, 'image/jpg') : $get->graphql->shortcode_media->edge_sidecar_to_children->edges[$x]->node->display_url;
                    $media[$x]['url'] = $url;
                    $media[$x]['type'] = 'foto';
                }
            }

            return json_encode(array('status' => true, 'media_type' => 'slide', "data" => [$media]));
        }
    }

}

$instagram = new Instagram();
if (empty($_GET) && empty($_POST)) {
    echo json_encode(array('status' => false, 'message' => 'variabel unset'));
} else {
    $GET = (!empty($_GET)) ? $_GET : $_POST;

    /** Wrapper Profile */
    if ($GET['scope'] == 'profile' && (!empty($GET['query']))) {
        $query = (!empty($_GET['query']) ? $_GET['query'] : $_POST['query']);
        $get = $instagram->profile($query);
        echo $get;
        /** Wrapper Stories */
    } else if ($GET['scope'] == 'stories' && (!empty($GET['query']))) {
        $query = (!empty($_GET['query']) ? $_GET['query'] : $_POST['query']);
        $get = $instagram->stories($query);
        echo $get;
        /** Wrapper Media Post */
    } else if ($GET['scope'] == 'media' && (!empty($GET['query']))) {
        $query = (!empty($_GET['query']) ? $_GET['query'] : $_POST['query']);
        echo ($query);
        /** IF BASE64 PARAMS SET , IT WILL BE RETURN BASE64 */

        $is_base64 = (!empty($_GET['base64']) ? (isset($_GET['base64'])) : (isset($_POST['base64'])));
        echo $is_base64;
        $get = ($is_base64) ? $instagram->media($query, true) : $instagram->media($query);
        echo $get;
        /** IF MISSMATCH SCOPE */
    } else {
        echo json_encode(array('status' => false, 'message' => 'variabel mismatch'));
    }
}
