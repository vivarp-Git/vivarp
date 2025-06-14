<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);
@ini_set("max_execution_time", 0);
@ini_set("memory_limit", "-1");
@set_time_limit(0);
require_once "config.php";
require_once "assets/libraries/DB/vendor/autoload.php";

$wo           = array();
// Connect to SQL Server
$sqlConnect   = $wo["sqlConnect"] = mysqli_connect($sql_db_host, $sql_db_user, $sql_db_pass, $sql_db_name, 3306);
// create new mysql connection
$mysqlMaria   = new Mysql;
// Handling Server Errors
$ServerErrors = array();
if (mysqli_connect_errno()) {
    $ServerErrors[] = "Failed to connect to MySQL: " . mysqli_connect_error();
}
if (!function_exists("curl_init")) {
    $ServerErrors[] = "PHP CURL is NOT installed on your web server !";
}
if (!extension_loaded("gd") && !function_exists("gd_info")) {
    $ServerErrors[] = "PHP GD library is NOT installed on your web server !";
}
if (!extension_loaded("zip")) {
    $ServerErrors[] = "ZipArchive extension is NOT installed on your web server !";
}
$query = mysqli_query($sqlConnect, "SET NAMES utf8mb4");
if (isset($ServerErrors) && !empty($ServerErrors)) {
    foreach ($ServerErrors as $Error) {
        echo "<h3>" . $Error . "</h3>";
    }
    die();
}
$baned_ips = Wo_GetBanned("user");
if (in_array($_SERVER["REMOTE_ADDR"], $baned_ips)) {
    exit();
}
$config    = Wo_GetConfig();
if ($config['developer_mode'] == 1) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}
$db        = new MysqliDb($sqlConnect);
$all_langs = Wo_LangsNamesFromDB();
$wo['iso'] = GetIso();
foreach ($all_langs as $key => $value) {
    $insert = false;
    if (!in_array($value, array_keys($config))) {
        $db->insert(T_CONFIG, array(
            "name" => $value,
            "value" => 1
        ));
        $insert = true;
    }
}
if ($insert == true) {
    $config = Wo_GetConfig();
}
if (isset($_GET["theme"]) && in_array($_GET["theme"], array(
        "default",
        "sunshine",
        "wowonder",
        "wondertag"
    ))) {
    $_SESSION["theme"] = $_GET["theme"];
}
if (isset($_SESSION["theme"]) && !empty($_SESSION["theme"])) {
    $config["theme"] = $_SESSION["theme"];
    if ($_SERVER["REQUEST_URI"] == "/v2/wonderful" || $_SERVER["REQUEST_URI"] == "/v2/wowonder") {
        header("Location: " . $_SERVER["HTTP_REFERER"]);
    }
}
$config["withdrawal_payment_method"] = json_decode($config['withdrawal_payment_method'],true);
// Config Url
$config["theme_url"] = $site_url . "/themes/" . $config["theme"];
$config["site_url"]  = $site_url;
$wo["site_url"]      = $site_url;
$config["wasabi_site_url"]         = "https://s3.".$config["wasabi_bucket_region"].".wasabisys.com";
if (!empty($config["wasabi_bucket_name"])) {
    $config["wasabi_site_url"] = "https://s3.".$config["wasabi_bucket_region"].".wasabisys.com/".$config["wasabi_bucket_name"];
}
$s3_site_url         = "https://test.s3.amazonaws.com";
if (!empty($config["bucket_name"])) {
    $s3_site_url = "https://{bucket}.s3.amazonaws.com";
    $s3_site_url = str_replace("{bucket}", $config["bucket_name"], $s3_site_url);
}
$config["s3_site_url"] = $s3_site_url;
$s3_site_url_2         = "https://test.s3.amazonaws.com";
if (!empty($config["bucket_name_2"])) {
    $s3_site_url_2 = "https://{bucket}.s3.amazonaws.com";
    $s3_site_url_2 = str_replace("{bucket}", $config["bucket_name_2"], $s3_site_url_2);
}
$config["s3_site_url_2"]   = $s3_site_url_2;
$wo["config"]              = $config;
$ccode                     = Wo_CustomCode("g");
$ccode                     = is_array($ccode) ? $ccode : array();
$wo["config"]["header_cc"] = !empty($ccode[0]) ? $ccode[0] : "";
$wo["config"]["footer_cc"] = !empty($ccode[1]) ? $ccode[1] : "";
$wo["config"]["styles_cc"] = !empty($ccode[2]) ? $ccode[2] : "";

$wo["script_version"]      = $wo["config"]["version"];
$http_header               = "http://";
if (!empty($_SERVER["HTTPS"])) {
    $http_header = "https://";
}
$wo["actual_link"] = $http_header . $_SERVER["HTTP_HOST"] . urlencode($_SERVER["REQUEST_URI"]);
// Define Cache Vireble
$cache             = new Cache();
$cache->Wo_OpenCacheDir();
$wo["purchase_code"] = "";
if (!empty($purchase_code)) {
    $wo["purchase_code"] = $purchase_code;
}
// Login With Url
$wo["facebookLoginUrl"]   = $config["site_url"] . "/login-with.php?provider=Facebook";
$wo["twitterLoginUrl"]    = $config["site_url"] . "/login-with.php?provider=Twitter";
$wo["googleLoginUrl"]     = $config["site_url"] . "/login-with.php?provider=Google";
$wo["linkedInLoginUrl"]   = $config["site_url"] . "/login-with.php?provider=LinkedIn";
$wo["VkontakteLoginUrl"]  = $config["site_url"] . "/login-with.php?provider=Vkontakte";
$wo["instagramLoginUrl"]  = $config["site_url"] . "/login-with.php?provider=Instagram";
$wo["QQLoginUrl"]         = $config["site_url"] . "/login-with.php?provider=QQ";
$wo["WeChatLoginUrl"]     = $config["site_url"] . "/login-with.php?provider=WeChat";
$wo["DiscordLoginUrl"]    = $config["site_url"] . "/login-with.php?provider=Discord";
$wo["MailruLoginUrl"]     = $config["site_url"] . "/login-with.php?provider=Mailru";
$wo["OkLoginUrl"]         = $config["site_url"] . "/login-with.php?provider=OkRu";
$wo["TikTokLoginUrl"]         = $config["site_url"] . "/login-with.php?provider=TikTok";
$wo["WordpressLoginUrl"]         = $config["site_url"] . "/login-with.php?provider=WordPress";
// Defualt User Pictures
$wo["userDefaultAvatar"]  = "upload/photos/d-avatar.jpg";
$wo["userDefaultBlur"]  = "upload/photos/blur.jpg";
$wo["userDefaultFAvatar"] = "upload/photos/f-avatar.jpg";
$wo["userDefaultCover"]   = "upload/photos/d-cover.jpg";
$wo["pageDefaultAvatar"]  = "upload/photos/d-page.jpg";
$wo["groupDefaultAvatar"] = "upload/photos/d-group.jpg";
// Get LoggedIn User Data
$wo["loggedin"]           = false;
$langs                    = Wo_LangsNamesFromDB();
if (Wo_IsLogged() == true) {
    $session_id         = !empty($_SESSION["user_id"]) ? $_SESSION["user_id"] : $_COOKIE["user_id"];
    $wo["user_session"] = Wo_GetUserFromSessionID($session_id);
    $wo["user"]         = Wo_UserData($wo["user_session"]);
    if (!empty($wo["user"]["language"])) {
        if (in_array($wo["user"]["language"], $langs)) {
            $_SESSION["lang"] = $wo["user"]["language"];
        }
    }
    if ($wo["user"]["user_id"] < 0 || empty($wo["user"]["user_id"]) || !is_numeric($wo["user"]["user_id"]) || Wo_UserActive($wo["user"]["username"]) === false) {
        header("Location: " . Wo_SeoLink("index.php?link1=logout"));
    }
    $wo["loggedin"] = true;
} else {
    $wo["userSession"] = getUserProfileSessionID();
}
if (!empty($_GET["c_id"]) && !empty($_GET["user_id"])) {
    $application = "windows";
    if (!empty($_GET["application"])) {
        if ($_GET["application"] == "phone") {
            $application = Wo_Secure($_GET["application"]);
        }
    }
    $c_id             = Wo_Secure($_GET["c_id"]);
    $user_id          = Wo_Secure($_GET["user_id"]);
    $check_if_session = Wo_CheckUserSessionID($user_id, $c_id, $application);
    if ($check_if_session === true) {
        $wo["user"]          = Wo_UserData($user_id);
        $session             = Wo_CreateLoginSession($user_id);
        $_SESSION["user_id"] = $session;
        setcookie("user_id", $session, time() + 10 * 365 * 24 * 60 * 60);
        if ($wo["user"]["user_id"] < 0 || empty($wo["user"]["user_id"]) || !is_numeric($wo["user"]["user_id"]) || Wo_UserActive($wo["user"]["username"]) === false) {
            header("Location: " . Wo_SeoLink("index.php?link1=logout"));
        }
        $wo["loggedin"] = true;
    }
}
if (!empty($_POST["user_id"]) && (!empty($_POST["s"]) || !empty($_POST["access_token"]))) {
    $application  = "windows";
    $access_token = !empty($_POST["s"]) ? $_POST["s"] : $_POST["access_token"];
    if (!empty($_GET["application"])) {
        if ($_GET["application"] == "phone") {
            $application = Wo_Secure($_GET["application"]);
        }
    }
    if ($application == "windows") {
        $access_token = $access_token;
    }
    $s                = Wo_Secure($access_token);
    $user_id          = Wo_Secure($_POST["user_id"]);
    $check_if_session = Wo_CheckUserSessionID($user_id, $s, $application);
    if ($check_if_session === true) {
        $wo["user"] = Wo_UserData($user_id);
        if ($wo["user"]["user_id"] < 0 || empty($wo["user"]["user_id"]) || !is_numeric($wo["user"]["user_id"]) || Wo_UserActive($wo["user"]["username"]) === false) {
            $json_error_data = array(
                "api_status" => "400",
                "api_text" => "failed",
                "errors" => array(
                    "error_id" => "7",
                    "error_text" => "User id is wrong."
                )
            );
            header("Content-type: application/json");
            echo json_encode($json_error_data, JSON_PRETTY_PRINT);
            exit();
        }
        $wo["loggedin"] = true;
    } else {
        $json_error_data = array(
            "api_status" => "400",
            "api_text" => "failed",
            "errors" => array(
                "error_id" => "6",
                "error_text" => "Session id is wrong."
            )
        );
        header("Content-type: application/json");
        echo json_encode($json_error_data, JSON_PRETTY_PRINT);
        exit();
    }
}
// Language Function
if (isset($_GET["lang"]) and !empty($_GET["lang"])) {
    if (in_array($_GET["lang"], array_keys($wo["config"])) && $wo["config"][$_GET["lang"]] == 1) {
        $lang_name = Wo_Secure(strtolower($_GET["lang"]));
        if (in_array($lang_name, $langs)) {
            Wo_CleanCache();
            $_SESSION["lang"] = $lang_name;
            if ($wo["loggedin"] == true) {
                mysqli_query($sqlConnect, "UPDATE " . T_USERS . " SET `language` = '" . $lang_name . "' WHERE `user_id` = " . Wo_Secure($wo["user"]["user_id"]));
                cache($wo["user"]["user_id"], 'users', 'delete');
            }
        }
    }
}
if ($wo["loggedin"] == true && $wo["config"]["cache_sidebar"] == 1) {
    if (!empty($_COOKIE["last_sidebar_update"])) {
        if ($_COOKIE["last_sidebar_update"] < time() - 120) {
            Wo_CleanCache();
        }
    } else {
        Wo_CleanCache();
    }
}
if (empty($_SESSION["lang"])) {
    $_SESSION["lang"] = $wo["config"]["defualtLang"];
}
$wo["language"]      = $_SESSION["lang"];
$wo["language_type"] = "ltr";
// Add rtl languages here.
$rtl_langs           = array(
    "arabic",
    "urdu",
    "hebrew",
    "persian"
);
if (!isset($_COOKIE["ad-con"])) {
    setcookie("ad-con", htmlentities(json_encode(array(
        "date" => date("Y-m-d"),
        "ads" => array()
    ))), time() + 10 * 365 * 24 * 60 * 60);
}
$wo["ad-con"] = array();
if (!empty($_COOKIE["ad-con"])) {
    $wo["ad-con"] = json_decode(html_entity_decode($_COOKIE["ad-con"]));
    $wo["ad-con"] = ToArray($wo["ad-con"]);
}
if (!is_array($wo["ad-con"]) || !isset($wo["ad-con"]["date"]) || !isset($wo["ad-con"]["ads"])) {
    setcookie("ad-con", htmlentities(json_encode(array(
        "date" => date("Y-m-d"),
        "ads" => array()
    ))), time() + 10 * 365 * 24 * 60 * 60);
}
if (is_array($wo["ad-con"]) && isset($wo["ad-con"]["date"]) && strtotime($wo["ad-con"]["date"]) < strtotime(date("Y-m-d"))) {
    setcookie("ad-con", htmlentities(json_encode(array(
        "date" => date("Y-m-d"),
        "ads" => array()
    ))), time() + 10 * 365 * 24 * 60 * 60);
}
if (!isset($_COOKIE["_us"])) {
    setcookie("_us", time() + 60 * 60 * 24, time() + 10 * 365 * 24 * 60 * 60);
}
if ((isset($_COOKIE["_us"]) && $_COOKIE["_us"] < time()) || 1) {
    setcookie("_us", time() + 60 * 60 * 24, time() + 10 * 365 * 24 * 60 * 60);
}
// checking if corrent language is rtl.
foreach ($rtl_langs as $lang) {
    if ($wo["language"] == strtolower($lang)) {
        $wo["language_type"] = "rtl";
    }
}
// Icons Virables
$error_icon   = '<i class="fa fa-exclamation-circle"></i> ';
$success_icon = '<i class="fa fa-check"></i> ';
// Include Language File
$wo["lang"]   = Wo_LangsFromDB($wo["language"]);
if (file_exists("assets/languages/extra/" . $wo["language"] . ".php")) {
    require "assets/languages/extra/" . $wo["language"] . ".php";
}
if (empty($wo["lang"])) {
    $wo["lang"] = Wo_LangsFromDB();
}
$wo["second_post_button_icon"] = $config["second_post_button"] == "wonder" ? '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-info"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="8"></line></svg>' : '<svg xmlns="http://www.w3.org/2000/svg" width="58.553" height="58.266" viewBox="0 0 58.553 58.266" class="feather flip"> <path d="M-7080.317,1279.764l-26.729-1.173a1.657,1.657,0,0,1-1.55-1.717l1.11-33.374a4.112,4.112,0,0,1,2.361-3.6l.014-.005a13.62,13.62,0,0,1,1.978-.363h.007a9.007,9.007,0,0,0,3.249-.771c2.645-1.845,3.973-4.658,5.259-7.378l.005-.013.031-.061.059-.13.012-.023c.272-.576.61-1.289.944-1.929l0-.007c.576-1.105,2.327-4.46,4.406-5.107a2.3,2.3,0,0,1,.59-.105c.036,0,.072,0,.109,0a2.55,2.55,0,0,1,1.212.324c2.941,1.554,1.212,7.451.561,9.672a38.306,38.306,0,0,1-3.7,8.454l-.71,1.218,18.363.808a3.916,3.916,0,0,1,3.784,3.735,3.783,3.783,0,0,1-1.123,2.834,3.629,3.629,0,0,1-2.559,1.055c-.046,0-.1,0-.145,0h-.027l-2.141-.093-9.331-.41-.075,1.7,9.333.408a3.721,3.721,0,0,1,2.666,1.3,3.855,3.855,0,0,1,.936,2.934,3.779,3.779,0,0,1-3.821,3.38c-.061,0-.122,0-.181-.005l-1.974-.082-8.9-.392-.075,1.7,8.9.39a3.723,3.723,0,0,1,2.666,1.3,3.86,3.86,0,0,1,.937,2.933,3.784,3.784,0,0,1-3.827,3.381c-.057,0-.118,0-.177,0l-1.976-.088-8.472-.372-.075,1.7,8.474.372a3.726,3.726,0,0,1,2.666,1.3,3.857,3.857,0,0,1,.935,2.933,3.782,3.782,0,0,1-3.827,3.381C-7080.2,1279.765-7080.26,1279.765-7080.317,1279.764Zm-38.4,0-.089,0a6.558,6.558,0,0,1-6.193-6.8l.907-27.293a6.446,6.446,0,0,1,2.074-4.553,6.214,6.214,0,0,1,3.954-1.672c.081,0,.17-.005.29-.005s.212,0,.292.005a6.561,6.561,0,0,1,6.192,6.8l-.907,27.293a6.441,6.441,0,0,1-2.072,4.547,6.249,6.249,0,0,1-4.261,1.681Z" transform="translate(7126.251 -1222.75)" fill="none" stroke="currentColor" stroke-width="2.5"></path> </svg>';
$theme_settings                = array();
$theme_settings["theme"]       = "wowonder";
if (file_exists("./themes/" . $config["theme"] . "/layout/404/dont-delete-this-file.json")) {
    $theme_settings = json_decode(file_get_contents("./themes/" . $config["theme"] . "/layout/404/dont-delete-this-file.json"), true);
}
if ($theme_settings["theme"] == "wonderful") {
    $wo["second_post_button_icon"] = $config["second_post_button"] == "wonder" ? "exclamation-circle" : "thumb-down";
}
$wo["second_post_button_text"]  = $config["second_post_button"] == "wonder" ? $wo["lang"]["wonder"] : $wo["lang"]["dislike"];
$wo["second_post_button_texts"] = $config["second_post_button"] == "wonder" ? $wo["lang"]["wonders"] : $wo["lang"]["dislikes"];
$wo["marker"]                   = "?";
if ($wo["config"]["seoLink"] == 0) {
    $wo["marker"] = "&";
}
require_once "assets/includes/data.php";
$wo["emo"]                           = $emo;
$wo["profile_picture_width_crop"]    = 150;
$wo["profile_picture_height_crop"]   = 150;
$wo["profile_picture_image_quality"] = 70;
$wo["redirect"]                      = 0;

$wo["update_cache"]                  = "";
if (!empty($wo["config"]["last_update"])) {
    $update_cache = time() - 21600;
    if ($update_cache < $wo["config"]["last_update"]) {
        $wo["update_cache"] = "?" . sha1(time());
    }
}

// night mode
if (empty($_COOKIE["mode"])) {
    setcookie("mode", "day", time() + 10 * 365 * 24 * 60 * 60, "/");
    $_COOKIE["mode"] = "day";
    $wo["mode_link"] = "night";
    $wo["mode_text"] = $wo["lang"]["night_mode"];
} else {
    if ($_COOKIE["mode"] == "day") {
        $wo["mode_link"] = "night";
        $wo["mode_text"] = $wo["lang"]["night_mode"];
    }
    if ($_COOKIE["mode"] == "night") {
        $wo["mode_link"] = "day";
        $wo["mode_text"] = $wo["lang"]["day_mode"];
    }
}
if (!empty($_GET["mode"])) {
    if ($_GET["mode"] == "day") {
        setcookie("mode", "day", time() + 10 * 365 * 24 * 60 * 60, "/");
        $_COOKIE["mode"] = "day";
        $wo["mode_link"] = "night";
        $wo["mode_text"] = $wo["lang"]["night_mode"];
    } elseif ($_GET["mode"] == "night") {
        setcookie("mode", "night", time() + 10 * 365 * 24 * 60 * 60, "/");
        $_COOKIE["mode"] = "night";
        $wo["mode_link"] = "day";
        $wo["mode_text"] = $wo["lang"]["day_mode"];
    }
}
include_once "assets/includes/onesignal_config.php";

// manage packages
$wo["pro_packages"]       = Wo_GetAllProInfo();
try {
    $wo["genders"]             = Wo_GetGenders($wo["language"], $langs);
    $wo["page_categories"]     = Wo_GetCategories(T_PAGES_CATEGORY);
    $wo["group_categories"]    = Wo_GetCategories(T_GROUPS_CATEGORY);
    $wo["blog_categories"]     = Wo_GetCategories(T_BLOGS_CATEGORY);
    $wo["products_categories"] = Wo_GetCategories(T_PRODUCTS_CATEGORY);
    $wo["job_categories"]      = Wo_GetCategories(T_JOB_CATEGORY);
    $wo["reactions_types"]     = Wo_GetReactionsTypes();
}
catch (Exception $e) {
    $wo["genders"]             = array();
    $wo["page_categories"]     = array();
    $wo["group_categories"]    = array();
    $wo["blog_categories"]     = array();
    $wo["products_categories"] = array();
    $wo["job_categories"]      = array();
    $wo["reactions_types"]     = array();
}
Wo_GetSubCategories();
$wo["config"]["currency_array"]        = (array) json_decode($wo["config"]["currency_array"]);
$wo["config"]["currency_symbol_array"] = (array) json_decode($wo["config"]["currency_symbol_array"]);
$wo["config"]["providers_array"]       = (array) json_decode($wo["config"]["providers_array"]);
if (!empty($wo["config"]["exchange"])) {
    $wo["config"]["exchange"] = (array) json_decode($wo["config"]["exchange"]);
}
$wo["currencies"] = array();
foreach ($wo["config"]["currency_symbol_array"] as $key => $value) {
    $wo["currencies"][] = array(
        "text" => $key,
        "symbol" => $value
    );
}
if (!empty($_GET["theme"])) {
    Wo_CleanCache();
}
$wo["post_colors"] = array();
if ($wo["config"]["colored_posts_system"] == 1) {
    $wo["post_colors"] = Wo_GetAllColors();
}


$wo['manage_pro_features'] = array('funding_request' => 'can_use_funding',
    'job_request' => 'can_use_jobs',
    'game_request' => 'can_use_games',
    'market_request' => 'can_use_market',
    'event_request' => 'can_use_events',
    'forum_request' => 'can_use_forum',
    'groups_request' => 'can_use_groups',
    'pages_request' => 'can_use_pages',
    'audio_call_request' => 'can_use_audio_call',
    'video_call_request' => 'can_use_video_call',
    'offer_request' => 'can_use_offer',
    'blog_request' => 'can_use_blog',
    'movies_request' => 'can_use_movies',
    'story_request' => 'can_use_story',
    'stickers_request' => 'can_use_stickers',
    'gif_request' => 'can_use_gif',
    'gift_request' => 'can_use_gift',
    'nearby_request' => 'can_use_nearby',
    'video_upload_request' => 'can_use_video_upload',
    'audio_upload_request' => 'can_use_audio_upload',
    'shout_box_request' => 'can_use_shout_box',
    'colored_posts_request' => 'can_use_colored_posts',
    'poll_request' => 'can_use_poll',
    'live_request' => 'can_use_live',
    'profile_background_request' => 'can_use_background',
    'affiliate_request' => 'can_use_affiliate',
    'chat_request' => 'can_use_chat',
    'ai_image_use' => 'can_use_ai_image',
    'ai_post_use' => 'can_use_ai_post',
    'ai_user_use' => 'can_use_ai_user',
    'ai_blog_use' => 'can_use_ai_blog',
);
$wo['available_pro_features'] = array();
$wo['available_verified_features'] = array();

foreach ($wo['manage_pro_features'] as $key => $value) {
    $wo['config'][$value] = true;
    if ($wo["loggedin"] && !empty($wo['user'])) {
        if ($wo['config'][$key] == 'verified' && !$wo['user']['verified']) {
            $wo['config'][$value] = false;
        }
        if ($wo['config'][$key] == 'admin' && !$wo['user']['admin']) {
            $wo['config'][$value] = false;
        }
        if ($wo['config'][$key] == 'pro' && !$wo['user']['is_pro']) {
            $wo['config'][$value] = false;
        }
        if ($wo['config'][$key] == 'pro' && $wo['user']['is_pro'] && !empty($wo["pro_packages"][$wo['user']['pro_type']]) && $wo["pro_packages"][$wo['user']['pro_type']][$value] != 1) {
            $wo['config'][$value] = false;
        }
        if ($wo['user']['admin']) {
            $wo['config'][$value] = true;
        }
    }
    if ($wo['config'][$key] == 'pro') {
        $wo['available_pro_features'][$key] = $value;
    }
    if ($wo['config'][$key] == 'verified') {
        $wo['available_verified_features'][$key] = $value;
    }
}
if (!$wo['config']['can_use_stickers']) {
    $wo['config']['stickers_system'] = 0;
}
if (!$wo['config']['can_use_gif']) {
    $wo['config']['stickers'] = 0;
}
if (!$wo['config']['can_use_gift']) {
    $wo['config']['gift_system'] = 0;
}
if (!$wo['config']['can_use_nearby']) {
    $wo['config']['find_friends'] = 0;
}
if (!$wo['config']['can_use_video_upload']) {
    $wo['config']['video_upload'] = 0;
}
if (!$wo['config']['can_use_audio_upload']) {
    $wo['config']['audio_upload'] = 0;
}
if (!$wo['config']['can_use_poll']) {
    $wo['config']['post_poll'] = 0;
}
if (!$wo['config']['can_use_background']) {
    $wo['config']['profile_back'] = 0;
}
if (!$wo['config']['can_use_chat']) {
    $wo['config']['chatSystem'] = 0;
}
if ($wo['config']['ai_image_system'] == 0 && in_array('ai_image_use',array_keys($wo['available_pro_features']))) {
    unset($wo['available_pro_features']['ai_image_use']);
}
if ($wo['config']['ai_post_system'] == 0 && in_array('ai_post_use',array_keys($wo['available_pro_features']))) {
    unset($wo['available_pro_features']['ai_post_use']);
}
if ($wo['config']['ai_user_system'] == 0 && in_array('ai_user_use',array_keys($wo['available_pro_features']))) {
    unset($wo['available_pro_features']['ai_user_use']);
}
if ($wo['config']['ai_blog_system'] == 0 && in_array('ai_blog_use',array_keys($wo['available_pro_features']))) {
    unset($wo['available_pro_features']['ai_blog_use']);
}
if (!$wo['config']['can_use_ai_image']) {
    $wo['config']['ai_image_system'] = 0;
}
if (!$wo['config']['can_use_ai_post']) {
    $wo['config']['ai_post_system'] = 0;
}
if (!$wo['config']['can_use_ai_user']) {
    $wo['config']['ai_user_system'] = 0;
}
if (!$wo['config']['can_use_ai_blog']) {
    $wo['config']['ai_blog_system'] = 0;
}

$wo['config']['report_reasons'] = json_decode($wo['config']['report_reasons'],true);


$wo['config']['filesVersion'] = "4.3.4";

if ($wo['config']['filesVersion'] != $wo['config']['version']) {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

$wo['reserved_usernames'] = array();
if (!empty($wo['config']['reserved_usernames'])) {
    $wo['reserved_usernames'] = explode(',', $wo['config']['reserved_usernames']);
}

// $wo['countries_ads'] = getCountriesAds();

$wo['watched_reels'] = array();
if (!empty($_COOKIE['watched_reels'])) {
    $wo['watched_reels'] = json_decode($_COOKIE['watched_reels'],true);
}

$wo['hiddenConfig'] = $wo['config'];
$wo['have_monetization'] = 0;
if ($wo['config']['monetization'] == 1 && $wo["loggedin"]) {
    $wo['have_monetization'] = $wo['user']['have_monetization'];
}