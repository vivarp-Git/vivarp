<?php
// +------------------------------------------------------------------------+
// | @author Deen Doughouz (DoughouzForest)
// | @author_url 1: http://www.wowonder.com
// | @author_url 2: http://codecanyon.net/user/doughouzforest
// | @author_email: wowondersocial@gmail.com
// +------------------------------------------------------------------------+
// | WoWonder - The Ultimate Social Networking Platform
// | Copyright (c) 2022 WoWonder. All rights reserved.
// +------------------------------------------------------------------------+
/* Script Main Functions (File 1) */
function Wo_GetTerms()
{
    global $sqlConnect;
    $data = array();
    $query = mysqli_query($sqlConnect, "SELECT * FROM " . T_TERMS);
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $data[$fetched_data['type']] = $fetched_data['text'];
        }
    }
    return $data;
}

function Wo_GetHtmlEmails()
{
    global $sqlConnect;
    $data = array();
    $query = mysqli_query($sqlConnect, "SELECT * FROM " . T_HTML_EMAILS);
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $data[$fetched_data['name']] = $fetched_data['value'];
        }
    }
    return $data;
}

function Wo_GetUserFromSessionID($session_id, $platform = 'web')
{
    global $sqlConnect, $db;
    if (empty($session_id)) {
        return false;
    }
    $session_id = Wo_Secure($session_id);
    $query = mysqli_query($sqlConnect, "SELECT * FROM " . T_APP_SESSIONS . " WHERE `session_id` = '{$session_id}' LIMIT 1");
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        if (empty($fetched_data['platform_details']) && $fetched_data['platform'] == 'web') {
            $ua = json_encode(getBrowser());
            if (isset($fetched_data['platform_details'])) {
                $update_session = $db->where('id', $fetched_data['id'])->update(T_APP_SESSIONS, array(
                    'platform_details' => $ua
                ));
            }
        }
        return $fetched_data['user_id'];
    }
    return false;
}

function Wo_GetDataFromSessionID($session_id, $platform = 'web')
{
    global $sqlConnect;
    if (empty($session_id)) {
        return false;
    }
    $platform = Wo_Secure($platform);
    $session_id = Wo_Secure($session_id);
    $data = array();
    $query = mysqli_query($sqlConnect, "SELECT * FROM " . T_APP_SESSIONS . " WHERE `session_id` = '{$session_id}' AND `platform` = '{$platform}' LIMIT 1");
    if (mysqli_num_rows($query)) {
        return mysqli_fetch_assoc($query);
    }
    return false;
}

function Wo_GetSessionDataFromUserID($user_id = 0)
{
    global $sqlConnect;
    if (empty($user_id)) {
        return false;
    }
    $user_id = Wo_Secure($user_id);
    $time = time() - 30;
    $query = mysqli_query($sqlConnect, "SELECT * FROM " . T_APP_SESSIONS . " WHERE `user_id` = '{$user_id}' AND `platform` = 'web' AND `time` > $time LIMIT 1");
    if (mysqli_num_rows($query)) {
        return mysqli_fetch_assoc($query);
    }
    return false;
}

function Wo_GetAllSessionsFromUserID($user_id = 0, $limit = 10, $offset = array())
{
    global $sqlConnect;
    if (empty($user_id)) {
        return false;
    }
    $offset_text = "";
    if (!empty($offset)) {
        $offset_text = implode(',', $offset);
        $offset_text = " AND `id` NOT IN (" . $offset_text . ") ";
    }
    $user_id = Wo_Secure($user_id);
    $query = mysqli_query($sqlConnect, "SELECT * FROM " . T_APP_SESSIONS . " WHERE `user_id` = '{$user_id}' " . $offset_text . " ORDER by time DESC LIMIT " . $limit);
    $data = array();
    if (mysqli_num_rows($query)) {
        while ($row = mysqli_fetch_assoc($query)) {
            $row['browser'] = 'Unknown';
            $row['unx_time'] = $row['time'];
            $row['time'] = Wo_Time_Elapsed_String($row['time']);
            $row['platform'] = ucfirst($row['platform']);
            $row['ip_address'] = '';
            if ($row['platform'] == 'web' || $row['platform'] == 'windows') {
                $row['platform'] = 'Unknown';
            }
            if ($row['platform'] == 'Phone') {
                $row['browser'] = 'Mobile';
            }
            if ($row['platform'] == 'Windows') {
                $row['browser'] = 'Desktop Application';
            }
            if (!empty($row['platform_details'])) {
                $uns = (array)json_decode($row['platform_details']);
                $row['browser'] = $uns['name'];
                $row['platform'] = ucfirst($uns['platform']);
                $row['ip_address'] = $uns['ip_address'];
            }
            $data[] = $row;
        }
    }
    return $data;
}

function Wo_GetPlatformFromUser_ID($user_id = 0)
{
    global $sqlConnect;
    if (empty($user_id)) {
        return false;
    }
    $user_id = Wo_Secure($user_id);
    $query = mysqli_query($sqlConnect, "SELECT `platform` FROM " . T_APP_SESSIONS . " WHERE `user_id` = '{$user_id}' ORDER BY `time` DESC LIMIT 1");
    if (mysqli_num_rows($query)) {
        $mysqli = mysqli_fetch_assoc($query);
        return $mysqli['platform'];
    }
    return false;
}

function Wo_SaveTerm($update_name, $value)
{
    global $wo, $config, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    $update_name = Wo_Secure($update_name);
    $value = mysqli_real_escape_string($sqlConnect, $value);
    $query_one = " UPDATE " . T_TERMS . " SET `text` = '{$value}' WHERE `type` = '{$update_name}'";
    $query = mysqli_query($sqlConnect, $query_one);
    if ($query) {
        return true;
    } else {
        return false;
    }
}

function Wo_SaveHTMLEmails($update_name, $value)
{
    global $wo, $config, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    $update_name = Wo_Secure($update_name);
    $value = mysqli_real_escape_string($sqlConnect, $value);
    $query_one = " UPDATE " . T_HTML_EMAILS . " SET `value` = '{$value}' WHERE `name` = '{$update_name}'";
    $query = mysqli_query($sqlConnect, $query_one);
    if ($query) {
        return true;
    } else {
        return false;
    }
}

function Wo_GetConfig()
{
    global $sqlConnect;
    $data = array();
    $query = mysqli_query($sqlConnect, "SELECT * FROM " . T_CONFIG);
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $data[$fetched_data['name']] = $fetched_data['value'];
        }
    }
    return $data;
}

function Wo_GetLangDetails($lang_key = '')
{
    global $sqlConnect, $wo;
    if (empty($lang_key)) {
        return false;
    }
    $lang_key = Wo_Secure($lang_key);
    $data = array();
    $query = mysqli_query($sqlConnect, "SELECT * FROM " . T_LANGS . " WHERE `lang_key` = '{$lang_key}'");
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            unset($fetched_data['lang_key']);
            unset($fetched_data['id']);
            unset($fetched_data['type']);
            $data[] = $fetched_data;
        }
    }
    return $data;
}

function Wo_LangsFromDB($lang = 'english')
{
    global $sqlConnect, $wo;
    $data = array();
    if (empty($lang)) {
        $lang = 'english';
    }
    $query = mysqli_query($sqlConnect, "SELECT `lang_key`, `$lang` FROM " . T_LANGS);
    if ($query) {
        if (mysqli_num_rows($query)) {
            while ($fetched_data = mysqli_fetch_assoc($query)) {
                $data[$fetched_data['lang_key']] = htmlspecialchars_decode((string)$fetched_data[$lang]);
            }
        }
    }
    return $data;
}

function sort_alphabetically($a, $b)
{
    return $a['name'] > $b['name'];
}

function Wo_LangsNamesFromDB($lang = 'english')
{
    global $sqlConnect, $wo;
    $data = array();
    $query = mysqli_query($sqlConnect, "SHOW COLUMNS FROM " . T_LANGS);
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $data[] = $fetched_data['Field'];
        }
        unset($data[0]);
        unset($data[1]);
        unset($data[2]);
    }
    asort($data);
    return $data;
}

function Wo_SaveConfig($update_name, $value)
{
    global $wo, $config, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (!array_key_exists($update_name, $config)) {
        return false;
    }
    $update_name = Wo_Secure($update_name);
    $value = mysqli_real_escape_string($sqlConnect, $value);
    $query_one = " UPDATE " . T_CONFIG . " SET `value` = '{$value}' WHERE `name` = '{$update_name}'";
    $query = mysqli_query($sqlConnect, $query_one);
    if ($query) {
        return true;
    } else {
        return false;
    }
}

function Wo_Login($username, $password)
{
    global $sqlConnect;
    if (empty($username) || empty($password)) {
        return false;
    }
    $username = Wo_Secure($username);
    $query_hash = mysqli_query($sqlConnect, "SELECT * FROM " . T_USERS . " WHERE (`username` = '{$username}' OR `email` = '{$username}' OR `phone_number` = '{$username}')");
    if (mysqli_num_rows($query_hash)) {
        $mysqli_hash_upgrade = mysqli_fetch_assoc($query_hash);
        $login_password = '';
        $hash = 'md5';
        if (preg_match('/^[a-f0-9]{32}$/', $mysqli_hash_upgrade['password'])) {
            $hash = 'md5';
        } else if (preg_match('/^[0-9a-f]{40}$/i', $mysqli_hash_upgrade['password'])) {
            $hash = 'sha1';
        } else if (strlen($mysqli_hash_upgrade['password']) == 60) {
            $hash = 'password_hash';
        }
        if ($hash == 'password_hash') {
            if (password_verify($password, $mysqli_hash_upgrade['password'])) {
                return true;
            }
        } else {
            $login_password = Wo_Secure($hash($password));
        }
        $query = mysqli_query($sqlConnect, "SELECT COUNT(`user_id`) FROM " . T_USERS . " WHERE (`username` = '{$username}' OR `email` = '{$username}' OR `phone_number` = '{$username}') AND `password` = '{$login_password}'");
        if (Wo_Sql_Result($query, 0) == 1) {
            if ($hash == 'sha1' || $hash == 'md5') {
                $new_password = Wo_Secure(password_hash($password, PASSWORD_DEFAULT));
                $query_ = mysqli_query($sqlConnect, "UPDATE " . T_USERS . " SET password = '$new_password' WHERE (`username` = '{$username}' OR `email` = '{$username}' OR `phone_number` = '{$username}')");
                cache($mysqli_hash_upgrade['password'], 'users', 'delete');
            }
            return true;
        }
    }
    return false;
}

function Wo_CreateLoginSession($user_id = 0)
{
    global $sqlConnect, $db;
    if (empty($user_id)) {
        return false;
    }
    $user_id = Wo_Secure($user_id);
    $hash = sha1(rand(111111111, 999999999)) . md5(microtime()) . rand(11111111, 99999999) . md5(rand(5555, 9999));
    $query_two = mysqli_query($sqlConnect, "DELETE FROM " . T_APP_SESSIONS . " WHERE `session_id` = '{$hash}'");
    if ($query_two) {
        $ua = json_encode(getBrowser());
        $delete_same_session = $db->where('user_id', $user_id)->where('platform_details', $ua)->delete(T_APP_SESSIONS);
        $query_three = mysqli_query($sqlConnect, "INSERT INTO " . T_APP_SESSIONS . " (`user_id`, `session_id`, `platform`, `platform_details`, `time`) VALUES('{$user_id}', '{$hash}', 'web', '$ua'," . time() . ")");
        if ($query_three) {
            return $hash;
        }
    }
}

function Wo_IsUserCookie($user_id, $password)
{
    global $sqlConnect;
    if (empty($user_id) || empty($password)) {
        return false;
    }
    $user_id = Wo_Secure($user_id);
    $password = Wo_Secure($password);
    $query = mysqli_query($sqlConnect, "SELECT COUNT(`user_id`) FROM " . T_USERS . " WHERE `user_id` = '{$user_id}' AND `password` = '{$password}'");
    return (Wo_Sql_Result($query, 0) == 1) ? true : false;
}

function Wo_SetLoginWithSession($user_email)
{
    if (empty($user_email)) {
        return false;
    }
    $user_email = Wo_Secure($user_email);
    $_SESSION['user_id'] = Wo_CreateLoginSession(Wo_UserIdFromEmail($user_email));
    setcookie("user_id", $_SESSION['user_id'], time() + (10 * 365 * 24 * 60 * 60));
    setcookie('ad-con', htmlentities(json_encode(array(
        'date' => date('Y-m-d'),
        'ads' => array()
    ))), time() + (10 * 365 * 24 * 60 * 60));
}

function Wo_UserActive($username)
{
    global $sqlConnect;
    if (empty($username)) {
        return false;
    }
    $username = Wo_Secure($username);
    $query = mysqli_query($sqlConnect, "SELECT COUNT(`user_id`) FROM " . T_USERS . "  WHERE (`username` = '{$username}' OR `email` = '{$username}' OR `phone_number` = '{$username}') AND `active` = '1'");
    return (Wo_Sql_Result($query, 0) == 1) ? true : false;
}

function Wo_UserInactive($username)
{
    global $sqlConnect;
    if (empty($username)) {
        return false;
    }
    $username = Wo_Secure($username);
    $query = mysqli_query($sqlConnect, "SELECT COUNT(`user_id`) FROM " . T_USERS . "  WHERE (`username` = '{$username}' OR `email` = '{$username}' OR `phone_number` = '{$username}') AND `active` = '2'");
    return (Wo_Sql_Result($query, 0) == 1) ? true : false;
}

function Wo_UserExists($username)
{
    global $sqlConnect;
    if (empty($username)) {
        return false;
    }
    $username = Wo_Secure($username);
    $query = mysqli_query($sqlConnect, "SELECT COUNT(`user_id`) FROM " . T_USERS . " WHERE `username` = '{$username}'");
    return (Wo_Sql_Result($query, 0) == 1) ? true : false;
}

function Wo_IsUserComplete($user_id)
{
    global $sqlConnect;
    if (empty($user_id)) {
        return false;
    }
    $user_id = Wo_Secure($user_id);
    $query = mysqli_query($sqlConnect, "SELECT COUNT(`user_id`) FROM " . T_USERS . " WHERE `user_id` = '{$user_id}' AND `start_up` = '0'");
    return (Wo_Sql_Result($query, 0) == 1) ? true : false;
}

function Wo_UserIdFromUsername($username)
{
    global $sqlConnect;
    if (empty($username)) {
        return false;
    }
    $username = Wo_Secure($username);
    $query = mysqli_query($sqlConnect, "SELECT `user_id` FROM " . T_USERS . " WHERE `username` = '{$username}'");
    return Wo_Sql_Result($query, 0, 'user_id');
}

function Wo_UserIdFromPhoneNumber($phone_number)
{
    global $sqlConnect;
    if (empty($phone_number)) {
        return false;
    }
    $phone_number = Wo_Secure($phone_number);
    $query = mysqli_query($sqlConnect, "SELECT `user_id` FROM " . T_USERS . " WHERE `phone_number` = '{$phone_number}'");
    return Wo_Sql_Result($query, 0, 'user_id');
}

function Wo_UserNameFromPhoneNumber($phone_number)
{
    global $sqlConnect;
    if (empty($phone_number)) {
        return false;
    }
    $phone_number = Wo_Secure($phone_number);
    $query = mysqli_query($sqlConnect, "SELECT `username` FROM " . T_USERS . " WHERE `phone_number` = '{$phone_number}'");
    return Wo_Sql_Result($query, 0, 'username');
}

function Wo_UserIdForLogin($username)
{
    global $sqlConnect;
    if (empty($username)) {
        return false;
    }
    $username = Wo_Secure($username);
    $query = mysqli_query($sqlConnect, "SELECT `user_id` FROM " . T_USERS . " WHERE `username` = '{$username}' OR `email` = '{$username}' OR `phone_number` = '{$username}'");
    return Wo_Sql_Result($query, 0, 'user_id');
}

function Wo_UserIdFromEmail($email)
{
    global $sqlConnect;
    if (empty($email)) {
        return false;
    }
    $email = Wo_Secure($email);
    $query = mysqli_query($sqlConnect, "SELECT `user_id` FROM " . T_USERS . " WHERE `email` = '{$email}'");
    return Wo_Sql_Result($query, 0, 'user_id');
}

function Wo_UserIDFromEmailCode($email_code)
{
    global $sqlConnect;
    if (empty($email_code)) {
        return false;
    }
    $email_code = Wo_Secure($email_code);
    $query = mysqli_query($sqlConnect, "SELECT `user_id` FROM " . T_USERS . " WHERE `email_code` = '{$email_code}' AND (`time_code_sent` > '" . time() . "' OR `time_code_sent` = '0')");
    return Wo_Sql_Result($query, 0, 'user_id');
}

function Wo_UserIDFromSMSCode($email_code)
{
    global $sqlConnect;
    if (empty($email_code)) {
        return false;
    }
    $email_code = Wo_Secure($email_code);
    $query = mysqli_query($sqlConnect, "SELECT `user_id` FROM " . T_USERS . " WHERE `sms_code` = '{$email_code}'");
    return Wo_Sql_Result($query, 0, 'user_id');
}

function Wo_IsBlocked($user_id)
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    $logged_user_id = Wo_Secure($wo['user']['user_id']);
    $user_id = Wo_Secure($user_id);
    $query = mysqli_query($sqlConnect, "SELECT COUNT(`id`) FROM " . T_BLOCKS . " WHERE (`blocker` = {$logged_user_id} AND `blocked` = {$user_id}) OR (`blocker` = {$user_id} AND `blocked` = {$logged_user_id})");
    return (Wo_Sql_Result($query, 0) == 1) ? true : false;
}
function Wo_IsUserBlocked($blocker)
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($blocker) || !is_numeric($blocker) || $blocker < 0) {
        return false;
    }
    $blocked = Wo_Secure($wo['user']['user_id']);
    $blocker = Wo_Secure($blocker);
    $query = mysqli_query($sqlConnect, "SELECT COUNT(`id`) FROM " . T_BLOCKS . " WHERE `blocker` = {$blocker} AND `blocked` = {$blocked}");
    return (Wo_Sql_Result($query, 0) == 1) ? true : false;
}

function Wo_RegisterBlock($user_id)
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    $logged_user_id = Wo_Secure($wo['user']['user_id']);
    $user_id = Wo_Secure($user_id);
    $query = mysqli_query($sqlConnect, "INSERT INTO " . T_BLOCKS . " (`blocker`, `blocked`) VALUES ('{$logged_user_id}', '{$user_id}')");
    return ($query) ? true : false;
}

function Wo_RemoveBlock($user_id)
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    $logged_user_id = Wo_Secure($wo['user']['user_id']);
    $user_id = Wo_Secure($user_id);
    $query = mysqli_query($sqlConnect, "DELETE FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}' AND `blocked` = '{$user_id}'");
    return ($query) ? true : false;
}

function Wo_GetBlockedMembers($user_id = 0)
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    $data = array();
    $logged_user_id = Wo_Secure($wo['user']['user_id']);
    $user_id = Wo_Secure($user_id);
    $query = mysqli_query($sqlConnect, "SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$user_id}'");
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $data[] = Wo_UserData($fetched_data['blocked']);
        }
    }
    return $data;
}

function Wo_EmailExists($email)
{
    global $sqlConnect;
    if (empty($email)) {
        return false;
    }
    $email = Wo_Secure($email);
    $query = mysqli_query($sqlConnect, "SELECT COUNT(`user_id`) FROM " . T_USERS . " WHERE `email` = '{$email}'");
    return (Wo_Sql_Result($query, 0) == 1) ? true : false;
}

function Wo_PhoneExists($phone)
{
    global $sqlConnect;
    if (empty($phone)) {
        return false;
    }
    $phone = Wo_Secure($phone);
    $query = mysqli_query($sqlConnect, "SELECT COUNT(`user_id`) FROM " . T_USERS . " WHERE `phone_number` = '{$phone}'");
    return (Wo_Sql_Result($query, 0) > 0) ? true : false;
}

function Wo_IsOnwerUser($user_id)
{
    global $wo;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    $user_id = Wo_Secure($user_id);
    $logged_user_id = Wo_Secure($wo['user']['user_id']);
    if ($user_id == $logged_user_id) {
        return true;
    } else {
        return false;
    }
}

function Wo_IsOnwer($user_id)
{
    global $wo;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    $user_id = Wo_Secure($user_id);
    $logged_user_id = Wo_Secure($wo['user']['user_id']);
    if (Wo_IsAdmin($logged_user_id) === false) {
        if ($user_id == $logged_user_id) {
            return true;
        } else {
            return false;
        }
    } else {
        return true;
    }
}

function Wo_IsReportExists($id = false, $type = 'user')
{
    global $sqlConnect, $wo;
    if ($wo['loggedin'] == false || !$id || !$type) {
        return false;
    }
    $id = Wo_Secure($id);
    $type = Wo_Secure($type);
    $user = $wo['user']['user_id'];
    $match = null;
    if ($type == 'user') {
        $sql = " SELECT `id` FROM " . T_REPORTS . " WHERE `profile_id` = '{$id}' AND `user_id` = '{$user}'";
        $data_rows = mysqli_query($sqlConnect, $sql);
        $match = mysqli_num_rows($data_rows) > 0;
    } else if ($type == 'page') {
        $sql = " SELECT `id` FROM " . T_REPORTS . " WHERE `page_id` = '{$id}' AND `user_id` = '{$user}'";
        $data_rows = mysqli_query($sqlConnect, $sql);
        $match = mysqli_num_rows($data_rows) > 0;
    } else if ($type == 'group') {
        $sql = " SELECT `id` FROM " . T_REPORTS . " WHERE `group_id` = '{$id}' AND `user_id` = '{$user}'";
        $data_rows = mysqli_query($sqlConnect, $sql);
        $match = mysqli_num_rows($data_rows) > 0;
    }
    return $match;
}

function writeCache($id, $type)
{
    global $wo, $sqlConnect, $cache, $db;
    if (empty($type) || empty($id)) {
        return false;
    }
    $id = md5($id);
    $path = "$type/$id.tmp";

    return $path;
}


function Wo_UserData($user_id, $password = true)
{
    global $wo, $sqlConnect, $cache, $db;
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    $data = array();
    $user_id = Wo_Secure($user_id);
    $query_one = "SELECT * FROM " . T_USERS . " WHERE `user_id` = '{$user_id}'";
    $generateCache = false;
    if ($wo['config']['cacheSystem'] == 1) {
        $fetched_data = cache($user_id, 'users', 'read');
        if (empty($fetched_data)) {
            $generateCache = true;
            $sql = mysqli_query($sqlConnect, $query_one);
            if (mysqli_num_rows($sql)) {
                $fetched_data = mysqli_fetch_assoc($sql);
            }
        } else {
            return $fetched_data;
        }
    } else {
        $sql = mysqli_query($sqlConnect, $query_one);
        if (mysqli_num_rows($sql)) {
            $fetched_data = mysqli_fetch_assoc($sql);
        }
    }
    if (empty($fetched_data)) {
        return array();
    }
    if ($password == false) {
        unset($fetched_data['password']);
    }
    $fetched_data['avatar_post_id'] = 0;
    $fetched_data['cover_post_id'] = 0;
    $query_avatar = mysqli_query($sqlConnect, " SELECT `id`  FROM " . T_POSTS . "  WHERE `postType` = 'profile_picture' AND `user_id` = '{$user_id}' ORDER BY `id` DESC LIMIT 1");
    if (mysqli_num_rows($query_avatar)) {
        $query_avatar_data = mysqli_fetch_assoc($query_avatar);
        if (!empty($query_avatar_data) && !empty($query_avatar_data['id'])) {
            $fetched_data['avatar_post_id'] = $query_avatar_data['id'];
        }
    }
    $query_avatar = mysqli_query($sqlConnect, " SELECT `id`  FROM " . T_POSTS . "  WHERE `postType` = 'profile_cover_picture' AND `user_id` = '{$user_id}' ORDER BY `id` DESC LIMIT 1");
    if (mysqli_num_rows($query_avatar)) {
        $query_avatar_data = mysqli_fetch_assoc($query_avatar);
        if (!empty($query_avatar_data) && !empty($query_avatar_data['id'])) {
            $fetched_data['cover_post_id'] = $query_avatar_data['id'];
        }
    }
    $fetched_data['avatar_org'] = $fetched_data['avatar'];
    $fetched_data['cover_org'] = $fetched_data['cover'];
    $explode2 = @end(explode('.', $fetched_data['cover']));
    $explode3 = @explode('.', $fetched_data['cover']);
    $fetched_data['cover_full'] = $wo['userDefaultCover'];
    if ($fetched_data['cover'] != $wo['userDefaultCover']) {
        @$fetched_data['cover_full'] = $explode3[0] . '_full.' . $explode2;
    }
    $fetched_data['avatar_full'] = $fetched_data['avatar'];
    $explode2 = @end(explode('.', $fetched_data['avatar']));
    $explode3 = @explode('.', $fetched_data['avatar']);
    if ($fetched_data['avatar'] != $wo['userDefaultAvatar'] && $fetched_data['avatar'] != $wo['userDefaultFAvatar']) {
        @$fetched_data['avatar_full'] = $explode3[0] . '_full.' . $explode2;
    } else {
        @$fetched_data['avatar_full'] = $fetched_data['avatar'];
    }
    $fetched_data["is_verified"] = 0;
    if (Wo_IsVerificationRequests($fetched_data["user_id"], 'user')) {
        $fetched_data["is_verified"] = 2;
    }
    if ($fetched_data["verified"] == 1) {
        $fetched_data["is_verified"] = 1;
    }
    $fetched_data['avatar'] = Wo_GetMedia($fetched_data['avatar']) . '?cache=' . $fetched_data['last_avatar_mod'];
    $fetched_data['cover'] = Wo_GetMedia($fetched_data['cover']) . '?cache=' . $fetched_data['last_cover_mod'];
    $fetched_data['id'] = $fetched_data['user_id'];
    $fetched_data['user_platform'] = Wo_GetPlatformFromUser_ID($fetched_data['user_id']);
    $fetched_data['type'] = 'user';
    $fetched_data['url'] = Wo_SeoLink('index.php?link1=timeline&u=' . $fetched_data['username']);
    $fetched_data['name'] = '';
    if (!empty($fetched_data['first_name'])) {
        if (!empty($fetched_data['last_name'])) {
            $fetched_data['name'] = $fetched_data['first_name'] . ' ' . $fetched_data['last_name'];
        } else {
            $fetched_data['name'] = $fetched_data['first_name'];
        }
    } else {
        $fetched_data['name'] = $fetched_data['username'];
    }
    if (!empty($fetched_data['details'])) {
        $fetched_data['details'] = (array)json_decode($fetched_data['details']);
    }
    $fetched_data['API_notification_settings'] = (array)json_decode(html_entity_decode($fetched_data['notification_settings']));
    if ($wo['loggedin']) {
        $fetched_data['is_notify_stopped'] = $db->where('following_id', $user_id)->where('follower_id', $wo['user']['user_id'])->where('notify', 1)->getValue(T_FOLLOWERS, 'COUNT(*)');
    }
    $fetched_data['following_data'] = '';
    $fetched_data['followers_data'] = '';
    $fetched_data['mutual_friends_data'] = '';
    $fetched_data['likes_data'] = '';
    $fetched_data['groups_data'] = '';
    $fetched_data['album_data'] = '';
    if (!empty($fetched_data['sidebar_data'])) {
        $sidebar_data = (array)json_decode($fetched_data['sidebar_data']);
        if (!empty($sidebar_data['following_data'])) {
            $fetched_data['following_data'] = $sidebar_data['following_data'];
        }
        if (!empty($sidebar_data['followers_data'])) {
            $fetched_data['followers_data'] = $sidebar_data['followers_data'];
        }
        if (!empty($sidebar_data['mutual_friends_data'])) {
            $fetched_data['mutual_friends_data'] = $sidebar_data['mutual_friends_data'];
        }
        if (!empty($sidebar_data['likes_data'])) {
            $fetched_data['likes_data'] = $sidebar_data['likes_data'];
        }
        if (!empty($sidebar_data['groups_data'])) {
            $fetched_data['groups_data'] = $sidebar_data['groups_data'];
        }
        if (!empty($sidebar_data['album_data'])) {
            $fetched_data['album_data'] = $sidebar_data['album_data'];
        }
    }
    $fetched_data['website'] = (strpos($fetched_data['website'], 'http') === false && !empty($fetched_data['website'])) ? 'http://' . $fetched_data['website'] : $fetched_data['website'];
    $fetched_data['working_link'] = (strpos($fetched_data['working_link'], 'http') === false && !empty($fetched_data['working_link'])) ? 'http://' . $fetched_data['working_link'] : $fetched_data['working_link'];
    $fetched_data['lastseen_unix_time'] = $fetched_data['lastseen'];
    if ($wo['config']['node_socket_flow'] == "1") {
        $time = time() - 02;
    } else {
        $time = time() - 60;
    }
    $fetched_data['lastseen_status'] = ($fetched_data['lastseen'] > $time) ? 'on' : 'off';
    $fetched_data['is_reported'] = false;
    if (Wo_IsReportExists($user_id, 'user')) {
        $fetched_data['is_reported'] = true;
    }
    $fetched_data['am_i_blocked'] = Wo_IsUserBlocked($user_id);
    $fetched_data['is_story_muted'] = false;
    $fetched_data['is_following_me'] = 0;
    $fetched_data['is_following'] = 0;
    if (!empty($wo['user']['id'])) {
        $is_muted = $db->where('user_id', $wo['user']['id'])->where('story_user_id', $user_id)->getValue(T_MUTE_STORY, 'COUNT(*)');
        if ($is_muted > 0) {
            $fetched_data['is_story_muted'] = true;
        }
        $fetched_data['is_following_me'] = (Wo_IsFollowing($wo['user']['user_id'], $user_id)) ? 1 : 0;
        $fetched_data['is_following'] = (Wo_IsFollowing($wo['user']['user_id'], $wo['user']['user_id'])) ? 1 : 0;
    }
    $fetched_data['is_reported_user'] = 0;
    if ($wo['loggedin']) {
        $fetched_data['is_reported_user'] = $db->where('user_id', $wo['user']['user_id'])->where('profile_id', $user_id)->getValue(T_REPORTS, 'COUNT(*)');
    }
    $fetched_data['is_open_to_work'] = 0;
    $fetched_data['is_providing_service'] = 0;
    $fetched_data['providing_service'] = 0;
    $fetched_data['open_to_work_data'] = '';
    $fetched_data['formated_langs'] = array();
    $wo['switched_accounts'] = array();
    if (!empty($_COOKIE['switched_accounts'])) {
        $switched_accounts = json_decode($_COOKIE['switched_accounts'],true);
        foreach ($switched_accounts as $key => $value) {
            $sessionExist =  $db->where('user_id', $value['user_id'])->where('session_id', $value['session'])->getValue(T_APP_SESSIONS, 'COUNT(*)');
            if ($sessionExist > 0) {
                $wo['switched_accounts'][] = $value;
            }
        }
    }
    if ($wo['config']['website_mode'] == 'linkedin') {
        $fetched_data['is_open_to_work'] = $db->where('user_id', $user_id)->where('type', 'find_job')->getValue(T_USER_OPEN_TO, 'COUNT(*)');
        $fetched_data['open_to_work_data'] = $db->where('user_id', $user_id)->where('type', 'find_job')->getOne(T_USER_OPEN_TO);
        $fetched_data['is_providing_service'] = $db->where('user_id', $user_id)->where('type', 'service')->getValue(T_USER_OPEN_TO, 'COUNT(*)');
        $fetched_data['providing_service'] = $db->where('user_id', $user_id)->where('type', 'service')->getOne(T_USER_OPEN_TO);
        if (!empty($fetched_data['languages']) && !empty($wo['lang'])) {
            $pieces = explode(",", $fetched_data['languages']);
            if (!empty($pieces)) {
                foreach ($pieces as $key => $value) {
                    $fetched_data['formated_langs'][] = $wo['lang'][$value];
                }
            }
        }
        if (!empty($fetched_data['open_to_work_data'])) {
            $fetched_data['open_to_work_data']->formated_workplaces = array();
            $fetched_data['open_to_work_data']->formated_job_type = array();
            if (!empty($fetched_data['open_to_work_data']->workplaces)) {
                $workplaces_pieces = explode(",", $fetched_data['open_to_work_data']->workplaces);
                if (!empty($workplaces_pieces)) {
                    foreach ($workplaces_pieces as $key => $value) {
                        if (!empty($value) && !empty($wo['lang'])) {
                            $fetched_data['open_to_work_data']->formated_workplaces[] = $wo['lang'][$value];
                        }
                    }
                }
            }
            if (!empty($fetched_data['open_to_work_data']->job_type)) {
                $job_type_pieces = explode(",", $fetched_data['open_to_work_data']->job_type);
                if (!empty($job_type_pieces)) {
                    foreach ($job_type_pieces as $key => $value) {
                        if (!empty($value) && !empty($wo['lang'])) {
                            $fetched_data['open_to_work_data']->formated_job_type[] = $wo['lang'][$value];
                        }
                    }
                }
            }
        }
    }
    if ($generateCache === true) {
        cache($user_id, 'users', 'write', $fetched_data);
    }
    return $fetched_data;
}

function Wo_UserStatus($user_id, $lastseen, $type = '')
{
    global $wo;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if ($wo['user']['showlastseen'] == 0) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    if (empty($lastseen) || !is_numeric($lastseen) || $lastseen < 0) {
        return false;
    }
    $status = '';
    $user_id = Wo_Secure($user_id);
    $lastseen = Wo_Secure($lastseen);
    if ($wo['config']['node_socket_flow'] == "1") {
        $time = time() - 03;
    } else {
        $time = time() - 60;
    }
    if ($lastseen < $time) {
        if ($type == 'profile') {
            $status = '<span class="small-last-seen"><span style="font-size:12px; color:#777;">' . Wo_Time_Elapsed_String($lastseen) . '</span></span>';
        } else {
            $status = '<span class="small-last-seen">' . Wo_Time_Elapsed_String($lastseen) . '</span>';
        }
    } else {
        $status = '<span class="online-text"> ' . $wo['lang']['online'] . ' </span>';
    }
    return $status;
}

function Wo_LastSeen($user_id, $type = '')
{
    global $wo, $sqlConnect, $cache;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    if ($type == 'first') {
        $user = Wo_UserData($user_id);
        if ($user['status'] == 1) {
            return false;
        }
    } else {
        if ($wo['user']['status'] == 1) {
            return false;
        }
    }
    $user_id = Wo_Secure($user_id);
    $query = mysqli_query($sqlConnect, " UPDATE " . T_USERS . " SET `lastseen` = " . time() . " WHERE `user_id` = '{$user_id}' AND `active` = '1'");
    if ($query) {
        if ($wo['config']['cacheSystem'] == 1) {
            cache($user_id, 'users', 'delete');
        }
        return true;
    } else {
        return false;
    }
}

function Wo_RegisterUser($registration_data, $invited = false)
{
    global $wo, $sqlConnect;
    if (empty($registration_data)) {
        return false;
    }
    if ($wo['config']['user_registration'] == 0 && !$invited) {
        return false;
    }
    $ip = '0.0.0.0';
    $get_ip = get_ip_address();
    if (!empty($get_ip)) {
        $ip = $get_ip;
    }
    if ($wo['config']['login_auth'] == 1) {
        $getIpInfo = fetchDataFromURL("http://ip-api.com/json/$get_ip");
        $getIpInfo = json_decode($getIpInfo, true);
        if ($getIpInfo['status'] == 'success' && !empty($getIpInfo['regionName']) && !empty($getIpInfo['countryCode']) && !empty($getIpInfo['timezone']) && !empty($getIpInfo['city'])) {
            $registration_data['last_login_data'] = json_encode($getIpInfo);
        }
    }
    $registration_data['registered'] = date('n') . '/' . date("Y");
    $registration_data['joined'] = time();
    $registration_data['password'] = Wo_Secure(password_hash($registration_data['password'], PASSWORD_DEFAULT));
    $registration_data['ip_address'] = Wo_Secure($ip);
    $registration_data['language'] = $wo['config']['defualtLang'];
    if (!empty($_SESSION['lang'])) {
        $lang_name = strtolower($_SESSION['lang']);
        $langs = Wo_LangsNamesFromDB();
        if (in_array($lang_name, $langs)) {
            $registration_data['language'] = Wo_Secure($lang_name);
        }
    }
    $registration_data['order_posts_by'] = $wo['config']['order_posts_by'];
    $fields = '`' . implode('`,`', array_keys($registration_data)) . '`';
    $data = '\'' . implode('\', \'', $registration_data) . '\'';
    $query = mysqli_query($sqlConnect, "INSERT INTO " . T_USERS . " ({$fields}) VALUES ({$data})");
    $user_id = mysqli_insert_id($sqlConnect);
    $query_2 = mysqli_query($sqlConnect, "INSERT INTO " . T_USERS_FIELDS . " (`user_id`) VALUES ({$user_id})");
    if ($query) {
        if ($invited) {
            @Wo_DeleteAdminInvitation('code', $invited);
            Wo_AddInvitedUser($user_id, $invited);
        }
        return true;
    } else {
        return false;
    }
}

function Wo_ActivateUser($email, $code)
{
    global $sqlConnect;
    $email = Wo_Secure($email);
    $code = Wo_Secure($code);
    $query = mysqli_query($sqlConnect, " SELECT COUNT(`user_id`)  FROM " . T_USERS . "  WHERE `email` = '{$email}' AND `email_code` = '{$code}' AND `active` = '0'");
    $result = Wo_Sql_Result($query, 0);
    if ($result == 1) {
        $query_two = mysqli_query($sqlConnect, " UPDATE " . T_USERS . "  SET `active` = '1' WHERE `email` = '{$email}' ");
        if ($query_two) {
            return true;
        }
    } else {
        return false;
    }
}

function Wo_ResetPassword($user_id, $password)
{
    global $sqlConnect;
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    if (empty($password)) {
        return false;
    }
    $user_id = Wo_Secure($user_id);
    $password = Wo_Secure(password_hash($password, PASSWORD_DEFAULT));
    $query = mysqli_query($sqlConnect, " UPDATE " . T_USERS . " SET `password` = '{$password}' WHERE `user_id` = '{$user_id}' ");
    if ($query) {
        return true;
    } else {
        return false;
    }
}

function Wo_GetLanguages()
{
    $data = array();
    $dir = scandir('assets/languages');
    $languages_name = array_diff($dir, array(
        ".",
        "..",
        "error_log",
        "index.html",
        ".htaccess",
        "_notes",
        "extra"
    ));
    return $languages_name;
}

function Wo_SlugPost($string)
{
    $slug = url_slug($string, array(
        'delimiter' => '-',
        'limit' => 80,
        'lowercase' => true,
        'replacements' => array(
            '/\b(an)\b/i' => 'a',
            '/\b(example)\b/i' => 'Test'
        )
    ));
    return $slug . '.html';
}

function Wo_GetPostIdFromUrl($string)
{
    $slug_string = '';
    $string = Wo_Secure($string);
    if (preg_match('/[^a-z\s-]/i', $string)) {
        $string_exp = @explode('_', $string);
        $slug_string = $string_exp[0];
    } else {
        $slug_string = $string;
    }
    return Wo_Secure($slug_string);
}

function Wo_GetBlogIdFromUrl($string)
{
    $slug_string = '';
    $string = Wo_Secure($string);
    if (preg_match('/[^a-z\s-]/i', $string)) {
        $string_exp = @explode('_', $string);
        $slug_string = $string_exp[0];
    } else {
        $slug_string = $string;
    }
    return Wo_Secure($slug_string);
}

function Wo_isValidPasswordResetToken($string)
{
    global $sqlConnect;
    $string_exp = explode('_', $string);
    $user_id = Wo_Secure($string_exp[0]);
    $password = Wo_Secure($string_exp[1]);
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    if (empty($password)) {
        return false;
    }
    $query = mysqli_query($sqlConnect, " SELECT COUNT(`user_id`) FROM " . T_USERS . " WHERE `user_id` = '{$user_id}' AND `email_code` = '{$password}' AND `active` = '1' AND `time_code_sent` > '" . time() . "'");
    return (Wo_Sql_Result($query, 0) == 1) ? true : false;
}

function Wo_isValidPasswordResetToken2($string)
{
    global $sqlConnect;
    $string_exp = explode('_', $string);
    $user_id = Wo_Secure($string_exp[0]);
    $password = Wo_Secure($string_exp[1]);
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    if (empty($password)) {
        return false;
    }
    $query = mysqli_query($sqlConnect, " SELECT COUNT(`user_id`) FROM " . T_USERS . " WHERE `user_id` = '{$user_id}' AND `password` = '{$password}' AND `active` = '1'  AND `time_code_sent` > '" . time() . "'");
    return (Wo_Sql_Result($query, 0) == 1) ? true : false;
}

function Wo_DeleteUser($user_id)
{
    global $wo, $sqlConnect, $cache, $db;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 1) {
        return false;
    }
    $user_id = Wo_Secure($user_id);
    if (Wo_IsAdmin() === false && Wo_IsModerator() === false) {
        if ($wo['user']['user_id'] != $user_id) {
            return false;
        }
    }
    if (Wo_IsModerator() === true) {
        if (Wo_IsAdmin($user_id)) {
            return false;
        }
    }
    $funding = $db->where('user_id', $user_id)->get(T_FUNDING);
    if (!empty($funding)) {
        foreach ($funding as $key => $fund) {
            @Wo_DeleteFromToS3($fund->image);
            if (file_exists($fund->image)) {
                try {
                    unlink($fund->image);
                } catch (Exception $e) {
                }
            }
            $posts = $db->where('fund_id', $fund->id)->get(T_POSTS);
            if (!empty($posts)) {
                foreach ($posts as $key => $post) {
                    $db->where('parent_id', $post->id)->delete(T_POSTS);
                }
            }
            $raise = $db->where('funding_id', $fund->id)->get(T_FUNDING_RAISE);
            foreach ($raise as $key => $value) {
                $raise_posts = $db->where('fund_raise_id', $value->id)->get(T_POSTS);
                if (!empty($raise_posts)) {
                    foreach ($posts as $key => $value1) {
                        $db->where('parent_id', $value1->id)->delete(T_POSTS);
                    }
                }
                $db->where('fund_raise_id', $value->id)->delete(T_POSTS);
            }
        }
        $db->where('user_id', $user_id)->delete(T_FUNDING);
    }

    $monetizations = $db->where('user_id', $user_id)->get(T_USER_MONETIZATION);
    if (!empty($monetizations)) {
        foreach ($monetizations as $key => $monetization) {
            $monetization = $db->where('monetization_id', $monetization->id)->getOne(T_MONETIZATION_SUBSCRIBTION);
        }
        $db->where('user_id', $user_id)->delete(T_USER_MONETIZATION);
    }

    $db->where('user_id', $user_id)->delete(T_MONETIZATION_SUBSCRIBTION);

    $user_data = Wo_UserData($user_id);
    $query_one_delete_photos = mysqli_query($sqlConnect, " SELECT `avatar`,`cover` FROM " . T_USERS . " WHERE `user_id` = {$user_id}");
    if (mysqli_num_rows($query_one_delete_photos)) {
        $fetched_data = mysqli_fetch_assoc($query_one_delete_photos);
        if (isset($fetched_data['avatar']) && !empty($fetched_data['avatar']) && $fetched_data['avatar'] != $wo['userDefaultAvatar'] && $fetched_data['avatar'] != $wo['userDefaultFAvatar']) {
            $explode2 = @end(explode('.', $fetched_data['avatar']));
            $explode3 = @explode('.', $fetched_data['avatar']);
            $media_2 = $explode3[0] . '_avatar_full.' . $explode2;
            @unlink(trim($media_2));
            @unlink($fetched_data['avatar']);
            $delete_from_s3 = Wo_DeleteFromToS3($fetched_data['avatar']);
            $delete_from_s3 = Wo_DeleteFromToS3($media_2);
        }
        if (isset($fetched_data['cover']) && !empty($fetched_data['cover']) && $fetched_data['cover'] != $wo['userDefaultCover']) {
            $explode2 = @end(explode('.', $fetched_data['cover']));
            $explode3 = @explode('.', $fetched_data['cover']);
            $media_2 = $explode3[0] . '_cover_full.' . $explode2;
            @unlink(trim($media_2));
            @unlink($fetched_data['cover']);
            $delete_from_s3 = Wo_DeleteFromToS3($fetched_data['cover']);
            $delete_from_s3 = Wo_DeleteFromToS3($media_2);
        }
    }
    $query_one_delete_media = mysqli_query($sqlConnect, " SELECT `media` FROM " . T_MESSAGES . " WHERE `from_id` = {$user_id} OR `to_id` = {$user_id}");
    if ($query_one_delete_media) {
        if (mysqli_num_rows($query_one_delete_media) > 0) {
            while ($fetched_data = mysqli_fetch_assoc($query_one_delete_media)) {
                if (isset($fetched_data['media']) && !empty($fetched_data['media'])) {
                    @unlink($fetched_data['media']);
                }
            }
        }
    }
    $query_two_delete_media = mysqli_query($sqlConnect, " SELECT `postFile`,`id`,`post_id` FROM " . T_POSTS . " WHERE `user_id` = {$user_id}");
    if ($query_two_delete_media) {
        if (mysqli_num_rows($query_two_delete_media) > 0) {
            while ($fetched_data = mysqli_fetch_assoc($query_two_delete_media)) {
                $query_one_reports = mysqli_query($sqlConnect, "DELETE FROM " . T_REPORTS . " WHERE `post_id` = " . $fetched_data['id']);
                $query_one_reports .= mysqli_query($sqlConnect, "DELETE FROM " . T_REPORTS . " WHERE `post_id` = " . $fetched_data['post_id']);
                if (isset($fetched_data['postFile']) && !empty($fetched_data['postFile'])) {
                    @unlink($fetched_data['postFile']);
                }
            }
        }
    }
    if ($wo['config']['cacheSystem'] == 1) {
        $query_two = mysqli_query($sqlConnect, "SELECT `id`,`post_id` FROM " . T_POSTS . " WHERE `user_id` = {$user_id} OR `recipient_id` = {$user_id}");
    }
    $query_four_delete_media = mysqli_query($sqlConnect, "SELECT `page_id` FROM " . T_PAGES . " WHERE `user_id` = {$user_id}");
    if ($query_four_delete_media) {
        if (mysqli_num_rows($query_four_delete_media) > 0) {
            while ($fetched_data = mysqli_fetch_assoc($query_four_delete_media)) {
                $delete_posts = Wo_DeletePage($fetched_data['page_id']);
            }
        }
    }
    $query_five_delete_media = mysqli_query($sqlConnect, "SELECT `id` FROM " . T_GROUPS . " WHERE `user_id` = {$user_id}");
    if ($query_five_delete_media) {
        if (mysqli_num_rows($query_five_delete_media) > 0) {
            while ($fetched_data = mysqli_fetch_assoc($query_five_delete_media)) {
                $delete_groups = Wo_DeleteGroup($fetched_data['id']);
            }
        }
    }
    $query_6_delete_media = mysqli_query($sqlConnect, "SELECT `id` FROM " . T_POSTS . " WHERE `user_id` = {$user_id} OR `recipient_id` = {$user_id}");
    if ($query_6_delete_media) {
        if (mysqli_num_rows($query_6_delete_media) > 0) {
            while ($fetched_data = mysqli_fetch_assoc($query_6_delete_media)) {
                $delete_posts = Wo_DeletePost($fetched_data['id']);
            }
        }
    }
    $query_7_delete_media = mysqli_query($sqlConnect, "SELECT `id` FROM " . T_FORUM_THREADS . " WHERE `user` = {$user_id}");
    if ($query_7_delete_media) {
        if (mysqli_num_rows($query_7_delete_media) > 0) {
            while ($fetched_data = mysqli_fetch_assoc($query_7_delete_media)) {
                $delete_posts = Wo_DeleteForumThread($fetched_data['id']);
            }
        }
    }
    $query_8_delete_media = mysqli_query($sqlConnect, "SELECT `id` FROM " . T_FORUM_THREAD_REPLIES . " WHERE `poster_id` = {$user_id}");
    if ($query_8_delete_media) {
        if (mysqli_num_rows($query_8_delete_media) > 0) {
            while ($fetched_data = mysqli_fetch_assoc($query_8_delete_media)) {
                $delete_posts = Wo_DeleteThreadReply($fetched_data['id']);
            }
        }
    }
    $query_9_delete_media = mysqli_query($sqlConnect, "SELECT `id` FROM " . T_EVENTS . " WHERE `poster_id` = {$user_id}");
    if ($query_9_delete_media) {
        if (mysqli_num_rows($query_9_delete_media) > 0) {
            while ($fetched_data = mysqli_fetch_assoc($query_9_delete_media)) {
                $delete_posts = Wo_DeleteEvent($fetched_data['id']);
            }
        }
    }
    $querydelete = mysqli_query($sqlConnect, "SELECT * FROM " . T_FUNDING . " WHERE `user_id` = {$user_id}");
    if ($querydelete) {
        if (mysqli_num_rows($querydelete) > 0) {
            while ($fetched_data = mysqli_fetch_assoc($querydelete)) {
                @Wo_DeleteFromToS3($fetched_data['image']);
                if (file_exists($fetched_data['image'])) {
                    try {
                        unlink($fetched_data['image']);
                    } catch (Exception $e) {
                    }
                }
                mysqli_query($sqlConnect, "DELETE FROM " . T_FUNDING . " WHERE `user_id` = {$user_id}");
                mysqli_query($sqlConnect, "DELETE FROM " . T_FUNDING_RAISE . " WHERE `funding_id` = '" . $fetched_data['id'] . "'");
            }
        }
    }
    $querydelete = mysqli_query($sqlConnect, "SELECT * FROM " . T_OFFER . " WHERE `user_id` = {$user_id}");
    if ($querydelete) {
        if (mysqli_num_rows($querydelete) > 0) {
            while ($fetched_data = mysqli_fetch_assoc($querydelete)) {
                @Wo_DeleteFromToS3($fetched_data['image']);
                if (file_exists($fetched_data['image'])) {
                    try {
                        unlink($fetched_data['image']);
                    } catch (Exception $e) {
                    }
                }
                mysqli_query($sqlConnect, "DELETE FROM " . T_OFFER . " WHERE `user_id` = {$user_id}");
            }
        }
    }
    $querydelete = mysqli_query($sqlConnect, "SELECT * FROM " . T_USER_EXPERIENCE . " WHERE `user_id` = {$user_id}");
    if ($querydelete) {
        if (mysqli_num_rows($querydelete) > 0) {
            while ($fetched_data = mysqli_fetch_assoc($querydelete)) {
                @Wo_DeleteFromToS3($fetched_data['image']);
                if (file_exists($fetched_data['image'])) {
                    try {
                        unlink($fetched_data['image']);
                    } catch (Exception $e) {
                    }
                }
                mysqli_query($sqlConnect, "DELETE FROM " . T_USER_EXPERIENCE . " WHERE `user_id` = {$user_id}");
            }
        }
    }
    $querydelete = mysqli_query($sqlConnect, "SELECT * FROM " . T_USER_CERTIFICATION . " WHERE `user_id` = {$user_id}");
    if ($querydelete) {
        if (mysqli_num_rows($querydelete) > 0) {
            while ($fetched_data = mysqli_fetch_assoc($querydelete)) {
                @Wo_DeleteFromToS3($fetched_data['pdf']);
                if (file_exists($fetched_data['pdf'])) {
                    try {
                        unlink($fetched_data['pdf']);
                    } catch (Exception $e) {
                    }
                }
                mysqli_query($sqlConnect, "DELETE FROM " . T_USER_CERTIFICATION . " WHERE `user_id` = {$user_id}");
            }
        }
    }
    $query_group_chat = mysqli_query($sqlConnect, "SELECT `group_id` FROM " . T_GROUP_CHAT . " WHERE `user_id` = {$user_id}");
    if ($query_group_chat) {
        if (mysqli_num_rows($query_group_chat) > 0) {
            while ($fetched_data = mysqli_fetch_assoc($query_group_chat)) {
                mysqli_query($sqlConnect, "DELETE FROM " . T_GROUP_CHAT_USERS . " WHERE `group_id` = '" . $fetched_data['group_id'] . "'");
            }
        }
    }
    $query_one = mysqli_query($sqlConnect, "DELETE FROM " . T_USERS . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_RECENT_SEARCHES . " WHERE `user_id` = {$user_id} OR `search_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_GAMES_PLAYERS . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_USER_PROJECTS . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_USER_OPEN_TO . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_FOLLOWERS . " WHERE `follower_id` = {$user_id} OR `following_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_MESSAGES . " WHERE `from_id` = {$user_id} OR `to_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_VIDEOS_CALLES . " WHERE `from_id` = {$user_id} OR `to_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_AUDIO_CALLES . " WHERE `from_id` = {$user_id} OR `to_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_AGORA . " WHERE `from_id` = {$user_id} OR `to_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_NOTIFICATION . " WHERE `notifier_id` = {$user_id} OR `recipient_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_REPORTS . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_USERS_FIELDS . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_APP_SESSIONS . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_COMMENTS . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_ANNOUNCEMENT_VIEWS . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_LIKES . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_WONDERS . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_PAYMENTS . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_COMMENT_REPLIES_LIKES . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_COMMENT_REPLIES_WONDERS . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_SAVED_POSTS . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_COMMENT_LIKES . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_COMMENT_WONDERS . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_COMMENTS_REPLIES . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_EVENTS_GOING . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_EVENTS_INT . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_BM_LIKES . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_BM_DISLIKES . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_USERADS_DATA . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_PAYMENT_TRANSACTIONS . " WHERE `userid` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_ACTIVITIES . " WHERE `user_id` = {$user_id} OR `follow_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_EVENTS_INV . " WHERE `inviter_id` = {$user_id} OR `invited_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_GROUP_MEMBERS . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_PAGES_INVAITES . " WHERE `inviter_id` = {$user_id} OR `invited_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_PINNED_POSTS . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_APPS . " WHERE `app_user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_APPS_PERMISSION . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_CODES . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_TOKENS . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_BLOG_REACTION . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_PAGES_LIKES . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_VERIFICATION_REQUESTS . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_A_REQUESTS . " WHERE `user_id` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_BLOCKS . " WHERE `blocker` = {$user_id} OR `blocked` = {$user_id}");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_U_CHATS . " WHERE `conversation_user_id` = '{$user_id}' OR `user_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_BLOG . " WHERE `user` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_BLOG_COMM_REPLIES . " WHERE `user_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_BLOG_COMM . " WHERE `user_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_MOVIE_COMM_REPLIES . " WHERE `user_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_MOVIE_COMMS . " WHERE `user_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_APPS_HASH . " WHERE `user_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_FORUM_THREADS . " WHERE `user` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_FORUM_THREAD_REPLIES . " WHERE `poster_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_EVENTS . " WHERE `poster_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_USER_ADS . " WHERE `user_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_USER_STORY . " WHERE `user_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_HIDDEN_POSTS . " WHERE `user_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_GROUP_CHAT . " WHERE `user_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_GROUP_CHAT_USERS . " WHERE `user_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_PAGE_RATING . " WHERE `user_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_FAMILY . " WHERE `user_id` = '{$user_id}' OR `member_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_REL_SHIP . " WHERE `from_id` = '{$user_id}' OR `to_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_PAGE_ADMINS . " WHERE `user_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_GROUP_ADMINS . " WHERE `user_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_REACTIONS . " WHERE `user_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_JOB . " WHERE `user_id` = '{$user_id}'");
    $query_one .= mysqli_query($sqlConnect, "DELETE FROM " . T_JOB_APPLY . " WHERE `user_id` = '{$user_id}'");
    $query_ones = mysqli_query($sqlConnect, "DELETE FROM " . T_POKES . " WHERE `received_user_id` = '{$user_id}' OR `send_user_id` = '{$user_id}'");
    $query_ones = mysqli_query($sqlConnect, "DELETE FROM " . T_USERGIFTS . " WHERE `from` = '{$user_id}' OR `to` = '{$user_id}'");
    $query_ones = mysqli_query($sqlConnect, "DELETE FROM " . T_STORY_SEEN . " WHERE `user_id` = '{$user_id}'");
    $query_ones = mysqli_query($sqlConnect, "DELETE FROM " . T_REFUND . " WHERE `user_id` = '{$user_id}'");
    $query_ones = mysqli_query($sqlConnect, "DELETE FROM " . T_INVITAION_LINKS . " WHERE `user_id` = '{$user_id}' OR `invited_id` = '{$user_id}'");
    $query_ones = mysqli_query($sqlConnect, "DELETE FROM " . T_AGORA . " WHERE `from_id ` = '{$user_id}' OR `to_id` = '{$user_id}'");
    $query_ones = mysqli_query($sqlConnect, "DELETE FROM " . T_MUTE . " WHERE `user_id` = '{$user_id}'");
    $query_ones = mysqli_query($sqlConnect, "DELETE FROM " . T_MUTE_STORY . " WHERE `user_id` = '{$user_id}' OR `story_user_id` = '{$user_id}'");
    $query_ones = mysqli_query($sqlConnect, "DELETE FROM " . T_CAST . " WHERE `user_id` = '{$user_id}'");
    $query_ones = mysqli_query($sqlConnect, "DELETE FROM " . T_CAST_USERS . " WHERE `user_id` = '{$user_id}'");
    $query_ones = mysqli_query($sqlConnect, "DELETE FROM " . T_LIVE_SUB . " WHERE `user_id` = '{$user_id}'");
    $query_ones = mysqli_query($sqlConnect, "DELETE FROM " . T_VOTES . " WHERE `user_id` = '{$user_id}'");
    $query_ones = mysqli_query($sqlConnect, "DELETE FROM " . T_BANK_TRANSFER . " WHERE `user_id` = '{$user_id}'");
    $query_ones = mysqli_query($sqlConnect, "DELETE FROM " . T_USERCARD . " WHERE `user_id` = '{$user_id}'");
    $query_ones = mysqli_query($sqlConnect, "DELETE FROM " . T_USER_ADDRESS . " WHERE `user_id` = '{$user_id}'");
    $query_ones = mysqli_query($sqlConnect, "DELETE FROM " . T_USER_ORDERS . " WHERE `user_id` = '{$user_id}' OR `product_owner_id` = '{$user_id}'");
    $query_ones = mysqli_query($sqlConnect, "DELETE FROM " . T_PURCHAES . " WHERE `user_id` = '{$user_id}' OR `owner_id` = '{$user_id}'");
    $query_ones = mysqli_query($sqlConnect, "DELETE FROM " . T_EMAILS . " WHERE `email_to` = '" . $user_data['email'] . "' OR `user_id` = '{$user_id}'");
    if ($query_one) {
        cache($user_id, 'users', 'delete');
        $wo['deletedUserData'] = $user_data;
        $send_message_data = array(
            'from_email' => $wo['config']['siteEmail'],
            'from_name' => $wo['config']['siteName'],
            'to_email' => $user_data['email'],
            'to_name' => $user_data['name'],
            'subject' => 'Your account was deleted',
            'charSet' => 'utf-8',
            'message_body' => Wo_LoadPage('emails/account-deleted'),
            'is_html' => true
        );
        $send = Wo_SendMessage($send_message_data);
        return true;
    }
}

function Wo_UpdateUserData($user_id, $update_data, $unverify = false)
{
    global $wo, $sqlConnect, $cache;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    if (empty($update_data)) {
        return false;
    }
    $user_id = Wo_Secure($user_id);
    $is_mod = Wo_IsModerator();
    $is_admin = Wo_IsAdmin();
    if ($is_admin === false && $is_mod === false) {
        if ($wo['user']['user_id'] != $user_id) {
            return false;
        }
    }
    if (!empty($update_data['admin']) && $update_data['admin'] == 1) {
        if ($is_admin === false) {
            return false;
        }
    }
    if (isset($update_data['verified'])) {
        if (empty($update_data['pro_'])) {
            if ($is_admin === false && $is_mod === false) {
                return false;
            }
        }
    }
    if ($is_mod) {
        $user_data_ = Wo_UserData($user_id);
        if ($user_data_['admin'] == 1) {
            return false;
        }
    }
    if (!empty($update_data['relationship'])) {
        if (!array_key_exists($update_data['relationship'], $wo['relationship'])) {
            $update_data['relationship_id'] = 1;
        }
    } else if (isset($update_data['relationship'])) {
        if (!array_key_exists($update_data['relationship'], $wo['relationship'])) {
            $update_data['relationship_id'] = 0;
        }
    }
    if (isset($update_data['country_id'])) {
        if (!array_key_exists($update_data['country_id'], $wo['countries_name'])) {
            $update_data['country_id'] = 1;
        }
    }
    if (!isset($update_data['relationship_id'])) {
        $update_data['relationship_id'] = $wo['user']['relationship_id'];
    }
    $update = array();
    foreach ($update_data as $field => $data) {
        $filter = ['first_name', 'last_name', 'about'];
        if (in_array($field, $filter)) {
            $finalData = Wo_Secure($data, 1);
        } else {
            $finalData = Wo_Secure($data, 0);
        }
        if ($field != 'pro_') {
            $update[] = '`' . $field . '` = \'' . $finalData . '\'';
        }
    }
    $impload = implode(', ', $update);
    $query_one = " UPDATE " . T_USERS . " SET {$impload} WHERE `user_id` = {$user_id} ";

    $query1 = mysqli_query($sqlConnect, $query_one);
    if ($unverify == true) {
        $query_two = " UPDATE " . T_USERS . " SET `verified` = '0' WHERE `user_id` = {$user_id} ";
        @mysqli_query($sqlConnect, $query_two);
    }
    if ($query1) {
        cache($user_id, 'users', 'delete');
        if (!empty($update_data['username'])) {
            Wo_UpdateUsernameInNotifications($user_id, $update_data['username']);
        }
        return true;
    } else {
        return false;
    }
}

function Wo_UpdateUsernameInNotifications($user_id = 0, $username = '')
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    if (empty($username)) {
        return false;
    }
    cache($user_id, 'users', 'delete');
    $query_one = "UPDATE " . T_NOTIFICATION . " SET `url` = 'index.php?link1=timeline&u={$username}' WHERE `notifier_id` = {$user_id} AND (`type` = 'following' OR `type` = 'visited_profile' OR `type` = 'accepted_request')";
    $query = mysqli_query($sqlConnect, $query_one);
    if ($query) {
        return true;
    }
}

function Wo_AddRelatedUser($userData, $relatedUserId)
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }

    $relatedForUser = json_decode($userData['related_users'],true);

    if (count($relatedForUser) > 2) {
        return false;
    }

    $filtered = array_filter($relatedForUser, function($userId) use ($relatedUserId) {
        return $userId == $relatedUserId;
    });

    if(count($filtered) > 0) {
        return false;
    }

    $relatedForUser[] =  $relatedUserId;

    $user_id = $userData['user_id'];
    $relatedForUser = json_encode($relatedForUser);

    $query_one = "UPDATE " . T_USERS . " SET `related_users` = '$relatedForUser' WHERE `user_id` = $user_id";

    $query = mysqli_query($sqlConnect, $query_one);

    if ($query) {
        return true;
    }
}

function addhttp($url)
{
    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
        $url = "http://" . $url;
    }
    return $url;
}

function Wo_GetMedia($media)
{
    global $wo;
    if (empty($media)) {
        return '';
    }
    if ($wo['config']['amazone_s3'] == 1) {
        if (empty($wo['config']['bucket_name'])) {
            return $wo['config']['site_url'] . '/' . $media;
        }
        if (!empty($wo['config']['amazon_endpoint']) && filter_var($wo['config']['amazon_endpoint'], FILTER_VALIDATE_URL)) {
            return $wo['config']['amazon_endpoint'] . "/" . $media;
        }
        return $wo['config']['s3_site_url'] . '/' . $media;
    } elseif ($wo['config']['wasabi_storage'] == 1) {
        if (empty($wo['config']['wasabi_bucket_name']) || empty($wo['config']['wasabi_access_key']) || empty($wo['config']['wasabi_secret_key']) || empty($wo['config']['wasabi_bucket_region'])) {
            return $wo['config']['site_url'] . '/' . $media;
        }
        if (!empty($wo['config']['wasabi_endpoint']) && filter_var($wo['config']['wasabi_endpoint'], FILTER_VALIDATE_URL)) {
            return $wo['config']['wasabi_endpoint'] . "/" . $media;
        }
        return $wo['config']['wasabi_site_url'] . '/' . $media;
    } else if ($wo['config']['spaces'] == 1) {
        if (empty($wo['config']['space_region']) || empty($wo['config']['space_name'])) {
            return $wo['config']['site_url'] . '/' . $media;
        }
        if (!empty($wo['config']['spaces_endpoint']) && filter_var($wo['config']['spaces_endpoint'], FILTER_VALIDATE_URL)) {
            return $wo['config']['spaces_endpoint'] . "/" . $media;
        }
        return 'https://' . $wo['config']['space_name'] . '.' . $wo['config']['space_region'] . '.digitaloceanspaces.com/' . $media;
    } else if ($wo['config']['ftp_upload'] == 1) {
        return addhttp($wo['config']['ftp_endpoint']) . '/' . $media;
    } else if ($wo['config']['cloud_upload'] == 1) {
        if (!empty($wo['config']['cloud_endpoint']) && filter_var($wo['config']['cloud_endpoint'], FILTER_VALIDATE_URL)) {
            return $wo['config']['cloud_endpoint'] . "/" . $media;
        }
        return 'https://storage.googleapis.com/' . $wo['config']['cloud_bucket_name'] . '/' . $media;
    } else if ($wo['config']['backblaze_storage'] == 1) {
        if (!empty($wo['config']['backblaze_endpoint']) && filter_var($wo['config']['backblaze_endpoint'], FILTER_VALIDATE_URL)) {
            return $wo['config']['backblaze_endpoint'] . "/" . $media;
        }
        return 'https://' . $wo['config']['backblaze_bucket_name'] . '.s3.' . $wo['config']['backblaze_bucket_region'] . '.backblazeb2.com/' . $media;
    }
    return $wo['config']['site_url'] . '/' . $media;
}

function Wo_UploadImage($file, $name, $type, $type_file, $user_id = 0, $placement = '', $ai_post = 0)
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($file) || empty($name) || empty($type) || empty($user_id)) {
        return false;
    }
    $ext = pathinfo($name, PATHINFO_EXTENSION);
    if (!file_exists('upload/photos/' . date('Y'))) {
        mkdir('upload/photos/' . date('Y'), 0777, true);
    }
    if (!file_exists('upload/photos/' . date('Y') . '/' . date('m'))) {
        mkdir('upload/photos/' . date('Y') . '/' . date('m'), 0777, true);
    }
    $allowed = 'jpg,png,jpeg,gif';
    $new_string = pathinfo($name, PATHINFO_FILENAME) . '.' . strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $extension_allowed = explode(',', $allowed);
    $file_extension = pathinfo($new_string, PATHINFO_EXTENSION);
    if (!in_array($file_extension, $extension_allowed)) {
        return false;
    }
    $ar = array(
        'image/png',
        'image/jpeg',
        'image/gif',
        'image/jpg'
    );
    if (!in_array($type_file, $ar)) {
        return false;
    }
    $dir = 'upload/photos/' . date('Y') . '/' . date('m');
    if ($placement == 'page') {
        $image_data['page_id'] = Wo_Secure($user_id);
    } else if ($placement == 'group') {
        $image_data['id'] = Wo_Secure($user_id);
    } else if ($placement == 'event') {
        $image_data['event_id'] = Wo_Secure($user_id);
    } else {
        $image_data['user_id'] = Wo_Secure($user_id);
    }
    if ($type == 'cover') {
        if ($placement == 'page') {
            $query_one_delete_cover = mysqli_query($sqlConnect, " SELECT `cover` FROM " . T_PAGES . " WHERE `page_id` = " . $image_data['page_id'] . " AND `active` = '1' ");
        } else if ($placement == 'group') {
            $query_one_delete_cover = mysqli_query($sqlConnect, " SELECT `cover` FROM " . T_GROUPS . " WHERE `id` = " . $image_data['id'] . " AND `active` = '1'");
        } else if ($placement == 'event') {
            $query_one_delete_cover = mysqli_query($sqlConnect, " SELECT `cover` FROM " . T_EVENTS . " WHERE `id` = " . $image_data['event_id']);
        } else {
            $query_one_delete_cover = mysqli_query($sqlConnect, " SELECT `cover` FROM " . T_USERS . " WHERE `user_id` = " . $image_data['user_id'] . " AND `active` = '1' ");
        }
        if (mysqli_num_rows($query_one_delete_cover)) {
            $fetched_data = mysqli_fetch_assoc($query_one_delete_cover);
        }
        $filename = $dir . '/' . Wo_GenerateKey() . '_' . date('d') . '_' . md5(time()) . '_cover.' . $ext;
        $image_data['cover'] = $filename;
        if (move_uploaded_file($file, $filename)) {
            $check_file = getimagesize($filename);
            if (!$check_file) {
                unlink($filename);
                return false;
            }
            $update_data = false;
            if ($placement == 'page') {
                $update_data = Wo_UpdatePageData($image_data['page_id'], $image_data);
            } else if ($placement == 'group') {
                $update_data = Wo_UpdateGroupData($image_data['id'], $image_data);
            } else if ($placement == 'event') {
                $update_data = Wo_UpdateEvent($image_data['event_id'], array(
                    "cover" => $image_data['cover']
                ));
            } else {
                $image_file = Wo_GetMedia($image_data['cover']);
                $blur = 0;
                $upload_p = true;
                if ($wo['config']['adult_images'] == 1 && detect_safe_search($image_file) == false && $wo['config']['adult_images_action'] == 1) {
                    $blur = 1;
                } elseif ($wo['config']['adult_images'] == 1 && detect_safe_search($image_file) == false && $wo['config']['adult_images_action'] == 0) {
                    Wo_DeleteFromToS3($image_file);
                    @unlink($image_file);
                    $upload_p = false;
                    return array(
                        'status' => 400,
                        'invalid_file' => 3
                    );
                }
                if ($upload_p == true) {
                    $update_data = Wo_UpdateUserData($image_data['user_id'], $image_data);
                    if ($update_data) {
                        $last_file = $filename;
                        $explode2 = @end(explode('.', $filename));
                        $explode3 = @explode('.', $filename);
                        $last_file = $explode3[0] . '_full.' . $explode2;
                        @Wo_CompressImage($filename, $last_file, $wo['config']['images_quality']);
                        $upload_s3 = Wo_UploadToS3($last_file);
                        if ($wo['config']['website_mode'] != 'askfm') {
                            $regsiter_cover_image = Wo_RegisterPost(array(
                                'user_id' => Wo_Secure($image_data['user_id']),
                                'postFile' => Wo_Secure($last_file, 0),
                                'time' => time(),
                                'postType' => Wo_Secure('profile_cover_picture'),
                                'postPrivacy' => '0',
                                'blur' => $blur,
                                'ai_post' => $ai_post
                            ));
                        }
                    }
                }
            }
            if ($update_data == true) {
                Wo_Resize_Crop_Image(918, 332, $filename, $filename, $wo['config']['images_quality']);
                $upload_s3 = Wo_UploadToS3($filename);
                return true;
            }
            return true;
        }
    } else if ($type == 'avatar') {
        $filename = $dir . '/' . Wo_GenerateKey() . '_' . date('d') . '_' . md5(time()) . '_avatar.' . $ext;
        $image_data['avatar'] = $filename;
        if ($placement == 'page') {
            $user_data = Wo_PageData($image_data['page_id']);
        } elseif ($placement == 'group') {
            $user_data = Wo_GroupData($image_data['id']);
        } else {
            $user_data = Wo_UserData($image_data['user_id']);
        }
        $image_data_d = array();
        @$image_data_d['avatar'] = $user_data['avatar'];
        if (move_uploaded_file($file, $filename)) {
            $check_file = getimagesize($filename);
            if (!$check_file) {
                unlink($filename);
                return false;
            }
            if ($placement == 'page') {
                $update_data = Wo_UpdatePageData($image_data['page_id'], $image_data);
                Wo_Resize_Crop_Image($wo['profile_picture_width_crop'], $wo['profile_picture_height_crop'], $filename, $filename, $wo['profile_picture_image_quality']);
                $upload_s3 = Wo_UploadToS3($filename);
                return true;
            } else if ($placement == 'group') {
                $update_data = Wo_UpdateGroupData($image_data['id'], $image_data);
                Wo_Resize_Crop_Image($wo['profile_picture_width_crop'], $wo['profile_picture_height_crop'], $filename, $filename, $wo['profile_picture_image_quality']);
                $upload_s3 = Wo_UploadToS3($filename);
                return true;
            } else if ($placement == 'app') {
                $update_data = Wo_UpdateAppImage($user_id, $filename);
                Wo_Resize_Crop_Image($wo['profile_picture_width_crop'], $wo['profile_picture_height_crop'], $filename, $filename, $wo['profile_picture_image_quality']);
                $upload_s3 = Wo_UploadToS3($filename);
                return true;
            } else {
                $image_file = Wo_GetMedia($image_data['avatar']);
                $blur = 0;
                $upload_p = true;
                if ($wo['config']['adult_images'] == 1 && detect_safe_search($image_file) == false && $wo['config']['adult_images_action'] == 1) {
                    $blur = 1;
                } elseif ($wo['config']['adult_images'] == 1 && detect_safe_search($image_file) == false && $wo['config']['adult_images_action'] == 0) {
                    Wo_DeleteFromToS3($image_file);
                    @unlink($image_file);
                    $upload_p = false;
                    return array(
                        'status' => 400,
                        'invalid_file' => 3
                    );
                }
                if ($upload_p == true) {
                    $image_data['startup_image'] = 1;
                    if (Wo_UpdateUserData($image_data['user_id'], $image_data)) {
                        $explode2 = @end(explode('.', $filename));
                        $explode3 = @explode('.', $filename);
                        $last_file = $explode3[0] . '_full.' . $explode2;
                        $compress = Wo_CompressImage($filename, $last_file, $wo['config']['images_quality']);
                        if ($compress) {
                            $upload_s3 = Wo_UploadToS3($last_file);
                            if ($wo['config']['website_mode'] != 'askfm') {
                                $regsiter_image = Wo_RegisterPost(array(
                                    'user_id' => Wo_Secure($image_data['user_id']),
                                    'postFile' => Wo_Secure($last_file, 0),
                                    'time' => time(),
                                    'postType' => Wo_Secure('profile_picture'),
                                    'postPrivacy' => '0',
                                    'blur' => $blur,
                                    'ai_post' => $ai_post
                                ));
                            }
                            Wo_Resize_Crop_Image($wo['profile_picture_width_crop'], $wo['profile_picture_height_crop'], $filename, $filename, $wo['profile_picture_image_quality']);
                            $upload_s3 = Wo_UploadToS3($filename);
                        } else {
                            Wo_UpdateUserData($image_data['user_id'], $image_data_d);
                        }
                        return true;
                    }
                }
            }
        }
    } else if ($type == 'background_image') {
        $query_one_delete_background_image = mysqli_query($sqlConnect, " SELECT `background_image` FROM " . T_USERS . " WHERE `user_id` = " . $image_data['user_id'] . " AND `active` = '1' ");
        if (mysqli_num_rows($query_one_delete_background_image)) {
            $fetched_data = mysqli_fetch_assoc($query_one_delete_background_image);
            $filename = $dir . '/' . Wo_GenerateKey() . '_' . date('d') . '_' . md5(time()) . '_background_image.' . $ext;
            $image_data['background_image'] = $filename;
            if (move_uploaded_file($file, $filename)) {
                $check_file = getimagesize($filename);
                if (!$check_file) {
                    unlink($filename);
                    return false;
                }
                $upload_s3 = Wo_UploadToS3($filename);
                if (isset($fetched_data['background_image']) && !empty($fetched_data['background_image'])) {
                    @unlink($fetched_data['background_image']);
                }
                if (Wo_UpdateUserData($image_data['user_id'], $image_data)) {
                    return true;
                }
            }
        }
    } else if ($type == 'page_background_image') {
        $query_one_delete_background_image = mysqli_query($sqlConnect, " SELECT `background_image` FROM " . T_PAGES . " WHERE `page_id` = " . $image_data['page_id'] . " AND `active` = '1' ");
        if (mysqli_num_rows($query_one_delete_background_image)) {
            $fetched_data = mysqli_fetch_assoc($query_one_delete_background_image);
            $filename = $dir . '/' . Wo_GenerateKey() . '_' . date('d') . '_' . md5(time()) . '_background_image.' . $ext;
            $image_data['background_image'] = $filename;
            if (move_uploaded_file($file, $filename)) {
                $check_file = getimagesize($filename);
                if (!$check_file) {
                    unlink($filename);
                    return false;
                }
                $upload_s3 = Wo_UploadToS3($filename);
                if (isset($fetched_data['background_image']) && !empty($fetched_data['background_image'])) {
                    @unlink($fetched_data['background_image']);
                }
                if (Wo_UpdatePageData($image_data['page_id'], $image_data)) {
                    return true;
                }
            }
        }
    }
}

function Wo_UserBirthday($birthday)
{
    global $wo;
    if (empty($birthday)) {
        return false;
    }
    $birthday = Wo_Secure($birthday);
    if ($wo['config']['age'] == 0) {
        $age = date_diff(date_create($birthday), date_create('today'))->y;
    } else {
        $age_style = explode('-', $birthday);
        $age = $age_style[1] . '/' . $age_style[2] . '/' . $age_style[0];
    }
    return $age;
}

function Wo_GetAllUsers($limit = '', $type = '', $filter = array(), $after = '')
{
    global $wo, $sqlConnect;
    $data = array();
    $query_one = " SELECT `user_id` FROM " . T_USERS . " WHERE `type` = 'user'";
    if (isset($filter) and !empty($filter)) {
        if (!empty($filter['query'])) {
            $query_one .= " AND ((`email` LIKE '%" . Wo_Secure($filter['query']) . "%') OR (`username` LIKE '%" . Wo_Secure($filter['query']) . "%') OR CONCAT( `first_name`,  ' ', `last_name` ) LIKE  '%" . Wo_Secure($filter['query']) . "%')";
        }
        if (isset($filter['source']) && $filter['source'] != 'all') {
            $query_one .= " AND `src` = '" . Wo_Secure($filter['source']) . "'";
        }
        if (isset($filter['status']) && $filter['status'] != 'all') {
            $query_one .= " AND `active` = '" . Wo_Secure($filter['status']) . "'";
        }
    }
    if (!empty($after) && is_numeric($after) && $after > 0) {
        $query_one .= " AND `user_id` < " . Wo_Secure($after);
    }
    if ($type == 'sidebar') {
        $query_one .= " ORDER BY RAND()";
    } else {
        $query_one .= " ORDER BY `user_id` DESC";
    }
    if (isset($limit) and !empty($limit)) {
        $query_one .= " LIMIT {$limit}";
    }
    $sql = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            $user_data = Wo_UserData($fetched_data['user_id']);
            $user_data['src'] = ($user_data['src'] == 'site') ? $wo['config']['siteName'] : $user_data['src'];;
            $data[] = $user_data;
        }
    }
    return $data;
}

function Wo_GetAllUsersByType($type = 'all')
{
    global $sqlConnect;
    $data = array();
    $query_one = " SELECT `user_id` FROM " . T_USERS;
    if ($type == 'active') {
        $query_one .= " WHERE `active` = '1'";
    } else if ($type == 'inactive') {
        $query_one .= " WHERE `active` = '0' OR `active` = '2'";
    } else if ($type == 'all') {
        $query_one .= "";
    }
    $sql = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            $data[] = Wo_UserData($fetched_data['user_id']);
        }
    }
    return $data;
}

function Wo_GetUsersByTime($type = 'week')
{
    global $sqlConnect;
    $types = array(
        'week',
        'month',
        '3month',
        '6month',
        '9month',
        'year'
    );
    if (empty($type) || !in_array($type, $types)) {
        return array();
    }
    $data = array();
    $end = time() - (60 * 60 * 24 * 7);
    $start = time() - (60 * 60 * 24 * 14);
    if ($type == 'month') {
        $end = time() - (60 * 60 * 24 * 30);
        $start = time() - (60 * 60 * 24 * 60);
    }
    if ($type == '3month') {
        $end = time() - (60 * 60 * 24 * 61);
        $start = time() - (60 * 60 * 24 * 150);
    }
    if ($type == '6month') {
        $end = time() - (60 * 60 * 24 * 151);
        $start = time() - (60 * 60 * 24 * 210);
    }
    if ($type == '9month') {
        $end = time() - (60 * 60 * 24 * 211);
        $start = time() - (60 * 60 * 24 * 300);
    }
    if ($type == 'year') {
        $end = time() - (60 * 60 * 24 * 365);
    }
    $sub1 = " WHERE `lastseen` >= '{$start}' ";
    $sub2 = " AND `lastseen` <= '{$end}' ";
    if ($type == 'year') {
        $sub2 = "";
    }
    $query_one = " SELECT `user_id` FROM " . T_USERS . $sub1 . $sub2;
    $sql = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            $data[] = Wo_UserData($fetched_data['user_id']);
        }
    }
    return $data;
}

function Wo_GetFollowingSug($limit, $query)
{
    global $wo, $sqlConnect;
    $data = array();
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($query)) {
        return false;
    }
    $query_one_search = " WHERE ((`username` LIKE '%" . Wo_Secure($query) . "%') OR CONCAT( `first_name`,  ' ', `last_name` ) LIKE  '%" . Wo_Secure($query) . "%')";
    $user_id = Wo_Secure($wo['user']['user_id']);
    $query_one = "SELECT `user_id` FROM " . T_USERS;
    $query_one .= $query_one_search;
    $logged_user_id = Wo_Secure($wo['user']['user_id']);
    $query_one .= " AND (`user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}') AND (`user_id` IN (SELECT `following_id` FROM " . T_FOLLOWERS . " WHERE `follower_id` = {$user_id} AND `following_id` <> {$user_id} AND `active` = '1') OR `user_id` IN (SELECT `follower_id` FROM " . T_FOLLOWERS . " WHERE `follower_id` <> {$user_id} AND `following_id` = {$user_id} AND `active` = '1'))) AND `active` = '1'";
    $query_one .= " LIMIT {$limit}";
    $sql = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            $user_data = Wo_UserData($fetched_data['user_id']);
            $html_fi['id'] = $user_data['id'];
            $html_fi['username'] = $user_data['username'];
            $html_fi['label'] = $user_data['name'];
            $html_fi['img'] = $user_data['avatar'];
            $data[] = $html_fi;
        }
    }
    if (empty($data)) {
        $sql = mysqli_query($sqlConnect, "SELECT `user_id` FROM " . T_USERS . " {$query_one_search} AND `user_id` <> {$user_id} AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}') LIMIT {$limit}");
        if (mysqli_num_rows($sql)) {
            while ($fetched_data = mysqli_fetch_assoc($sql)) {
                $user_data = Wo_UserData($fetched_data['user_id']);
                $html_fi['username'] = $user_data['username'];
                $html_fi['label'] = $user_data['name'];
                $html_fi['img'] = $user_data['avatar'];
                $data[] = $html_fi;
            }
        }
    }
    return $data;
}

function Wo_GetHashtagSug($limit, $query)
{
    global $wo, $sqlConnect;
    $data = array();
    $html_fi = array();
    $query_one = "SELECT * FROM " . T_HASHTAGS . " WHERE `tag` LIKE '%{$query}%' ORDER BY `trend_use_num` DESC";
    $query_one .= " LIMIT {$limit}";
    $sql = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            $html_fi['username'] = $fetched_data['tag'];
            $html_fi['label'] = $fetched_data['tag'];
            $data[] = $html_fi;
        }
    }
    return $data;
}

function Wo_WelcomeUsers($limit = '', $type = '')
{
    global $wo, $sqlConnect;
    if (empty($limit)) {
        $limit = 12;
    }
    $data = array();
    $query_one = " SELECT `user_id` FROM " . T_USERS . " WHERE `active` = '1' AND `avatar` <> '" . Wo_Secure($wo['userDefaultAvatar']) . "' ORDER BY RAND() LIMIT {$limit}";
    $sql = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            $data[] = Wo_UserData($fetched_data['user_id']);
        }
    }
    return $data;
}

function Wo_FeaturedUsersAPI($limit = '', $offset = '')
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    $pro_types = array();
    $type_text = "";
    foreach ($wo['pro_packages'] as $key => $value) {
        if ($value['featured_member'] == 1) {
            $pro_types[] = "'" . $value['id'] . "'";
        }
    }
    if (!empty($pro_types)) {
        $type_text = " AND `pro_type` IN (" . implode(',', $pro_types) . ")";
    }
    $data = array();
    $logged_user_id = $wo['user']['user_id'];
    $offset_query = '';
    if (!empty($offset)) {
        $offset_query = " AND `user_id` < $offset ";
    }
    $query_one = " SELECT `user_id` FROM " . T_USERS . " WHERE `active` = '1' AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}') AND `is_pro` = '1' {$type_text} {$offset_query} ORDER BY `user_id` DESC LIMIT {$limit}";
    $sql = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            $data[] = Wo_UserData($fetched_data['user_id']);
        }
    }
    return $data;
}

function Wo_FeaturedUsers($limit = '', $type = '')
{
    global $wo, $sqlConnect;
    // if ($wo['loggedin'] == false) {
    //     return false;
    // }
    $pro_types = array(
        1,
        2,
        3,
        4
    );
    $type_text = "";
    foreach ($wo['pro_packages'] as $key => $value) {
        if ($value['featured_member'] == 1) {
            $pro_types[] = "'" . $value['id'] . "'";
        }
    }
    if (!empty($pro_types)) {
        $type_text = " AND `pro_type` IN (" . implode(',', $pro_types) . ")";
    }
    $data = array();
    $logged_user_id = $wo['user']['user_id'];
    $query_one = " SELECT `user_id` FROM " . T_USERS . " WHERE `active` = '1' AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}') AND `is_pro` = '1' {$type_text} ORDER BY RAND() LIMIT {$limit}";
    $sql = mysqli_query($sqlConnect, $query_one);
    $mysql_count = mysqli_num_rows($sql);
    if ($mysql_count > 7) {
        $query_one = " SELECT `user_id` FROM " . T_USERS . " WHERE `active` = '1' AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}') AND `is_pro` = '1' {$type_text} ORDER BY RAND() LIMIT {$limit}";
        $sql = mysqli_query($sqlConnect, $query_one);
        if (mysqli_num_rows($sql)) {
            while ($fetched_data = mysqli_fetch_assoc($sql)) {
                $data[] = Wo_UserData($fetched_data['user_id']);
            }
        }
    } else {
        if (mysqli_num_rows($sql)) {
            while ($fetched_data = mysqli_fetch_assoc($sql)) {
                $data[] = Wo_UserData($fetched_data['user_id']);
            }
        }
    }
    return $data;
}

function Wo_UserSug($limit = 20)
{
    global $wo, $sqlConnect;
    if (!is_numeric($limit)) {
        return false;
    }
    $data = array();
    $user_id = Wo_Secure($wo['user']['user_id']);
    $query_one = " SELECT `user_id` FROM " . T_USERS . " WHERE `active` = '1' AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$user_id}') AND `user_id` NOT IN (SELECT `following_id` FROM " . T_FOLLOWERS . " WHERE `follower_id` = {$user_id}) AND `user_id` <> {$user_id}";
    if (isset($limit)) {
        $query_one .= " ORDER BY RAND() LIMIT {$limit}";
    }
    $sql = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            $data[] = Wo_UserData($fetched_data['user_id']);
        }
    }
    return $data;
}

function Wo_ImportImageFromLogin($media, $amazon = 0)
{
    global $wo;
    if (!file_exists('upload/photos/' . date('Y'))) {
        mkdir('upload/photos/' . date('Y'), 0777, true);
    }
    if (!file_exists('upload/photos/' . date('Y') . '/' . date('m'))) {
        mkdir('upload/photos/' . date('Y') . '/' . date('m'), 0777, true);
    }
    $dir = 'upload/photos/' . date('Y') . '/' . date('m');
    $file_dir = $dir . '/' . Wo_GenerateKey() . '_avatar.jpg';
    $getImage = fetchDataFromURL($media);
    if (!empty($getImage)) {
        $importImage = file_put_contents($file_dir, $getImage);
        if ($importImage) {
            Wo_Resize_Crop_Image($wo['profile_picture_width_crop'], $wo['profile_picture_height_crop'], $file_dir, $file_dir, 100);
        }
    }
    if (file_exists($file_dir)) {
        $upload_s3 = Wo_UploadToS3($file_dir, array(
            'amazon' => $amazon
        ));
        return $file_dir;
    } else {
        return false;
    }
}

// function Wo_ImportImageFromFile($media, $custom_name = '_url_image') {
//     global $wo;
//     if (empty($media)) {
//         return false;
//     }
//     if (!file_exists('upload/photos/' . date('Y'))) {
//         mkdir('upload/photos/' . date('Y'), 0777, true);
//     }
//     if (!file_exists('upload/photos/' . date('Y') . '/' . date('m'))) {
//         mkdir('upload/photos/' . date('Y') . '/' . date('m'), 0777, true);
//     }
//     $extension = 0; //image_type_to_extension($size[2]);
//     if (empty($extension)) {
//         $extension = '.jpg';
//     }
//     $dir               = 'upload/photos/' . date('Y') . '/' . date('m');
//     $file_dir          = $dir . '/' . Wo_GenerateKey() . $custom_name . $extension;
//     $fileget           = file_get_contents($media);
//     if (!empty($fileget)) {
//         $importImage = @file_put_contents($file_dir, $fileget);
//     }
//     if (file_exists($file_dir)) {
//         $upload_s3 = Wo_UploadToS3($file_dir);
//         $check_image = getimagesize($file_dir);
//         if (!$check_image) {
//             unlink($file_dir);
//         }
//         return $file_dir;
//     } else {
//         return false;
//     }
// }
function Wo_ImportImageFromFile($media, $custom_name = '_url_image', $type = '')
{
    global $wo;
    if (empty($media)) {
        return false;
    }
    if (!file_exists('upload/photos/' . date('Y'))) {
        mkdir('upload/photos/' . date('Y'), 0777, true);
    }
    if (!file_exists('upload/photos/' . date('Y') . '/' . date('m'))) {
        mkdir('upload/photos/' . date('Y') . '/' . date('m'), 0777, true);
    }
    $extension = 0; //image_type_to_extension($size[2]);
    if (empty($extension)) {
        $extension = '.jpg';
    }
    $dir = 'upload/photos/' . date('Y') . '/' . date('m');
    $file_dir = $dir . '/' . Wo_GenerateKey() . $custom_name . $extension;
    $fileget = file_get_contents($media);
    if (!empty($fileget)) {
        $importImage = @file_put_contents($file_dir, $fileget);
    }
    if (file_exists($file_dir)) {
        if ($type == 'avatar' || $type == 'cover') {
            $filename = $file_dir;
            $explode2 = @end(explode('.', $filename));
            $explode3 = @explode('.', $filename);
            $last_file = $explode3[0] . '_full.' . $explode2;
            $compress = Wo_CompressImage($filename, $last_file, $wo['config']['images_quality']);
            if ($compress) {
                Wo_UploadToS3($last_file);
                if ($type == 'avatar') {
                    Wo_Resize_Crop_Image($wo['profile_picture_width_crop'], $wo['profile_picture_height_crop'], $filename, $filename, $wo['profile_picture_image_quality']);
                }
            }
        }
        $upload_s3 = Wo_UploadToS3($file_dir);
        $check_image = getimagesize($file_dir);
        if (!$check_image) {
            unlink($file_dir);
        }
        return $file_dir;
    } else {
        return false;
    }
}

function Wo_ImportImageFromUrl($media, $custom_name = '_url_image')
{
    global $wo;
    if (empty($media)) {
        return false;
    }
    if (!file_exists('upload/photos/' . date('Y'))) {
        mkdir('upload/photos/' . date('Y'), 0777, true);
    }
    if (!file_exists('upload/photos/' . date('Y') . '/' . date('m'))) {
        mkdir('upload/photos/' . date('Y') . '/' . date('m'), 0777, true);
    }
    //$size      = getimagesize($media);
    $extension = 0; //image_type_to_extension($size[2]);
    if (empty($extension)) {
        $extension = '.jpg';
    }
    $dir = 'upload/photos/' . date('Y') . '/' . date('m');
    $file_dir = $dir . '/' . Wo_GenerateKey() . $custom_name . $extension;
    $fileget = fetchDataFromURL($media);
    if (!empty($fileget)) {
        $importImage = @file_put_contents($file_dir, $fileget);
    }
    if (file_exists($file_dir)) {
        $check_image = getimagesize($file_dir);
        $upload_s3 = Wo_UploadToS3($file_dir);
        if (!$check_image) {
            unlink($file_dir);
        }
        return $file_dir;
    } else {
        return false;
    }
}

function Wo_IsFollowingNotify($following_id, $user_id = 0)
{
    global $sqlConnect, $wo;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($following_id) || !is_numeric($following_id) || $following_id < 0) {
        return false;
    }
    if ((empty($user_id) || !is_numeric($user_id) || $user_id < 0)) {
        $user_id = $wo['user']['user_id'];
    }
    $following_id = Wo_Secure($following_id);
    $user_id = Wo_Secure($user_id);
    $query = mysqli_query($sqlConnect, " SELECT COUNT(`id`) FROM " . T_FOLLOWERS . " WHERE `following_id` = {$following_id} AND `follower_id` = {$user_id} AND `active` = '1' AND `notify` = '1'");
    return (Wo_Sql_Result($query, 0) == 1) ? true : false;
}

function Wo_IsFollowing($following_id, $user_id = 0)
{
    global $sqlConnect, $wo;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($following_id) || !is_numeric($following_id) || $following_id < 0) {
        return false;
    }
    if ((empty($user_id) || !is_numeric($user_id) || $user_id < 0)) {
        $user_id = $wo['user']['user_id'];
    }
    $following_id = Wo_Secure($following_id);
    $user_id = Wo_Secure($user_id);
    $query = mysqli_query($sqlConnect, " SELECT COUNT(`id`) FROM " . T_FOLLOWERS . " WHERE `following_id` = {$following_id} AND `follower_id` = {$user_id} AND `active` = '1' ");
    return (Wo_Sql_Result($query, 0) == 1) ? true : false;
}

function Wo_RegisterFollow($following_id = 0, $followers_id = 0)
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (!isset($following_id) or empty($following_id) or !is_numeric($following_id) or $following_id < 1) {
        return false;
    }
    if (!is_array($followers_id)) {
        $followers_id = array(
            $followers_id
        );
    }
    foreach ($followers_id as $follower_id) {
        if (!isset($follower_id) or empty($follower_id) or !is_numeric($follower_id) or $follower_id < 1) {
            continue;
        }
        if (Wo_IsBlocked($following_id)) {
            continue;
        }
        $following_id = Wo_Secure($following_id);
        $follower_id = Wo_Secure($follower_id);
        $active = 1;
        if (Wo_IsFollowing($following_id, $follower_id) === true) {
            continue;
        }
        $follower_data = Wo_UserData($follower_id);
        $following_data = Wo_UserData($following_id);
        if (empty($follower_data['user_id']) || empty($following_data['user_id'])) {
            continue;
        }

        if ($following_data['follow_privacy'] == 1) {
            if (Wo_IsFollowing($follower_id, $following_id) === false) {
                return false;
            }
        }
        if ($following_data['confirm_followers'] == 1) {
            $active = 0;
        }
        if ($wo['config']['connectivitySystem'] == 1) {
            $active = 0;
        }
        $query = mysqli_query($sqlConnect, " INSERT INTO " . T_FOLLOWERS . " (`following_id`,`follower_id`,`active`) VALUES ({$following_id},{$follower_id},'{$active}')");
        if ($query) {
            cache($following_id, 'users', 'delete');
            cache($follower_id, 'users', 'delete');
            if ($active == 1) {
                $notification_data = array(
                    'recipient_id' => $following_id,
                    'notifier_id' => $follower_id,
                    'type' => 'following',
                    'url' => 'index.php?link1=timeline&u=' . $follower_data['username']
                );
                Wo_RegisterNotification($notification_data);
                $activity_data = array(
                    'user_id' => $follower_id,
                    'follow_id' => $following_id,
                    'activity_type' => 'following'
                );
                $add_activity = Wo_RegisterActivity($activity_data);
            }
            else{
                $notification_data = array(
                    'recipient_id' => $following_id,
                    'notifier_id' => $follower_id,
                    'type' => 'friends_request',
                    'url' => ''
                );
                Wo_RegisterNotification($notification_data);
            }
        }
    }
    return true;
}

function Wo_CountFollowRequests($data = array())
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    $get = array();
    $user_id = Wo_Secure($wo['user']['user_id']);
    if (empty($data['account_id']) || $data['account_id'] == 0) {
        $data['account_id'] = $user_id;
        $account = $wo['user'];
    }
    if (!is_numeric($data['account_id']) || $data['account_id'] < 1) {
        return false;
    }
    if ($data['account_id'] != $user_id) {
        $data['account_id'] = Wo_Secure($data['account_id']);
        $account = Wo_UserData($data['account_id']);
    }
    $query_one = " SELECT COUNT(`id`) AS `FollowRequests` FROM " . T_FOLLOWERS . " WHERE `active` = '0' AND `following_id` =  " . $account['user_id'] . " AND `follower_id` IN (SELECT `user_id` FROM " . T_USERS . " WHERE `active` = '1')";
    if (isset($data['unread']) && $data['unread'] == true) {
        $query_one .= " AND `seen` = 0";
    }
    $query_one .= " ORDER BY `id` DESC";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        $sql_fetch_one = mysqli_fetch_assoc($sql_query_one);
        return $sql_fetch_one['FollowRequests'];
    }
    return false;
}

function Wo_IsFollowRequested($following_id = 0, $follower_id = 0)
{
    global $sqlConnect, $wo;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (!isset($following_id) or empty($following_id) or !is_numeric($following_id) or $following_id < 1) {
        return false;
    }
    if ((!isset($follower_id) or empty($follower_id) or !is_numeric($follower_id) or $follower_id < 1)) {
        $follower_id = $wo['user']['user_id'];
    }
    if (!is_numeric($follower_id) or $follower_id < 1) {
        return false;
    }
    $following_id = Wo_Secure($following_id);
    $follower_id = Wo_Secure($follower_id);
    $query = "SELECT `id` FROM " . T_FOLLOWERS . " WHERE `follower_id` = {$follower_id} AND `following_id` = {$following_id} AND `active` = '0'";
    $sql_query = mysqli_query($sqlConnect, $query);
    if (mysqli_num_rows($sql_query) > 0) {
        return true;
    }
}

function Wo_DeleteFollow($following_id = 0, $follower_id = 0)
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (!isset($following_id) or empty($following_id) or !is_numeric($following_id) or $following_id < 1) {
        return false;
    }
    if (!isset($follower_id) or empty($follower_id) or !is_numeric($follower_id) or $follower_id < 1) {
        return false;
    }
    $following_id = Wo_Secure($following_id);
    $follower_id = Wo_Secure($follower_id);
    if (Wo_IsFollowing($following_id, $follower_id) === false && Wo_IsFollowRequested($following_id, $follower_id) === false) {
        return false;
    } else {
        $query = mysqli_query($sqlConnect, " DELETE FROM " . T_FOLLOWERS . " WHERE `following_id` = {$following_id} AND `follower_id` = {$follower_id}");
        if ($wo['config']['connectivitySystem'] == 1) {
            $query_two = "DELETE FROM " . T_FOLLOWERS . " WHERE `follower_id` = {$following_id} AND `following_id` = {$follower_id}";
            $sql_query_two = mysqli_query($sqlConnect, $query_two);
            Wo_DeleteSelectedActivity($follower_id, 'friend', $following_id);
            Wo_DeleteSelectedActivity($following_id, 'friend', $follower_id);
        } else {
            Wo_DeleteSelectedActivity($follower_id, 'following', $following_id);
        }
        if ($query) {
            cache($following_id, 'users', 'delete');
            cache($follower_id, 'users', 'delete');
            return true;
        }
    }
}

function Wo_CountMutualFriends($user_id, $active = true)
{
    global $wo, $sqlConnect;
    $data = array();
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $user_id = Wo_Secure($user_id);
    $sub_sql = '';
    if ($active === true) {
        $sub_sql = "AND `active` = '1'";
    }
    $logged_user_id = Wo_Secure($wo['user']['user_id']);
    $query_text = "SELECT f1.following_id
FROM " . T_FOLLOWERS . " f1 INNER JOIN " . T_FOLLOWERS . " f2
  ON f1.following_id = f2.following_id
WHERE f1.follower_id = {$user_id}
  AND f2.follower_id = {$logged_user_id} AND f1.`following_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND f1.`following_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}') AND f1.active = 1 GROUP BY following_id";
    $query = mysqli_query($sqlConnect, $query_text);
    $fetched_data = mysqli_num_rows($query);
    return $fetched_data;
}

function Wo_CountFollowing($user_id, $active = true)
{
    global $wo, $sqlConnect;
    $data = array();
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $user_id = Wo_Secure($user_id);
    $sub_sql = '';
    if ($active === true) {
        $sub_sql = "AND `active` = '1'";
    }
    $query_text = "SELECT COUNT(`user_id`) AS count FROM " . T_USERS . " WHERE `user_id` IN (SELECT `following_id` FROM " . T_FOLLOWERS . " WHERE `follower_id` = {$user_id} AND `following_id` <> {$user_id} {$sub_sql}) {$sub_sql}";
    if ($wo['loggedin'] == true) {
        $logged_user_id = Wo_Secure($wo['user']['user_id']);
        $query_text .= " AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}')";
    }
    $query = mysqli_query($sqlConnect, $query_text);
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        return $fetched_data['count'];
    }
    return false;
}

function Wo_AcceptFollowRequest($following_id = 0, $follower_id = 0)
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (!isset($following_id) or empty($following_id) or !is_numeric($following_id) or $following_id < 1) {
        return false;
    }
    if (!isset($follower_id) or empty($follower_id) or !is_numeric($follower_id) or $follower_id < 1) {
        return false;
    }
    $following_id = Wo_Secure($following_id);
    $follower_id = Wo_Secure($follower_id);
    if (Wo_IsFollowRequested($following_id, $follower_id) === false) {
        return false;
    }
    $follower_data = Wo_UserData($follower_id);
    if (empty($follower_data['user_id'])) {
        return false;
    }
    $query = mysqli_query($sqlConnect, "UPDATE " . T_FOLLOWERS . " SET `active` = '1' WHERE `following_id` = {$follower_id} AND `follower_id` = {$following_id} AND `active` = '0'");
    if ($wo['config']['connectivitySystem'] == 1) {
        $query_two = mysqli_query($sqlConnect, "INSERT INTO " . T_FOLLOWERS . " (`following_id`,`follower_id`,`active`) VALUES ({$following_id},{$follower_id},'1') ");
    }
    if ($query) {
        $notification_data = array(
            'recipient_id' => $following_id,
            'type' => 'accepted_request',
            'url' => 'index.php?link1=timeline&u=' . $follower_data['username']
        );
        $activity_data = array(
            'user_id' => $follower_id,
            'follow_id' => $following_id,
            'activity_type' => 'friend'
        );
        $add_activity = Wo_RegisterActivity($activity_data);
        $activity_data = array(
            'user_id' => $following_id,
            'follow_id' => $follower_id,
            'activity_type' => 'friend'
        );
        $add_activity = Wo_RegisterActivity($activity_data);
        if (Wo_RegisterNotification($notification_data) === true) {
            return true;
        } else {
            return false;
        }
    }
}

function Wo_DeleteFollowRequest($following_id, $follower_id)
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (!isset($following_id) or empty($following_id) or !is_numeric($following_id) or $following_id < 1) {
        return false;
    }
    if (!isset($follower_id) or empty($follower_id) or !is_numeric($follower_id) or $follower_id < 1) {
        return false;
    }
    $following_id = Wo_Secure($following_id);
    $follower_id = Wo_Secure($follower_id);
    if (Wo_IsFollowRequested($following_id, $follower_id) === false) {
        return false;
    } else {
        $query = mysqli_query($sqlConnect, " DELETE FROM " . T_FOLLOWERS . " WHERE `following_id` = {$follower_id} AND `follower_id` = {$following_id} ");
        if ($query) {
            return true;
        }
    }
}

function Wo_GetFollowRequests($user_id = 0, $search_query = '')
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    $data = array();
    if (empty($user_id) or $user_id == 0) {
        $user_id = $wo['user']['user_id'];
    }
    if (!is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $user_id = Wo_Secure($user_id);
    $query = "SELECT `user_id` FROM " . T_USERS . " WHERE `user_id` IN (SELECT `follower_id` FROM " . T_FOLLOWERS . " WHERE `follower_id` <> {$user_id} AND `following_id` = {$user_id} AND `active` = '0') AND `active` = '1' ";
    if (!empty($search_query)) {
        $search_query = Wo_Secure($search_query);
        $query .= " AND `name` LIKE '%$search_query%'";
    }
    $query .= " ORDER BY `user_id` DESC";
    $sql_query = mysqli_query($sqlConnect, $query);
    if (mysqli_num_rows($sql_query)) {
        while ($sql_fetch = mysqli_fetch_assoc($sql_query)) {
            $data[] = Wo_UserData($sql_fetch['user_id']);
        }
    }
    return $data;
}

function GetGroupChatRequests()
{
    global $wo, $sqlConnect, $db;
    if ($wo['loggedin'] == false) {
        return false;
    }
    return $db->where('user_id', $wo['user']['id'])->where('active', '0')->get(T_GROUP_CHAT_USERS);
}

function Wo_CountFollowers($user_id)
{
    global $wo, $sqlConnect;
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $data = array();
    $user_id = Wo_Secure($user_id);
    $query_text = " SELECT COUNT(`user_id`) AS count FROM " . T_USERS . " WHERE `user_id` IN (SELECT `follower_id` FROM " . T_FOLLOWERS . " WHERE `follower_id` <> {$user_id} AND `following_id` = {$user_id} AND `active` = '1') AND `active` = '1'";
    if ($wo['loggedin'] == true) {
        $logged_user_id = Wo_Secure($wo['user']['user_id']);
        $query_text .= " AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}')";
    }
    $query = mysqli_query($sqlConnect, $query_text);
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        return $fetched_data['count'];
    }
    return false;
}

function Wo_SearchFollowers($user_id, $filter = '', $limit = 10, $event_id = 0)
{
    global $wo, $sqlConnect;
    $data = array();
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 1) {
        return false;
    }
    if (empty($event_id)) {
        return false;
    }
    $user_id = Wo_Secure($user_id);
    $filter = Wo_Secure($filter);
    $event_id = Wo_Secure($event_id);
    $query = " SELECT `user_id` FROM " . T_USERS . " WHERE `user_id` IN (SELECT `follower_id` FROM " . T_FOLLOWERS . " WHERE `follower_id` <> {$user_id} AND `following_id` = {$user_id} AND `active` = '1') AND `active` = '1'";
    if (!empty($filter)) {
        $query .= " AND (`username` LIKE '%$filter%' OR `first_name` LIKE '%$filter%' OR `last_name` LIKE '%$filter%')";
    }
    $query .= " AND `user_id` NOT IN (SELECT `invited_id` FROM " . T_EVENTS_INV . " WHERE `inviter_id` = '$user_id') ";
    $query .= " AND `user_id` NOT IN (SELECT `user_id` FROM " . T_EVENTS_GOING . " WHERE `event_id` = '$event_id') ";
    $query .= " AND `user_id` NOT IN (SELECT `poster_id` FROM " . T_EVENTS . " WHERE `id` = '$event_id') ";
    $query .= " LIMIT {$limit} ";
    $sql_query = mysqli_query($sqlConnect, $query);
    if (mysqli_num_rows($sql_query)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query)) {
            $data[] = Wo_UserData($fetched_data['user_id']);
        }
    }
    return $data;
}

function Wo_GetFollowing($user_id, $type = '', $limit = '', $after_user_id = '', $placement = array())
{
    global $wo, $sqlConnect;
    $data = array();
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $user_id = Wo_Secure($user_id);
    $after_user_id = Wo_Secure($after_user_id);
    $query = "SELECT `user_id` FROM " . T_USERS . " WHERE `user_id` IN (SELECT `following_id` FROM " . T_FOLLOWERS . " WHERE `follower_id` = {$user_id} AND `following_id` <> {$user_id} AND `active` = '1') AND `active` = '1' ";
    if (!empty($after_user_id) && is_numeric($after_user_id)) {
        $query .= " AND `user_id` < {$after_user_id}";
    }
    if ($wo['loggedin'] == true) {
        $logged_user_id = Wo_Secure($wo['user']['user_id']);
        $query .= " AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}')";
    }
    if ($type == 'sidebar' && !empty($limit) && is_numeric($limit)) {
        $query .= " ORDER BY RAND() LIMIT {$limit}";
    }
    if ($type == 'profile' && !empty($limit) && is_numeric($limit)) {
        $query .= " ORDER BY `user_id` DESC LIMIT {$limit}";
    }
    if (!empty($placement)) {
        if ($placement['in'] == 'profile_sidebar' && is_array($placement['following_data'])) {
            foreach ($placement['following_data'] as $key => $id) {
                $user_data = Wo_UserData($id, false);
                if (!empty($user_data)) {
                    $data[] = $user_data;
                }
            }
            return $data;
        }
    }
    $sql_query = mysqli_query($sqlConnect, $query);
    if (mysqli_num_rows($sql_query)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query)) {
            $user_data = Wo_UserData($fetched_data['user_id'], false);
            if ($wo['loggedin']) {
                $user_data['family_member'] = Wo_GetFamalyMember($fetched_data['user_id'], $wo['user']['id']);
            }
            $data[] = $user_data;
        }
    }
    return $data;
}

function Wo_GetMutualFriends($user_id, $type = '', $limit = '', $after_user_id = '', $placement = array())
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    $data = array();
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $user_id = Wo_Secure($user_id);
    $after_user_id = Wo_Secure($after_user_id);
    $logged_user_id = Wo_Secure($wo['user']['user_id']);
    $query = "SELECT f1.*
FROM " . T_FOLLOWERS . " f1 INNER JOIN " . T_FOLLOWERS . " f2
  ON f1.following_id = f2.following_id
WHERE f1.follower_id = {$user_id}
  AND f2.follower_id = {$logged_user_id} AND f1.`following_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND f1.`following_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}') AND f1.active = 1 GROUP BY following_id ";
    if (!empty($after_user_id) && is_numeric($after_user_id)) {
        $query .= " AND f1.id < {$after_user_id}";
    }
    if ($type == 'sidebar' && !empty($limit) && is_numeric($limit)) {
        $query .= " ORDER BY RAND()";
    }
    if ($type == 'profile' && !empty($limit) && is_numeric($limit)) {
        $query .= " ORDER BY f1.id DESC";
    }
    $query .= " LIMIT {$limit} ";
    if (!empty($placement)) {
        if ($placement['in'] == 'profile_sidebar' && is_array($placement['mutual_friends_data'])) {
            foreach ($placement['mutual_friends_data'] as $key => $id) {
                $user_data = Wo_UserData($id, false);
                if (!empty($user_data)) {
                    $data[] = $user_data;
                }
            }
            return $data;
        }
    }
    $sql_query = mysqli_query($sqlConnect, $query);
    if ($sql_query != false && mysqli_num_rows($sql_query)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query)) {
            $user_data = Wo_UserData($fetched_data['following_id'], false);
            $data[] = $user_data;
        }
    }
    return $data;
}

function Wo_GetFollowers($user_id, $type = '', $limit = '', $after_user_id = '', $placement = array())
{
    global $wo, $sqlConnect;
    $data = array();
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $user_id = Wo_Secure($user_id);
    $after_user_id = Wo_Secure($after_user_id);
    $query = " SELECT `user_id` FROM " . T_USERS . " WHERE `user_id` IN (SELECT `follower_id` FROM " . T_FOLLOWERS . " WHERE `follower_id` <> {$user_id} AND `following_id` = {$user_id} AND `active` = '1') AND `active` = '1'";
    if (!empty($after_user_id) && is_numeric($after_user_id)) {
        $query .= " AND `user_id` < {$after_user_id}";
    }
    if ($wo['loggedin'] == true) {
        $logged_user_id = Wo_Secure($wo['user']['user_id']);
        $query .= " AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}')";
    }
    if ($type == 'sidebar' && !empty($limit) && is_numeric($limit)) {
        $query .= " ORDER BY RAND()";
    }
    if ($type == 'profile' && !empty($limit) && is_numeric($limit)) {
        $query .= " ORDER BY `user_id` DESC";
    }
    $query .= " LIMIT {$limit} ";
    if (!empty($placement)) {
        if ($placement['in'] == 'profile_sidebar' && is_array($placement['followers_data'])) {
            foreach ($placement['followers_data'] as $key => $id) {
                $user_data = Wo_UserData($id, false);
                if (!empty($user_data)) {
                    $data[] = $user_data;
                }
            }
            return $data;
        }
    }
    $sql_query = mysqli_query($sqlConnect, $query);
    if (mysqli_num_rows($sql_query)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query)) {
            $user_data = Wo_UserData($fetched_data['user_id'], false);
            if ($wo['loggedin']) {
                $user_data['family_member'] = Wo_GetFamalyMember($fetched_data['user_id'], $wo['user']['id']);
            }
            $data[] = $user_data;
        }
    }
    return $data;
}

function getRandFollower()
{
    global $wo, $sqlConnect;
    $user_id = $wo['user']['user_id'];
    $data = array();
    $query = " SELECT `user_id` FROM " . T_USERS . " WHERE `user_id` IN (SELECT `follower_id` FROM " . T_FOLLOWERS . " WHERE `active` = '1' AND ((`follower_id` <> {$user_id} AND `following_id` = {$user_id}) OR (`following_id` <> {$user_id} AND `follower_id` = {$user_id}))) AND `active` = '1' AND `user_id` != {$user_id} ORDER BY RAND() LIMIT 6";
    $sql_query = mysqli_query($sqlConnect, $query);
    if (mysqli_num_rows($sql_query)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query)) {
            $user_data = Wo_UserData($fetched_data['user_id'], false);
            $data[] = $user_data;
        }
    }
    return $data;
}

function Wo_GetFollowButton($user_id = 0)
{
    global $wo;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (!is_numeric($user_id) or $user_id < 0) {
        return false;
    }
    if ($user_id == $wo['user']['user_id']) {
        return false;
    }
    $account = $wo['follow'] = Wo_UserData($user_id);
    if (!isset($wo['follow']['user_id'])) {
        return false;
    }
    $user_id = Wo_Secure($user_id);
    $logged_user_id = Wo_Secure($wo['user']['user_id']);
    $follow_button = 'buttons/follow';
    $unfollow_button = 'buttons/unfollow';
    $add_frined_button = 'buttons/add-friend';
    $unfrined_button = 'buttons/unfriend';
    $accept_button = 'buttons/accept-request';
    $request_button = 'buttons/requested';
    if (Wo_IsFollowing($user_id, $logged_user_id)) {
        if ($wo['config']['connectivitySystem'] == 1) {
            return Wo_LoadPage($unfrined_button);
        } else {
            return Wo_LoadPage($unfollow_button);
        }
    } else {
        if (Wo_IsFollowRequested($user_id, $logged_user_id)) {
            return Wo_LoadPage($request_button);
        } else if (Wo_IsFollowRequested($logged_user_id, $user_id)) {
            return Wo_LoadPage($accept_button);
        } else {
            if ($account['follow_privacy'] == 1) {
                if (Wo_IsFollowing($logged_user_id, $user_id)) {
                    if ($wo['config']['connectivitySystem'] == 1) {
                        return Wo_LoadPage($add_frined_button);
                    } else {
                        return Wo_LoadPage($follow_button);
                    }
                }
            } else if ($account['follow_privacy'] == 0) {
                if ($wo['config']['connectivitySystem'] == 1) {
                    return Wo_LoadPage($add_frined_button);
                } else {
                    return Wo_LoadPage($follow_button);
                }
            }
        }
    }
}

function Wo_GetNotifyButton($user_id = 0)
{
    global $wo;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (!is_numeric($user_id) or $user_id < 0) {
        return false;
    }
    if ($user_id == $wo['user']['user_id']) {
        return false;
    }
    $account = $wo['follow'] = Wo_UserData($user_id);
    if (!isset($wo['follow']['user_id'])) {
        return false;
    }
    $user_id = Wo_Secure($user_id);
    $logged_user_id = Wo_Secure($wo['user']['user_id']);
    $wo['user_name_n'] = $account['name'];
    $notify_button = 'buttons/notify';
    $unnotify_button = 'buttons/unnotify';
    if (Wo_IsFollowing($user_id, $logged_user_id)) {
        if (Wo_IsFollowingNotify($user_id, $logged_user_id)) {
            if ($wo['config']['connectivitySystem'] == 1) {
                return Wo_LoadPage($unnotify_button);
            } else {
                return Wo_LoadPage($unnotify_button);
            }
        } else {
            return Wo_LoadPage($notify_button);
        }
    }
    return '';
}

function Wo_GetFollowNotifyUsers($user_id = 0)
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (!is_numeric($user_id) or $user_id < 0) {
        return false;
    }
    $user_id = Wo_Secure($user_id);
    $data = array();
    $query = mysqli_query($sqlConnect, " SELECT `follower_id` FROM " . T_FOLLOWERS . " WHERE `following_id` = {$user_id} AND `active` = '1' AND `notify` = '1'");
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $data[] = $fetched_data['follower_id'];
        }
    }
    return $data;
}

function Wo_RegisterNotification($data = array())
{
    global $wo, $sqlConnect;
    if (empty($data['session_id'])) {
        if ($wo['loggedin'] == false) {
            return false;
        }
    }
    if (!isset($data['recipient_id']) or empty($data['recipient_id']) or !is_numeric($data['recipient_id']) or $data['recipient_id'] < 1) {
        return false;
    }
    if (Wo_IsBlocked($data['recipient_id'])) {
        return false;
    }
    if (!isset($data['post_id']) or empty($data['post_id'])) {
        $data['post_id'] = 0;
    }
    if (!is_numeric($data['post_id']) or $data['recipient_id'] < 0) {
        return false;
    }
    if (empty($data['notifier_id']) or $data['notifier_id'] == 0) {
        $data['notifier_id'] = Wo_Secure($wo['user']['user_id']);
    }
    if (!is_numeric($data['notifier_id']) or $data['notifier_id'] < 1) {
        return false;
    }
    if ($data['notifier_id'] == $wo['user']['user_id']) {
        $notifier = $wo['user'];
    } else {
        $data['notifier_id'] = Wo_Secure($data['notifier_id']);
        $notifier = Wo_UserData($data['notifier_id']);
        if (!isset($notifier['user_id'])) {
            return false;
        }
    }
    if (!isset($data['comment_id']) or empty($data['comment_id'])) {
        $data['comment_id'] = 0;
    } else {
        $data['comment_id'] = Wo_Secure($data['comment_id']);
    }
    if (!isset($data['reply_id']) or empty($data['reply_id'])) {
        $data['reply_id'] = 0;
    } else {
        $data['reply_id'] = Wo_Secure($data['reply_id']);
    }
    // if ($notifier['user_id'] != $wo['user']['user_id']) {
    //     return false;
    // }
    if ($data['recipient_id'] == $data['notifier_id']) {
        return false;
    }
    if (!isset($data['text'])) {
        $data['text'] = '';
    }
    if (!isset($data['type']) or empty($data['type'])) {
        return false;
    }
    if (!isset($data['url']) and empty($data['url']) and !isset($data['full_link']) and empty($data['full_link'])) {
        return false;
    }
    $recipient = Wo_UserData($data['recipient_id']);
    if (!isset($recipient['user_id'])) {
        return false;
    }
    $url = $data['url'];
    $recipient['user_id'] = Wo_Secure($recipient['user_id']);
    $data['post_id'] = Wo_Secure($data['post_id']);
    $data['type'] = Wo_Secure($data['type']);
    if (!empty($data['type2'])) {
        $data['type2'] = Wo_Secure($data['type2']);
    } else {
        $data['type2'] = '';
    }
    if ($data['text'] != strip_tags($data['text'])) {
        $data['text'] = '';
    }
    $data['text'] = Wo_Secure($data['text']);
    $notifier['user_id'] = Wo_Secure($notifier['user_id']);
    $page_notifcation_query = '';
    $page_notifcation_query2 = '';
    $send_notification = true;
    if (!empty($recipient['notification_settings'])) {
        //$old = unserialize(html_entity_decode($recipient['notification_settings']));
        $recipient['notification_settings'] = (array)json_decode(html_entity_decode($recipient['notification_settings']));
        // if (empty($recipient['notification_settings']) && !empty($old)) {
        //     $impload   = json_encode($old);
        //     $query_one = " UPDATE " . T_USERS . " SET `notification_settings` = '{$impload}' WHERE `user_id` = '".$recipient['user_id']."' ";
        //     //$query1    = mysqli_query($sqlConnect, $query_one);
        //     // Wo_UpdateUserData($recipient['user_id'], array(
        //     //     'notification_settings' => json_encode(value)
        //     // ));
        // }
    } else {
        $recipient['notification_settings'] = array();
    }
    if (($data['type'] == 'liked_post' || $data['type'] == 'reaction') && $recipient['notification_settings']['e_liked'] != 1) {
        $send_notification = false;
    }
    if ($data['type'] == 'share_post' && $recipient['notification_settings']['e_shared'] != 1) {
        $send_notification = false;
    }
    if ($data['type'] == 'comment' && $recipient['notification_settings']['e_commented'] != 1) {
        $send_notification = false;
    }
    if ($data['type'] == 'following' && $recipient['notification_settings']['e_followed'] != 1) {
        $send_notification = false;
    }
    if ($data['type'] == 'wondered_post' && $recipient['notification_settings']['e_wondered'] != 1) {
        $send_notification = false;
    }
    if (($data['type'] == 'comment_mention' || $data['type'] == 'post_mention') && $recipient['notification_settings']['e_mentioned'] != 1) {
        $send_notification = false;
    }
    if ($data['type'] == 'accepted_request' && $recipient['notification_settings']['e_accepted'] != 1) {
        $send_notification = false;
    }
    if ($data['type'] == 'visited_profile' && $recipient['notification_settings']['e_visited'] != 1) {
        $send_notification = false;
    }
    if ($data['type'] == 'joined_group' && $recipient['notification_settings']['e_joined_group'] != 1) {
        $send_notification = false;
    }
    if ($data['type'] == 'liked_page' && $recipient['notification_settings']['e_liked_page'] = !1) {
        $send_notification = false;
    }
    if ($data['type'] == 'profile_wall_post' && $recipient['notification_settings']['e_profile_wall_post'] != 1) {
        $send_notification = false;
    }
    if ($send_notification == false) {
        return false;
    }
    if (!empty($data['page_id']) && $data['page_id'] > 0) {
        $page = Wo_PageData($data['page_id']);
        if (!isset($page['page_id'])) {
            return false;
        }
        $page_id = Wo_Secure($page['page_id']);
        if (isset($data['page_enable'])) {
            if ($data['page_enable'] !== false) {
                $notifier['user_id'] = 0;
            }
        } else {
            $notifier['user_id'] = 0;
        }
        $page_notifcation_query = '`page_id`,';
        $page_notifcation_query2 = "{$page_id}, ";
    }
    $group_notifcation_query = '';
    $group_notifcation_query2 = '';
    if (!empty($data['group_id']) && $data['group_id'] > 0) {
        $group = Wo_GroupData($data['group_id']);
        if (!isset($group['id'])) {
        }
        $group_id = Wo_Secure($group['id']);
        $group_notifcation_query = '`group_id`,';
        $group_notifcation_query2 = "{$group_id}, ";
    }
    $event_notifcation_query = '';
    $event_notifcation_query2 = '';
    if (!empty($data['event_id']) && $data['event_id'] > 0) {
        $event = Wo_EventData($data['event_id']);
        $event_id = Wo_Secure($event['id']);
        $event_notifcation_query = '`event_id`,';
        $event_notifcation_query2 = "{$event_id}, ";
    }
    $thread_notifcation_query = '';
    $thread_notifcation_query2 = '';
    if (!empty($data['thread_id']) && $data['thread_id'] > 0) {
        $thread_id = Wo_Secure($data['thread_id']);
        $thread_notifcation_query = '`thread_id`,';
        $thread_notifcation_query2 = "{$thread_id}, ";
    }
    $story_notifcation_query = '';
    $story_notifcation_query2 = '';
    if (!empty($data['story_id']) && $data['story_id'] > 0) {
        $story_id = Wo_Secure($data['story_id']);
        $story_notifcation_query = '`story_id`,';
        $story_notifcation_query2 = "{$story_id}, ";
    }
    $blog_notifcation_query = '';
    $blog_notifcation_query2 = '';
    if (!empty($data['blog_id']) && $data['blog_id'] > 0) {
        $blog_id = Wo_Secure($data['blog_id']);
        $blog_notifcation_query = '`blog_id`,';
        $blog_notifcation_query2 = "{$blog_id}, ";
    }
    $group_chat_notifcation_query = '';
    $group_chat_notifcation_query2 = '';
    if (!empty($data['group_chat_id']) && $data['group_chat_id'] > 0) {
        $group_chat_id = Wo_Secure($data['group_chat_id']);
        $group_chat_notifcation_query = ',`group_chat_id`';
        $group_chat_notifcation_query2 = ",{$group_chat_id} ";
    }
    $query_one = " SELECT `id` FROM " . T_NOTIFICATION . " WHERE `recipient_id` = " . $recipient['user_id'] . " AND `post_id` = " . $data['post_id'] . " AND `type` = '" . $data['type'] . "'";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) > 0) {
        if ($data['type'] != "following") {
            if ($data['type'] != "reaction" && empty($data['story_id'])) {
                $query_two = " DELETE FROM " . T_NOTIFICATION . " WHERE `recipient_id` = " . $recipient['user_id'] . " AND `post_id` = " . $data['post_id'] . " AND `type` = '" . $data['type'] . "'";
                $sql_query_two = mysqli_query($sqlConnect, $query_two);
            } elseif (!empty($data['story_id'])) {
                $query_two = " DELETE FROM " . T_NOTIFICATION . " WHERE `recipient_id` = " . $recipient['user_id'] . " AND `story_id` = " . $data['story_id'] . " AND `type` = '" . $data['type'] . "'";
                $sql_query_two = mysqli_query($sqlConnect, $query_two);
            } elseif ($data['type'] == "reaction" && $data['text'] == "message") {
                $query_two = " DELETE FROM " . T_NOTIFICATION . " WHERE `recipient_id` = " . $recipient['user_id'] . " AND `type` = '" . $data['type'] . "'";
                $sql_query_two = mysqli_query($sqlConnect, $query_two);
            }
        }
    }
    if (!isset($data['undo']) or $data['undo'] != true) {
        $query_three = "INSERT INTO " . T_NOTIFICATION . " (`recipient_id`, `notifier_id`, {$page_notifcation_query} {$group_notifcation_query} {$story_notifcation_query} {$blog_notifcation_query} {$event_notifcation_query} {$thread_notifcation_query} `post_id`, `comment_id`, `reply_id`, `type`, `type2`, `text`, `url`, `time` {$group_chat_notifcation_query}) VALUES (" . $recipient['user_id'] . "," . $notifier['user_id'] . ",{$page_notifcation_query2} {$group_notifcation_query2} {$story_notifcation_query2} {$blog_notifcation_query2} {$event_notifcation_query2} {$thread_notifcation_query2} " . $data['post_id'] . ",'" . $data['comment_id'] . "','" . $data['reply_id'] . "','" . $data['type'] . "','" . $data['type2'] . "','" . $data['text'] . "','{$url}'," . time() . " {$group_chat_notifcation_query2})";
        $sql_query_three = mysqli_query($sqlConnect, $query_three);
        $post_data = array();
        $admin_ids = array();
        if (!empty($data['post_id'])) {
            $post_data = Wo_PostData($data['post_id']);
        }
        $my_id = $wo['user']['user_id'];
        if (!empty($post_data['page_id'])) {
            $admin_post_id = $post_data['id'];
            $admins = Wo_GetPageAdmins($post_data['page_id'], 'user_id');
            // $PageData = Wo_PageData($post_data['page_id']);
            // if (!empty($PageData)) {
            //     $admin_notify = array();
            //     $admin_notify['user_id'] = $PageData['user_id'];
            //     $admin_notify['page_id'] = $post_data['page_id'];
            //     $admin_notify['is_page_onwer'] = true;
            //     $admins[] = $admin_notify;
            // }
            if (!empty($admins)) {
                foreach ($admins as $admin) {
                    if ($admin['user_id'] != $wo['user']['user_id']) {
                        $admin_id = $admin['user_id'];
                        $admin_ids[] = "('$admin_id', '$my_id', '$admin_post_id','" . $data['comment_id'] . "','" . $data['reply_id'] . "','" . $data['type'] . "','" . $data['type2'] . "','" . $data['text'] . "','{$url}'," . time() . ")";
                    }
                }
            }
        }
        if (!empty($admin_ids)) {
            $implode_query = implode(',', $admin_ids);
            $query_admins = "INSERT INTO " . T_NOTIFICATION . " (`recipient_id`, `notifier_id`, `post_id`, `comment_id`, `reply_id`, `type`, `type2`, `text`, `url`, `time`) VALUES ";
            $sql_query_three = mysqli_query($sqlConnect, $query_admins . $implode_query);
        }
        if ($sql_query_three) {
            if ($wo['config']['emailNotification'] == 1 && $recipient['emailNotification'] == 1) {
                $send_mail = false;
                if (($data['type'] == 'liked_post' || $data['type'] == 'reaction') && $recipient['e_liked'] == 1) {
                    $send_mail = true;
                }
                if (($data['type'] == 'share_post' || $data['type'] == 'shared_your_post' || $data['type'] == 'shared_a_post_in_timeline') && $recipient['e_shared'] == 1) {
                    $send_mail = true;
                }
                if ($data['type'] == 'comment' && $recipient['e_commented'] == 1) {
                    $send_mail = true;
                }
                if ($data['type'] == 'following' && $recipient['e_followed'] == 1) {
                    $send_mail = true;
                }
                if ($data['type'] == 'wondered_post' && $recipient['e_wondered'] == 1) {
                    $send_mail = true;
                }
                if (($data['type'] == 'comment_mention' || $data['type'] == 'post_mention') && $recipient['e_mentioned'] == 1) {
                    $send_mail = true;
                }
                if ($data['type'] == 'accepted_request' && $recipient['e_accepted'] == 1) {
                    $send_mail = true;
                }
                if ($data['type'] == 'visited_profile' && $recipient['e_visited'] == 1) {
                    $send_mail = true;
                }
                if ($data['type'] == 'joined_group' && $recipient['e_joined_group'] == 1) {
                    $send_mail = true;
                }
                if ($data['type'] == 'liked_page' && $recipient['e_liked_page'] == 1) {
                    $send_mail = true;
                }
                if ($data['type'] == 'profile_wall_post' && $recipient['e_profile_wall_post'] == 1) {
                    $send_mail = true;
                }
                if ($send_mail == true) {
                    $post_data_id = $post_data;
                    $post_data['text'] = '';
                    if (!empty($post_data_id['postText'])) {
                        $post_data['text'] = substr($post_data_id['postText'], 0, 20);
                    }
                    $data['notifier'] = $notifier;
                    $data['url'] = Wo_SeoLink($url);
                    $data['post_data'] = $post_data;
                    $wo['emailNotification'] = $data;
                    $send_message_data = array(
                        'from_email' => $wo['config']['siteEmail'],
                        'from_name' => $wo['config']['siteName'],
                        'to_email' => $recipient['email'],
                        'to_name' => $recipient['name'],
                        'subject' => 'New notification',
                        'charSet' => 'utf-8',
                        'message_body' => Wo_LoadPage('emails/notifiction-email'),
                        'is_html' => true,
                        'notifier' => $notifier
                    );
                    if ($wo['config']['smtp_or_mail'] == 'smtp') {
                        $send_message_data['insert_database'] = 1;
                    }
                    $send = Wo_SendMessage($send_message_data);
                }
            }
            if ($wo['config']['android_push_native'] == 1 || $wo['config']['ios_push_native'] == 1 || $wo['config']['web_push'] == 1) {
                Wo_NotificationWebPushNotifier();
            }
            return true;
        }
    }
}

function Wo_GetNotifications($data = array())
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    $get = array();
    if (!isset($data['account_id']) or empty($data['account_id'])) {
        $data['account_id'] = $wo['user']['user_id'];
    }
    if (!is_numeric($data['account_id']) or $data['account_id'] < 1) {
        return false;
    }
    if ($data['account_id'] == $wo['user']['user_id']) {
        $account = $wo['user'];
    } else {
        $data['account_id'] = $data['account_id'];
        $account = Wo_UserData($data['account_id']);
    }
    if ($account['user_id'] != $wo['user']['user_id']) {
        return false;
    }
    if (empty($data['limit'])) {
        $data['limit'] = 15;
    }
    $new_notif = Wo_CountNotifications(array(
        'unread' => true
    ));
    if ($new_notif > 0) {
        $query_4 = '';
        if (isset($data['type_2']) && !empty($data['type_2'])) {
            if ($data['type_2'] == 'popunder') {
                $timepopunder = time() - 60;
                $query_4 = ' AND `seen_pop` = 0 AND `time` >= ' . $timepopunder;
            }
        }
        $query_one = " SELECT * FROM " . T_NOTIFICATION . " WHERE `recipient_id` = " . $account['user_id'] . " AND `seen` = 0 {$query_4} ORDER BY `id` DESC";
        if (!empty($data['delete_fromDB'])) {
            $query_one .= " LIMIT 1";
        }
    } else {
        $query_one = " SELECT * FROM " . T_NOTIFICATION . " WHERE `recipient_id` = " . $account['user_id'];
        if (isset($data['unread']) && $data['unread'] == true) {
            $query_one .= " AND `seen` = 0";
        }
        if (isset($data['type_2']) && !empty($data['type_2'])) {
            if ($data['type_2'] == 'popunder') {
                $timepopunder = time() - 60;
                $query_one .= ' AND `seen_pop` = 0 AND `time` >= ' . $timepopunder;
            }
        }
        if (isset($data['remove_notification']) && !empty($data['remove_notification'])) {
            foreach ($data['remove_notification'] as $key => $remove_notification) {
                $query_one .= ' AND `type` <> "$remove_notification"';
            }
        }
        if (isset($data['offset']) && is_numeric($data['offset']) && $data['offset'] > 0) {
            $offset = Wo_Secure($data['offset']);
            $query_one .= " AND `id` < $offset ";
        }
        $query_one .= " ORDER BY `id` DESC LIMIT " . $data['limit'];
    }
    if (isset($data['all']) && $data['all'] == true) {
        $query_one = "SELECT * FROM " . T_NOTIFICATION . " WHERE `recipient_id` = " . $account['user_id'] . " AND `seen` = 0 ORDER BY `id` DESC LIMIT 20";
    }
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if ($sql_query_one) {
        if (mysqli_num_rows($sql_query_one) > 0) {
            while ($sql_fetch_one = mysqli_fetch_assoc($sql_query_one)) {
                if (!empty($sql_fetch_one['page_id']) && empty($sql_fetch_one['notifier_id'])) {
                    $sql_fetch_one['notifier'] = Wo_PageData($sql_fetch_one['page_id']);
                    $sql_fetch_one['notifier']['url'] = Wo_SeoLink('index.php?link1=timeline&u=' . $sql_fetch_one['notifier']['page_name']);
                } else {
                    if (!empty($sql_fetch_one['notifier_id'])) {
                        $sql_fetch_one['notifier'] = Wo_UserData($sql_fetch_one['notifier_id']);
                        $sql_fetch_one['notifier']['url'] = Wo_SeoLink('index.php?link1=timeline&u=' . $sql_fetch_one['notifier']['username']);
                    }
                }
                // if (preg_match_all('/^index\.php\?link1=post&id=(.*)$/i', $sql_fetch_one['url'],$matches)) {
                //     if (!empty($matches[1][0]) && is_numeric($matches[1][0])) {
                //         $post = Wo_PostData($matches[1][0]);
                //         $sql_fetch_one['url']      = $post['url'];
                //         $sql_fetch_one['ajax_url']      = '?link1=post&id='.$post['seo_id'];
                //     }
                // }
                // else{
                //     $cutted_url                = substr($sql_fetch_one['url'], 9);
                //     $sql_fetch_one['url']      = Wo_SeoLink($sql_fetch_one['url']);
                //     $sql_fetch_one['ajax_url'] = $cutted_url;
                // }
                $cutted_url = substr($sql_fetch_one['url'], 9);
                $sql_fetch_one['url'] = Wo_SeoLink($sql_fetch_one['url']);
                $sql_fetch_one['ajax_url'] = $cutted_url;
                $get[] = $sql_fetch_one;
            }
        }
    }
    if (empty($data['delete_fromDB'])) {
        mysqli_multi_query($sqlConnect, " DELETE FROM " . T_NOTIFICATION . " WHERE `time` < " . (time() - (60 * 60 * 24 * 5)) . " AND `seen` <> 0");
    }
    return $get;
}

function Wo_CountNotifications($data = array())
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    $get = array();
    if (empty($data['account_id']) or $data['account_id'] == 0) {
        $data['account_id'] = Wo_Secure($wo['user']['user_id']);
        $account = $wo['user'];
    }
    if (!is_numeric($data['account_id']) or $data['account_id'] < 1) {
        return false;
    }
    if ($data['account_id'] != $wo['user']['user_id']) {
        $data['account_id'] = Wo_Secure($data['account_id']);
        $account = Wo_UserData($data['account_id']);
    }
    $query_one = " SELECT COUNT(`id`) AS `notifications` FROM " . T_NOTIFICATION . " WHERE `recipient_id` = " . $account['user_id'];
    if (isset($data['unread']) && $data['unread'] == true) {
        $query_one .= " AND `seen` = 0";
    }
    if (isset($data['remove_notification']) && !empty($data['remove_notification'])) {
        foreach ($data['remove_notification'] as $key => $remove_notification) {
            $query_one .= ' AND `type` <> "$remove_notification"';
        }
    }
    $query_one .= " ORDER BY `id` DESC";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        $sql_fetch_one = mysqli_fetch_assoc($sql_query_one);
        return $sql_fetch_one['notifications'];
    }
    return false;
}

function Wo_GetSearch($search_qeury)
{
    global $sqlConnect, $wo;
    $search_qeury = Wo_Secure($search_qeury);
    $data = array();
    $query_text = "SELECT `user_id` FROM " . T_USERS . " WHERE ((`username` LIKE '%$search_qeury%') OR CONCAT( `first_name`,  ' ', `last_name` ) LIKE '%$search_qeury%') AND `active` = '1'";
    if ($wo['loggedin'] == true) {
        $logged_user_id = Wo_Secure($wo['user']['user_id']);
        $query_text .= " AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}')";
    }
    $query_text .= " LIMIT 3";
    $query = mysqli_query($sqlConnect, $query_text);
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $data[] = Wo_UserData($fetched_data['user_id']);
        }
    }
    $query = mysqli_query($sqlConnect, " SELECT `page_id` FROM " . T_PAGES . " WHERE ((`page_name` LIKE '%$search_qeury%') OR `page_title` LIKE '%$search_qeury%') AND `active` = '1' LIMIT 3");
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $data[] = Wo_PageData($fetched_data['page_id']);
        }
    }
    $query = mysqli_query($sqlConnect, " SELECT `id` FROM " . T_GROUPS . " WHERE ((`group_name` LIKE '%$search_qeury%') OR `group_title` LIKE '%$search_qeury%') AND `active` = '1' LIMIT 3");
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $data[] = Wo_GroupData($fetched_data['id']);
        }
    }
    return $data;
}

function Wo_GetRecentSerachs()
{
    global $sqlConnect, $wo;
    if ($wo['loggedin'] == false) {
        return false;
    }
    $user_id = Wo_Secure($wo['user']['user_id']);
    $data = array();
    $query = mysqli_query($sqlConnect, "SELECT `search_id`,`search_type` FROM " . T_RECENT_SEARCHES . " WHERE `user_id` = {$user_id} AND `search_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$user_id}') AND `search_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$user_id}') ORDER BY `id` DESC LIMIT 10");
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            if ($fetched_data['search_type'] == 'user') {
                $fetched_data_2 = Wo_UserData($fetched_data['search_id']);
            } else if ($fetched_data['search_type'] == 'page') {
                $fetched_data_2 = Wo_PageData($fetched_data['search_id']);
            } else if ($fetched_data['search_type'] == 'group') {
                $fetched_data_2 = Wo_GroupData($fetched_data['search_id']);
            } else {
                return false;
            }
            $data[] = $fetched_data_2;
        }
    }
    return $data;
}

function Wo_GetSearchFilter($result, $limit = 30, $offset = 0)
{
    global $wo, $sqlConnect, $db;
    $data = array();
    $profiledata = array();
    $time = time() - 60;
    if (empty($result)) {
        return array();
    }
    $custom_query = '';
    $profile_search_sql = "";
    $profile_search = array();
    foreach ($_GET as $key => $val) {
        if (substr($key, 0, 4) == 'fid_' && !empty($val)) {
            $custom_type = $db->where('id', substr($key, 4))->getOne(T_FIELDS);
            if (!empty($custom_type)) {
                $profile_search[$key] = Wo_Secure($val);
                $profile_search_sql = "AND (SELECT COUNT(*) FROM " . T_USERS_FIELDS . " WHERE ";
                if (!empty($custom_type) && ($custom_type->type == 'textbox' || $custom_type->type == 'textarea')) {
                    $profile_search_sql .= "`" . Wo_Secure($key) . "` LIKE '%" . Wo_Secure($val) . "%' AND";
                } else {
                    $profile_search_sql .= "`" . Wo_Secure($key) . "` = '" . Wo_Secure($val) . "' AND";
                }
            }
        }
    }
    if (substr($profile_search_sql, -3) == "AND") {
        $profile_search_sql = substr($profile_search_sql, 0, -3);
    }
    if (!empty($profile_search)) {
        $custom_query = $profile_search_sql . ' AND ' . T_USERS . '.user_id = user_id) > 0 ';
    }
    $query = '';
    if (!empty($result['query'])) {
        $query = Wo_Secure($result['query']);
    }
    if (!empty($result['country'])) {
        $country = Wo_Secure($result['country']);
    }
    if (!empty($result['status'])) {
        $result['status'] = Wo_Secure($result['status']);
    }
    if (!empty($result['verified'])) {
        $result['verified'] = Wo_Secure($result['verified']);
    }
    if (!empty($result['filterbyage']) && $result['filterbyage'] == 'yes') {
        if (!empty($result['age_from'])) {
            $result['age_from'] = Wo_Secure($result['age_from']);
        }
        if (!empty($result['age_to'])) {
            $result['age_to'] = Wo_Secure($result['age_to']);
        }
    }
    if (!empty($result['image'])) {
        $result['image'] = Wo_Secure($result['image']);
    }
    $job_type_main = "";
    if (!empty($result['job_type'])) {
        $job_type_query = "";
        foreach ($result['job_type'] as $key => $value) {
            if (in_array($value, array(
                'full_time',
                'contract',
                'part_time',
                'internship',
                'temporary'
            ))) {
                if (empty($job_type_query)) {
                    $job_type_query = "`job_type` LIKE '%" . Wo_Secure($value) . "%' ";
                } else {
                    $job_type_query .= " OR `job_type` LIKE '%" . Wo_Secure($value) . "%' ";
                }
            }
        }
        $job_type_main .= " OR `user_id` IN (SELECT `user_id` FROM " . T_USER_OPEN_TO . " WHERE " . $job_type_query . ") ";
    }
    $workplaces_type_main = "";
    if (!empty($result['workplaces'])) {
        $job_type_query = "";
        foreach ($result['workplaces'] as $key => $value) {
            if (in_array($value, array(
                'on_site',
                'hybrid',
                'remote'
            ))) {
                if (empty($job_type_query)) {
                    $job_type_query = "`workplaces` LIKE '%" . Wo_Secure($value) . "%' ";
                } else {
                    $job_type_query .= " OR `workplaces` LIKE '%" . Wo_Secure($value) . "%' ";
                }
            }
        }
        $workplaces_type_main .= " OR `user_id` IN (SELECT `user_id` FROM " . T_USER_OPEN_TO . " WHERE " . $job_type_query . ") ";
    }
    $query = " SELECT `user_id` FROM " . T_USERS . " WHERE (`username` LIKE '%{$query}%' OR CONCAT( `first_name`,  ' ', `last_name` ) LIKE  '%{$query}%') {$job_type_main} {$workplaces_type_main} {$custom_query}";
    if ($wo['loggedin'] == true) {
        $logged_user_id = Wo_Secure($wo['user']['user_id']);
        $query .= " AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}')";
    }
    // if (!empty($result['gender'])) {
    //     if ($result['gender'] == 'male') {
    //         $query .= " AND (`gender` = 'male') ";
    //     } else if ($result['gender'] == 'female') {
    //         $query .= " AND (`gender` = 'female') ";
    //     }
    // }
    if (!empty($result['gender']) && $result['gender'] != 'all') {
        $query .= " AND (`gender` = '" . Wo_Secure($result['gender']) . "') ";
    }
    if (!empty($result['country'])) {
        if ($result['country'] != 'all') {
            $query .= " AND (`country_id` = '{$country}')";
        }
    }
    if (isset($result['verified'])) {
        if ($result['verified'] == 'on') {
            $query .= " AND (`verified` = 1 ) ";
        } else if ($result['verified'] == 'off') {
            $query .= " AND (`verified` = 0 ) ";
        }
    }
    if (isset($result['status'])) {
        if ($result['status'] == 'on') {
            $query .= " AND (`lastseen` >= {$time}) ";
        } else if ($result['status'] == 'off') {
            $query .= " AND (`lastseen` <= {$time}) ";
        }
    }
    if (!empty($result['filterbyage']) && $result['filterbyage'] == 'yes') {
        if (!empty($result['age_from']) && $result['age_from'] > 0) {
            $query .= " AND TIMESTAMPDIFF(YEAR, `birthday`, CURDATE()) > '" . $result['age_from'] . "' AND TIMESTAMPDIFF(YEAR, `birthday`, CURDATE()) < '" . $result['age_to'] . "' ";
        }
    }
    if (isset($result['image'])) {
        $result['image'] = Wo_Secure($result['image']);
        $d_image = Wo_Secure($wo['userDefaultAvatar']);
        if ($result['image'] == 'yes') {
            $query .= " AND (`avatar` <> '{$d_image}') ";
        } else if ($result['image'] == 'no') {
            $query .= " AND (`avatar` = '{$d_image}') ";
        }
    }
    if ($wo['loggedin'] == true || !empty($result['user_id'])) {
        if (!empty($result['user_id'])) {
            $user_id = Wo_Secure($result['user_id']);
        } else {
            $user_id = Wo_Secure($wo['user']['user_id']);
        }
        $query .= " AND `user_id` <> '{$user_id}'";
    }
    $query .= " AND `active` = '1' ";
    if ($offset > 0) {
        $query .= " AND `user_id` < {$offset} AND `user_id` <> {$offset}";
    }
    if (!empty($limit)) {
        $limit = Wo_Secure($limit);
        $query .= " ORDER BY `user_id` DESC LIMIT {$limit}";
    }

    $codes = $db->objectbuilder()->paginate(T_USERS, 1);
    $sql_query_one = mysqli_query($sqlConnect, $query);
    if (mysqli_num_rows($sql_query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query_one)) {
            $data[$fetched_data['user_id']] = Wo_UserData($fetched_data['user_id']);
        }
    }
    // if( !empty( $profile_search ) ){
    //     $profile_sql_query_one = mysqli_query($sqlConnect, $profile_search_sql);
    //     while ($profile_fetched_data = mysqli_fetch_assoc($profile_sql_query_one)) {
    //         $data[$fetched_data['user_id']] = Wo_UserData($profile_fetched_data['user_id']);
    //     }
    // }
    return $data;
}

function Wo_GetMessagesUsers($user_id, $searchQuery = '', $limit = 50, $new = false, $update = 0)
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (!is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    if (!isset($user_id)) {
        $user_id = $wo['user']['user_id'];
    }
    $data = array();
    $excludes = array();
    if (isset($searchQuery) and !empty($searchQuery)) {
        $query_one = " SELECT `user_id` as `conversation_user_id` FROM " . T_USERS . " WHERE (`user_id` IN (SELECT `from_id` FROM " . T_MESSAGES . " WHERE `to_id` = {$user_id} AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$user_id}') AND `active` = '1' ";
        if (isset($new) && $new == true) {
            $query_one .= " AND `seen` = 0";
        }
        $query_one .= " ORDER BY `user_id` DESC)";
        if (!isset($new) or $new == false) {
            $query_one .= " OR `user_id` IN (SELECT `to_id` FROM " . T_MESSAGES . " WHERE `from_id` = {$user_id} ORDER BY `id` DESC)";
        }
        $query_one .= ") AND ((`username` LIKE '%{$searchQuery}%') OR CONCAT( `first_name`,  ' ', `last_name` ) LIKE  '%{$searchQuery}%')";
    } else {
        $query_one = "SELECT * FROM " . T_U_CHATS . " WHERE `user_id` = '$user_id' AND (`conversation_user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$user_id}') AND `conversation_user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$user_id}')) ORDER BY `time` DESC";
    }
    $query_one .= " LIMIT {$limit}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) > 0) {
        while ($sql_fetch_one = mysqli_fetch_assoc($sql_query_one)) {
            $user = Wo_UserData($sql_fetch_one['conversation_user_id']);
            if (!empty($user)) {
                if (!empty($sql_fetch_one['time'])) {
                    $user['chat_time'] = $sql_fetch_one['time'];
                }
                $user['message'] = $sql_fetch_one;
                $data[] = $user;
            }
        }
    }
    return $data;
}

function Wo_GetMessagesUsersAPP2($fetch_array = array())
{
    global $wo, $sqlConnect;
    if (empty($fetch_array['session_id'])) {
        if ($wo['loggedin'] == false) {
            return false;
        }
    }
    if (!is_numeric($fetch_array['user_id']) or $fetch_array['user_id'] < 1) {
        return false;
    }
    if (!isset($fetch_array['user_id'])) {
        $user_id = $wo['user']['user_id'];
    }
    $user_id = Wo_Secure($fetch_array['user_id']);
    $searchQuery = '';
    if (!empty($fetch_array['searchQuery'])) {
        $searchQuery = Wo_Secure($fetch_array['searchQuery']);
    }
    $data = array();
    $excludes = array();
    $offset_query = "";
    if (!empty($fetch_array['offset'])) {
        $offset_query = " AND `time` < " . $fetch_array['offset'];
    }
    if (isset($searchQuery) and !empty($searchQuery)) {
        $query_one = "SELECT `user_id` as `conversation_user_id` FROM " . T_USERS . " WHERE (`user_id` IN (SELECT `from_id` FROM " . T_MESSAGES . " WHERE `to_id` = {$user_id} AND `page_id` = 0 AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$user_id}') AND `active` = '1' ";
        if (isset($fetch_array['new']) && $fetch_array['new'] == true) {
            $query_one .= " AND `seen` = 0";
        }
        $query_one .= " ORDER BY `user_id` DESC)";
        if (!isset($fetch_array['new']) or $fetch_array['new'] == false) {
            $query_one .= " OR `user_id` IN (SELECT `to_id` FROM " . T_MESSAGES . " WHERE `from_id` = {$user_id} AND `page_id` = 0 ORDER BY `id` DESC)";
        }
        $query_one .= ") AND ((`username` LIKE '%{$searchQuery}%') OR CONCAT( `first_name`,  ' ', `last_name` ) LIKE  '%{$searchQuery}%')";
        if (!empty($fetch_array['limit'])) {
            $limit = Wo_Secure($fetch_array['limit']);
            $query_one .= "LIMIT {$limit}";
        }
    } else {
        $time = time() - 60;
        $query_one_2 = '';
        $full = '';
        if (!empty($fetch_array['type']) && $fetch_array['type'] == 'online') {
            $query_one_2 = " `lastseen` > {$time}";
        } else if (!empty($fetch_array['type']) && $fetch_array['type'] == 'offline') {
            $query_one_2 = " `lastseen` < {$time}";
        }
        if (!empty($query_one_2)) {
            $full = " AND (`user_id` IN (SELECT `user_id` FROM " . T_USERS . " WHERE {$query_one_2})) ";
        }
        $query_one = "SELECT * FROM " . T_U_CHATS . " WHERE `user_id` = '$user_id' AND `page_id` = 0 AND (`conversation_user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$user_id}') AND `conversation_user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$user_id}')) {$full} {$offset_query}  ORDER BY `time` DESC";
    }
    if (!empty($fetch_array['limit'])) {
        $limit = Wo_Secure($fetch_array['limit']);
        $query_one .= " LIMIT {$limit}";
    }
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) > 0) {
        while ($sql_fetch_one = mysqli_fetch_assoc($sql_query_one)) {
            $new_data = Wo_UserData($sql_fetch_one['conversation_user_id']);
            if (!empty($new_data) && !empty($new_data['username'])) {
                //$new_data['chat_time'] = $sql_fetch_one['time'];
                if (!empty($sql_fetch_one['time'])) {
                    $new_data['chat_time'] = $sql_fetch_one['time'];
                }
                $new_data['chat_id'] = $sql_fetch_one['id'];
                $data[] = $new_data;
            }
        }
    }
    return $data;
}

function Wo_GetPageChatList($user_id, $limit = 50, $new = false, $update = 0)
{
    global $wo, $sqlConnect, $db;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (!is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    if (!isset($user_id)) {
        $user_id = $wo['user']['user_id'];
    }
    $page_query = '';
    $data = array();
    $excludes = array();
    $page_query = "SELECT * FROM " . T_MESSAGES . " WHERE (`to_id` = '$user_id' OR `from_id` = '$user_id') AND `page_id` > 0 GROUP BY `from_id`,`page_id` ORDER BY `time` DESC LIMIT {$limit}";
    $sql_query_page = mysqli_query($sqlConnect, $page_query);
    $ids = array();
    if ($sql_query_page) {
        if (mysqli_num_rows($sql_query_page) > 0) {
            while ($sql_fetch_one = mysqli_fetch_assoc($sql_query_page)) {
                $to_id = $sql_fetch_one['to_id'];
                $from_id = $sql_fetch_one['from_id'];
                if (!in_array($to_id . ',' . $from_id . ',' . $sql_fetch_one['page_id'], $ids) && !in_array($from_id . ',' . $to_id . ',' . $sql_fetch_one['page_id'], $ids)) {
                    $ids[] = $to_id . ',' . $from_id . ',' . $sql_fetch_one['page_id'];
                    $ids[] = $from_id . ',' . $to_id . ',' . $sql_fetch_one['page_id'];
                    $last_message = $db->rawQuery("SELECT * FROM " . T_MESSAGES . " WHERE ( (`to_id` = '$to_id' AND `from_id` = '$from_id') OR (`to_id` = '$from_id' AND `from_id` = '$to_id') ) AND `page_id` = '" . $sql_fetch_one['page_id'] . "' ORDER BY `time` DESC LIMIT 1");
                    $last_message = ToArray($last_message);
                    $sql_fetch_one = $last_message[0];
                    if ($sql_fetch_one['from_id'] == $user_id) {
                        $user = Wo_UserData($sql_fetch_one['to_id']);
                        if (!empty($user)) {
                            $user_data = $user;
                            $user_data['message'] = $sql_fetch_one;
                            if (!empty($sql_fetch_one['time'])) {
                                $user_data['chat_time'] = $sql_fetch_one['time'];
                            }
                            $data[] = $user_data;
                        }
                    } else {
                        $user = Wo_UserData($sql_fetch_one['from_id']);
                        if (!empty($user)) {
                            $user_data = $user;
                            $user_data['message'] = $sql_fetch_one;
                            if (!empty($sql_fetch_one['time'])) {
                                $user_data['chat_time'] = $sql_fetch_one['time'];
                            }
                            $data[] = $user_data;
                        }
                    }
                }
            }
        }
    }
    return $data;
}

function Wo_GetMessages($data = array(), $limit = 50)
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    $message_data = array();
    $user_id = Wo_Secure($data['user_id']);
    $logged_user_id = Wo_Secure($wo['user']['user_id']);
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    $query_one = " SELECT * FROM " . T_MESSAGES;
    if (isset($data['new']) && $data['new'] == true) {
        $query_one .= " WHERE `seen` = 0 AND `from_id` = {$user_id} AND `to_id` = {$logged_user_id} AND `deleted_two` = '0'";
    } else {
        $query_one .= " WHERE ((`from_id` = {$user_id} AND `to_id` = {$logged_user_id} AND `deleted_two` = '0') OR (`from_id` = {$logged_user_id} AND `to_id` = {$user_id} AND `deleted_one` = '0'))";
    }
    if (!empty($data['message_id'])) {
        $data['message_id'] = Wo_Secure($data['message_id']);
        $query_one .= " AND `id` = " . $data['message_id'];
    } else if (!empty($data['before_message_id']) && is_numeric($data['before_message_id']) && $data['before_message_id'] > 0) {
        $data['before_message_id'] = Wo_Secure($data['before_message_id']);
        $query_one .= " AND `id` < " . $data['before_message_id'] . " AND `id` <> " . $data['before_message_id'];
    } else if (!empty($data['after_message_id']) && is_numeric($data['after_message_id']) && $data['after_message_id'] > 0) {
        $data['after_message_id'] = Wo_Secure($data['after_message_id']);
        $query_one .= " AND `id` > " . $data['after_message_id'] . " AND `id` <> " . $data['after_message_id'];
    }
    if (!empty($data['type']) && $data['type'] == 'user') {
        $query_one .= " AND `page_id` = 0 ";
    }
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    $query_limit_from = mysqli_num_rows($sql_query_one) - 50;
    if ($query_limit_from < 1) {
        $query_limit_from = 0;
    }
    if (isset($limit)) {
        $query_one .= " ORDER BY `id` ASC LIMIT {$query_limit_from}, 50";
    }
    $query = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $fetched_data['messageUser'] = Wo_UserData($fetched_data['from_id']);
            $fetched_data['or_text'] = $fetched_data['text'];
            $fetched_data['text'] = Wo_Markup($fetched_data['text']);
            $fetched_data['text'] = Wo_Emo($fetched_data['text']);
            $fetched_data['onwer'] = ($fetched_data['messageUser']['user_id'] == $wo['user']['user_id']) ? 1 : 0;
            if (!empty($fetched_data['stickers']) && !Wo_IsUrl($fetched_data['stickers'])) {
                $fetched_data['stickers'] = Wo_GetMedia($fetched_data['stickers']);
            }
            if ($fetched_data['messageUser']['user_id'] == $user_id && $fetched_data['seen'] == 0 && empty($data['not_seen'])) {
                mysqli_query($sqlConnect, " UPDATE " . T_MESSAGES . " SET `seen` = " . time() . " WHERE `id` = " . $fetched_data['id']);
            }
            $fetched_data['reply'] = array();
            if (!empty($fetched_data['reply_id'])) {
                $fetched_data['reply'] = GetMessageById($fetched_data['reply_id']);
            }
            $fetched_data['story'] = array();
            if (!empty($fetched_data['story_id'])) {
                $fetched_data['story'] = Wo_GetStroies(array(
                    'id' => $fetched_data['story_id']
                ));
                if (!empty($fetched_data['story']) && !empty($fetched_data['story'][0])) {
                    $fetched_data['story'] = $fetched_data['story'][0];
                }
            }
            $message_data[] = $fetched_data;
        }
    }
    return $message_data;
}

function GetMessageById($id)
{
    global $wo, $sqlConnect, $db;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($id) || !is_numeric($id)) {
        return array();
    }
    $id = Wo_Secure($id);
    $query_one = " SELECT * FROM " . T_MESSAGES . " WHERE id = " . $id;
    $query = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($query)) {
        $data = array();
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $fetched_data['messageUser'] = Wo_UserData($fetched_data['from_id']);
            $fetched_data['messageUser']['password'] = '';
            $fetched_data['or_text'] = $fetched_data['text'];
            $fetched_data['text'] = Wo_Markup($fetched_data['text']);
            $fetched_data['text'] = Wo_Emo($fetched_data['text']);
            $fetched_data['onwer'] = ($fetched_data['messageUser']['user_id'] == $wo['user']['user_id']) ? 1 : 0;
            if (!empty($fetched_data['stickers']) && !Wo_IsUrl($fetched_data['stickers'])) {
                $fetched_data['stickers'] = Wo_GetMedia($fetched_data['stickers']);
            }
            if ($fetched_data['messageUser']['user_id'] == $wo['user']['user_id'] && $fetched_data['seen'] == 0) {
                mysqli_query($sqlConnect, " UPDATE " . T_MESSAGES . " SET `seen` = " . time() . " WHERE `id` = " . $fetched_data['id']);
            }
            $fetched_data['reaction'] = Wo_GetPostReactionsTypes($fetched_data['id'], 'message');
            $fetched_data['pin'] = 'no';
            $mute = $db->where('user_id', $wo['user']['id'])->where('message_id', $fetched_data['id'])->where('pin', 'yes')->getOne(T_MUTE);
            if (!empty($mute)) {
                $fetched_data['pin'] = 'yes';
            }
            $fetched_data['fav'] = 'no';
            $mute = $db->where('user_id', $wo['user']['id'])->where('message_id', $fetched_data['id'])->where('fav', 'yes')->getOne(T_MUTE);
            if (!empty($mute)) {
                $fetched_data['fav'] = 'yes';
            }
            $data = $fetched_data;
        }
        return $data;
    }
    return array();
}

function Wo_GetGroupMessages($args = array())
{
    global $wo, $sqlConnect, $db;
    if ($wo['loggedin'] == false) {
        return false;
    }
    $options = array(
        "id" => false,
        "offset" => 0,
        "group_id" => false,
        "limit" => 50,
        "old" => false,
        "new" => false
    );
    $args = array_merge($options, $args);
    $offset = Wo_Secure($args['offset']);
    $id = Wo_Secure($args['id']);
    $group_id = Wo_Secure($args['group_id']);
    $limit = Wo_Secure($args['limit']);
    $new = Wo_Secure($args['new']);
    $old = Wo_Secure($args['old']);
    $query_one = '';
    $data = array();
    $logged_user_id = Wo_Secure($wo['user']['user_id']);
    $message_data = array();
    if (empty($group_id) || !is_numeric($group_id) || $group_id < 0) {
        return false;
    }
    if ($id && is_numeric($id) && $id > 0) {
        $query_one .= " AND `id` = '$id' ";
    }
    if ($new && $offset && $offset > 0 && !$old) {
        $query_one .= " AND `id` > {$offset} AND `id` <> {$offset} ";
    }
    if ($old && $offset && $offset > 0 && !$new) {
        $query_one .= " AND `id` < {$offset} AND `id` <> {$offset} ";
    }
    $query_one = " SELECT * FROM " . T_MESSAGES . " WHERE `group_id` = '$group_id' {$query_one} ";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    $query_limit_from = mysqli_num_rows($sql_query_one) - 50;
    if ($query_limit_from < 1) {
        $query_limit_from = 0;
    }
    if (isset($limit)) {
        $query_one .= " ORDER BY `id` ASC LIMIT {$query_limit_from}, 50";
    }
    $query = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $fetched_data['user_data'] = Wo_UserData($fetched_data['from_id']);
            $fetched_data['org_text'] = $fetched_data['text'];
            $fetched_data['text'] = Wo_Markup($fetched_data['text']);
            $fetched_data['text'] = Wo_Emo($fetched_data['text']);
            $fetched_data['onwer'] = 0;
            if (!empty($fetched_data['user_data'])) {
                $fetched_data['onwer'] = ($fetched_data['user_data']['user_id'] == $wo['user']['user_id']) ? 1 : 0;
            }
            $fetched_data['reply'] = array();
            if (!empty($fetched_data['reply_id'])) {
                $fetched_data['reply'] = GetMessageById($fetched_data['reply_id']);
            }
            $fetched_data['pin'] = 'no';
            $mute = $db->where('user_id', $wo['user']['id'])->where('message_id', $fetched_data['id'])->where('pin', 'yes')->getOne(T_MUTE);
            if (!empty($mute)) {
                $fetched_data['pin'] = 'yes';
            }
            $fetched_data['fav'] = 'no';
            $mute = $db->where('user_id', $wo['user']['id'])->where('message_id', $fetched_data['id'])->where('fav', 'yes')->getOne(T_MUTE);
            if (!empty($mute)) {
                $fetched_data['fav'] = 'yes';
            }
            $message_data[] = $fetched_data;
        }
    }
    return $message_data;
}

function Wo_GetPageMessages($args = array())
{
    global $wo, $sqlConnect, $db;
    if ($wo['loggedin'] == false) {
        return false;
    }
    $options = array(
        "id" => false,
        "offset" => 0,
        "page_id" => false,
        "limit" => 50,
        "old" => false,
        "new" => false
    );
    $args = array_merge($options, $args);
    $offset = Wo_Secure($args['offset']);
    $id = Wo_Secure($args['id']);
    $page_id = Wo_Secure($args['page_id']);
    $limit = Wo_Secure($args['limit']);
    $new = Wo_Secure($args['new']);
    $old = Wo_Secure($args['old']);
    $query_one = '';
    $data = array();
    $logged_user_id = Wo_Secure($wo['user']['user_id']);
    $message_data = array();
    if (empty($page_id) || !is_numeric($page_id) || $page_id < 0) {
        return false;
    }
    if ($id && is_numeric($id) && $id > 0) {
        $query_one .= " AND `id` = '$id' ";
    }
    if ($new && $offset && $offset > 0 && !$old) {
        $query_one .= " AND `id` > {$offset} AND `id` <> {$offset} ";
    }
    if ($old && $offset && $offset > 0 && !$new) {
        $query_one .= " AND `id` < {$offset} AND `id` <> {$offset} ";
    }
    $page_data = Wo_PageData($page_id);
    $page_user_id = $page_data['user_id'];
    if ($logged_user_id != $page_user_id) {
        $query_one = " SELECT * FROM " . T_MESSAGES . " WHERE `page_id` = '$page_id' {$query_one} AND ((`from_id` = '$logged_user_id' AND `to_id` = '$page_user_id') OR (`from_id` = '$page_user_id' AND `to_id` = '$logged_user_id') ) ";
    } elseif (!empty($args['from_id']) && !empty($args['to_id'])) {
        $from_id = $args['from_id'];
        $to_id = $args['to_id'];
        $query_one = " SELECT * FROM " . T_MESSAGES . " WHERE `page_id` = '$page_id' {$query_one} AND ((`from_id` = '$from_id' AND `to_id` = '$to_id') OR (`from_id` = '$to_id' AND `to_id` = '$from_id') ) ";
    } elseif (!empty($id)) {
        $query_one = " SELECT * FROM " . T_MESSAGES . " WHERE `page_id` = '$page_id' {$query_one} ";
    }
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    $query_limit_from = mysqli_num_rows($sql_query_one) - 50;
    if ($query_limit_from < 1) {
        $query_limit_from = 0;
    }
    if (isset($limit)) {
        if (!empty($args['limit_type']) && $args['limit_type'] == 1) {
            $query_one .= " ORDER BY `id` DESC LIMIT $limit";
        } else {
            $query_one .= " ORDER BY `id` ASC LIMIT {$query_limit_from}, 50";
        }
    }
    $query = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $fetched_data['user_data'] = Wo_UserData($fetched_data['from_id']);
            $fetched_data['text'] = Wo_Markup($fetched_data['text']);
            $fetched_data['text'] = Wo_Emo($fetched_data['text']);
            $fetched_data['onwer'] = ($fetched_data['user_data']['user_id'] == $wo['user']['user_id']) ? 1 : 0;
            $fetched_data['reply'] = array();
            if (!empty($fetched_data['reply_id'])) {
                $fetched_data['reply'] = GetMessageById($fetched_data['reply_id']);
            }
            if ($fetched_data['from_id'] != $wo['user']['user_id']) {
                $db->where('from_id', $fetched_data['from_id'])->where('to_id', $fetched_data['to_id'])->update(T_MESSAGES, array(
                    'seen' => time()
                ));
            }
            $fetched_data['reaction'] = Wo_GetPostReactionsTypes($fetched_data['id'], 'message');
            $fetched_data['pin'] = 'no';
            $mute = $db->where('user_id', $wo['user']['id'])->where('message_id', $fetched_data['id'])->where('pin', 'yes')->getOne(T_MUTE);
            if (!empty($mute)) {
                $fetched_data['pin'] = 'yes';
            }
            $fetched_data['fav'] = 'no';
            $mute = $db->where('user_id', $wo['user']['id'])->where('message_id', $fetched_data['id'])->where('fav', 'yes')->getOne(T_MUTE);
            if (!empty($mute)) {
                $fetched_data['fav'] = 'yes';
            }
            $message_data[] = $fetched_data;
        }
    }
    return $message_data;
}

function Wo_GetGroupMessagesAPP($args = array())
{
    global $wo, $sqlConnect, $db;
    if ($wo['loggedin'] == false) {
        return false;
    }
    $options = array(
        "id" => false,
        "offset" => 0,
        "group_id" => false,
        "limit" => 50,
        "old" => false,
        "new" => false
    );
    $args = array_merge($options, $args);
    $offset = Wo_Secure($args['offset']);
    $id = Wo_Secure($args['id']);
    $group_id = Wo_Secure($args['group_id']);
    $limit = Wo_Secure($args['limit']);
    $new = Wo_Secure($args['new']);
    $old = Wo_Secure($args['old']);
    $query_one = '';
    $data = array();
    $logged_user_id = Wo_Secure($wo['user']['user_id']);
    $message_data = array();
    if (empty($group_id) || !is_numeric($group_id) || $group_id < 0) {
        return false;
    }
    if ($id && is_numeric($id) && $id > 0) {
        $query_one .= " AND `id` = '$id' ";
    }
    if ($new && $offset && $offset > 0 && !$old) {
        $query_one .= " AND `id` > {$offset} AND `id` <> {$offset} ";
    }
    if ($old && $offset && $offset > 0 && !$new) {
        $query_one .= " AND `id` < {$offset} AND `id` <> {$offset} ";
    }
    $query_one = " SELECT * FROM " . T_MESSAGES . " WHERE `group_id` = '$group_id' {$query_one} ";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (isset($limit)) {
        $query_one .= " ORDER BY `id` DESC LIMIT {$limit}";
    }
    $query = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $fetched_data['user_data'] = Wo_UserData($fetched_data['from_id']);
            $fetched_data['orginal_text'] = Wo_EditMarkup($fetched_data['text']);
            $fetched_data['text'] = Wo_Markup($fetched_data['text']);
            $fetched_data['text'] = Wo_Emo($fetched_data['text']);
            $fetched_data['onwer'] = ($fetched_data['user_data']['user_id'] == $wo['user']['user_id']) ? 1 : 0;
            $fetched_data['reply'] = array();
            if (!empty($fetched_data['reply_id'])) {
                $fetched_data['reply'] = GetMessageById($fetched_data['reply_id']);
            }
            $fetched_data['chat_data'] = $db->where('user_id', $wo['user']['user_id'])->where('group_id', $group_id)->ArrayBuilder()->getOne(T_GROUP_CHAT_USERS);
            $message_data[] = $fetched_data;
        }
    }
    return $message_data;
}

function Wo_GetMessagesHeader($data = array(), $type = '')
{
    global $wo, $sqlConnect;
    if (empty($data['session_id'])) {
        if ($wo['loggedin'] == false) {
            return false;
        }
    }
    $message_data = array();
    $user_id = Wo_Secure($data['user_id']);
    if (!empty($data['session_id'])) {
        $logged_user_id = Wo_GetUserFromSessionID($data['session_id'], $data['platform']);
        if (empty($logged_user_id)) {
            return false;
        }
    } else {
        $logged_user_id = Wo_Secure($wo['user']['user_id']);
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    $query_one = " SELECT * FROM " . T_MESSAGES;
    if (isset($data['new']) && $data['new'] == true) {
        $query_one .= " WHERE `seen` = 0 AND `from_id` = {$user_id} AND `to_id` = {$logged_user_id} AND `deleted_two` = '0'";
    } else {
        $query_one .= " WHERE ((`from_id` = {$user_id} AND `to_id` = {$logged_user_id} AND `deleted_two` = '0') OR (`from_id` = {$logged_user_id} AND `to_id` = {$user_id} AND `deleted_one` = '0'))";
    }
    if (!empty($data['message_id'])) {
        $data['message_id'] = Wo_Secure($data['message_id']);
        $query_one .= " AND `id` = " . $data['message_id'];
    } else if (!empty($data['before_message_id']) && is_numeric($data['before_message_id']) && $data['before_message_id'] > 0) {
        $data['before_message_id'] = Wo_Secure($data['before_message_id']);
        $query_one .= " AND `id` < " . $data['before_message_id'] . " AND `id` <> " . $data['before_message_id'];
    } else if (!empty($data['after_message_id']) && is_numeric($data['after_message_id']) && $data['after_message_id'] > 0) {
        $data['after_message_id'] = Wo_Secure($data['after_message_id']);
        $query_one .= " AND `id` > " . $data['after_message_id'] . " AND `id` <> " . $data['after_message_id'];
    }
    if ($type == 'user') {
        $query_one .= " AND `page_id` = 0 ";
    }
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    $query_limit_from = mysqli_num_rows($sql_query_one) - 50;
    if ($query_limit_from < 1) {
        $query_limit_from = 0;
    }
    $query_one .= " ORDER BY `id` DESC LIMIT 1";
    $query = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        if (!isset($data['user_data'])) {
            $fetched_data['messageUser'] = Wo_UserData($fetched_data['from_id']);
            $fetched_data['onwer'] = ($fetched_data['messageUser']['user_id'] == $logged_user_id) ? 1 : 0;
        }
        if (!empty($fetched_data['text'])) {
            $fetched_data['text'] = Wo_EditMarkup($fetched_data['text']);
        }
        $fetched_data['reaction'] = Wo_GetPostReactionsTypes($fetched_data['id'], 'message');
        return $fetched_data;
    }
    return false;
}

function Wo_RegisterMessage($ms_data = array())
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($ms_data)) {
        return false;
    }
    if (empty($ms_data['to_id']) || !is_numeric($ms_data['to_id']) || $ms_data['to_id'] < 0) {
        return false;
    }
    if (empty($ms_data['from_id']) || !is_numeric($ms_data['from_id']) || $ms_data['from_id'] < 0) {
        return false;
    }
    if ($ms_data['to_id'] == $ms_data['from_id']) {
        return false;
    }
    if (!isset($ms_data['stickers'])) {
        if ((empty($ms_data['text']) && $ms_data['text'] != 0) || !isset($ms_data['text']) || strlen($ms_data['text']) < 0) {
            if (empty($ms_data['media']) || !isset($ms_data['media']) || strlen($ms_data['media']) < 0) {
                return false;
            }
        }
    }
    $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
    $i = 0;
    preg_match_all($link_regex, $ms_data['text'], $matches);
    foreach ($matches[0] as $match) {
        $match_url = strip_tags($match);
        $syntax = '[a]' . urlencode($match_url) . '[/a]';
        $ms_data['text'] = str_replace($match, $syntax, $ms_data['text']);
    }
    $mention_regex = '/@([A-Za-z0-9_]+)/i';
    preg_match_all($mention_regex, $ms_data['text'], $matches);
    foreach ($matches[1] as $match) {
        $match = Wo_Secure($match);
        $match_user = Wo_UserData(Wo_UserIdFromUsername($match));
        $match_search = '@' . $match;
        $match_replace = '@[' . $match_user['user_id'] . ']';
        if (isset($match_user['user_id'])) {
            $ms_data['text'] = str_replace($match_search, $match_replace, $ms_data['text']);
            $mentions[] = $match_user['user_id'];
        }
    }
    $hashtag_regex = '/#([^`~!@$%^&*\#()\-+=\\|\/\.,<>?\'\":;{}\[\]* ]+)/i';
    preg_match_all($hashtag_regex, $ms_data['text'], $matches);
    foreach ($matches[1] as $match) {
        if (!is_numeric($match)) {
            $hashdata = Wo_GetHashtag($match);
            if (is_array($hashdata)) {
                $match_search = '#' . $match;
                $match_replace = '#[' . $hashdata['id'] . ']';
                if (mb_detect_encoding($match_search, 'ASCII', true)) {
                    $ms_data['text'] = preg_replace("/$match_search\b/i", $match_replace, $ms_data['text']);
                } else {
                    $ms_data['text'] = str_replace($match_search, $match_replace, $ms_data['text']);
                }
                //$ms_data['text']      = preg_replace("/$match_search\b/i", $match_replace,  $ms_data['text']);
                $hashtag_query = " UPDATE " . T_HASHTAGS . " SET `last_trend_time` = " . time() . ", `trend_use_num` = " . ($hashdata['trend_use_num'] + 1) . " WHERE `id` = " . $hashdata['id'];
            }
        }
    }
    $fields = '`' . implode('`, `', array_keys($ms_data)) . '`';
    $data = '\'' . implode('\', \'', $ms_data) . '\'';
    $query = mysqli_query($sqlConnect, " INSERT INTO " . T_MESSAGES . " ({$fields}) VALUES ({$data})");
    if ($query) {
        $message_id = mysqli_insert_id($sqlConnect);
        if (!empty($ms_data['from_id'])) {
            $from_id = $ms_data['from_id'];
        }
        $update_user_chats = Wo_CreateUserChat($ms_data['to_id'], $from_id);
        return $message_id;
    } else {
        return false;
    }
}

function Wo_RegisterMessageGroup($ms_data = array())
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($ms_data)) {
        return false;
    }
    if (empty($ms_data['group_id']) || !is_numeric($ms_data['group_id']) || $ms_data['group_id'] < 0) {
        return false;
    }
    if (empty($ms_data['from_id']) || !is_numeric($ms_data['from_id']) || $ms_data['from_id'] < 0) {
        return false;
    }
    if (!isset($ms_data['stickers'])) {
        if (empty($ms_data['text']) || !isset($ms_data['text']) || strlen($ms_data['text']) < 0) {
            if (empty($ms_data['media']) || !isset($ms_data['media']) || strlen($ms_data['media']) < 0) {
                return false;
            }
        }
    }
    $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
    $i = 0;
    preg_match_all($link_regex, $ms_data['text'], $matches);
    foreach ($matches[0] as $match) {
        $match_url = strip_tags($match);
        $syntax = '[a]' . urlencode($match_url) . '[/a]';
        $ms_data['text'] = str_replace($match, $syntax, $ms_data['text']);
    }
    $mention_regex = '/@([A-Za-z0-9_]+)/i';
    preg_match_all($mention_regex, $ms_data['text'], $matches);
    foreach ($matches[1] as $match) {
        $match = Wo_Secure($match);
        $match_user = Wo_UserData(Wo_UserIdFromUsername($match));
        $match_search = '@' . $match;
        $match_replace = '@[' . $match_user['user_id'] . ']';
        if (isset($match_user['user_id'])) {
            $ms_data['text'] = str_replace($match_search, $match_replace, $ms_data['text']);
            $mentions[] = $match_user['user_id'];
        }
    }
    $hashtag_regex = '/#([^`~!@$%^&*\#()\-+=\\|\/\.,<>?\'\":;{}\[\]* ]+)/i';
    preg_match_all($hashtag_regex, $ms_data['text'], $matches);
    foreach ($matches[1] as $match) {
        if (!is_numeric($match)) {
            $hashdata = Wo_GetHashtag($match);
            if (is_array($hashdata)) {
                $match_search = '#' . $match;
                $match_replace = '#[' . $hashdata['id'] . ']';
                if (mb_detect_encoding($match_search, 'ASCII', true)) {
                    $ms_data['text'] = preg_replace("/$match_search\b/i", $match_replace, $ms_data['text']);
                } else {
                    $ms_data['text'] = str_replace($match_search, $match_replace, $ms_data['text']);
                }
                //$ms_data['text']      = preg_replace("/$match_search\b/i", $match_replace,  $ms_data['text']);
                $hashtag_query = " UPDATE " . T_HASHTAGS . " SET `last_trend_time` = " . time() . ", `trend_use_num` = " . ($hashdata['trend_use_num'] + 1) . " WHERE `id` = " . $hashdata['id'];
            }
        }
    }
    $fields = '`' . implode('`, `', array_keys($ms_data)) . '`';
    $data = '\'' . implode('\', \'', $ms_data) . '\'';
    $query = mysqli_query($sqlConnect, " INSERT INTO " . T_MESSAGES . " ({$fields}) VALUES ({$data})");
    if ($query) {
        $message_id = mysqli_insert_id($sqlConnect);
        if (!empty($ms_data['from_id'])) {
            $from_id = $ms_data['from_id'];
        }
        return $message_id;
    } else {
        return false;
    }
}

function Wo_RegisterGroupMessage($ms_data = array())
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($ms_data)) {
        return false;
    }
    if (empty($ms_data['group_id']) || !is_numeric($ms_data['group_id']) || $ms_data['group_id'] < 0) {
        return false;
    }
    if (empty($ms_data['from_id']) || !is_numeric($ms_data['from_id']) || $ms_data['from_id'] < 0) {
        return false;
    }
    if (empty($ms_data['text']) || !isset($ms_data['text']) || strlen($ms_data['text']) < 0) {
        if (empty($ms_data['media']) || !isset($ms_data['media']) || strlen($ms_data['media']) < 0) {
            return false;
        }
    }
    $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
    $i = 0;
    preg_match_all($link_regex, $ms_data['text'], $matches);
    foreach ($matches[0] as $match) {
        $match_url = strip_tags($match);
        $syntax = '[a]' . urlencode($match_url) . '[/a]';
        $ms_data['text'] = str_replace($match, $syntax, $ms_data['text']);
    }
    $mention_regex = '/@([A-Za-z0-9_]+)/i';
    preg_match_all($mention_regex, $ms_data['text'], $matches);
    foreach ($matches[1] as $match) {
        $match = Wo_Secure($match);
        $match_user = Wo_UserData(Wo_UserIdFromUsername($match));
        $match_search = '@' . $match;
        $match_replace = '@[' . $match_user['user_id'] . ']';
        if (isset($match_user['user_id'])) {
            $ms_data['text'] = str_replace($match_search, $match_replace, $ms_data['text']);
            $mentions[] = $match_user['user_id'];
        }
    }
    $hashtag_regex = '/#([^`~!@$%^&*\#()\-+=\\|\/\.,<>?\'\":;{}\[\]* ]+)/i';
    preg_match_all($hashtag_regex, $ms_data['text'], $matches);
    foreach ($matches[1] as $match) {
        if (!is_numeric($match)) {
            $hashdata = Wo_GetHashtag($match);
            if (is_array($hashdata)) {
                $match_search = '#' . $match;
                $match_replace = '#[' . $hashdata['id'] . ']';
                if (mb_detect_encoding($match_search, 'ASCII', true)) {
                    $ms_data['text'] = preg_replace("/$match_search\b/i", $match_replace, $ms_data['text']);
                } else {
                    $ms_data['text'] = str_replace($match_search, $match_replace, $ms_data['text']);
                }
                $hashtag_query = " UPDATE " . T_HASHTAGS . " SET `last_trend_time` = " . time() . ", `trend_use_num` = " . ($hashdata['trend_use_num'] + 1) . " WHERE `id` = " . $hashdata['id'];
            }
        }
    }
    $fields = '`' . implode('`, `', array_keys($ms_data)) . '`';
    $data = '\'' . implode('\', \'', $ms_data) . '\'';
    $query = mysqli_query($sqlConnect, " INSERT INTO " . T_MESSAGES . " ({$fields}) VALUES ({$data})");
    if ($query) {
        $message_id = mysqli_insert_id($sqlConnect);
        return $message_id;
    } else {
        return false;
    }
}

function Wo_RegisterPageMessage($ms_data = array())
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($ms_data)) {
        return false;
    }
    if (empty($ms_data['page_id']) || !is_numeric($ms_data['page_id']) || $ms_data['page_id'] < 0) {
        return false;
    }
    if (empty($ms_data['from_id']) || !is_numeric($ms_data['from_id']) || $ms_data['from_id'] < 0) {
        return false;
    }
    if (empty($ms_data['text']) || !isset($ms_data['text']) || strlen($ms_data['text']) < 0) {
        if (empty($ms_data['media']) || !isset($ms_data['media']) || strlen($ms_data['media']) < 0) {
            if (empty($ms_data['stickers'])) {
                if (empty($ms_data['lng']) && empty($ms_data['lat'])) {
                    return false;
                }
            }
        }
    }
    $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
    $i = 0;
    preg_match_all($link_regex, $ms_data['text'], $matches);
    foreach ($matches[0] as $match) {
        $match_url = strip_tags($match);
        $syntax = '[a]' . urlencode($match_url) . '[/a]';
        $ms_data['text'] = str_replace($match, $syntax, $ms_data['text']);
    }
    $mention_regex = '/@([A-Za-z0-9_]+)/i';
    preg_match_all($mention_regex, $ms_data['text'], $matches);
    foreach ($matches[1] as $match) {
        $match = Wo_Secure($match);
        $match_user = Wo_UserData(Wo_UserIdFromUsername($match));
        $match_search = '@' . $match;
        $match_replace = '@[' . $match_user['user_id'] . ']';
        if (isset($match_user['user_id'])) {
            $ms_data['text'] = str_replace($match_search, $match_replace, $ms_data['text']);
            $mentions[] = $match_user['user_id'];
        }
    }
    $hashtag_regex = '/#([^`~!@$%^&*\#()\-+=\\|\/\.,<>?\'\":;{}\[\]* ]+)/i';
    preg_match_all($hashtag_regex, $ms_data['text'], $matches);
    foreach ($matches[1] as $match) {
        if (!is_numeric($match)) {
            $hashdata = Wo_GetHashtag($match);
            if (is_array($hashdata)) {
                $match_search = '#' . $match;
                $match_replace = '#[' . $hashdata['id'] . ']';
                if (mb_detect_encoding($match_search, 'ASCII', true)) {
                    $ms_data['text'] = preg_replace("/$match_search\b/i", $match_replace, $ms_data['text']);
                } else {
                    $ms_data['text'] = str_replace($match_search, $match_replace, $ms_data['text']);
                }
                $hashtag_query = " UPDATE " . T_HASHTAGS . " SET `last_trend_time` = " . time() . ", `trend_use_num` = " . ($hashdata['trend_use_num'] + 1) . " WHERE `id` = " . $hashdata['id'];
            }
        }
    }
    $fields = '`' . implode('`, `', array_keys($ms_data)) . '`';
    $data = '\'' . implode('\', \'', $ms_data) . '\'';
    $query = mysqli_query($sqlConnect, " INSERT INTO " . T_MESSAGES . " ({$fields}) VALUES ({$data})");
    if ($query) {
        $message_id = mysqli_insert_id($sqlConnect);
        Wo_CreateUserChat($ms_data['to_id'], $ms_data['from_id'], $ms_data['page_id']);
        return $message_id;
    } else {
        return false;
    }
}

function Wo_CreateUserChat($user_id = 0, $from_id = 0, $page_id = 0)
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($user_id)) {
        return false;
    }
    if (!empty($from_id)) {
        $logged_user_id = $from_id;
    } else {
        $logged_user_id = Wo_Secure($wo['user']['user_id']);
    }
    $user_id = Wo_Secure($user_id);
    $time = time();
    $added_query = "";
    if (!empty($page_id) && is_numeric($page_id) && $page_id > 0) {
        $page_id = Wo_Secure($page_id);
        $added_query = " AND `page_id` = '$page_id' ";
    } else {
        $added_query = " AND `page_id` = '0' ";
    }
    $query_one = mysqli_query($sqlConnect, "SELECT COUNT(`id`) as count FROM " . T_U_CHATS . " WHERE `conversation_user_id` = '$user_id' AND `user_id` = '$logged_user_id' $added_query ");
    if (mysqli_num_rows($query_one)) {
        $query_one_fetch = mysqli_fetch_assoc($query_one);
        if ($query_one_fetch['count'] > 0) {
            $query_two = mysqli_query($sqlConnect, "UPDATE " . T_U_CHATS . " SET `time` = '$time' WHERE `conversation_user_id` = '$user_id' AND `user_id` = '$logged_user_id' $added_query ");
            $query_two = mysqli_query($sqlConnect, "UPDATE " . T_U_CHATS . " SET `time` = '$time' WHERE `conversation_user_id` = '$logged_user_id' AND `user_id` = '$user_id' $added_query ");
            $query_five = mysqli_query($sqlConnect, "SELECT COUNT(`id`) as count FROM " . T_U_CHATS . " WHERE `user_id` = '$user_id' AND `conversation_user_id` = '$logged_user_id' $added_query ");
            if (mysqli_num_rows($query_five)) {
                $query_five_fetch = mysqli_fetch_assoc($query_five);
                if ($query_five_fetch['count'] == 0) {
                    if (!empty($page_id) && is_numeric($page_id) && $page_id > 0) {
                        $query_three = mysqli_query($sqlConnect, "INSERT INTO " . T_U_CHATS . " (`user_id`, `conversation_user_id`,`page_id`, `time`) VALUES ('$user_id', '$logged_user_id', '$page_id', '$time')");
                    } else {
                        $query_three = mysqli_query($sqlConnect, "INSERT INTO " . T_U_CHATS . " (`user_id`, `conversation_user_id`, `time`) VALUES ('$user_id', '$logged_user_id', '$time')");
                    }
                }
            }
            if ($query_two) {
                return true;
            }
        } else {
            if (!empty($page_id) && is_numeric($page_id) && $page_id > 0) {
                $query_two = mysqli_query($sqlConnect, "INSERT INTO " . T_U_CHATS . " (`user_id`, `conversation_user_id`,`page_id`, `time`) VALUES ('$logged_user_id', '$user_id', '$page_id', '$time')");
            } else {
                $query_two = mysqli_query($sqlConnect, "INSERT INTO " . T_U_CHATS . " (`user_id`, `conversation_user_id`, `time`) VALUES ('$logged_user_id', '$user_id', '$time')");
            }
            if ($query_two) {
                $query_one__ = mysqli_query($sqlConnect, "SELECT COUNT(`id`) as count FROM " . T_U_CHATS . " WHERE `conversation_user_id` = '$logged_user_id' AND `user_id` = '$user_id' $added_query ");
                if (mysqli_num_rows($query_one__)) {
                    $query_one_fetch__ = mysqli_fetch_assoc($query_one__);
                    if ($query_one_fetch__['count'] == 0) {
                        if (!empty($page_id) && is_numeric($page_id) && $page_id > 0) {
                            $query_three = mysqli_query($sqlConnect, "INSERT INTO " . T_U_CHATS . " (`user_id`, `conversation_user_id`,`page_id`, `time`) VALUES ('$user_id', '$logged_user_id', '$page_id', '$time')");
                        } else {
                            $query_three = mysqli_query($sqlConnect, "INSERT INTO " . T_U_CHATS . " (`user_id`, `conversation_user_id`, `time`) VALUES ('$user_id', '$logged_user_id', '$time')");
                        }
                    }
                }
                return true;
            }
        }
    }
}

function Wo_DeleteConversation($user_id = 0)
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    $user_id = Wo_Secure($user_id);
    $user_data = Wo_UserData($user_id);
    if (empty($user_data)) {
        return false;
    }
    $my_id = $wo['user']['user_id'];
    $query_one = "SELECT id FROM " . T_MESSAGES . " WHERE (`from_id` = {$user_id} AND `to_id` = '{$my_id}') OR (`from_id` = {$my_id} AND `to_id` = '{$user_id}')";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        while ($sql_fetch_one = mysqli_fetch_assoc($sql_query_one)) {
            $deleteMessage = Wo_DeleteMessage($sql_fetch_one['id'], '', $my_id);
        }
    }
    $query_one = mysqli_query($sqlConnect, "DELETE FROM " . T_U_CHATS . " WHERE `conversation_user_id` = '$user_id' AND `user_id` = '$my_id'");
    if ($query_one) {
        return true;
    }
}

function Wo_DeletePageConversation($user_id = 0, $page_id = 0)
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0 || empty($page_id) || !is_numeric($page_id) || $page_id < 0) {
        return false;
    }
    $user_id = Wo_Secure($user_id);
    $user_data = Wo_UserData($user_id);
    if (empty($user_data)) {
        return false;
    }
    $page_id = Wo_Secure($page_id);
    $page_data = Wo_PageData($page_id);
    if (empty($page_data)) {
        return false;
    }
    $my_id = $wo['user']['user_id'];
    $query_one = "SELECT id FROM " . T_MESSAGES . " WHERE (`from_id` = {$user_id} AND `page_id` = '{$page_id}' AND `to_id` = '{$my_id}') OR (`from_id` = {$my_id} AND `page_id` = '{$page_id}' AND `to_id` = '{$user_id}')";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        while ($sql_fetch_one = mysqli_fetch_assoc($sql_query_one)) {
            $deleteMessage = Wo_DeleteMessage($sql_fetch_one['id'], '', $my_id);
        }
    }
    $query_one = mysqli_query($sqlConnect, "DELETE FROM " . T_U_CHATS . " WHERE `conversation_user_id` = '$user_id' AND `page_id` = '{$page_id}' AND `user_id` = '$my_id'");
    if ($query_one) {
        return true;
    }
}

function Wo_DeleteGroupConversation($id = 0)
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($id) || !is_numeric($id) || $id < 0) {
        return false;
    }
    $user_id = Wo_Secure($user_id);
    $user_data = Wo_UserData($user_id);
    if (empty($user_data)) {
        return false;
    }
    $my_id = $wo['user']['user_id'];
    $query_one = "SELECT id FROM " . T_MESSAGES . " WHERE (`from_id` = {$user_id} AND `to_id` = '{$my_id}') OR (`from_id` = {$my_id} AND `to_id` = '{$user_id}')";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        while ($sql_fetch_one = mysqli_fetch_assoc($sql_query_one)) {
            $deleteMessage = Wo_DeleteMessage($sql_fetch_one['id'], '', $deleter_id);
        }
    }
    $query_one = mysqli_query($sqlConnect, "DELETE FROM " . T_U_CHATS . " WHERE `conversation_user_id` = '$user_id' AND `user_id` = '$my_id'");
    if ($query_one) {
        return true;
    }
}

function Wo_DeleteMessage($message_id, $media = '', $deleter_id = 0)
{
    global $wo, $sqlConnect;
    if (empty($deleter_id)) {
        if ($wo['loggedin'] == false) {
            return false;
        }
    }
    if (empty($message_id) || !is_numeric($message_id) || $message_id < 0) {
        return false;
    }
    $user_id = $deleter_id;
    if (empty($user_id) && $wo['loggedin'] == true) {
        $user_id = $wo['user']['user_id'];
    }
    $message_id = Wo_Secure($message_id);
    $query_one = "SELECT * FROM " . T_MESSAGES . " WHERE `id` = {$message_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if ($sql_query_one) {
        if (mysqli_num_rows($sql_query_one) == 1) {
            $sql_fetch_one = mysqli_fetch_assoc($sql_query_one);
            if ($sql_fetch_one['to_id'] != $user_id && $sql_fetch_one['from_id'] != $user_id) {
                return false;
            }
            if ($sql_fetch_one['deleted_one'] == 1 || $sql_fetch_one['deleted_two'] == 1) {
                $query = mysqli_query($sqlConnect, "DELETE FROM " . T_MESSAGES . " WHERE `id` = {$message_id}");
                if ($query) {
                    if (isset($sql_fetch_one['media']) and !empty($sql_fetch_one['media'])) {
                        @unlink($sql_fetch_one['media']);
                        $delete_from_s3 = Wo_DeleteFromToS3($sql_fetch_one['media']);
                    }
                    return true;
                } else {
                    return false;
                }
            } else {
                $delete_type = 'deleted_one';
                if ($sql_fetch_one['to_id'] == $user_id) {
                    $delete_type = 'deleted_two';
                }
                $query = mysqli_query($sqlConnect, "UPDATE " . T_MESSAGES . " set `$delete_type` = '1' WHERE `id` = {$message_id}");
                if ($query) {
                    return true;
                }
            }
            return false;
        }
    }
}

function Wo_CountMessages($data = array(), $type = '')
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($data['user_id']) or $data['user_id'] == 0) {
        $data['user_id'] = $wo['user']['user_id'];
    }
    if (!is_numeric($data['user_id']) or $data['user_id'] < 1) {
        return false;
    }
    $data['user_id'] = Wo_Secure($data['user_id']);
    if ($type == 'interval') {
        $account = $wo['user'];
    } else {
        $account = Wo_UserData($data['user_id']);
    }
    if (empty($account['user_id'])) {
        return false;
    }
    $logged_user_id = Wo_Secure($wo['user']['user_id']);
    if (isset($data['user_id']) && is_numeric($data['user_id']) && $data['user_id'] > 0) {
        $user_id = Wo_Secure($data['user_id']);
        if (isset($data['new']) && $data['new'] == true) {
            $query = " SELECT COUNT(`id`) AS `messages` FROM " . T_MESSAGES . " WHERE `to_id` = {$logged_user_id} AND (`from_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$user_id}') AND `from_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$user_id}'))";
            if ($wo['user']['user_id'] != $user_id) {
                $query .= " AND `from_id` = {$user_id}";
            }
        } else {
            $query = "SELECT COUNT(`id`) AS `messages` FROM " . T_MESSAGES . " WHERE ((`from_id` = {$user_id} AND `to_id` = {$logged_user_id} AND `deleted_two` = '0') OR (`from_id` = {$logged_user_id} AND `to_id` = {$user_id} AND `deleted_one` = '0') AND (`from_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$user_id}') AND `from_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$user_id}')))";
        }
    } else {
        $query = " SELECT COUNT(`from_id`) AS `messages` FROM " . T_MESSAGES . " WHERE `to_id` = {$logged_user_id} AND (`from_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$user_id}') AND `from_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$user_id}'))";
    }
    if (isset($data['new']) && $data['new'] == true) {
        $query .= " AND `seen` = 0";
    }
    if ($type == 'user') {
        $query .= " AND `page_id` = 0";
    }
    if (!empty($data['page_id']) && $data['page_id'] > 0) {
        $page_id = Wo_Secure($data['page_id']);
        $query .= " AND `page_id` = '$page_id' ";
    }
    $sql_query = mysqli_query($sqlConnect, $query);
    if (mysqli_num_rows($sql_query)) {
        $sql_fetch = mysqli_fetch_assoc($sql_query);
        return $sql_fetch['messages'];
    }
    return false;
}

function Wo_SeenMessage($message_id)
{
    global $sqlConnect;
    $message_id = Wo_Secure($message_id);
    $query = mysqli_query($sqlConnect, " SELECT `seen` FROM " . T_MESSAGES . " WHERE `id` = '{$message_id}'");
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        if ($fetched_data['seen'] > 0) {
            $data = array();
            $data['time'] = date('c', $fetched_data['seen']);
            $data['seen'] = Wo_Time_Elapsed_String($fetched_data['seen']);
            return $data;
        } else {
            return false;
        }
    }
    return false;
}

function Wo_GetMessageButton($user_id = 0)
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (!is_numeric($user_id) or $user_id < 0) {
        return false;
    }
    if ($user_id == $wo['user']['user_id']) {
        return false;
    }
    $user_id = Wo_Secure($user_id);
    $logged_user_id = Wo_Secure($wo['user']['user_id']);
    $message_button = 'buttons/message';
    $account = $wo['message'] = Wo_UserData($user_id);
    if (!isset($account['user_id'])) {
        return false;
    }
    if ($account['message_privacy'] == 1) {
        if (Wo_IsFollowing($logged_user_id, $user_id) === true) {
            return Wo_LoadPage($message_button);
        }
    } else if ($account['message_privacy'] == 0) {
        return Wo_LoadPage($message_button);
    } else if ($account['message_privacy'] == 2) {
        return false;
    }
}

function Wo_MarkupAPI($text = '', $link = true, $hashtag = true, $mention = true, $post_id = 0)
{
    global $sqlConnect;
    if (!empty($text)) {
        if ($mention == true) {
            $Orginaltext = $text;
            $mention_regex = '/@\[([0-9]+)\]/i';
            if (preg_match_all($mention_regex, $text, $matches)) {
                foreach ($matches[1] as $match) {
                    $match = Wo_Secure($match);
                    $match_user = Wo_UserData($match);
                    $match_search = '@[' . $match . ']';
                    if (isset($match_user['user_id'])) {
                        $match_replace = '<span class="hash" onclick="InjectAPI(\'{&quot;type&quot; : &quot;mention&quot;, &quot;user_id&quot;:&quot;' . $match_user['user_id'] . '&quot;}\');">' . $match_user['name'] . '</span>';
                        $text = str_replace($match_search, $match_replace, $text);
                    } else {
                        $match_replace = '';
                        $Orginaltext = str_replace($match_search, $match_replace, $Orginaltext);
                        $text = str_replace($match_search, $match_replace, $text);
                        if (!empty($post_id)) {
                            mysqli_query($sqlConnect, "UPDATE " . T_POSTS . " SET `postText` = '" . $Orginaltext . "' WHERE `id` = {$post_id}");
                        }
                    }
                }
            }
        }
        if ($link == true) {
            $link_search = '/\[a\](.*?)\[\/a\]/i';
            if (preg_match_all($link_search, $text, $matches)) {
                foreach ($matches[1] as $match) {
                    $match_decode = urldecode($match);
                    $match_decode_url = $match_decode;
                    $count_url = mb_strlen($match_decode);
                    if ($count_url > 50) {
                        $match_decode_url = mb_substr($match_decode_url, 0, 30) . '....' . mb_substr($match_decode_url, 30, 20);
                    }
                    $match_url = $match_decode;
                    if (!preg_match("/http(|s)\:\/\//", $match_decode)) {
                        $match_url = 'http://' . $match_url;
                    }
                    $text = str_replace('[a]' . $match . '[/a]', '<span onclick="InjectAPI(\'{&quot;type&quot; : &quot;url&quot;, &quot;link&quot;:&quot;' . strip_tags($match_url) . '&quot;}\');" class="hash" rel="nofollow">' . $match_decode_url . '</span>', $text);
                }
            }
        }
        if ($hashtag == true) {
            $hashtag_regex = '/(#\[([0-9]+)\])/i';
            preg_match_all($hashtag_regex, $text, $matches);
            $match_i = 0;
            foreach ($matches[1] as $match) {
                $hashtag = $matches[1][$match_i];
                $hashkey = $matches[2][$match_i];
                $hashdata = Wo_GetHashtag($hashkey);
                if (is_array($hashdata)) {
                    $hashlink = '<span class="hash" onclick="InjectAPI(\'{&quot;type&quot; : &quot;hashtag&quot;, &quot;tag&quot;:&quot;' . $hashdata['tag'] . '&quot;}\');">#' . $hashdata['tag'] . '</span>';
                    $text = str_replace($hashtag, $hashlink, $text);
                }
                $match_i++;
            }
        }
    }
    return $text;
}

function Wo_Markup($text, $link = true, $hashtag = true, $mention = true, $post_id = 0, $comment_id = 0, $reply_id = 0)
{
    global $sqlConnect;
    if (!empty($text)) {
        if ($mention == true) {
            $Orginaltext = $text;
            $mention_regex = '/@\[([0-9]+)\]/i';
            if (preg_match_all($mention_regex, $text, $matches)) {
                foreach ($matches[1] as $match) {
                    $match = Wo_Secure($match);
                    $match_user = Wo_UserData($match);
                    $match_search = '@[' . $match . ']';
                    if (isset($match_user['user_id'])) {
                        $match_replace = '<span class="user-popover" data-id="' . $match_user['id'] . '" data-type="' . $match_user['type'] . '"><a href="' . Wo_SeoLink('index.php?link1=timeline&u=' . $match_user['username']) . '" class="hash" data-ajax="?link1=timeline&u=' . $match_user['username'] . '">' . $match_user['name'] . '</a></span>';
                        $text = str_replace($match_search, $match_replace, $text);
                    } else {
                        $match_replace = '';
                        $Orginaltext = str_replace($match_search, $match_replace, $Orginaltext);
                        $text = str_replace($match_search, $match_replace, $text);
                        if (!empty($post_id)) {
                            mysqli_query($sqlConnect, "UPDATE " . T_POSTS . " SET `postText` = '" . $Orginaltext . "' WHERE `id` = {$post_id}");
                        } elseif (!empty($comment_id)) {
                            mysqli_query($sqlConnect, "UPDATE " . T_COMMENTS . " SET `text` = '" . $Orginaltext . "' WHERE `id` = {$comment_id}");
                        } elseif (!empty($reply_id)) {
                            mysqli_query($sqlConnect, "UPDATE " . T_COMMENTS_REPLIES . " SET `text` = '" . $Orginaltext . "' WHERE `id` = {$reply_id}");
                        }
                    }
                }
            }
        }
        if ($link == true) {
            $link_search = '/\[a\](.*?)\[\/a\]/i';
            if (preg_match_all($link_search, $text, $matches)) {
                foreach ($matches[1] as $match) {
                    $match_decode = urldecode($match);
                    $match_decode_url = $match_decode;
                    $count_url = mb_strlen($match_decode);
                    if ($count_url > 50) {
                        $match_decode_url = mb_substr($match_decode_url, 0, 30) . '....' . mb_substr($match_decode_url, 30, 20);
                    }
                    $match_url = $match_decode;
                    if (!preg_match("/http(|s)\:\/\//", $match_decode)) {
                        $match_url = 'http://' . $match_url;
                    }
                    $text = str_replace('[a]' . $match . '[/a]', '<a href="' . strip_tags($match_url) . '" target="_blank" class="hash" rel="nofollow">' . $match_decode_url . '</a>', $text);
                }
            }
        }
        if ($hashtag == true) {
            $hashtag_regex = '/(#\[([0-9]+)\])/i';
            preg_match_all($hashtag_regex, $text, $matches);
            $match_i = 0;
            foreach ($matches[1] as $match) {
                $hashtag = $matches[1][$match_i];
                $hashkey = $matches[2][$match_i];
                $hashdata = Wo_GetHashtag($hashkey);
                if (is_array($hashdata)) {
                    $hashlink = '<a href="' . Wo_SeoLink('index.php?link1=hashtag&hash=' . $hashdata['tag']) . '" class="hash">#' . $hashdata['tag'] . '</a>';
                    $text = str_replace($hashtag, $hashlink, $text);
                }
                $match_i++;
            }
        }
    }

    return $text;
}

function Wo_EditMarkup($text, $link = true, $hashtag = true, $mention = true, $post_id = 0, $comment_id = 0, $reply_id = 0)
{
    global $sqlConnect;
    if (!empty($text)) {
        if ($mention == true) {
            $Orginaltext = $text;
            $mention_regex = '/@\[([0-9]+)\]/i';
            if (preg_match_all($mention_regex, $text, $matches)) {
                foreach ($matches[1] as $match) {
                    $match = Wo_Secure($match);
                    $match_user = Wo_UserData($match);
                    $match_search = '@[' . $match . ']';
                    if (isset($match_user['user_id'])) {
                        $match_replace = '@' . $match_user['name'];
                        $text = str_replace($match_search, $match_replace, $text);
                    } else {
                        $match_replace = '';
                        $Orginaltext = str_replace($match_search, $match_replace, $Orginaltext);
                        $text = str_replace($match_search, $match_replace, $text);
                        if (!empty($post_id)) {
                            mysqli_query($sqlConnect, "UPDATE " . T_POSTS . " SET `postText` = '" . $Orginaltext . "' WHERE `id` = {$post_id}");
                        } elseif (!empty($comment_id)) {
                            mysqli_query($sqlConnect, "UPDATE " . T_COMMENTS . " SET `text` = '" . $Orginaltext . "' WHERE `id` = {$comment_id}");
                        } elseif (!empty($reply_id)) {
                            mysqli_query($sqlConnect, "UPDATE " . T_COMMENTS_REPLIES . " SET `text` = '" . $Orginaltext . "' WHERE `id` = {$reply_id}");
                        }
                    }
                }
            }
        }
        if ($link == true) {
            $link_search = '/\[a\](.*?)\[\/a\]/i';
            if (preg_match_all($link_search, $text, $matches)) {
                foreach ($matches[1] as $match) {
                    $match_decode = urldecode($match);
                    $match_url = $match_decode;
                    if (!preg_match("/http(|s)\:\/\//", $match_decode)) {
                        $match_url = 'http://' . $match_url;
                    }
                    $text = str_replace('[a]' . $match . '[/a]', $match_decode, $text);
                }
            }
        }
        if ($hashtag == true) {
            $hashtag_regex = '/(#\[([0-9]+)\])/i';
            preg_match_all($hashtag_regex, $text, $matches);
            $match_i = 0;
            foreach ($matches[1] as $match) {
                $hashtag = $matches[1][$match_i];
                $hashkey = $matches[2][$match_i];
                $hashdata = Wo_GetHashtag($hashkey);
                if (is_array($hashdata)) {
                    $hashlink = '#' . $hashdata['tag'];
                    $text = str_replace($hashtag, $hashlink, $text);
                }
                $match_i++;
            }
        }
    }
    return $text;
}

function Wo_Emo($string = '')
{
    global $emo, $wo;
    if (!empty($string)) {
        foreach ($emo as $code => $name) {
            $code = $code;
            $name = '<i class="twa-lg twa twa-' . $name . '"></i>';
            $string = str_replace($code, $name, $string);
        }
    }
    return $string;
}

function Wo_EmoPhone($string = '')
{
    global $emo_full;
    foreach ($emo_full as $code => $name) {
        $code = $code;
        $string = str_replace($code, $name, $string);
    }
    return $string;
}

function Wo_UploadLogo($data = array())
{
    global $wo, $sqlConnect;
    if (isset($data['file']) && !empty($data['file'])) {
        $data['file'] = $data['file'];
    }
    if (isset($data['name']) && !empty($data['name'])) {
        $data['name'] = Wo_Secure($data['name']);
    }
    if (isset($data['name']) && !empty($data['name'])) {
        $data['name'] = Wo_Secure($data['name']);
    }
    if (empty($data)) {
        return false;
    }
    $allowed = 'jpg,png,jpeg,gif';
    $new_string = pathinfo($data['name'], PATHINFO_FILENAME) . '.' . strtolower(pathinfo($data['name'], PATHINFO_EXTENSION));
    $extension_allowed = explode(',', $allowed);
    $file_extension = pathinfo($new_string, PATHINFO_EXTENSION);
    if (!in_array($file_extension, $extension_allowed)) {
        return false;
    }
    $dir = "themes/" . $wo['config']['theme'] . "/img/";
    $filename = $dir . "logo.{$file_extension}";
    if (move_uploaded_file($data['file'], $filename)) {
        $check_file = getimagesize($filename);
        if (!$check_file) {
            unlink($filename);
            return false;
        }
        if (Wo_SaveConfig('logo_extension', $file_extension . '?cache=' . rand(100, 999))) {
            return true;
        }
    }
}

function Wo_UploadNightLogo($data = array())
{
    global $wo, $sqlConnect;
    if (isset($data['file']) && !empty($data['file'])) {
        $data['file'] = $data['file'];
    }
    if (isset($data['name']) && !empty($data['name'])) {
        $data['name'] = Wo_Secure($data['name']);
    }
    if (isset($data['name']) && !empty($data['name'])) {
        $data['name'] = Wo_Secure($data['name']);
    }
    if (empty($data)) {
        return false;
    }
    $allowed = 'jpg,png,jpeg,gif';
    $new_string = pathinfo($data['name'], PATHINFO_FILENAME) . '.' . strtolower(pathinfo($data['name'], PATHINFO_EXTENSION));
    $extension_allowed = explode(',', $allowed);
    $file_extension = pathinfo($new_string, PATHINFO_EXTENSION);
    if (!in_array($file_extension, $extension_allowed)) {
        return false;
    }
    $dir = "themes/" . $wo['config']['theme'] . "/img/";
    $filename = $dir . "night-logo.{$file_extension}";
    if (move_uploaded_file($data['file'], $filename)) {
        $check_file = getimagesize($filename);
        if (!$check_file) {
            unlink($filename);
            return false;
        }
        if (Wo_SaveConfig('logo_extension', $file_extension . '?cache=' . rand(100, 999))) {
            return true;
        }
    }
}

function Wo_UploadBackground($data = array())
{
    global $wo, $sqlConnect;
    if (isset($data['file']) && !empty($data['file'])) {
        $data['file'] = $data['file'];
    }
    if (isset($data['name']) && !empty($data['name'])) {
        $data['name'] = Wo_Secure($data['name']);
    }
    if (isset($data['name']) && !empty($data['name'])) {
        $data['name'] = Wo_Secure($data['name']);
    }
    if (empty($data)) {
        return false;
    }
    $allowed = 'jpg,png,jpeg,gif';
    $new_string = pathinfo($data['name'], PATHINFO_FILENAME) . '.' . strtolower(pathinfo($data['name'], PATHINFO_EXTENSION));
    $extension_allowed = explode(',', $allowed);
    $file_extension = pathinfo($new_string, PATHINFO_EXTENSION);
    if (!in_array($file_extension, $extension_allowed)) {
        return false;
    }
    $dir = "themes/" . $wo['config']['theme'] . "/img/backgrounds/";
    $filename = $dir . "background-1.{$file_extension}";
    if (move_uploaded_file($data['file'], $filename)) {
        $check_file = getimagesize($filename);
        if (!$check_file) {
            unlink($filename);
            return false;
        }
        if (Wo_SaveConfig('background_extension', $file_extension)) {
            return true;
        }
    }
}

function Wo_UploadFavicon($data = array())
{
    global $wo, $sqlConnect;
    if (isset($data['file']) && !empty($data['file'])) {
        $data['file'] = $data['file'];
    }
    if (isset($data['name']) && !empty($data['name'])) {
        $data['name'] = Wo_Secure($data['name']);
    }
    if (isset($data['name']) && !empty($data['name'])) {
        $data['name'] = Wo_Secure($data['name']);
    }
    if (empty($data)) {
        return false;
    }
    $allowed = 'jpg,png,jpeg,gif';
    $new_string = pathinfo($data['name'], PATHINFO_FILENAME) . '.' . strtolower(pathinfo($data['name'], PATHINFO_EXTENSION));
    $extension_allowed = explode(',', $allowed);
    $file_extension = pathinfo($new_string, PATHINFO_EXTENSION);
    if (!in_array($file_extension, $extension_allowed)) {
        return false;
    }
    $dir = "themes/" . $wo['config']['theme'] . "/img/";
    $filename = $dir . "icon.{$file_extension}";
    if (move_uploaded_file($data['file'], $filename)) {
        $check_file = getimagesize($filename);
        if (!$check_file) {
            unlink($filename);
            return false;
        }
        if (Wo_SaveConfig('favicon_extension', $file_extension)) {
            return true;
        }
    }
}

function Wo_ShareFile($data = array(), $type = 0, $crop = true)
{
    global $wo, $sqlConnect, $s3;
    $allowed = '';
    if (!file_exists('upload/files/' . date('Y'))) {
        @mkdir('upload/files/' . date('Y'), 0777, true);
    }
    if (!file_exists('upload/files/' . date('Y') . '/' . date('m'))) {
        @mkdir('upload/files/' . date('Y') . '/' . date('m'), 0777, true);
    }
    if (!file_exists('upload/photos/' . date('Y'))) {
        @mkdir('upload/photos/' . date('Y'), 0777, true);
    }
    if (!file_exists('upload/photos/' . date('Y') . '/' . date('m'))) {
        @mkdir('upload/photos/' . date('Y') . '/' . date('m'), 0777, true);
    }
    if (!file_exists('upload/videos/' . date('Y'))) {
        @mkdir('upload/videos/' . date('Y'), 0777, true);
    }
    if (!file_exists('upload/videos/' . date('Y') . '/' . date('m'))) {
        @mkdir('upload/videos/' . date('Y') . '/' . date('m'), 0777, true);
    }
    if (!file_exists('upload/sounds/' . date('Y'))) {
        @mkdir('upload/sounds/' . date('Y'), 0777, true);
    }
    if (!file_exists('upload/sounds/' . date('Y') . '/' . date('m'))) {
        @mkdir('upload/sounds/' . date('Y') . '/' . date('m'), 0777, true);
    }

    if (isset($data['file']) && !empty($data['file'])) {
        $data['file'] = $data['file'];
    }
    if (isset($data['name']) && !empty($data['name'])) {
        $data['name'] = Wo_Secure($data['name']);
    }
    if (empty($data)) {
        return false;
    }
    if (empty($data['is_video'])) {
        $data['is_video'] = 0;
    }
    $new_string = pathinfo($data['name'], PATHINFO_FILENAME) . '.' . strtolower(pathinfo($data['name'], PATHINFO_EXTENSION));
    $file_extension = pathinfo($new_string, PATHINFO_EXTENSION);
    if ($data['is_video'] == 0) {
        if ($wo['config']['fileSharing'] == 1) {
            if (isset($data['types'])) {
                $allowed = $data['types'];
            } else {
                $allowed = $wo['config']['allowedExtenstion'];
            }
        } else {
            $allowed = 'jpg,png,jpeg,gif,mp4,m4v,webm,flv,mov,mpeg,mp3,wav,mkv';
        }
        $extension_allowed = explode(',', $allowed);
        if (!in_array($file_extension, $extension_allowed)) {
            return false;
        }
    }
    if ($data['size'] > $wo['config']['maxUpload']) {
        return false;
    }
    if ($file_extension == 'jpg' || $file_extension == 'jpeg' || $file_extension == 'png' || $file_extension == 'gif') {
        $folder = 'photos';
        $fileType = 'image';
    } else if ($file_extension == 'mp4' || $file_extension == 'mov' || $file_extension == 'webm' || $file_extension == 'flv' || $file_extension == 'mkv') {
        $folder = 'videos';
        $fileType = 'video';
    } elseif (!empty($data['is_video']) && $data['is_video'] == 1) {
        $folder = 'videos';
        $fileType = 'video';
    } else if ($file_extension == 'mp3' || $file_extension == 'wav') {
        $folder = 'sounds';
        $fileType = 'soundFile';
    } else {
        $folder = 'files';
        $fileType = 'file';
    }
    if (empty($folder) || empty($fileType)) {
        return false;
    }
    if ($data['is_video'] == 0) {
        $mime_types = explode(',', str_replace(' ', '', $wo['config']['mime_types'] . ',application/json,application/octet-stream'));
        if (Wo_IsAdmin()) {
            $mime_types = explode(',', str_replace(' ', '', $wo['config']['mime_types'] . ',application/json,application/octet-stream,image/svg+xml'));
        }
        if (!in_array($data['type'], $mime_types)) {
            return false;
        }
    }
    $dir = "upload/{$folder}/" . date('Y') . '/' . date('m');
    $filename = $dir . '/' . Wo_GenerateKey() . '_' . date('d') . '_' . md5(time()) . "_{$fileType}.{$file_extension}";
    $second_file = pathinfo($filename, PATHINFO_EXTENSION);
    if (move_uploaded_file($data['file'], $filename)) {
        if ($second_file == 'jpg' || $second_file == 'jpeg' || $second_file == 'png' || $second_file == 'gif') {
            $check_file = getimagesize($filename);
            if (!$check_file) {
                unlink($filename);
                return false;
            }
            if ($crop == true) {
                if ($type == 1) {
                    if ($second_file != 'gif') {
                        @Wo_CompressImage($filename, $filename, $wo['config']['images_quality']);
                    }
                    $explode2 = @end(explode('.', $filename));
                    $explode3 = @explode('.', $filename);
                    $last_file = $explode3[0] . '_small.' . $explode2;
                    if (Wo_Resize_Crop_Image(400, 400, $filename, $last_file, $wo['config']['images_quality'])) {
                        if ($second_file != 'gif' && $wo['config']['watermark'] == 1 && !empty($wo['add_watermark']) && $wo['add_watermark'] == true) {
                            watermark_image($last_file);
                        }
                        if (empty($data['local_upload'])) {
                            if (($wo['config']['amazone_s3'] == 1 || $wo['config']['wasabi_storage'] == 1 || $wo['config']['ftp_upload'] == 1 || $wo['config']['spaces'] == 1 || $wo['config']['cloud_upload'] == 1 || $wo['config']['backblaze_storage'] == 1) && !empty($last_file)) {
                                $upload_s3 = Wo_UploadToS3($last_file);
                            }
                        }
                    }
                } else {
                    if (!isset($data['compress']) && $second_file != 'gif') {
                        @Wo_CompressImage($filename, $filename, $wo['config']['images_quality']);
                    }
                }
            }
            if ($second_file != 'gif' && $wo['config']['watermark'] == 1 && !empty($wo['add_watermark']) && $wo['add_watermark'] == true) {
                watermark_image($filename);
            }
        }
        if (!empty($data['crop'])) {
            $crop_image = Wo_Resize_Crop_Image($data['crop']['width'], $data['crop']['height'], $filename, $filename, $wo['config']['images_quality']);
        }
        if (empty($data['local_upload'])) {
            if (($wo['config']['amazone_s3'] == 1 || $wo['config']['wasabi_storage'] == 1 || $wo['config']['ftp_upload'] == 1 || $wo['config']['spaces'] == 1 || $wo['config']['cloud_upload'] == 1 || $wo['config']['backblaze_storage'] == 1) && !empty($filename)) {
                $upload_s3 = Wo_UploadToS3($filename);
            }
        }
        $last_data = array();
        $last_data['filename'] = $filename;
        $last_data['name'] = $data['name'];
        return $last_data;
    }
}

function Wo_DisplaySharedFile($media, $placement = '', $cache = false, $is_video = false)
{
    global $wo, $sqlConnect, $db;
    $orginal = $media['filename'];
    if (!$is_video) {
        $wo['media']['filename'] = Wo_GetMedia($media['filename']);
    }
    $wo['media']['video_thumb'] = ((!empty($media['postFileThumb'])) ? Wo_GetMedia($media['postFileThumb']) : '');
    $wo['media']['name'] = Wo_Secure($media['name']);
    $wo['media']['type'] = $media['type'];
    $wo['media']['lightbox'] = $media['lightbox'] ?? null;
    $wo['media']['storyId'] = @$media['storyId'];
    $wo['is_video_ad'] = '';
    $wo['wo_ad_media'] = '';
    $wo['wo_ad_url'] = '';
    $wo['wo_ad_id'] = 0;
    $wo['rvad_con'] = '';
    $icon_size = 'fa-2x';
    if ($placement == 'chat') {
        $icon_size = '';
    }
    if (!empty($wo['media']['filename'])) {
        $file_extension = pathinfo($wo['media']['filename'], PATHINFO_EXTENSION);
        $file = '';
        $media_file = '';
        $start_link = "<a href=" . $wo['media']['filename'] . ">";
        $end_link = '</a>';
        $file_extension = strtolower($file_extension);
        if (!empty($cache)) {
            $wo['media']['filename'] = $wo['media']['filename'] . "?cache=" . $cache;
        }
        if ($file_extension == 'jpg' || $file_extension == 'jpeg' || $file_extension == 'png' || $file_extension == 'gif') {
            if ($placement == 'api') {
                $media_file .= "<img src='" . $wo['media']['filename'] . "' alt='image' class='image-file pointer' onclick=\"InjectAPI('{&quot;type&quot; : &quot;lightbox&quot;, &quot;image_url&quot;:&quot;" . $wo['media']['filename'] . "&quot;}');\">";
            } else {
                if ($placement != 'chat' && $placement != 'message') {
                    if (!empty($wo['story']) && $wo['story']['blur'] == 1) {
                        $media_file .= "<button class='btn btn-main image_blur_btn remover_blur_btn_" . $wo['story']['id'] . "' onclick='Wo_RemoveBlur(this," . $wo['story']['id'] . ")'>" . $wo['lang']['view_image'] . "</button>
                        <img src='" . $wo['media']['filename'] . "' alt='image' class='image-file pointer image_blur remover_blur_" . $wo['story']['id'] . "' onclick='Wo_OpenLightBox(" . $media['storyId'] . ");'>";
                    } else {
                        if (!$wo['story']['can_not_see_monetized']) {
                            $media_file .= "<img src='" . $wo['media']['filename'] . "' alt='image' class='image-file pointer' onclick='Wo_OpenLightBox(" . $media['storyId'] . ");'>";
                        }
                    }
                } else {
                    $media_file .= "<span data-href='" . $wo['media']['filename'] . "'  onclick='Wo_OpenLighteBox(this,event);'><img src='" . $wo['media']['filename'] . "' alt='image' class='image-file pointer'></span>";
                }
            }
        }


        // if(isset($wo['story']['can_not_see_monetized']) && $wo['story']['can_not_see_monetized'] == true && !isset($wo['user'])) {
        //     $media_file .= "<a style='padding:10px;' class='btn btn-main image_blur_btn remover_blur_btn_" . $wo['story']['id'] . "' href='" . Wo_SeoLink('index.php?link1=welcome') ."'>" . $wo['lang']['subscribe'] . "</button>";
        // } else 
        if(isset($wo['story']['can_not_see_monetized']) && $wo['story']['can_not_see_monetized'] == true && (!$wo['loggedin'] || ($wo['loggedin'] && $wo['story']['user_id'] !== $wo['user']['id']))) {
            $media_file .= "<img src='" . $wo['media']['filename'] . "' alt='image' class='image-file pointer'>";
            $media_file .= "<div class='wo_media_monetize'><div class='wo_media_monetize_innr'><svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 495.787 495.787' xml:space='preserve' fill='currentColor'> <g> <g> <path d='M247.893,0C110.986,0,0,110.986,0,247.893s110.986,247.893,247.893,247.893s247.893-110.986,247.893-247.893 C495.669,111.034,384.752,0.118,247.893,0z M247.893,474.453L247.893,474.453c-125.126,0-226.56-101.434-226.56-226.56 s101.434-226.56,226.56-226.56c125.126,0,226.56,101.434,226.56,226.56C474.336,372.97,372.97,474.336,247.893,474.453z'></path> </g> </g> <g> <g> <path d='M320.853,212.48v-26.667c-2.181-40.354-36.663-71.298-77.016-69.117c-37.305,2.016-67.101,31.812-69.117,69.117v26.453 c-13.33,6.609-21.642,20.325-21.333,35.2v96.427c-0.237,21.206,16.762,38.59,37.969,38.827c0.286,0.003,0.572,0.003,0.858,0 h113.28c21.395-0.117,38.71-17.432,38.827-38.827v-96C344.222,232.477,335.013,218.579,320.853,212.48z M196.053,185.813 c0.117-28.547,23.293-51.627,51.84-51.627c28.547,0,51.723,23.08,51.84,51.627v22.827h-103.68V185.813z M304.853,361.387H191.36 c-9.661,0-17.493-7.832-17.493-17.493v-96c-0.239-9.423,7.206-17.255,16.629-17.493c0.288-0.007,0.576-0.007,0.864,0h113.28 c9.661,0,17.493,7.832,17.493,17.493l0.213,96C322.347,353.555,314.515,361.387,304.853,361.387z'></path> </g> </g> <g> <g> <path d='M247.893,264.32c-9.532,0.112-17.264,7.75-17.493,17.28c0.099,5.289,2.614,10.241,6.827,13.44v21.333 c0,5.891,4.776,10.667,10.667,10.667s10.667-4.776,10.667-10.667V295.04c4.378-3.178,6.99-8.244,7.04-13.653 C265.367,271.809,257.473,264.2,247.893,264.32z'></path> </g> </g> </svg>" . $wo['lang']['post_is_monetized'] . "<br><a class='btn btn-mat remover_blur_btn_" . $wo['story']['id'] . "' href='".Wo_SeoLink('index.php?link1=monetization&user='.$wo['story']['publisher']['username'])."'  data-ajax='?link1=monetization&user=".$wo['story']['publisher']['username']."'><svg xmlns='http://www.w3.org/2000/svg' height='24' viewBox='0 -960 960 960' width='24'><path fill='currentColor' d='M880-720v480q0 33-23.5 56.5T800-160H160q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h640q33 0 56.5 23.5T880-720Zm-720 80h640v-80H160v80Zm0 160v240h640v-240H160Zm0 240v-480 480Z'></path></svg>" . $wo['lang']['subscribe'] . "</a></div></div>";
            return $media_file;
        }

        if ($file_extension == 'pdf') {
            $file .= '<i class="fa ' . $icon_size . ' fa-file-pdf-o"></i> ' . $wo['media']['name'];
        }
        if ($file_extension == 'txt') {
            $file .= '<i class="fa ' . $icon_size . ' fa-file-text-o"></i> ' . $wo['media']['name'];
        }
        if ($file_extension == 'zip' || $file_extension == 'rar' || $file_extension == 'tar') {
            $file .= '<i class="fa ' . $icon_size . ' fa-file-archive-o"></i> ' . $wo['media']['name'];
        }
        if ($file_extension == 'doc' || $file_extension == 'docx') {
            $file .= '<i class="fa ' . $icon_size . ' fa-file-word-o"></i> ' . $wo['media']['name'];
        }
        if ($file_extension == 'mp3' || $file_extension == 'wav') {
            if ($placement == 'chat') {
                $file .= '<i class="fa ' . $icon_size . ' fa-music"></i> ' . $wo['media']['name'];
            } else if ($placement == 'message') {
                $media_file .= Wo_LoadPage('players/chat-audio');
            } else if ($placement == 'record') {
                $media_file .= Wo_LoadPage('players/audio');
            } else {
                $media_file .= Wo_LoadPage('players/audio');
            }
        }
        if (empty($file)) {
            $file .= '<i class="fa ' . $icon_size . ' fa-file-o"></i> ' . $wo['media']['name'];
        }
        if ($file_extension == 'mp4' || $file_extension == 'mkv' || $file_extension == 'avi' || $file_extension == 'webm' || $file_extension == 'mov' || $file_extension == 'm3u8' || $is_video) {
            if ($placement == 'message' || $placement == 'chat') {
                $media_file .= Wo_LoadPage('players/chat-video');
            } else {
                $t_users = T_USERS;
                $lats_ad_id = (!empty($_GET['ad_id']) && is_numeric($_GET['ad_id'])) ? $_GET['ad_id'] : false;
                if (!empty($wo['ad-con']['ads'])) {
                    $con_list = implode(',', $wo['ad-con']['ads']);
                    if ($con_list) {
                        $db->where(" `id` NOT IN ({$con_list}) ");
                    }
                }
                    
                $db->where(" `user_id` IN (SELECT `user_id` FROM `$t_users` WHERE `wallet` > 0) ");
                $db->where("`status`", 1);
                $db->where("`appears`", 'video');
                if (!empty($lats_ad_id)) {
                    $db->where("id", $lats_ad_id, "<>");
                }
                if ($wo['loggedin'] && !empty($wo['user']['country_id'])) {
                    $usr_country = $wo['user']['country_id'];
                    $db->where(" `audience` LIKE '%$usr_country%' ");
                }
                $start = date('m-d-y');
                $video_ad = $db->where("((start = '') OR (start <= '{$start}' && end >= '{$start}'))")->where("((budget = 0) OR (spent < budget))")->orderBy('RAND()')->getOne(T_USER_ADS);
                if (!empty($video_ad)) {
                    $wo['is_video_ad'] = ",'ads'";
                    $wo['wo_ad_url'] = $video_ad->url;
                    $wo['wo_ad_media'] = $video_ad->ad_media;
                    $wo['wo_ad_id'] = $video_ad->id;
                    $wo['rvad_con'] = "rvad-" . $video_ad->bidding;
                    if ($video_ad->bidding == 'views') {
                        Wo_RegisterAdConversionView($video_ad->id);
                    } else {
                        Wo_RegisterAdView($video_ad->id);
                    }
                }
                $wo['story']['240p_video'] = '';
                $wo['story']['360p_video'] = '';
                $wo['story']['480p_video'] = '';
                $wo['story']['720p_video'] = '';
                $wo['story']['1080p_video'] = '';
                $wo['story']['2048p_video'] = '';
                $wo['story']['4096p_video'] = '';
                if ($file_extension == 'm3u8') {
                    $wo['media']['filename'] = $wo['config']['s3_site_url_2'] . '/' . $orginal;
                    $media_file .= Wo_LoadPage('players /videojs');
                } else {
                    if ($wo['config']['ffmpeg_system'] == 'on') {
                        $explode_video = explode('_video', $wo['media']['filename']);
                        if (!empty($wo['story'])) {
                            if ($wo['story']['240p'] == 1) {
                                $wo['story']['240p_video'] = $explode_video[0] . '_video_240p_converted.mp4';
                            }
                            if ($wo['story']['360p'] == 1) {
                                $wo['story']['360p_video'] = $explode_video[0] . '_video_360p_converted.mp4';
                            }
                            if ($wo['story']['480p'] == 1) {
                                $wo['story']['480p_video'] = $explode_video[0] . '_video_480p_converted.mp4';
                            }
                            if ($wo['story']['720p'] == 1) {
                                $wo['story']['720p_video'] = $explode_video[0] . '_video_720p_converted.mp4';
                            }
                            if ($wo['story']['1080p'] == 1) {
                                $wo['story']['1080p_video'] = $explode_video[0] . '_video_1080p_converted.mp4';
                            }
                            if ($wo['story']['2048p'] == 1) {
                                $wo['story']['2048p_video'] = $explode_video[0] . '_video_2048p_converted.mp4';
                            }
                            if ($wo['story']['4096p'] == 1) {
                                $wo['story']['4096p_video'] = $explode_video[0] . '_video_4096p_converted.mp4';
                            }
                        }
                    }
                    $media_file .= Wo_LoadPage('players/video');
                }
            }
        }
        $last_file_view = '';
        if (isset($media_file) && !empty($media_file)) {
            $last_file_view = $media_file;
        } else {
            $last_file_view = $start_link . $file . $end_link;
        }
        return $last_file_view;
    }
}

function Wo_IsAdmin($user_id = 0)
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    $user_id = Wo_Secure($user_id);
    if (!empty($user_id) && $user_id > 0) {
        $query = mysqli_query($sqlConnect, "SELECT COUNT(`user_id`) as count FROM " . T_USERS . " WHERE admin = '1' AND user_id = {$user_id}");
        if (mysqli_num_rows($query)) {
            $sql = mysqli_fetch_assoc($query);
            if ($sql['count'] > 0) {
                return true;
            } else {
                return false;
            }
        }
    }
    if ($wo['user']['admin'] == 1) {
        return true;
    }
    return false;
}

function Wo_IsModerator($user_id = '')
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    $user_id = Wo_Secure($user_id);
    if (!empty($user_id) && $user_id > 0) {
        $query = mysqli_query($sqlConnect, "SELECT COUNT(`user_id`) as count FROM " . T_USERS . " WHERE admin = '2' AND user_id = {$user_id}");
        if (mysqli_num_rows($query)) {
            $sql = mysqli_fetch_assoc($query);
            if ($sql['count'] > 0) {
                return true;
            } else {
                return false;
            }
        }
    }
    if ($wo['user']['admin'] == 2) {
        return true;
    }
    return false;
}

function Wo_CheckIfUserCanPost($num = 10)
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    $user_id = Wo_Secure($wo['user']['user_id']);
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 1) {
        return false;
    }
    $time = time() - 3200;
    $query = mysqli_query($sqlConnect, "SELECT COUNT(`id`) as count FROM " . T_POSTS . " WHERE `user_id` = {$user_id} AND `time` > {$time}");
    if (mysqli_num_rows($query)) {
        $sql_query = mysqli_fetch_assoc($query);
        if ($sql_query['count'] > $num) {
            return false;
        }
    }
    return true;
}

function Wo_CheckIfUserCanRegister($num = 10)
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == true) {
        return false;
    }
    $ip = get_ip_address();
    if (empty($ip)) {
        return true;
    }
    $time = time() - 3200;
    $query = mysqli_query($sqlConnect, "SELECT COUNT(`user_id`) as count FROM " . T_USERS . " WHERE `ip_address` = '{$ip}' AND `joined` > {$time}");
    if (mysqli_num_rows($query)) {
        $sql_query = mysqli_fetch_assoc($query);
        if ($sql_query['count'] > $num) {
            return false;
        }
    }
    return true;
}

function Wo_RegisterPost($re_data = array('recipient_id' => 0))
{
    error_log(print_r($re_data, true));

    global $wo, $sqlConnect;
    if ($wo['config']['website_mode'] == 'instagram' && empty($re_data['postFile']) && empty($re_data['multi_image']) && empty($re_data['postSticker']) && empty($re_data['product_id']) && empty($re_data['album_name'])) {
        if (!preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $re_data["postText"])) {
            header("Content-type: application/json");
            echo json_encode(array(
                'status' => 400,
                'errors' => $wo['lang']['please_select_a_media_file'],
                'invalid_file' => false
            ));
            exit();
        }
    }
    $is_there_video = false;
    $playtube_root = preg_quote($wo['config']['playtube_url']);
    $deepsound_root = preg_quote($wo['config']['deepsound_url']);
    if (empty($re_data['user_id']) or $re_data['user_id'] == 0) {
        $re_data['user_id'] = $wo['user']['user_id'];
    }
    if (!is_numeric($re_data['user_id']) or $re_data['user_id'] < 0) {
        return false;
    }
    if ($re_data['user_id'] == $wo['user']['user_id']) {
        $timeline = $wo['user'];
    } else {
        $re_data['user_id'] = Wo_Secure($re_data['user_id']);
        $timeline = Wo_UserData($re_data['user_id']);
    }
    if ($timeline['user_id'] != $wo['user']['user_id'] && !Wo_IsAdmin()) {
        return false;
    }
    if (!empty($re_data['page_id'])) {
        if (Wo_IsPageOnwer($re_data['page_id']) === false && Wo_UserCanPostPage($re_data['page_id']) === false) {
            return false;
        }
    }
    if (!empty($re_data['group_id'])) {
        if (Wo_CanBeOnGroup($re_data['group_id']) === false) {
            return false;
        }
    }
    if (!Wo_CheckIfUserCanPost($wo['config']['post_limit'])) {
        return false;
    }
    if (!empty($re_data['postText'])) {
        if ($wo['config']['maxCharacters'] > 0) {
            if ((mb_strlen($re_data['postText']) - 10) > $wo['config']['maxCharacters']) {
                return false;
            }
        }
        $re_data['postVine'] = '';
        $re_data['postYoutube'] = '';
        $re_data['postVimeo'] = '';
        $re_data['postDailymotion'] = '';
        $re_data['postFacebook'] = '';
        $re_data['postFacebook'] = '';
        $re_data['postPlaytube'] = '';
        if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $re_data['postText'], $match)) {
            $re_data['postYoutube'] = Wo_Secure($match[1]);
            //$re_data['postText'] = preg_replace('/((?:https?:\/\/)?www\.youtube\.com\/watch\?v=\w+)/', "", $re_data['postText']);
            //$re_data['postText'] = preg_replace($match[0], "", $re_data['postText']);
            $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
            preg_match_all($link_regex, $re_data['postText'], $matches);
            foreach ($matches[0] as $match) {
                $match_url = strip_tags($match);
                $syntax = '';
                $re_data['postText'] = str_replace($match, $syntax, $re_data['postText']);
            }
            $is_there_video = true;
        }
        if (Wo_IsUrl($wo['config']['playtube_url']) && preg_match('#' . $playtube_root . '\/(?:watch|embed)\/(.*)#i', $re_data['postText'], $match)) {
            $re_data['postPlaytube'] = ((!empty($match[1])) ? Wo_Secure($match[1]) : '');
            $is_there_video = true;
        }
        if (Wo_IsUrl($wo['config']['deepsound_url']) && preg_match('#' . $deepsound_root . '\/(?:track|embed)\/(.*)#i', $re_data['postText'], $match)) {
            $re_data['postDeepsound'] = ((!empty($match[1])) ? Wo_Secure($match[1]) : '');
        }
        if (preg_match("#(?<=vine.co/v/)[0-9A-Za-z]+#", $re_data['postText'], $match)) {
            $re_data['postVine'] = Wo_Secure($match[0]);
            $is_there_video = true;
        }
        if (preg_match("#https?://vimeo.com/([0-9]+)#i", $re_data['postText'], $match)) {
            $re_data['postVimeo'] = Wo_Secure($match[1]);
            $is_there_video = true;
        }
        if (preg_match('#(http|https)://www.dailymotion.com/video/([A-Za-z0-9]+)#s', $re_data['postText'], $match)) {
            $re_data['postDailymotion'] = Wo_Secure($match[2]);
            $is_there_video = true;
        }
        if (preg_match('~([A-Za-z0-9]+)/videos/(?:t\.\d+/)?(\d+)~i', $re_data['postText'], $match)) {
            $re_data['postFacebook'] = Wo_Secure($match[0]);
            $is_there_video = true;
        }
        if (preg_match('~fb.watch\/(.*)~', $re_data['postText'], $match)) {
            $re_data['postFacebook'] = Wo_Secure($match[1]);
            $is_there_video = true;
        }
        if (preg_match("~\bfacebook\.com.*?\bv=(\d+)~", $re_data['postText'], $match)) {
            $is_there_video = true;
        }
        if (preg_match('~https://www.facebook.com\/(.*)\/(.*)\/(?:t\.\d+/)?(\d+)~i', $re_data['postText'], $match) || preg_match('~https://fb.watch\/(.*)~', $re_data['postText'], $match) || preg_match('~(?:https://www.facebook.com\/watch\/\?v=)(.*)~', $re_data['postText'],$match) || preg_match('~(?:https://www.facebook.com\/watch\?v=)(.*)~', $re_data['postText'],$match)) {
            $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
            preg_match_all($link_regex, $re_data['postText'], $matches);
            if (!empty($matches) && !empty($matches[0]) && !empty($matches[0][0])) {
                $re_data['postFacebook'] = Wo_Secure($matches[0][0]);
                $is_there_video = true;
            }
        }
        if (preg_match('%(?:https?://)(?:www\.)?soundcloud\.com/([\-a-z0-9_]+/[\-a-z0-9_]+)%im', $re_data['postText'], $match)) {
            $arrContextOptions = array(
                "ssl" => array(
                    "verify_peer" => false,
                    "verify_peer_name" => false
                )
            );
            $url = "https://api.soundcloud.com/resolve.json?url=" . $match[0] . "&client_id=d4f8636b1b1d07e4461dcdc1db226a53";
            $track_json = @file_get_contents($url, false, stream_context_create($arrContextOptions));
            $track = json_decode($track_json, true);
            if (!empty($track[0]['tracks'][0]['id'])) {
                $re_data['postSoundCloud'] = $track[0]['tracks'][0]['id'];
            } else if (!empty($track['id'])) {
                $re_data['postSoundCloud'] = $track['id'];
            }
            $is_there_video = true;
        }
        $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
        $i = 0;
        preg_match_all($link_regex, $re_data['postText'], $matches);
        foreach ($matches[0] as $match) {
            $match_url = strip_tags($match);
            $syntax = '[a]' . urlencode($match_url) . '[/a]';
            $re_data['postText'] = str_replace($match, $syntax, $re_data['postText']);
        }
        $mention_regex = '/@([A-Za-z0-9_]+)/i';
        preg_match_all($mention_regex, $re_data['postText'], $matches);
        foreach ($matches[1] as $match) {
            $match = Wo_Secure($match);
            $match_user = Wo_UserData(Wo_UserIdFromUsername($match));
            $match_search = '@' . $match;
            $match_replace = '@[' . $match_user['user_id'] . ']';
            if (isset($match_user['user_id'])) {
                $re_data['postText'] = str_replace($match_search, $match_replace, $re_data['postText']);
                $mentions[] = $match_user['user_id'];
            }
        }
        $hashtag_regex = '/#([^`~!@$%^&*\#()\-+=\\|\/\.,<>?\'\":;{}\[\]* ]+)/i';
        preg_match_all($hashtag_regex, $re_data['postText'], $matches);
        foreach ($matches[1] as $match) {
            $re_data['postText'] = str_replace('#' . $match, '#' . mb_strtolower($match, 'UTF-8'), $re_data['postText']);
            $match = mb_strtolower($match, 'UTF-8');
            if (!is_numeric($match)) {
                $hashdata = Wo_GetHashtag($match);
                if (is_array($hashdata)) {
                    $match_search = '#' . $match;
                    $match_replace = '#[' . $hashdata['id'] . ']';
                    if (mb_detect_encoding($match_search, 'ASCII', true)) {
                        $re_data['postText'] = preg_replace("/$match_search\b/i", $match_replace, $re_data['postText']);
                    } else {
                        $re_data['postText'] = str_replace($match_search, $match_replace, $re_data['postText']);
                    }
                    $hashtag_query = "UPDATE " . T_HASHTAGS . " SET
                    `last_trend_time` = " . time() . ",
                    `trend_use_num`   = " . ($hashdata['trend_use_num'] + 1) . ",
                    `expire`          = '" . date('Y-m-d', strtotime(date('Y-m-d') . " +1week")) . "'
                    WHERE `id` = " . $hashdata['id'];
                    $hashtag_sql_query = mysqli_query($sqlConnect, $hashtag_query);
                }
            }
        }
    }
    $re_data['registered'] = date('n') . '/' . date("Y");
    if ($is_there_video == true) {
        $re_data['postFile'] = '';
        $re_data['postLinkImage'] = '';
        $re_data['postLinkTitle'] = '';
        $re_data['postLinkContent'] = '';
        $re_data['postLink'] = '';
    }
    if (!empty($re_data['postPlaytube'])) {
        $re_data['postYoutube'] = '';
        $re_data['postVimeo'] = '';
        $re_data['postDailymotion'] = '';
        $re_data['postFacebook'] = '';
        $re_data['postSoundCloud'] = '';
        $re_data['postDeepsound'] = '';
    }
    if (!empty($re_data['postDeepsound'])) {
        $re_data['postYoutube'] = '';
        $re_data['postVimeo'] = '';
        $re_data['postDailymotion'] = '';
        $re_data['postFacebook'] = '';
        $re_data['postSoundCloud'] = '';
        $re_data['postPlaytube'] = '';
    }
    if (!empty($re_data['postVine'])) {
        $re_data['postYoutube'] = '';
        $re_data['postVimeo'] = '';
        $re_data['postDailymotion'] = '';
        $re_data['postFacebook'] = '';
        $re_data['postSoundCloud'] = '';
        $re_data['postPlaytube'] = '';
        $re_data['postDeepsound'] = '';
    } else if (!empty($re_data['postYoutube'])) {
        $re_data['postVine'] = '';
        $re_data['postVimeo'] = '';
        $re_data['postDailymotion'] = '';
        $re_data['postFacebook'] = '';
        $re_data['postSoundCloud'] = '';
        $re_data['postPlaytube'] = '';
        $re_data['postDeepsound'] = '';
    }
    if (!empty($re_data['postVimeo'])) {
        $re_data['postVine'] = '';
        $re_data['postYoutube'] = '';
        $re_data['postDailymotion'] = '';
        $re_data['postFacebook'] = '';
        $re_data['postSoundCloud'] = '';
        $re_data['postPlaytube'] = '';
        $re_data['postDeepsound'] = '';
    }
    if (!empty($re_data['postDailymotion'])) {
        $re_data['postYoutube'] = '';
        $re_data['postVimeo'] = '';
        $re_data['postVine'] = '';
        $re_data['postFacebook'] = '';
        $re_data['postSoundCloud'] = '';
        $re_data['postPlaytube'] = '';
        $re_data['postDeepsound'] = '';
    }
    if (!empty($re_data['postFacebook'])) {
        $re_data['postYoutube'] = '';
        $re_data['postVimeo'] = '';
        $re_data['postDailymotion'] = '';
        $re_data['postVine'] = '';
        $re_data['postSoundCloud'] = '';
        $re_data['postPlaytube'] = '';
        $re_data['postDeepsound'] = '';
    }
    if (!empty($re_data['postSoundCloud'])) {
        $re_data['postYoutube'] = '';
        $re_data['postVimeo'] = '';
        $re_data['postDailymotion'] = '';
        $re_data['postFacebook'] = '';
        $re_data['postVine'] = '';
        $re_data['postPlaytube'] = '';
        $re_data['postDeepsound'] = '';
    }
    if (empty($re_data['multi_image'])) {
        $re_data['multi_image'] = 0;
    }
    if (empty($re_data['postText']) && empty($re_data['album_name']) && $re_data['multi_image'] == 0 && empty($re_data['postFacebook']) && empty($re_data['postVimeo']) && empty($re_data['postDailymotion']) && empty($re_data['postVine']) && empty($re_data['postYoutube']) && empty($re_data['postFile']) && empty($re_data['postSoundCloud']) && empty($re_data['postFeeling']) && empty($re_data['postListening']) && empty($re_data['postPlaying']) && empty($re_data['postWatching']) && empty($re_data['postTraveling']) && empty($re_data['postMap']) && empty($re_data['product_id']) && empty($re_data['blog_id']) && empty($re_data['page_event_id']) && empty($re_data['postRecord']) && empty($re_data['postSticker']) && empty($re_data['postPlaytube']) && empty($re_data['postDeepsound']) && empty($re_data['fund_raise_id']) && empty($re_data['fund_id']) && $re_data['multi_image_post'] == 0) {
        return false;
    }
    if (!empty($re_data['recipient_id']) && is_numeric($re_data['recipient_id']) && $re_data['recipient_id'] > 0) {
        if ($re_data['recipient_id'] == $re_data['user_id']) {
            return false;
        }
        $recipient = Wo_UserData($re_data['recipient_id']);
        if (empty($recipient['user_id'])) {
            return false;
        }
        if (!empty($recipient['user_id'])) {
            if ($recipient['post_privacy'] == 'ifollow') {
                if (Wo_IsFollowing($recipient['user_id'], $wo['user']['user_id']) === false) {
                    return false;
                }
            } else if ($recipient['post_privacy'] == 'nobody') {
                return false;
            }
        }
    }
    if (!isset($re_data['postType'])) {
        $re_data['postType'] = 'post';
    }
    if (!empty($re_data['page_id'])) {
        if (Wo_IsPageOnwer($re_data['page_id'])) {
            $re_data['user_id'] = 0;
        }
    }
    $fields = '`' . implode('`, `', array_keys($re_data)) . '`';
    $data = '\'' . implode('\', \'', $re_data) . '\'';
    $query = mysqli_query($sqlConnect, "INSERT INTO " . T_POSTS . " ({$fields}) VALUES ({$data})");
    $post_id = mysqli_insert_id($sqlConnect);
    if ($query) {
        mysqli_query($sqlConnect, "UPDATE " . T_POSTS . " SET `post_id` = {$post_id} WHERE `id` = {$post_id}");
        if (isset($recipient['user_id'])) {
            $notification_data_array = array(
                'recipient_id' => $recipient['user_id'],
                'post_id' => $post_id,
                'type' => 'profile_wall_post',
                'url' => 'index.php?link1=post&id=' . $post_id,
                'type2' => ($re_data['postPrivacy'] == 4 ? 'anonymous' : '')
            );
            Wo_RegisterNotification($notification_data_array);
        }
        if (isset($mentions) && is_array($mentions)) {
            foreach ($mentions as $mention) {
                $notification_data_array = array(
                    'recipient_id' => $mention,
                    'page_id' => $re_data['page_id'],
                    'type' => 'post_mention',
                    'post_id' => $post_id,
                    'url' => 'index.php?link1=post&id=' . $post_id
                );
                Wo_RegisterNotification($notification_data_array);
            }
        }
        //Register point level system for createpost
        if (!empty($re_data['blog_id']) && $re_data['active'] == 1) {
            Wo_RegisterPoint($post_id, "createblog");
        } else {
            if (isset($re_data['multi_image_post']) && $re_data['multi_image_post'] != 1 && empty($re_data['blog_id'])) {
                Wo_RegisterPoint($post_id, "createpost");
            }
        }
        return $post_id;
    }
}

function Wo_GetHashtag($tag = '', $type = true)
{
    global $sqlConnect;
    $create = false;
    if (empty($tag)) {
        return false;
    }
    $tag = Wo_Secure($tag);
    $md5_tag = md5($tag);
    if (is_numeric($tag)) {
        $query = " SELECT * FROM " . T_HASHTAGS . " WHERE `id` = {$tag}";
    } else {
        $query = " SELECT * FROM " . T_HASHTAGS . " WHERE `hash` = '{$md5_tag}' ";
        $create = true;
    }
    $sql_query = mysqli_query($sqlConnect, $query);
    $sql_numrows = mysqli_num_rows($sql_query);
    $week = date('Y-m-d', strtotime(date('Y-m-d') . " +1week"));
    if ($sql_numrows == 1) {
        if (mysqli_num_rows($sql_query)) {
            $sql_fetch = mysqli_fetch_assoc($sql_query);
            return $sql_fetch;
        }
        return false;
    } elseif ($sql_numrows == 0 && $type == true) {
        if ($create == true) {
            $hash = md5($tag);
            $query_two = " INSERT INTO " . T_HASHTAGS . " (`hash`, `tag`, `last_trend_time`,`expire`) VALUES ('{$hash}', '{$tag}', " . time() . ", '$week')";
            $sql_query_two = mysqli_query($sqlConnect, $query_two);
            if ($sql_query_two) {
                $sql_id = mysqli_insert_id($sqlConnect);
                $data = array(
                    'id' => $sql_id,
                    'hash' => $hash,
                    'tag' => $tag,
                    'last_trend_time' => time(),
                    'trend_use_num' => 0
                );
                return $data;
            }
        }
    }
}

function Wo_PostData($post_id, $placement = '', $limited = '', $comments_limit = 0)
{
    global $wo, $sqlConnect, $cache, $db;
    if (empty($post_id) || !is_numeric($post_id) || $post_id < 0) {
        return false;
    }
    $data = array();
    $post_id = Wo_Secure($post_id);
    $query_one = "SELECT * FROM " . T_POSTS . " WHERE `id` = {$post_id}";
    if ($wo['config']['post_approval'] == 1 && !Wo_IsAdmin()) {
        $query_one .= " AND `active` = '1' ";
    }
    $hashed_post_Id = md5($post_id);
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        $fetched_data = mysqli_fetch_assoc($sql_query_one);
    }
    if (empty($fetched_data['id'])) {
        return false;
    }
    if (!empty($fetched_data['page_id'])) {
        if (empty($fetched_data['user_id'])) {
            $fetched_data['publisher'] = Wo_PageData($fetched_data['page_id']);
            $fetched_data['publisher']['banned'] = 0;
            $fetched_data['page_info'] = array();
        } else {
            $fetched_data['publisher'] = Wo_UserData($fetched_data['user_id']);
            $fetched_data['page_info'] = Wo_PageData($fetched_data['page_id']);
        }
    } else {
        $fetched_data['publisher'] = Wo_UserData($fetched_data['user_id']);
    }
    if ($fetched_data['id'] == $fetched_data['post_id']) {
        $story = $fetched_data;
    } else {
        $query_two = "SELECT * FROM " . T_POSTS . " WHERE `id` = " . $fetched_data['post_id'];
        $sql_query_two = mysqli_query($sqlConnect, $query_two);
        if ($sql_query_two) {
            if (mysqli_num_rows($sql_query_two) != 1) {
                return false;
            }
            $sql_fetch_two = mysqli_fetch_assoc($sql_query_two);
            $story = $sql_fetch_two;
            if (!empty($story['page_id'])) {
                $story['publisher'] = Wo_PageData($story['page_id']);
            } else {
                $story['publisher'] = Wo_UserData($story['user_id']);
            }
        } else {
            return false;
        }
    }
    $story['limit_comments'] = 3;
    $story['limited_comments'] = false;
    if ($limited == 'not_limited') {
        $story['limit_comments'] = 10000;
        $story['limited_comments'] = false;
    }
    if (!empty($limited) && is_numeric($limited) && $limited > 0) {
        $story['limit_comments'] = Wo_Secure($limited);
        $story['limited_comments'] = false;
    }
    $story['is_group_post'] = false;
    $story['group_recipient_exists'] = false;
    $story['group_admin'] = false;
    if ($placement != 'admin') {
        if (!empty($story['group_id'])) {
            if ($wo['config']['groups'] == 0) {
                return false;
            }
            $story['group_recipient_exists'] = true;
            $story['group_recipient'] = Wo_GroupData($story['group_id']);
            if ($story['group_recipient']['privacy'] == 2) {
                if ($wo['loggedin'] == true) {
                    if ($story['publisher']['user_id'] != $wo['user']['user_id']) {
                        if (Wo_IsGroupOnwer($story['group_id']) === false) {
                            if (Wo_IsGroupJoined($story['group_id']) === false && (!Wo_IsAdmin() || Wo_IsModerator())) {
                                return false;
                            }
                        }
                    }
                } else {
                    return false;
                }
            }
            if (Wo_IsGroupOnwer($story['group_id']) === false) {
                $story['is_group_post'] = true;
            } else {
                $story['group_admin'] = true;
            }
        }
        if ($story['postPrivacy'] == 1) {
            if ($wo['loggedin'] == true) {
                if (!empty($story['publisher']['page_id'])) {
                } else {
                    if ($story['publisher']['user_id'] != $wo['user']['user_id']) {
                        if (Wo_IsFollowing($wo['user']['user_id'], $story['publisher']['user_id']) === false) {
                            return false;
                        }
                    }
                }
            } else {
                return false;
            }
        }
        if ($story['postPrivacy'] == 2) {
            if ($wo['loggedin'] == true) {
                if (!empty($story['publisher']['page_id'])) {
                    if ($story['publisher']['user_id'] != $wo['user']['user_id']) {
                        if (Wo_IsPageLiked($story['publisher']['page_id'], $wo['user']['user_id']) === false) {
                            return false;
                        }
                    }
                } else {
                    if ($story['publisher']['user_id'] != $wo['user']['user_id']) {
                        if (Wo_IsFollowing($story['publisher']['user_id'], $wo['user']['user_id']) === false && empty($story['group_id'])) {
                            return false;
                        }
                    }
                }
            } else {
                return false;
            }
        }
        if ($story['postPrivacy'] == 3) {
            if ($wo['loggedin'] == true) {
                if (!empty($story['publisher']['page_id'])) {
                } else {
                    if ($wo['user']['user_id'] != $story['publisher']['user_id']) {
                        return false;
                    }
                }
            } else {
                return false;
            }
        }
    }

    $story['mentions_users'] = [];
    $mention_regex = '/@\[([0-9]+)\]/i';
    if (preg_match_all($mention_regex, $story['postText'], $matches)) {
        foreach ($matches[1] as $match) {
            $match = Wo_Secure($match);
            $match_user = Wo_UserData($match);
            $match_search = '@[' . $match . ']';
            if (isset($match_user['user_id'])) {
                $story['mentions_users'][$match_user['username']] = $match_user['name'];
            }
        }
    }

    $story['post_is_promoted'] = 0;
    $story['postText_API'] = Wo_MarkupAPI($story['postText'], true, true, true, $story['post_id']);
    $story['postText_API'] = Wo_Emo($story['postText_API']);
    $story['Orginaltext'] = Wo_EditMarkup($story['postText'], true, true, true, $story['post_id']);
    if (!empty($story['Orginaltext'])) {
        $story['Orginaltext'] = str_replace('<br>', "\n", $story['Orginaltext']);
    }
    $story['postText'] = Wo_Emo($story['postText']);
    $story['postText'] = Wo_Markup($story['postText'], true, true, true, $story['post_id']);
    $story['post_time'] = Wo_Time_Elapsed_String($story['time']);
    $story['page'] = 0;
    if (!empty($story['postFeeling'])) {
        $story['postFeelingIcon'] = $wo['feelingIcons'][$story['postFeeling']];
    }
    if ($wo['config']['useSeoFrindly'] == 1) {
        $story['url'] = Wo_SeoLink('index.php?link1=post&id=' . $story['id']) . '_' . Wo_SlugPost($story['Orginaltext']);
        $story['seo_id'] = $story['id'] . '_' . Wo_SlugPost($story['Orginaltext']);
    } else {
        $story['url'] = Wo_SeoLink('index.php?link1=post&id=' . $story['id']);
        $story['seo_id'] = $story['id'];
    }
    $story['via_type'] = '';
    if ($story['id'] != $fetched_data['id'] && $story['user_id'] != $fetched_data['user_id']) {
        $story['via_type'] = 'share';
        $story['via'] = $fetched_data['publisher'];
    }
    $story['recipient_exists'] = false;
    $story['recipient'] = '';
    if ($story['recipient_id'] > 0) {
        $story['recipient_exists'] = true;
        $story['recipient'] = Wo_UserData($story['recipient_id']);
    }
    $story['admin'] = false;
    if ($wo['loggedin'] == true) {
        if (!empty($story['page_id'])) {
            if (Wo_IsPageOnwer($story['page_id'])) {
                $story['admin'] = true;
            }
        } else {
            if (!empty($story['job_id'])) {
                $is_job_owner = $db->where('id', $story['job_id'])->where('user_id', $wo['user']['user_id'])->getValue(T_JOB, 'COUNT(*)');
                if ($is_job_owner > 0) {
                    $story['admin'] = true;
                }
            } else {
                if (!empty($story['publisher']) && !empty($wo['user']) && $story['publisher']['user_id'] == $wo['user']['user_id']) {
                    $story['admin'] = true;
                }
            }
        }
        if ($story['recipient_exists'] == true) {
            if ($story['recipient']['user_id'] == $wo['user']['user_id']) {
                $story['admin'] = true;
            }
        }
    }
    if (!empty($story['page_id'])) {
        if ($wo['config']['pages'] == 0) {
            return false;
        }
    }
    $story['post_share'] = 0;
    $story['is_post_saved'] = false;
    $story['is_post_reported'] = false;
    $story['is_post_boosted'] = 0;
    $story['is_liked'] = false;
    $story['is_wondered'] = false;
    $story['post_comments'] = 0;
    $story['post_shares'] = 0;
    $story['post_likes'] = 0;
    $story['post_wonders'] = 0;
    $story['postLinkImage'] = Wo_GetMedia($story['postLinkImage']);
    $story['is_post_pinned'] = (Wo_IsPostPinned($story['id']) === true) ? true : false;
    if (!empty($comments_limit) && $comments_limit > 0) {
        $story['get_post_comments'] = Wo_GetPostCommentsLimited($story['id'], $comments_limit);
    } else {
        $story['get_post_comments'] = ($story['comments_status'] == 1) ? Wo_GetPostComments($story['id'], $story['limit_comments']) : array();
    }
    $story['photo_album'] = array();
    if (!empty($story['album_name'])) {
        $parent_id = ($story['parent_id'] > 0) ? $story['parent_id'] : $story['id'];
        $story['photo_album'] = Wo_GetAlbumPhotos($parent_id);
    }
    if ($story['boosted'] == 1) {
        $story['is_post_boosted'] = 1;
    }
    if ($story['multi_image'] == 1) {
        $parent_id = ($story['parent_id'] > 0) ? $story['parent_id'] : $story['id'];
        $story['photo_multi'] = Wo_GetAlbumPhotos($parent_id);
    }
    if ($story['product_id'] > 0) {
        $story['product'] = Wo_GetProduct($story['product_id']);
    }
    if ($story['page_event_id'] > 0) {
        $story['event'] = Wo_EventData($story['page_event_id']);
    }
    if ($story['event_id'] > 0) {
        $story['event'] = Wo_EventData($story['event_id']);
    }
    $story['options'] = array();
    $story['voted_id'] = 0;
    if ($story['poll_id'] == 1) {
        $options = Ju_GetPercentageOfOptionPost($story['id']);
        if (!empty($options)) {
            $story['options'] = $options;
        }
        if ($wo['loggedin']) {
            $option = $db->where('post_id', $post_id)->where('user_id', $wo['user']['id'])->getOne(T_VOTES, 'option_id');
            if (!empty($option)) {
                $story['voted_id'] = $option->option_id;
            }
        }
    }
    if ($wo['loggedin'] == true) {
        $story['post_share'] = Wo_CountPostShare($story['id']);
        $story['post_comments'] = Wo_CountPostComment($story['id']);
        $story['post_shares'] = Wo_CountShares($story['id']);
        $story['post_likes'] = Wo_CountLikes($story['id']);
        $story['post_wonders'] = Wo_CountWonders($story['id']);
        $story['is_liked'] = (Wo_IsLiked($story['id'], $wo['user']['user_id']) === true) ? true : false;
        $story['is_wondered'] = (Wo_IsWondered($story['id'], $wo['user']['user_id']) === true) ? true : false;
        $story['is_post_saved'] = (Wo_IsPostSaved($story['id'], $wo['user']['user_id']) === true) ? true : false;
        $story['is_post_reported'] = (Wo_IsPostRepotred($story['id'], $wo['user']['user_id']) === true) ? true : false;
        if (Wo_IsBlocked($story['user_id']) || Wo_IsBlocked($story['recipient_id'])) {
            if (empty($story['group_id'])) {
                return false;
            }
        }
    }
    $story['postFile_full'] = '';
    $story['shared_from'] = ($story['shared_from'] > 0) ? Wo_UserData($story['shared_from']) : false;
    if (!empty($story['postFile'])) {
        $story['postFile_full'] = Wo_GetMedia($story['postFile']);
    }
    if (!empty($story['postPhoto'])) {
        $story['postPhoto'] = Wo_GetMedia($story['postPhoto']);
    }
    if (!empty($story['blog_id'])) {
        $story['blog'] = Wo_GetArticle($story['blog_id']);
    }
    if ($wo['config']['second_post_button'] == 'reaction') {
        $story['reaction'] = Wo_GetPostReactionsTypes($story['id']);
    }
    $story['job'] = array();
    if (!empty($story['job_id'])) {
        $story['job'] = Wo_GetJobById($story['job_id']);
    }
    $story['offer'] = array();
    if (!empty($story['offer_id'])) {
        $story['offer'] = Wo_GetOfferById($story['offer_id']);
    }
    $story['fund'] = array();
    if (!empty($story['fund_raise_id'])) {
        $story['fund'] = GetFundByRaiseId($story['fund_raise_id'], $story['user_id']);
        unset($story['fund']['user_data']);
    }
    $story['fund_data'] = array();
    if (!empty($story['fund_id'])) {
        $story['fund_data'] = GetFundingById($story['fund_id']);
        unset($story['fund_data']['user_data']);
    }
    $story['forum'] = array();
    if (!empty($story['forum_id'])) {
        $forum = Wo_GetForumInfo($story['forum_id']);
        if (!empty($forum) && !empty($forum['forum'])) {
            if (strlen($forum['forum']['description']) > 200) {
                $forum['forum']['description'] = substr($forum['forum']['description'], 0, 200) . '...';
            }
            $story['forum'] = $forum['forum'];
        }
    }
    $story['thread'] = array();
    if (!empty($story['thread_id'])) {
        $thread = Wo_GetForumThreads(array(
            "id" => $story['thread_id'],
            "preview" => true
        ));
        if (!empty($thread) && !empty($thread[0])) {
            if (strlen($thread[0]['orginal_headline']) > 200) {
                $thread[0]['orginal_headline'] = substr($thread[0]['orginal_headline'], 0, 200) . '...';
            }
            $story['thread'] = $thread[0];
        }
    }
    $story['is_still_live'] = false;
    $story['live_sub_users'] = 0;
    if (!empty($story['stream_name']) && !empty($story['live_time']) && $story['live_ended'] == 0) {
        $story['is_still_live'] = true;
        $story['live_sub_users'] = $db->where('post_id', $story['id'])->where('time', time() - 6, '>=')->getValue(T_LIVE_SUB, 'COUNT(*)');
    }

    $story['have_next_image'] = true;
    $story['have_pre_image'] = true;


    $after_post_id = Wo_Secure($story['id']);

    $row = mysqli_query($sqlConnect, "SELECT * FROM " . T_ALBUMS_MEDIA . " WHERE `post_id` = '{$after_post_id}' && `parent_id` != '0'");
    if (mysqli_num_rows($row)) {
        $fetched_data = mysqli_fetch_assoc($row);
        $query_check_hash = mysqli_query($sqlConnect, "SELECT COUNT(id) as count FROM " . T_ALBUMS_MEDIA . " WHERE `post_id` < '" . $fetched_data['post_id'] . "' AND `parent_id` = '" . $fetched_data['parent_id'] . "'");
        if (mysqli_num_rows($query_check_hash)) {
            $query_get_hash = mysqli_fetch_assoc($query_check_hash);
            if ($query_get_hash['count'] == 0) {
                $story['have_next_image'] = false;
            }
        }
        $query_check_hash = mysqli_query($sqlConnect, "SELECT COUNT(id) as count FROM " . T_ALBUMS_MEDIA . " WHERE `post_id` > '" . $fetched_data['post_id'] . "' AND `parent_id` = '" . $fetched_data['parent_id'] . "'");
        if (mysqli_num_rows($query_check_hash)) {
            $query_get_hash = mysqli_fetch_assoc($query_check_hash);
            if ($query_get_hash['count'] == 0) {
                $story['have_pre_image'] = false;
            }
        }
    }
    $story['is_monetized_post'] = false;
    $story['can_not_see_monetized'] = 0;
    if($story['postPrivacy'] == "6") {
        $story['is_monetized_post'] = true;
        $can_see = false;
        if(Wo_IsSubscriptionPaidForPublisher($story['publisher']['user_id'])) {
            $can_see = true;
        }

        if(isset($wo['user']) && $story['publisher']['user_id'] == $wo['user']['user_id']) {
            $can_see = true;
        }

        if(!$can_see) {
            $story['postYoutube'] = '';
            $story['postPlaytube'] = '';
            $story['postVimeo'] = '';
            $story['postFacebook'] = '';
            $story['postDailymotion'] = '';
            $story['postSticker'] = '';
            $story['postDeepsound'] = '';
            $story['postSticker'] = '';
            $story['multi_image'] = 0;
            $story['multi_image_post'] = 0;
            $story['product_id'] = 0;
            $story['poll_id'] = 0;
            $story['blog_id'] = 0;
            $story['forum_id'] = 0;
            $story['thread_id'] = 0;
            $story['postRecord'] = '';
            $story['job_id'] = 0;
            $story['offer_id'] = 0;
            $story['fund_raise_id'] = 0;
            $story['fund_id'] = 0;
            $story['stream_name'] = '';
            $story['photo_album'] = '';

            $new_target = $story['blur_url'];

            $subscribe_link = $wo['config']['site_url'] . "/monetization/" . $story['publisher']['name'];
            if(!isset($wo['user'])) {
                $subscribe_link = $wo['config']['site_url'] . "/welcome";
            }
            if (!empty($story['postFile'])) {
                $story['postFile'] = $new_target;
            }
            
            if (empty($story['postFile'])) {
                $story['postText'] = '<span class="wo_monetize_content"><span class="wo_monetize_content_innr"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 495.787 495.787" xml:space="preserve" fill="currentColor"> <g> <g> <path d="M247.893,0C110.986,0,0,110.986,0,247.893s110.986,247.893,247.893,247.893s247.893-110.986,247.893-247.893 C495.669,111.034,384.752,0.118,247.893,0z M247.893,474.453L247.893,474.453c-125.126,0-226.56-101.434-226.56-226.56 s101.434-226.56,226.56-226.56c125.126,0,226.56,101.434,226.56,226.56C474.336,372.97,372.97,474.336,247.893,474.453z"></path> </g> </g> <g> <g> <path d="M320.853,212.48v-26.667c-2.181-40.354-36.663-71.298-77.016-69.117c-37.305,2.016-67.101,31.812-69.117,69.117v26.453 c-13.33,6.609-21.642,20.325-21.333,35.2v96.427c-0.237,21.206,16.762,38.59,37.969,38.827c0.286,0.003,0.572,0.003,0.858,0 h113.28c21.395-0.117,38.71-17.432,38.827-38.827v-96C344.222,232.477,335.013,218.579,320.853,212.48z M196.053,185.813 c0.117-28.547,23.293-51.627,51.84-51.627c28.547,0,51.723,23.08,51.84,51.627v22.827h-103.68V185.813z M304.853,361.387H191.36 c-9.661,0-17.493-7.832-17.493-17.493v-96c-0.239-9.423,7.206-17.255,16.629-17.493c0.288-0.007,0.576-0.007,0.864,0h113.28 c9.661,0,17.493,7.832,17.493,17.493l0.213,96C322.347,353.555,314.515,361.387,304.853,361.387z"></path> </g> </g> <g> <g> <path d="M247.893,264.32c-9.532,0.112-17.264,7.75-17.493,17.28c0.099,5.289,2.614,10.241,6.827,13.44v21.333 c0,5.891,4.776,10.667,10.667,10.667s10.667-4.776,10.667-10.667V295.04c4.378-3.178,6.99-8.244,7.04-13.653 C265.367,271.809,257.473,264.2,247.893,264.32z"></path> </g> </g> </svg>'.$wo['lang']['post_is_monetized'];
                //if($new_target) {
                    $story['postText'] .= '<br><a class="btn btn-main btn-mat" href="'.Wo_SeoLink('index.php?link1=monetization&user='.$story['publisher']['username']).'"  data-ajax="?link1=monetization&user='.$story['publisher']['username'].'"><svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24"><path fill="currentColor" d="M880-720v480q0 33-23.5 56.5T800-160H160q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h640q33 0 56.5 23.5T880-720Zm-720 80h640v-80H160v80Zm0 160v240h640v-240H160Zm0 240v-480 480Z"></path></svg> '.$wo['lang']['subscribe'].'</a>';
                //}
                $story['postText'] .= '</span>';
                $story['postText'] .= '</span>';
            }
                
            $story['postFile_full'] = $new_target;
            $story['can_not_see_monetized'] = 1;
        }
        }

    return $story;
}

function Wo_IsSubscriptionPaidForPublisher($publisher_id) {
    global $wo, $sqlConnect, $db;
    if ($wo['loggedin'] == false) {
        return false;
    }

    $user_subscriptions = $db->where('user_id',$wo['user']['user_id'])
        ->where('status',1)
        ->get(T_MONETIZATION_SUBSCRIBTION);

    foreach ($user_subscriptions as $user_subscription) {
        $monetization = $db->where('id',$user_subscription->monetization_id)
            ->where('status',1)
            ->where('user_id', $publisher_id)
            ->getOne(T_USER_MONETIZATION);

        if($monetization) {
            $lastPaymentTimestamp = strtotime($user_subscription->last_payment_date);
            $nextPaymentTimestamp = $lastPaymentTimestamp + ($monetization->paid_every * 24 * 60 * 60);
            if(time() <= $nextPaymentTimestamp) {
                return true;
            }
        }
}
}

function Wo_ShouldSubscriptionBePaid($monetization_id, $last_payment_date) {
    global $db;
        $shouldBePaid = false;
        $monetization = $db->where('id',$monetization_id)
            ->where('status',1)
            ->getOne(T_USER_MONETIZATION);

        if($monetization) {
            $lastPaymentTimestamp = strtotime($last_payment_date);
            $nextPaymentTimestamp = $lastPaymentTimestamp + ($monetization->paid_every * $monetization->multiplier * 24 * 60 * 60);
            if(time() > $nextPaymentTimestamp) {
                $shouldBePaid = true;
        }
    }
        return $shouldBePaid;
}


function Wo_CountPostShare($post_id)
{
    global $wo, $sqlConnect;
    $data = array();
    if (empty($post_id) or !is_numeric($post_id) or $post_id < 1) {
        return false;
    }
    $sql = "SELECT COUNT(`id`) AS `shares` FROM " . T_POSTS . " WHERE `parent_id` = " . $post_id;
    $query = mysqli_query($sqlConnect, $sql);
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        return $fetched_data['shares'];
    }
    return false;
}

function Wo_CountUserPosts($user_id)
{
    global $wo, $sqlConnect;
    $data = array();
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $user_id = Wo_Secure($user_id);
    $query = mysqli_query($sqlConnect, "SELECT COUNT(`id`) AS count FROM " . T_POSTS . " WHERE `user_id` = {$user_id}");
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        return $fetched_data['count'];
    }
    return false;
}

function Wo_PostExists($post_id)
{
    global $sqlConnect;
    if (empty($post_id) || !is_numeric($post_id) || $post_id < 0) {
        return false;
    }
    $post_id = Wo_Secure($post_id);
    $query = mysqli_query($sqlConnect, "SELECT COUNT(`id`) FROM " . T_POSTS . " WHERE `id` = {$post_id}");
    return (Wo_Sql_Result($query, 0) == 1) ? true : false;
}

function Wo_IsPostOnwer($post_id, $user_id)
{
    global $sqlConnect;
    if (empty($post_id) || !is_numeric($post_id) || $post_id < 0) {
        return false;
    }
    $post_id = Wo_Secure($post_id);
    $user_id = Wo_Secure($user_id);
    $query = mysqli_query($sqlConnect, "SELECT COUNT(`id`) FROM " . T_POSTS . " WHERE `id` = {$post_id} AND (`user_id` = {$user_id} OR `page_id` IN (SELECT `page_id` FROM " . T_PAGES . " WHERE `user_id` = {$user_id}) OR `page_id` IN (SELECT `page_id` FROM " . T_PAGE_ADMINS . " WHERE `user_id` = {$user_id}))");
    return (Wo_Sql_Result($query, 0) == 1) ? true : false;
}

function Wo_GetPostPublisherBox($user_id = 0, $recipient_id = 0)
{
    global $wo;
    if ($wo['loggedin'] == false) {
        return false;
    }
    $continue = true;
    if (empty($user_id) or $user_id == 0) {
        $user_id = $wo['user']['user_id'];
    }
    if (!is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    if ($user_id == $wo['user']['user_id']) {
        $user_timline = $wo['user'];
    } else {
        $user_id = Wo_Secure($user_id);
        $user_timline = Wo_UserData($user_id);
    }
    if (!isset($recipient_id) or empty($recipient_id)) {
        $recipient_id = 0;
    }
    if (!is_numeric($recipient_id) or $recipient_id < 0) {
        return false;
    }
    $recipient_id = Wo_Secure($recipient_id);
    if ($user_id == $recipient_id) {
        $recipient_id = 0;
    }
    if ($recipient_id > 0) {
        $recipient = Wo_UserData($recipient_id);
        if (!isset($recipient['user_id'])) {
            return false;
        }
        if ($recipient['post_privacy'] == "ifollow") {
            if (Wo_IsFollowing($wo['user']['user_id'], $recipient_id) === false) {
                $continue = false;
            }
        } elseif ($recipient['post_privacy'] == "nobody") {
            $continue = false;
        } elseif ($recipient['post_privacy'] == "everyone") {
            $continue = true;
        }
        $wo['input']['recipient'] = $recipient;
    }
    if ($continue == true) {
        $wo['input']['user_timline'] = $user_timline;
        return Wo_LoadPage('story/publisher-box');
    }
}

function Wo_GetPosts($data = array('filter_by' => 'all', 'after_post_id' => 0, 'page_id' => 0, 'group_id' => 0, 'publisher_id' => 0, 'limit' => 5, 'event_id' => 0, 'ad-id' => 0, 'is_reel' => 'only', 'not_in' => array(), 'not_monetization' => false))
{
    global $wo, $sqlConnect;
    if (empty($data['filter_by'])) {
        $data['filter_by'] = 'all';
    }
    $subquery_one = " `id` > 0 ";
    if (!empty($data['after_post_id']) && is_numeric($data['after_post_id']) && $data['after_post_id'] > 0) {
        $data['after_post_id'] = Wo_Secure($data['after_post_id']);
        $subquery_one = " `id` < " . $data['after_post_id'] . " AND `id` <> " . $data['after_post_id'];
    } else if (!empty($data['before_post_id']) && is_numeric($data['before_post_id']) && $data['before_post_id'] > 0) {
        $data['before_post_id'] = Wo_Secure($data['before_post_id']);
        $subquery_one = " `id` > " . $data['before_post_id'] . " AND `id` <> " . $data['before_post_id'];
    }
    if (!empty($data['publisher_id']) && is_numeric($data['publisher_id']) && $data['publisher_id'] > 0) {
        $data['publisher_id'] = Wo_Secure($data['publisher_id']);
        $Wo_publisher = Wo_UserData($data['publisher_id']);
    }
    if (!empty($data['page_id']) && is_numeric($data['page_id']) && $data['page_id'] > 0) {
        $data['page_id'] = Wo_Secure($data['page_id']);
        $Wo_page_publisher = Wo_PageData($data['page_id']);
    }
    if (!empty($data['group_id']) && is_numeric($data['group_id']) && $data['group_id'] > 0) {
        $data['group_id'] = Wo_Secure($data['group_id']);
        $Wo_group_publisher = Wo_GroupData($data['group_id']);
    }
    if (!empty($data['event_id']) && is_numeric($data['event_id']) && $data['event_id'] > 0) {
        $data['event_id'] = Wo_Secure($data['event_id']);
        $Wo_event_publisher = Wo_EventData($data['event_id']);
    }
    $multi_image_post = '';
    if (!empty($data['placement']) && $data['placement'] == 'multi_image_post') {
        $multi_image_post = ' AND `multi_image_post` = 0 ';
    }
    $query_text = "SELECT `id` FROM " . T_POSTS . " WHERE {$subquery_one} AND `postType` <> 'profile_picture_deleted' {$multi_image_post}";
    if (isset($Wo_publisher['user_id'])) {
        $user_id = Wo_Secure($Wo_publisher['user_id']);
        $query_text .= " AND (`user_id` = {$user_id} OR `recipient_id` = {$user_id}) AND postShare IN (0,1) AND `id` NOT IN (SELECT `post_id` from " . T_PINNED_POSTS . " WHERE `user_id` = {$user_id})  AND `page_id` NOT IN (SELECT `page_id` from " . T_PAGES . " WHERE user_id = {$user_id}) AND `group_id` = 0 AND `event_id` = 0";
        switch ($data['filter_by']) {
            case 'text':
                $query_text .= " AND `postText` <> '' AND `postFile` = '' AND `postYoutube` = '' AND `postFacebook` = ''  AND `postVimeo` = ''  AND `postDailymotion` = '' AND `postSoundCloud` = '' ";
                break;
            case 'files':
                $query_text .= " AND (`postFile` LIKE '%_file%' AND `postFile` NOT LIKE '%_video%' AND `postFile` NOT LIKE '%_avatar%' AND `postFile` NOT LIKE '%_soundFile%' AND `postFile` NOT LIKE '%_image%')";
                break;
            case 'photos':
                $query_text .= " AND (`postFile` LIKE '%_image%' OR `postFile` LIKE '%_avatar%' OR `postFile` LIKE '%_cover%' OR multi_image = '1' OR album_name <> '') ";
                break;
            case 'music':
                $query_text .= " AND (`postSoundCloud` <> '' OR `postFile` LIKE '%_soundFile%')";
                break;
            case 'video':
                $query_text .= " AND (`postYoutube` <> '' OR `postVine` <> '' OR `postFacebook` <> '' OR `postDailymotion` <> '' OR `postVimeo` <> '' OR `postPlaytube` <> '' OR `postFile` LIKE '%_video%')";
                break;
            case 'maps':
                $query_text .= " AND `postMap` <> ''";
                break;
        }
        if (!$wo['loggedin'] || $Wo_publisher['user_id'] != $wo['user']['id']) {
            $query_text .= " AND `postPrivacy` <> '3'";
        }
        $query_text .= " AND `postPrivacy` <> '4' ";
        if ($wo['loggedin'] && $wo['config']['website_mode'] == 'linkedin') {
            $logged_user_id = Wo_Secure($wo['user']['user_id']);
            $query_text .= " AND (`postPrivacy` <> '5' OR (`postPrivacy` = '5' AND `user_id` = '{$logged_user_id}') OR (`postPrivacy` = '5' AND `user_id` IN (SELECT `user_id` FROM " . T_JOB . ")))";
        }
    } else if (isset($Wo_page_publisher['page_id'])) {
        $page_id = Wo_Secure($Wo_page_publisher['page_id']);
        $query_text .= " AND (`page_id` = {$page_id}) AND `id` NOT IN (SELECT `post_id` from " . T_PINNED_POSTS . " WHERE `page_id` = {$page_id})";
        // if ($wo['config']['job_system'] == 1 && $data['filter_by'] != 'job') {
        //     $query_text .= " AND `job_id` = '0' ";
        // }
        switch ($data['filter_by']) {
            case 'text':
                $query_text .= " AND `postText` <> '' AND `postFile` = '' AND `postYoutube` = '' AND `postFacebook` = ''  AND `postVimeo` = ''  AND `postDailymotion` = '' AND `postSoundCloud` = '' ";
                break;
            case 'files':
                $query_text .= " AND (`postFile` LIKE '%_file%' AND `postFile` NOT LIKE '%_video%' AND `postFile` NOT LIKE '%_avatar%' AND `postFile` NOT LIKE '%_soundFile%' AND `postFile` NOT LIKE '%_image%')";
                break;
            case 'photos':
                $query_text .= " AND (`postFile` LIKE '%_image%' OR `postFile` LIKE '%_avatar%' OR multi_image = '1' OR album_name <> '')";
                break;
            case 'music':
                $query_text .= " AND (`postSoundCloud` <> '' OR `postFile` LIKE '%_soundFile%')";
                break;
            case 'video':
                $query_text .= " AND (`postYoutube` <> '' OR `postVine` <> '' OR `postFacebook` <> '' OR `postDailymotion` <> '' OR `postVimeo` <> '' OR `postPlaytube` <> '' OR `postFile` LIKE '%_video%')";
                break;
            case 'maps':
                $query_text .= " AND `postMap` <> ''";
                break;
            case 'job':
                if ($wo['config']['job_system'] == 1) {
                    $query_text .= " AND `job_id` > '0'";
                }
                break;
            case 'offer':
                if ($wo['config']['offer_system'] == 1) {
                    $query_text .= " AND `offer_id` > '0'";
                }
                break;
        }
        if (!$wo['loggedin'] || $Wo_page_publisher['user_id'] != $wo['user']['id']) {
            $query_text .= " AND `postPrivacy` <> '3'";
        }
    } else if (isset($Wo_group_publisher['id'])) {
        $group_id = Wo_Secure($Wo_group_publisher['id']);
        $query_text .= " AND (`group_id` = {$group_id}) AND `id` NOT IN (SELECT `post_id` from " . T_PINNED_POSTS . " WHERE `group_id` = {$group_id})";
        switch ($data['filter_by']) {
            case 'text':
                $query_text .= " AND `postText` <> '' AND `postFile` = '' AND `postYoutube` = '' AND `postFacebook` = ''  AND `postVimeo` = ''  AND `postDailymotion` = '' AND `postSoundCloud` = '' ";
                break;
            case 'files':
                $query_text .= " AND (`postFile` LIKE '%_file%' AND `postFile` NOT LIKE '%_video%' AND `postFile` NOT LIKE '%_avatar%' AND `postFile` NOT LIKE '%_soundFile%' AND `postFile` NOT LIKE '%_image%')";
                break;
            case 'photos':
                $query_text .= " AND (`postFile` LIKE '%_image%' OR `postFile` LIKE '%_avatar%' OR multi_image = '1' OR album_name <> '')";
                break;
            case 'music':
                $query_text .= " AND (`postSoundCloud` <> '' OR `postFile` LIKE '%_soundFile%')";
                break;
            case 'video':
                $query_text .= " AND (`postYoutube` <> '' OR `postVine` <> '' OR `postFacebook` <> '' OR `postDailymotion` <> '' OR `postVimeo` <> '' OR `postPlaytube` <> '' OR `postFile` LIKE '%_video%')";
                break;
            case 'maps':
                $query_text .= " AND `postMap` <> ''";
                break;
        }
        if (!$wo['loggedin'] || $Wo_group_publisher['user_id'] != $wo['user']['id']) {
            $query_text .= " AND `postPrivacy` <> '3'";
        }
    } else if (isset($Wo_event_publisher['id'])) {
        $event_id = Wo_Secure($Wo_event_publisher['id']);
        $query_text .= " AND (`event_id` = {$event_id}) AND `id` NOT IN (SELECT `post_id` from " . T_PINNED_POSTS . " WHERE `event_id` = {$event_id})";
        switch ($data['filter_by']) {
            case 'text':
                $query_text .= " AND `postText` <> '' AND `postFile` = '' AND `postYoutube` = '' AND `postFacebook` = ''  AND `postVimeo` = ''  AND `postDailymotion` = '' AND `postSoundCloud` = '' ";
                break;
            case 'files':
                $query_text .= " AND (`postFile` LIKE '%_file%' AND `postFile` NOT LIKE '%_video%' AND `postFile` NOT LIKE '%_avatar%' AND `postFile` NOT LIKE '%_soundFile%' AND `postFile` NOT LIKE '%_image%')";
                break;
            case 'photos':
                $query_text .= " AND (`postFile` LIKE '%_image%' OR `postFile` LIKE '%_avatar%' OR multi_image = '1' OR album_name <> '')";
                break;
            case 'music':
                $query_text .= " AND (`postSoundCloud` <> '' OR `postFile` LIKE '%_soundFile%')";
                break;
            case 'video':
                $query_text .= " AND (`postYoutube` <> '' OR `postVine` <> '' OR `postFacebook` <> '' OR `postDailymotion` <> '' OR `postVimeo` <> '' OR `postPlaytube` <> '' OR `postFile` LIKE '%_video%')";
                break;
            case 'maps':
                $query_text .= " AND `postMap` <> ''";
                break;
        }
        if (!$wo['loggedin'] || $Wo_event_publisher['id'] != $wo['user']['id']) {
            $query_text .= " AND `postPrivacy` <> '3'";
        }
    } else {
        $logged_user_id = ($wo['loggedin'] ? Wo_Secure($wo['user']['user_id']) : 0);
        $groups_not_joined = array();
        if ($logged_user_id) {
            $query_groups = "SELECT `group_id` FROM " . T_POSTS . " WHERE (`user_id` IN (SELECT `following_id` FROM " . T_FOLLOWERS . " WHERE `follower_id` = {$logged_user_id} AND `active` = '1') AND `group_id` <> 0 AND `group_id` NOT IN (SELECT `group_id` FROM " . T_GROUP_MEMBERS . " WHERE `user_id` = '{$logged_user_id}' AND `active` = '1'))";
            $query_groups = mysqli_query($sqlConnect, $query_groups);
            if (mysqli_num_rows($query_groups)) {
                while ($fetched_data_groups = mysqli_fetch_assoc($query_groups)) {
                    if (!in_array($fetched_data_groups['group_id'], $groups_not_joined)) {
                        $groups_not_joined[] = $fetched_data_groups['group_id'];
                    }
                }
            }
        }
        $add_filter_query = false;
        if ($wo['config']['order_posts_by'] == 0) {
            if ($wo['loggedin'] && $wo['user']['order_posts_by'] == 1) {
                $add_filter_query = true;
            }
        } else {
            $add_filter_query = true;
        }
        if ($add_filter_query == true) {
            $query_text .= "
            AND (
                  `user_id` IN (SELECT `following_id` FROM " . T_FOLLOWERS . " WHERE `follower_id` = {$logged_user_id} AND `active` = '1')
                  OR `recipient_id` IN (SELECT `following_id` FROM " . T_FOLLOWERS . " WHERE `follower_id` = {$logged_user_id} AND `active` = '1' )
                  OR `user_id` IN ({$logged_user_id}) OR `page_id` IN (SELECT `page_id` FROM " . T_PAGES . " WHERE `user_id` = {$logged_user_id} AND `active` = '1')
                  OR `page_id` IN (SELECT `page_id` FROM " . T_PAGES_LIKES . " WHERE `user_id` = {$logged_user_id} AND `active` = '1')
                  OR `group_id` IN (SELECT `id` FROM " . T_GROUPS . " WHERE `user_id` = {$logged_user_id})
                  OR `event_id` IN (SELECT `event_id` FROM " . T_EVENTS_GOING . " WHERE `user_id` = {$logged_user_id})
                  OR `group_id` IN (SELECT `group_id` FROM " . T_GROUP_MEMBERS . " WHERE `user_id` = {$logged_user_id}
                  )
            )";
        }
        if (isset($data['not_monetization']) && $data['not_monetization']) {
            $query_text .= " AND (`postPrivacy` <> '6') ";
        }
        if ($logged_user_id) {
            $query_text .= " AND (`postPrivacy` <> '3' OR (`user_id` = {$logged_user_id} AND `postPrivacy` >= '0'))";
        } else {
            $query_text .= " AND (`postPrivacy` <> '3' OR ( `postPrivacy` >= '0'))";
        }
        if ($wo['config']['website_mode'] == 'linkedin') {
            $query_text .= " AND (`postPrivacy` <> '5' OR (`postPrivacy` = '5' AND `user_id` = '{$logged_user_id}') OR (`postPrivacy` = '5' AND `user_id` IN (SELECT `user_id` FROM " . T_JOB . ")))";
        }
        $query_text .= " AND `postShare` NOT IN (1)";
        if (!empty($groups_not_joined)) {
            $implode_groups = implode(',', $groups_not_joined);
            $query_text .= " AND `group_id` NOT IN ($implode_groups)";
        }
        switch ($data['filter_by']) {
            case 'text':
                $query_text .= " AND `postText` <> '' AND `postFile` = '' AND `postYoutube` = '' AND `postFacebook` = ''  AND `postVimeo` = ''  AND `postDailymotion` = '' AND `postSoundCloud` = '' ";
                break;
            case 'files':
                $query_text .= " AND (`postFile` LIKE '%_file%' AND `postFile` NOT LIKE '%_video%' AND `postFile` NOT LIKE '%_avatar%' AND `postFile` NOT LIKE '%_soundFile%' AND `postFile` NOT LIKE '%_image%')";
                break;
            case 'photos':
                $query_text .= " AND (`postFile` LIKE '%_image%' OR `postFile` LIKE '%_avatar%' OR multi_image = '1' OR album_name <> '')";
                break;
            case 'music':
                $query_text .= " AND (`postSoundCloud` <> '' OR `postFile` LIKE '%_soundFile%')";
                break;
            case 'video':
                $query_text .= " AND (`postYoutube` <> '' OR `postVine` <> '' OR `postFacebook` <> '' OR `postDailymotion` <> '' OR `postVimeo` <> '' OR `postPlaytube` <> '' OR `postFile` LIKE '%_video%')";
                break;
            case 'local_video':
                $query_text .= " AND (`postFile` LIKE '%_video%')";
                break;
            case 'maps':
                $query_text .= " AND `postMap` <> ''";
                break;
        }
    }

    if(!isset($data['is_reel']) || $data['is_reel'] == 'disable') {
        $query_text .= " AND `is_reel` = 0 ";
    }

    if(isset($data['is_reel']) && $data['is_reel'] == 'only') {
        $query_text .= " AND `is_reel` = 1 ";
    }
    if (!empty($data['not_in']) && is_array($data['not_in'])) {
        $not_in = implode(',', $data['not_in']);
        $query_text .= " AND `id` NOT IN (" . $not_in . ") ";
    }

    if (empty($data['anonymous']) || $data['anonymous'] != true) {
        $query_text .= " AND `postPrivacy` <> '4' ";
    }
    if ($data['filter_by'] != 'job' && empty($Wo_page_publisher['page_id'])) {
        if ($wo['config']['website_mode'] != 'linkedin') {
            $query_text .= " AND `job_id` = '0' ";
        }
    }
    $user = ($wo['loggedin']) ? $wo['user']['id'] : 0;
        if ((!isset($data['publisher_id']) || $data['publisher_id'] == $user) && empty($Wo_page_publisher['page_id']) && empty($Wo_group_publisher['id'])) {
        if ($user !== 0) {
            $query_text .= " AND `shared_from` <>  {$user}";
        }
    }
    $query_text .= " AND `id` NOT IN (SELECT `post_id` FROM " . T_HIDDEN_POSTS . " WHERE `user_id` = {$user})";
    if ($wo['config']['job_system'] != 1) {
        $query_text .= " AND `job_id` = '0' ";
    }
    if ($wo['config']['post_approval'] == 1) {
        $query_text .= " AND `active` = '1' ";
    } else {
        if ($wo['config']['blog_approval'] == 1) {
            $query_text .= " AND `active` = '1' ";
        }
    }
    if (!$wo['loggedin']) {
        $query_text .= " AND `postPrivacy` = '0'";
    }
    if (empty($data['limit']) or !is_numeric($data['limit']) or $data['limit'] < 1) {
        $data['limit'] = 5;
    }
    $limit = Wo_Secure($data['limit']);
    $last_ad = 0;
    if (!empty($data['ad-id'])) {
        $last_ad = $data['ad-id'];
    }
    if (isset($data['order']) && $data['order'] != 'rand') {
        $query_text .= " ORDER BY `id` " . Wo_Secure($data['order']) . " LIMIT {$limit}";
    } elseif (isset($data['order']) && $data['order'] == 'rand') {
        $query_text .= " ORDER BY RAND() LIMIT {$limit}";
    } else {
        $query_text .= " ORDER BY `id` DESC LIMIT {$limit}";
    }
    $filter = $data['filter_by'];
    if ($data['filter_by'] == 'most_liked') {
        $commentscount = " (SELECT Count(*) FROM " . T_COMMENTS . " WHERE post_id = p.id) ";
        $likes_count = '';
        if ($wo['config']['second_post_button'] !== 'reaction') {
            $likes_count = " ( SELECT COUNT(*) FROM " . T_LIKES . " WHERE post_id = p.id ) ";
        } else {
            $likes_count = " ( SELECT COUNT(*) FROM " . T_REACTIONS . " WHERE post_id = p.id ) ";
        }
        $hour = time() - (60 * 60 * 72);
        $sq = '';
        if ((isset($data['after_post_id']) && $data['after_post_id'] > 0) && $data['lasttotal'] > 0 && $data['dt'] > 0) {
            $id = Wo_Secure($data['after_post_id']);
            $total = Wo_Secure($data['lasttotal']);
            $sq = " p.id <> " . $id . " AND p.time >= " . $hour . "
                    AND (
                        ( $commentscount + $likes_count ) < $total
                        AND
                        ( $commentscount + $likes_count ) > 0
                    ) ";
        } else {
            $sq = "p.id > 0 AND p.time >= " . $hour;
        }
        $query_text = "SELECT p.id AS `id`,
                            $commentscount AS comments_count,
                            $likes_count AS likes_count,
                            ( $commentscount + $likes_count ) AS Total,
                            p.time AS `time`
                    FROM   " . T_POSTS . " p
                    WHERE
                            $sq
                    ORDER  BY total DESC
                    LIMIT {$limit}";
    }
    $data = array();
    // print_r($query_text);
    // exit();
    $sql = mysqli_query($sqlConnect, $query_text);
    $ids = array();
    if (mysqli_num_rows($sql)) {
        while ($fetched_data = mysqli_fetch_assoc($sql)) {
            if ($filter !== 'most_liked') {
                $post = Wo_PostData($fetched_data['id']);

                if (is_array($post)) {
                    if ($filter == 'local_video') {
                        if (ifVideoPost($post['postFile'])) {
                            $data[] = $post;
                        }
                    }
                    else{
                        $data[] = $post;
                    }
                }
            } else {
                if ($fetched_data['comments_count'] > 0 || $fetched_data['likes_count'] > 0) {
                    $post = Wo_PostData($fetched_data['id']);

                    if (is_array($post)) {
                        $post["LastTotal"] = $fetched_data['Total'];
                        $ids[] = $fetched_data['id'];
                        $post["dt"] = $fetched_data['time'];
                        $data[] = $post;
                    }
                }
            }
        }
    }
    if ($filter !== 'most_liked' && $filter !== 'job' && $filter !== 'local_video') {
        if (is_numeric($last_ad) && count($data) > 1) {
            $ad = Wo_GetPostAds(Wo_Secure($last_ad));
            if (is_array($ad) && !empty($ad)) {
                if ($ad['bidding'] == 'views') {
                    Wo_RegisterAdConversionView($ad['id']);
                }
                $data[] = $ad;
            }
        }
    }
    return $data;
}

function ifVideoPost($postFile='')
{
    if (empty($postFile)) {
        return false;
    }

    $file_extension = pathinfo($postFile, PATHINFO_EXTENSION);
    $file_extension = strtolower($file_extension);

    if ($file_extension == 'mp4' || $file_extension == 'mkv' || $file_extension == 'avi' || $file_extension == 'webm' || $file_extension == 'mov' || $file_extension == 'm3u8') {
        return true;
    }
    return false;
}

function Wo_DeletePost($post_id = 0, $type = '')
{
    global $wo, $sqlConnect, $cache;
    if ($post_id < 1 || empty($post_id) || !is_numeric($post_id)) {
        return false;
    }
    if ($wo['loggedin'] == false) {
        return false;
    }
    $user_id = Wo_Secure($wo['user']['user_id']);
    $post_id = Wo_Secure($post_id);
    $query = mysqli_query($sqlConnect, "SELECT `id`, `user_id`, `recipient_id`, `page_id`, `postFile`, `postType`, `postText`, `postLinkImage`, `multi_image`, `album_name`,`parent_id`,`blog_id`,`job_id`,`postRecord`,`240p`,`360p`,`480p`,`720p`,`1080p`,`2048p`,`4096p` FROM " . T_POSTS . " WHERE `id` = {$post_id} AND (`user_id` = {$user_id} OR `recipient_id` = {$user_id} OR `page_id` IN (SELECT `page_id` FROM " . T_PAGES . " WHERE `user_id` = {$user_id}) OR `group_id` IN (SELECT `id` FROM " . T_GROUPS . " WHERE `user_id` = {$user_id}) OR `page_id` IN (SELECT `page_id` FROM " . T_PAGE_ADMINS . " WHERE `user_id` = {$user_id}))");
    $is_me = mysqli_num_rows($query);
    $post_info = mysqli_fetch_assoc($query);
    $row = mysqli_query($sqlConnect, "SELECT * FROM " . T_POSTS . " WHERE `id` = '{$post_id}'");
    if (mysqli_num_rows($row)) {
        $fetched_data = mysqli_fetch_assoc($row);
    }
    if ($is_me > 0 || (Wo_IsAdmin() || Wo_IsModerator()) || $type == 'shared') {
        // $post_image = $db->where('post_id',$post_id)->getOne(T_ALBUMS_MEDIA);
        // if (!empty($post_image)) {
        //     mysqli_query($sqlConnect, "DELETE FROM " . T_ALBUMS_MEDIA . " WHERE `image` LIKE '%$post_image->image%' ");
        //     $explode2 = @end(explode('.', $post_image->image));
        //     $explode3 = @explode('.', $post_image->image);
        //     $media_2  = $explode3[0] . '_small.' . $explode2;
        //     @unlink(trim($media_2));
        //     @unlink($post_image->image);
        //     $delete_from_s3 = Wo_DeleteFromToS3($media_2);
        //     $delete_from_s3 = Wo_DeleteFromToS3($post_image->image);
        // }
        // delete shared posts
        //if (!empty($post_info->parent_id)) {
        mysqli_query($sqlConnect, "DELETE FROM " . T_POSTS . " WHERE `parent_id` = {$post_id}");
        //}
        // delete shared posts
        $is_this_post_shared = Wo_IsThisPostShared($post_id);
        $is_post_shared = Wo_IsPostShared($post_id);
        //$fetched_data = mysqli_fetch_assoc($query);
        /* if ($fetched_data['postType'] == 'profile_picture' || $fetched_data['postType'] == 'profile_picture_deleted' || $fetched_data['postType'] == 'profile_cover_picture') {
            $Query       = mysqli_query($sqlConnect, "SELECT * FROM " . T_USERS . " WHERE `user_id` = '".$fetched_data['user_id']."'");
            if (mysqli_num_rows($Query)) {
                $user_pic = mysqli_fetch_assoc($Query);
            }
            if (!empty($user_pic)) {
                if ($fetched_data['postType'] == 'profile_picture' || $fetched_data['postType'] == 'profile_picture_deleted') {
                    $explode2 = @end(explode('.', $user_pic['avatar']));
                    $explode3 = @explode('.', $user_pic['avatar']);
                    if ($user_pic['avatar'] != $wo['userDefaultAvatar'] && $user_pic['avatar'] != $wo['userDefaultFAvatar']) {
                        if ($user_pic['gender'] == 'male') {
                            mysqli_query($sqlConnect, "UPDATE " . T_USERS . " SET `avatar` = '".$wo['userDefaultAvatar']."' WHERE `user_id` = '" . $fetched_data['user_id'] . "'");
                        }
                        else{
                            mysqli_query($sqlConnect, "UPDATE " . T_USERS . " SET `avatar` = '".$wo['userDefaultFAvatar']."' WHERE `user_id` = '" . $fetched_data['user_id'] . "'");
                        }
                        if (file_exists($explode3[0] . '_full.' . $explode2)) {
                            @unlink($explode3[0] . '_full.' . $explode2);
                        }
                        Wo_DeleteFromToS3($explode3[0] . '_full.' . $explode2);
                        if (file_exists($user_pic['avatar'])) {
                            @unlink($user_pic['avatar']);
                        }
                        Wo_DeleteFromToS3($user_pic['avatar']);
                    }
                }
                if ($fetched_data['postType'] == 'profile_cover_picture' || $fetched_data['postType'] == 'profile_picture_deleted') {
                    $explode2 = @end(explode('.', $user_pic['cover']));
                    $explode3 = @explode('.', $user_pic['cover']);
                    if ($user_pic['cover'] != $wo['userDefaultCover']) {
                        mysqli_query($sqlConnect, "UPDATE " . T_USERS . " SET `cover` = '".$wo['userDefaultCover']."' WHERE `user_id` = '" . $fetched_data['user_id'] . "'");
                        if (file_exists($explode3[0] . '_full.' . $explode2)) {
                            @unlink($explode3[0] . '_full.' . $explode2);
                        }
                        Wo_DeleteFromToS3($explode3[0] . '_full.' . $explode2);
                        if (file_exists($user_pic['cover'])) {
                            @unlink($user_pic['cover']);
                        }
                        Wo_DeleteFromToS3($user_pic['cover']);
                    }
                }
            }
        } */
        if (!empty($fetched_data['job_id'])) {
            $job_id = $fetched_data['job_id'];
            $row = mysqli_query($sqlConnect, "SELECT * FROM " . T_JOB . " WHERE `id` = '{$job_id}'");
            if (mysqli_num_rows($row)) {
                $job = mysqli_fetch_assoc($row);
                //$job = $db->where('id',$post_info->job_id)->getOne(T_JOB);
                if (!empty($job)) {
                    if ($job['image_type'] != 'cover') {
                        @unlink($job['image']);
                        Wo_DeleteFromToS3($job['image']);
                    }
                }
            }
            mysqli_query($sqlConnect, "DELETE FROM " . T_JOB . " WHERE `id` = {$job_id}");
            mysqli_query($sqlConnect, "DELETE FROM " . T_JOB_APPLY . " WHERE `job_id` = {$job_id}");
            // $db->where('id',$post_info->job_id)->delete(T_JOB);
            // $db->where('job_id',$post_info->job_id)->delete(T_JOB_APPLY);
        }
        if (!empty($fetched_data['offer_id'])) {
            $offer_id = $fetched_data['offer_id'];
            $row = mysqli_query($sqlConnect, "SELECT * FROM " . T_OFFER . " WHERE `id` = '{$offer_id}'");
            if (mysqli_num_rows($row)) {
                $offer = mysqli_fetch_assoc($row);
                //$offer = $db->where('id',$post_info->offer_id)->getOne(T_OFFER);
                if (!empty($offer)) {
                    if (!empty($offer['image'])) {
                        @unlink($offer['image']);
                        Wo_DeleteFromToS3($offer['image']);
                    }
                }
            }
            mysqli_query($sqlConnect, "DELETE FROM " . T_OFFER . " WHERE `id` = {$offer_id}");
            //$db->where('id',$post_info->offer_id)->delete(T_OFFER);
        }
        if (!empty($fetched_data['postText'])) {
            $hashtag_regex = '/(#\[([0-9]+)\])/i';
            preg_match_all($hashtag_regex, $fetched_data['postText'], $matches);
            $match_i = 0;
            foreach ($matches[1] as $match) {
                $hashtag = $matches[1][$match_i];
                $hashkey = $matches[2][$match_i];
                $hashdata = Wo_GetHashtag($hashkey);
                if (is_array($hashdata)) {
                    $hash_id = Wo_Secure($hashdata['id']);
                    $query_check_hash = mysqli_query($sqlConnect, "SELECT COUNT(id) as count FROM " . T_POSTS . " WHERE postText LIKE '%#[$hash_id]%'");
                    if (mysqli_num_rows($query_check_hash)) {
                        $query_get_hash = mysqli_fetch_assoc($query_check_hash);
                        if ($query_get_hash['count'] < 2) {
                            $delete = mysqli_query($sqlConnect, "DELETE FROM " . T_HASHTAGS . " WHERE id = $hash_id");
                        }
                    }
                }
                $match_i++;
            }
        }
        if (!empty($fetched_data['blog_id']) && $fetched_data['blog_id'] > 0) {
            //Wo_DeleteMyBlog($fetched_data['blog_id']);
        }
        if (!empty($fetched_data['blur_url']) && $fetched_data['blur_url'] != $wo["userDefaultBlur"]) {
            @unlink(trim($fetched_data['blur_url']));
            Wo_DeleteFromToS3($fetched_data['blur_url']);
        }
        if (isset($fetched_data['postFile']) && !empty($fetched_data['postFile'])) {
            if ($fetched_data['postType'] != 'profile_picture' && $fetched_data['postType'] != 'profile_cover_picture' && !$is_post_shared && !$is_this_post_shared) {
                @unlink(trim($fetched_data['postFile']));
                $delete_from_s3 = Wo_DeleteFromToS3($fetched_data['postFile']);
                $explode_video = explode('_video', $fetched_data['postFile']);
                if (strpos($fetched_data['postFile'], '_video') !== false) {
                    if ($post_info['240p'] == 1) {
                        $video_240p = $explode_video[0] . '_video_240p_converted.mp4';
                        @unlink(trim($video_240p));
                        $delete_from_s3 = Wo_DeleteFromToS3($video_240p);
                    }
                    if ($post_info['360p'] == 1) {
                        $video_360p = $explode_video[0] . '_video_360p_converted.mp4';
                        @unlink(trim($video_360p));
                        $delete_from_s3 = Wo_DeleteFromToS3($video_360p);
                    }
                    if ($post_info['480p'] == 1) {
                        $video_480p = $explode_video[0] . '_video_480p_converted.mp4';
                        @unlink(trim($video_480p));
                        $delete_from_s3 = Wo_DeleteFromToS3($video_480p);
                    }
                    if ($post_info['720p'] == 1) {
                        $video_720p = $explode_video[0] . '_video_720p_converted.mp4';
                        @unlink(trim($video_720p));
                        $delete_from_s3 = Wo_DeleteFromToS3($video_720p);
                    }
                    if ($post_info['1080p'] == 1) {
                        $video_1080p = $explode_video[0] . '_video_1080p_converted.mp4';
                        @unlink(trim($video_1080p));
                        $delete_from_s3 = Wo_DeleteFromToS3($video_1080p);
                    }
                    if ($post_info['2048p'] == 1) {
                        $video_2048p = $explode_video[0] . '_video_2048p_converted.mp4';
                        @unlink(trim($video_2048p));
                        $delete_from_s3 = Wo_DeleteFromToS3($video_2048p);
                    }
                    if ($post_info['4096p'] == 1) {
                        $video_4096p = $explode_video[0] . '_video_4096p_converted.mp4';
                        @unlink(trim($video_4096p));
                        $delete_from_s3 = Wo_DeleteFromToS3($video_4096p);
                    }
                } else if (strpos($fetched_data['postFile'], '_image') !== false) {
                    $explode2 = @end(explode('.', $fetched_data['postFile']));
                    $explode3 = @explode('.', $fetched_data['postFile']);
                    $media_2 = $explode3[0] . '_small.' . $explode2;
                    @unlink(trim($media_2));
                    Wo_DeleteFromToS3($media_2);
                }
            }
        }

        if ($fetched_data['postPrivacy'] == '6' && !empty($fetched_data['blur_url'])) {
            $new_target = $fetched_data['blur_url'];
            @unlink(trim($new_target));
        }

        if (!empty($fetched_data['postFileThumb']) && !$is_post_shared && !$is_this_post_shared) {
            if (file_exists($fetched_data['postFileThumb'])) {
                @unlink(trim($fetched_data['postFileThumb']));
            } else if ($wo['config']['amazone_s3'] == 1 || $wo['config']['wasabi_storage'] == 1 || $wo['config']['ftp_upload'] == 1 || $wo['config']['backblaze_storage'] == 1) {
                @Wo_DeleteFromToS3($fetched_data['postFileThumb']);
            }
        }
        if (!empty($fetched_data['postRecord']) && !$is_post_shared && !$is_this_post_shared) {
            if (file_exists($fetched_data['postRecord'])) {
                @unlink(trim($fetched_data['postRecord']));
            } else if ($wo['config']['amazone_s3'] == 1 || $wo['config']['wasabi_storage'] == 1 || $wo['config']['ftp_upload'] == 1 || $wo['config']['backblaze_storage'] == 1) {
                @Wo_DeleteFromToS3($fetched_data['postRecord']);
            }
        }
        if (isset($fetched_data['postLinkImage']) && !empty($fetched_data['postLinkImage']) && !$is_post_shared && !$is_this_post_shared) {
            @unlink($fetched_data['postLinkImage']);
            $delete_from_s3 = Wo_DeleteFromToS3($fetched_data['postLinkImage']);
        }
        if (!empty($fetched_data['album_name']) || !empty($fetched_data['multi_image']) && !$is_post_shared && !$is_this_post_shared) {
            $query_delete_4 = mysqli_query($sqlConnect, "SELECT `image` FROM " . T_ALBUMS_MEDIA . " WHERE `post_id` = {$post_id}");
            if (mysqli_num_rows($query_delete_4)) {
                while ($fetched_delete_data = mysqli_fetch_assoc($query_delete_4)) {
                    $explode2 = @end(explode('.', $fetched_delete_data['image']));
                    $explode3 = @explode('.', $fetched_delete_data['image']);
                    $media_2 = $explode3[0] . '_small.' . $explode2;
                    @unlink(trim($media_2));
                    @unlink($fetched_delete_data['image']);
                    $delete_from_s3 = Wo_DeleteFromToS3($media_2);
                    $delete_from_s3 = Wo_DeleteFromToS3($fetched_delete_data['image']);
                }
            }
        }
        if (!empty($fetched_data['multi_image_post'])) {
            $query_two_2 = mysqli_query($sqlConnect, "SELECT * FROM " . T_ALBUMS_MEDIA . " WHERE `image` = '" . $fetched_data['postFile'] . "' ");
            if (mysqli_num_rows($query_two_2)) {
                while ($fetched_data_s = mysqli_fetch_assoc($query_two_2)) {
                    $explode2 = @end(explode('.', $fetched_data_s['image']));
                    $explode3 = @explode('.', $fetched_data_s['image']);
                    $media_2 = $explode3[0] . '_small.' . $explode2;
                    @unlink(trim($media_2));
                    @unlink($fetched_data_s['image']);
                    $delete_from_s3 = Wo_DeleteFromToS3($media_2);
                    $delete_from_s3 = Wo_DeleteFromToS3($fetched_data_s['image']);
                    mysqli_query($sqlConnect, "DELETE FROM " . T_ALBUMS_MEDIA . " WHERE `image` = '" . $fetched_data['postFile'] . "' ");
                }
            }
        }
        $query_two_2 = mysqli_query($sqlConnect, "SELECT `id` FROM " . T_COMMENTS . " WHERE `post_id` = {$post_id}");
        if (mysqli_num_rows($query_two_2)) {
            while ($fetched_data = mysqli_fetch_assoc($query_two_2)) {
                Wo_DeletePostComment($fetched_data['id']);
            }
        }
        $product = Wo_PostData($post_id);
        $product_id = $product['product_id'];
        if (!empty($product_id) && !$is_post_shared && !$is_this_post_shared && empty($post_info['parent_id'])) {
            $query_two_3 = mysqli_query($sqlConnect, "SELECT `image` FROM " . T_PRODUCTS_MEDIA . " WHERE `product_id` = {$product_id}");
            if (mysqli_num_rows($query_two_3)) {
                while ($fetched_data = mysqli_fetch_assoc($query_two_3)) {
                    $explode2 = @end(explode('.', $fetched_data['image']));
                    $explode3 = @explode('.', $fetched_data['image']);
                    $media_2 = $explode3[0] . '_small.' . $explode2;
                    @unlink(trim($media_2));
                    @unlink($fetched_data['image']);
                    $delete_from_s3 = Wo_DeleteFromToS3($media_2);
                    $delete_from_s3 = Wo_DeleteFromToS3($fetched_data['image']);
                }
            }
            $query_two_3 = mysqli_query($sqlConnect, "SELECT `id` FROM " . T_PRODUCT_REVIEW . " WHERE `product_id` = {$product_id}");
            if (mysqli_num_rows($query_two_3)) {
                while ($fetched_data = mysqli_fetch_assoc($query_two_3)) {
                    $query_two_ = mysqli_query($sqlConnect, "SELECT `image` FROM " . T_ALBUMS_MEDIA . " WHERE `review_id` = '" . $fetched_data['id'] . "'");
                    if (mysqli_num_rows($query_two_)) {
                        while ($fetched_data_ = mysqli_fetch_assoc($query_two_)) {
                            $explode2 = @end(explode('.', $fetched_data_['image']));
                            $explode3 = @explode('.', $fetched_data_['image']);
                            $media_2 = $explode3[0] . '_small.' . $explode2;
                            @unlink(trim($media_2));
                            @unlink($fetched_data_['image']);
                            $delete_from_s3 = Wo_DeleteFromToS3($media_2);
                            $delete_from_s3 = Wo_DeleteFromToS3($fetched_data_['image']);
                        }
                    }
                    mysqli_query($sqlConnect, "DELETE FROM " . T_ALBUMS_MEDIA . " WHERE `review_id` = '" . $fetched_data['id'] . "'");
                }
            }
            mysqli_query($sqlConnect, "DELETE FROM " . T_USER_ORDERS . " WHERE `product_id` = {$product_id}");
            mysqli_query($sqlConnect, "DELETE FROM " . T_PRODUCT_REVIEW . " WHERE `product_id` = {$product_id}");
            mysqli_query($sqlConnect, "DELETE FROM " . T_USERCARD . " WHERE `product_id` = {$product_id}");
            mysqli_query($sqlConnect, "DELETE FROM " . T_PRODUCTS_MEDIA . " WHERE `product_id` = {$product_id}");
            mysqli_query($sqlConnect, "DELETE FROM " . T_PRODUCTS . " WHERE `id` = {$product_id}");
        }
        if ($is_me > 0 || (Wo_IsAdmin() || Wo_IsModerator())) {
            Wo_RegisterPoint($post_id, "createpost", "-", $fetched_data['user_id']);
        }
        $query_delete = mysqli_query($sqlConnect, "DELETE FROM " . T_POSTS . " WHERE `id` = {$post_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_POSTS . " WHERE `post_id` = {$post_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_ALBUMS_MEDIA . " WHERE `post_id` = {$post_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_COMMENTS . " WHERE `post_id` = {$post_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_WONDERS . " WHERE `post_id` = {$post_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_LIKES . " WHERE `post_id` = {$post_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_NOTIFICATION . " WHERE `post_id` = {$post_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_SAVED_POSTS . " WHERE `post_id` = {$post_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_REPORTS . " WHERE `post_id` = {$post_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_PINNED_POSTS . " WHERE `post_id` = {$post_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_ACTIVITIES . " WHERE `post_id` = {$post_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_POLLS . " WHERE `post_id` = {$post_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_VOTES . " WHERE `post_id` = {$post_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_HIDDEN_POSTS . " WHERE `post_id` = {$post_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_REACTIONS . " WHERE `post_id` = '{$post_id}'");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_LIVE_SUB . " WHERE `post_id` = '{$post_id}'");
        $query_get_images = mysqli_query($sqlConnect, "SELECT * FROM " . T_ALBUMS_MEDIA . " WHERE `post_id` = {$post_id} OR `parent_id` = {$post_id}");
        if (mysqli_num_rows($query_get_images)) {
            while ($fetched_delete_data = mysqli_fetch_assoc($query_get_images)) {
                $explode2 = @end(explode('.', $fetched_delete_data['image']));
                $explode3 = @explode('.', $fetched_delete_data['image']);
                $media_2 = $explode3[0] . '_small.' . $explode2;
                @unlink(trim($media_2));
                @unlink($fetched_delete_data['image']);
                $delete_from_s3 = Wo_DeleteFromToS3($media_2);
                $delete_from_s3 = Wo_DeleteFromToS3($fetched_delete_data['image']);
                if (!empty($fetched_delete_data['parent_id'])) {
                    Wo_DeletePost($fetched_delete_data['post_id']);
                }
            }
        }
        return true;
    } else {
        return false;
    }
}

function Wo_DeleteGame($game_id)
{
    global $wo, $sqlConnect, $cache;
    if ($game_id < 1 || empty($game_id) || !is_numeric($game_id)) {
        return false;
    }
    if ($wo['loggedin'] == false) {
        return false;
    }
    $user_id = Wo_Secure($wo['user']['user_id']);
    if (Wo_IsAdmin($user_id) === false) {
        return false;
    }
    $game_id = Wo_Secure($game_id);
    $query_delete = mysqli_query($sqlConnect, "DELETE FROM " . T_GAMES . " WHERE `id` = {$game_id}");
    $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_GAMES_PLAYERS . " WHERE `game_id` = {$game_id}");
    if ($query_delete) {
        return true;
    } else {
        return false;
    }
}

function Wo_DeleteGift($gift_id)
{
    global $wo, $sqlConnect, $cache;
    if ($gift_id < 1 || empty($gift_id) || !is_numeric($gift_id)) {
        return false;
    }
    if ($wo['loggedin'] == false) {
        return false;
    }
    $user_id = Wo_Secure($wo['user']['user_id']);
    if (Wo_IsAdmin($user_id) === false) {
        return false;
    }
    $gift_id = Wo_Secure($gift_id);
    $query_delete = mysqli_query($sqlConnect, "DELETE FROM " . T_GIFTS . " WHERE `id` = {$gift_id}");
    $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_USERGIFTS . " WHERE `gift_id` = {$gift_id}");
    $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_NOTIFICATION . " WHERE `type2` = 'gift_{$gift_id}'");
    if ($query_delete) {
        return true;
    } else {
        return false;
    }
}

function Wo_DeleteSticker($sticker_id)
{
    global $wo, $sqlConnect, $cache;
    if ($sticker_id < 1 || empty($sticker_id) || !is_numeric($sticker_id)) {
        return false;
    }
    if ($wo['loggedin'] == false) {
        return false;
    }
    $user_id = Wo_Secure($wo['user']['user_id']);
    if (Wo_IsAdmin($user_id) === false) {
        return false;
    }
    $sticker_id = Wo_Secure($sticker_id);
    $query_delete = mysqli_query($sqlConnect, "DELETE FROM " . T_STICKERS . " WHERE `id` = {$sticker_id}");
    // $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_USERGIFTS . " WHERE `gift_id` = {$gift_id}");
    // $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_NOTIFICATION . " WHERE `type2` = 'gift_{$gift_id}'");
    if ($query_delete) {
        return true;
    } else {
        return false;
    }
}

function Wo_GetUserIdFromPostId($post_id = 0)
{
    global $sqlConnect;
    if (empty($post_id) or !is_numeric($post_id) or $post_id < 1) {
        return false;
    }
    $post_id = Wo_Secure($post_id);
    $query_one = "SELECT `user_id` FROM " . T_POSTS . " WHERE `id` = {$post_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if ($sql_query_one) {
        if (mysqli_num_rows($sql_query_one) == 1) {
            $sql_fetch_one = mysqli_fetch_assoc($sql_query_one);
            return $sql_fetch_one['user_id'];
        }
    }
}

function Wo_GetPinnedPost($user_id, $type = '')
{
    global $sqlConnect, $wo;
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 1) {
        return false;
    }
    $query_type = 'user_id';
    if ($type == 'page') {
        $query_type = 'page_id';
    } else if ($type == 'profile') {
        $query_type = 'user_id';
    } else if ($type == 'group') {
        $query_type = 'group_id';
    } else if ($type == 'event') {
        $query_type = 'event_id';
    }
    $data = array();
    $query_one = mysqli_query($sqlConnect, "SELECT `post_id` FROM " . T_PINNED_POSTS . " WHERE `{$query_type}` = {$user_id} AND `active` = '1'");
    if (mysqli_num_rows($query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($query_one)) {
            $post = Wo_PostData($fetched_data['post_id']);
            if (is_array($post)) {
                $data[] = $post;
            }
        }
    }
    return $data;
}

function Wo_IsPostPinned($post_id)
{
    global $sqlConnect, $wo;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($post_id) || !is_numeric($post_id) || $post_id < 1) {
        return false;
    }
    $post_id = Wo_Secure($post_id);
    $query_one = mysqli_query($sqlConnect, "SELECT COUNT(`id`) as `pinned` FROM " . T_PINNED_POSTS . " WHERE `post_id` = {$post_id} AND `active` = '1'");
    if (mysqli_num_rows($query_one)) {
        $sql_query_one = mysqli_fetch_assoc($query_one);
        return ($sql_query_one['pinned'] == 1) ? true : false;
    }
    return false;
}

include_once('./assets/libraries/SimpleImage-master/vendor/claviska/simpleimage/src/claviska/SimpleImage-Class.php');
function Wo_IsUserPinned($id, $type = '')
{
    global $sqlConnect, $wo;
    if ($wo['loggedin'] == false) {
        return false;
    }
    $id = Wo_Secure($id);
    $query_type = 'user_id';
    if ($type == 'page') {
        $query_type = 'page_id';
    } else if ($type == 'profile') {
        $query_type = 'user_id';
    } else if ($type == 'group') {
        $query_type = 'group_id';
    } else if ($type == 'event') {
        $query_type = 'event_id';
    }
    $query_one = mysqli_query($sqlConnect, "SELECT COUNT(`id`) as `pinned` FROM " . T_PINNED_POSTS . " WHERE `{$query_type}` = {$id} AND `active` = '1'");
    if (mysqli_num_rows($query_one)) {
        $sql_query_one = mysqli_fetch_assoc($query_one);
        return ($sql_query_one['pinned'] == 1) ? true : false;
    }
    return false;
}

function Wo_PinPost($post_id = 0, $type = '', $id = 0)
{
    global $sqlConnect, $wo;
    if ($wo['loggedin'] == false) {
        return false;
    }
    $user_id = Wo_Secure($wo['user']['user_id']);
    $post_id = Wo_Secure($post_id);
    $continue = false;
    if (empty($type)) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 1) {
        return false;
    }
    if (empty($post_id) || !is_numeric($post_id) || $post_id < 1) {
        return false;
    }
    if (Wo_PostExists($post_id) === false) {
        return false;
    }
    if (Wo_IsPostOnwer($post_id, $user_id) === false) {
        return false;
    }
    if ($type == 'page') {
        if (Wo_IsPageOnwer($id) === false) {
            return false;
        }
        $where_delete_query = " WHERE `page_id` = {$id} AND `active` = '1'";
        $where_insert_query = " (`page_id`, `post_id`, `active`) VALUES ({$id}, {$post_id}, '1')";
    } else if ($type == 'group') {
        if (Wo_IsGroupOnwer($id) === false) {
            return false;
        }
        $where_delete_query = " WHERE `group_id` = {$id} AND `active` = '1'";
        $where_insert_query = " (`group_id`, `post_id`, `active`) VALUES ({$id}, {$post_id}, '1')";
    } else if ($type == 'event') {
        if (Is_EventOwner($id) === false) {
            return false;
        }
        $where_delete_query = " WHERE `event_id` = {$id} AND `active` = '1'";
        $where_insert_query = " (`event_id`, `post_id`, `active`) VALUES ({$id}, {$post_id}, '1')";
    } else if ($type == 'profile') {
        $where_delete_query = " WHERE `user_id` = {$user_id} AND `active` = '1'";
        $where_insert_query = " (`user_id`, `post_id`, `active`) VALUES ({$user_id}, {$post_id}, '1')";
    }
    $delete_query_text = "DELETE FROM " . T_PINNED_POSTS;
    $query_text = $delete_query_text . $where_delete_query;
    $insert_query_text = "INSERT INTO " . T_PINNED_POSTS;
    $insert_text = $insert_query_text . $where_insert_query;
    if (Wo_IsPostPinned($post_id)) {
        $query_two = mysqli_query($sqlConnect, $query_text);
        return 'unpin';
    } else {
        if (Wo_IsUserPinned($id, $type)) {
            $query_two = mysqli_query($sqlConnect, $query_text);
            $continue = true;
        } else {
            $continue = true;
        }
        if ($continue === true) {
            $query_three = mysqli_query($sqlConnect, $insert_text);
            if ($query_three) {
                return 'pin';
            }
        }
    }
}

function Wo_BoostPost($post_id)
{
    global $sqlConnect, $wo;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if ($wo['config']['pro'] == 0) {
        return false;
    }
    if ($wo['user']['is_pro'] == 0 || $wo['pro_packages'][$wo['user']['pro_type']]['posts_promotion'] < 1) {
        return false;
    }
    $user_id = Wo_Secure($wo['user']['user_id']);
    $post_id = Wo_Secure($post_id);
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 1) {
        return false;
    }
    if (empty($post_id) || !is_numeric($post_id) || $post_id < 1) {
        return false;
    }
    if (Wo_PostExists($post_id) === false) {
        return false;
    }
    if (Wo_IsPostOnwer($post_id, $user_id) === false) {
        return false;
    }
    if (Wo_IsPostBoosted($post_id)) {
        $query_text = "UPDATE " . T_POSTS . " SET `boosted` = '0' WHERE `id` = '{$post_id}' AND (`user_id` = {$user_id} OR `page_id` IN (SELECT `page_id` FROM " . T_PAGES . " WHERE `user_id` = {$user_id}))";
        $query_two = mysqli_query($sqlConnect, $query_text);
        return 'unboosted';
    } else {
        $query_select = mysqli_query($sqlConnect, "SELECT COUNT(`id`) as count FROM " . T_POSTS . " WHERE `boosted` = '1' AND (`user_id` = {$user_id} OR `page_id` IN (SELECT `page_id` FROM " . T_PAGES . " WHERE `user_id` = {$user_id}))");
        if (mysqli_num_rows($query_select)) {
            $query_select_fetch = mysqli_fetch_assoc($query_select);
            $query_textt = "UPDATE " . T_POSTS . " SET `boosted` = '0' WHERE `id` IN (SELECT * FROM (SELECT `id` FROM " . T_POSTS . " WHERE `boosted` = '1' AND (`user_id` = {$user_id} OR `page_id` IN (SELECT `page_id` FROM " . T_PAGES . " WHERE `user_id` = {$user_id})) ORDER BY `id` DESC LIMIT 1) as t)";
            $continue = 0;
            if ($query_select_fetch['count'] > ($wo['pro_packages'][$wo['user']['pro_type']]['posts_promotion'] - 1)) {
                $continue = 1;
            }
        }
        if ($continue == 1) {
            $query_two = mysqli_query($sqlConnect, $query_textt);
        }
        $query_text = "UPDATE " . T_POSTS . " SET `boosted` = '1' WHERE `id` = '{$post_id}' AND (`user_id` = {$user_id} OR `page_id` IN (SELECT `page_id` FROM " . T_PAGES . " WHERE `user_id` = {$user_id}))";
        $query_two = mysqli_query($sqlConnect, $query_text);
        return 'boosted';
    }
}

function Wo_IsPostBoosted($post_id)
{
    global $sqlConnect, $wo;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($post_id) || !is_numeric($post_id) || $post_id < 1) {
        return false;
    }
    $post_id = Wo_Secure($post_id);
    $query_one = mysqli_query($sqlConnect, "SELECT COUNT(`id`) as `count` FROM " . T_POSTS . " WHERE `id` = {$post_id} AND `boosted` = '1'");
    if (mysqli_num_rows($query_one)) {
        $sql_query_one = mysqli_fetch_assoc($query_one);
        return ($sql_query_one['count'] == 1) ? true : false;
    }
    return false;
}

function Wo_RegisterActivity($data = array())
{
    global $sqlConnect, $wo;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if ($wo['user']['show_activities_privacy'] == 0) {
        return false;
    }
    if (!empty($data['post_id'])) {
        if (!is_numeric($data['post_id']) || $data['post_id'] < 1) {
            return false;
        }
    }
    if (empty($data['user_id']) || !is_numeric($data['user_id']) || $data['user_id'] < 1) {
        return false;
    }
    if (empty($data['activity_type'])) {
        return false;
    }
    $comment_id = 0;
    if (empty($data['comment_id']) || !is_numeric($data['comment_id']) || $data['comment_id'] < 1) {
        $comment_id = 0;
    } else {
        $comment_id = Wo_Secure($data['comment_id']);
    }
    $replay_id = 0;
    if (empty($data['reply_id']) || !is_numeric($data['reply_id']) || $data['reply_id'] < 1) {
        $replay_id = 0;
    } else {
        $replay_id = Wo_Secure($data['reply_id']);
    }
    $follow_id = 0;
    if (empty($data['follow_id']) || !is_numeric($data['follow_id']) || $data['follow_id'] < 1) {
        $follow_id = 0;
    } else {
        $follow_id = Wo_Secure($data['follow_id']);
    }
    @$post_id = Wo_Secure($data['post_id']);
    @$user_id = Wo_Secure($data['user_id']);
    @$post_user_id = Wo_Secure($data['post_user_id']);
    @$activity_type = Wo_Secure($data['activity_type']);
    @$follow_id = Wo_Secure($data['follow_id']);
    $time = time();
    if ($comment_id > 0 || $replay_id > 0) {
    } else {
        if ($user_id == $post_user_id) {
            return false;
        }
    }
    $query_insert = "INSERT INTO " . T_ACTIVITIES . " (`user_id`, `post_id`,`comment_id`,`reply_id`, `follow_id`, `activity_type`, `time`) VALUES ('{$user_id}', '{$post_id}', '{$comment_id}','{$replay_id}','{$follow_id}','{$activity_type}', '{$time}')";
    if (Wo_IsActivity($post_id, $comment_id, $replay_id, $follow_id, $user_id, $activity_type) === true) {
        $query_delete = mysqli_query($sqlConnect, "DELETE FROM " . T_ACTIVITIES . " WHERE `user_id` = '{$user_id}' AND `post_id` = '{$post_id}'");
        if ($query_delete) {
            $query_one = mysqli_query($sqlConnect, $query_insert);
        }
    } else {
        $query_one = mysqli_query($sqlConnect, $query_insert);
    }
    if ($query_one) {
        return true;
    }
}

function Wo_IsActivity($post_id, $comment_id, $replay_id, $follow_id, $user_id, $activity_type)
{
    global $sqlConnect, $wo;
    if ($wo['loggedin'] == false) {
        return false;
    }
    $query = mysqli_query($sqlConnect, "SELECT `id` FROM " . T_ACTIVITIES . " WHERE `user_id` = '{$user_id}' AND ( `post_id` = '{$post_id}' OR `comment_id` = '{$comment_id}' OR `reply_id` = '{$replay_id}' OR `follow_id` = '{$follow_id}' ) AND `activity_type` = '{$activity_type}'");
    return (mysqli_num_rows($query) > 0) ? true : false;
}

function Wo_DeleteSelectedActivity($user_id, $activity_type, $follow_id)
{
    global $sqlConnect, $wo;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($follow_id) || !is_numeric($follow_id) || $follow_id < 1) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 1) {
        return false;
    }
    $query = mysqli_query($sqlConnect, "DELETE FROM " . T_ACTIVITIES . " WHERE `user_id` = '{$user_id}' AND `follow_id` = '{$follow_id}' AND `activity_type` = '{$activity_type}'");
    return ($query) ? true : false;
}

function Wo_DeleteActivity($post_id, $user_id, $activity_type)
{
    global $sqlConnect, $wo;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($post_id) || !is_numeric($post_id) || $post_id < 1) {
        return false;
    }
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 1) {
        return false;
    }
    $query = mysqli_query($sqlConnect, "DELETE FROM " . T_ACTIVITIES . " WHERE `user_id` = '{$user_id}' AND `post_id` = '{$post_id}' AND `activity_type` = '{$activity_type}'");
    return ($query) ? true : false;
}

function Wo_GetActivity($id)
{
    global $sqlConnect, $wo;
    if ($wo['loggedin'] == false) {
        return false;
    }
    $user_id = Wo_Secure($wo['user']['user_id']);
    if (empty($id) || !is_numeric($id) || $id < 1) {
        return false;
    }
    $query = mysqli_query($sqlConnect, "SELECT * FROM " . T_ACTIVITIES . " WHERE `id` = {$id}");
    if (mysqli_num_rows($query) == 1) {
        $finel_fetched_data = mysqli_fetch_assoc($query);
        $finel_fetched_data['postData'] = Wo_PostData($finel_fetched_data['post_id']);
        $finel_fetched_data['activator'] = Wo_UserData($finel_fetched_data['user_id']);
        return $finel_fetched_data;
    }
    return false;
}

function Wo_GetActivities($data = array('after_activity_id' => 0, 'before_activity_id' => 0, 'limit' => 5, 'me' => false))
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    $user_id = Wo_Secure($wo['user']['user_id']);
    $get = array();
    if (empty($data['limit'])) {
        $data['limit'] = 5;
    }
    $limit = Wo_Secure($data['limit']);
    $subquery_one = " `id` > 0 ";
    if (!empty($data['after_activity_id']) && is_numeric($data['after_activity_id']) && $data['after_activity_id'] > 0) {
        $data['after_activity_id'] = Wo_Secure($data['after_activity_id']);
        $subquery_one = " `id` < " . $data['after_activity_id'] . " AND `id` <> " . $data['after_activity_id'];
    } else if (!empty($data['before_activity_id']) && is_numeric($data['before_activity_id']) && $data['before_activity_id'] > 0) {
        $data['before_activity_id'] = Wo_Secure($data['before_activity_id']);
        $subquery_one = " `id` > " . $data['before_activity_id'] . " AND `id` <> " . $data['before_activity_id'];
    }
    $query_text = "SELECT `id` FROM " . T_ACTIVITIES . " WHERE {$subquery_one}";
    if (!empty($data['me'])) {
        $query_text .= " AND user_id = '{$wo['user']['user_id']}'";
    } else {
        $query_text .= " AND `user_id` IN (SELECT `following_id` FROM " . T_FOLLOWERS . " WHERE `follower_id` = {$user_id} AND `active` = '1') AND `user_id` NOT IN ($user_id)";
    }
    $query_text .= " ORDER BY `id` DESC LIMIT {$limit}";
    $sql_query_one = mysqli_query($sqlConnect, $query_text);
    if (mysqli_num_rows($sql_query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query_one)) {
            if (is_array($fetched_data)) {
                $get[] = Wo_GetActivity($fetched_data['id']);
            }
        }
    }
    return $get;
}

function Wo_DeleteReactions($post_id)
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($post_id) || !is_numeric($post_id) || $post_id < 1) {
        return false;
    }
    $post_id = Wo_Secure($post_id);
    $logged_user_id = Wo_Secure($wo['user']['user_id']);
    if (Wo_IsReacted($post_id, $wo['user']['user_id']) == true) {
        Wo_RegisterPoint($post_id, "reaction", '-');
        $query_one = "DELETE FROM " . T_REACTIONS . " WHERE `post_id` = {$post_id} AND `user_id` = {$logged_user_id}";
        $sql_query_one = mysqli_query($sqlConnect, $query_one);
        $query_delete_one = mysqli_query($sqlConnect, "DELETE FROM " . T_NOTIFICATION . " WHERE `post_id` = {$post_id} AND `notifier_id` = {$logged_user_id} AND `type` = 'reaction'");
        return true;
    }
}

function Wo_DeleteCommentReactions($comment_id)
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($comment_id) || !is_numeric($comment_id) || $comment_id < 1) {
        return false;
    }
    $comment_id = Wo_Secure($comment_id);
    $logged_user_id = Wo_Secure($wo['user']['user_id']);
    if (Wo_IsReacted($comment_id, $wo['user']['user_id'], "comment") == true) {
        $query_one = "DELETE FROM " . T_REACTIONS . " WHERE `comment_id` = {$comment_id} AND `user_id` = {$logged_user_id}";
        $sql_query_one = mysqli_query($sqlConnect, $query_one);
        $query_delete_one = mysqli_query($sqlConnect, "DELETE FROM " . T_NOTIFICATION . " WHERE `comment_id` = {$comment_id} AND `notifier_id` = {$logged_user_id} AND `type` = 'reaction'");
        return true;
    }
}

function Wo_DeleteReplayReactions($replay_id)
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($replay_id) || !is_numeric($replay_id) || $replay_id < 1) {
        return false;
    }
    $replay_id = Wo_Secure($replay_id);
    $logged_user_id = Wo_Secure($wo['user']['user_id']);
    if (Wo_IsReacted($replay_id, $wo['user']['user_id'], "replay") == true) {
        $query_one = "DELETE FROM " . T_REACTIONS . " WHERE `replay_id` = {$replay_id} AND `user_id` = {$logged_user_id}";
        $query_delete_one = mysqli_query($sqlConnect, "DELETE FROM " . T_NOTIFICATION . " WHERE `reply_id` = {$replay_id} AND `notifier_id` = {$logged_user_id} AND `type` = 'reaction'");
        $sql_query_one = mysqli_query($sqlConnect, $query_one);
        return true;
    }
}

function Wo_AddReactions($post_id, $reaction)
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($post_id) || empty($reaction) || !is_numeric($post_id) || $post_id < 1) {
        return false;
    }
    $post_id = Wo_Secure($post_id);
    $user_id = Wo_GetUserIdFromPostId($post_id);
    $page_id = 0;
    $logged_user_id = Wo_Secure($wo['user']['user_id']);
    $post = Wo_PostData($post_id);
    $text = 'post';
    $type2 = Wo_Secure($reaction);
    if (empty($user_id)) {
        $user_id = Wo_GetUserIdFromPageId($post['page_id']);
        if (empty($user_id)) {
            return false;
        }
    }
    if (Wo_IsReacted($post_id, $wo['user']['user_id']) == true) {
        $query_one = "DELETE FROM " . T_REACTIONS . " WHERE `post_id` = '{$post_id}' AND `user_id` = '{$logged_user_id}'";
        $query_delete_one = mysqli_query($sqlConnect, "DELETE FROM " . T_NOTIFICATION . " WHERE `post_id` = '{$post_id}' AND `recipient_id` = '{$user_id}' AND `type` = 'reaction'");
        $delete_activity = Wo_DeleteActivity($post_id, $logged_user_id, 'reaction');
        $sql_query_one = mysqli_query($sqlConnect, $query_one);
        //Register point level system for reaction
        Wo_RegisterPoint($post_id, "reaction", "-");
    }
    $query_two = "INSERT INTO " . T_REACTIONS . " (`user_id`, `post_id`, `reaction`) VALUES ('{$logged_user_id}', '{$post_id}','{$reaction}')";
    $sql_query_two = mysqli_query($sqlConnect, $query_two);
    if ($sql_query_two) {
        $activity_data = array(
            'post_id' => $post_id,
            'user_id' => $logged_user_id,
            'post_user_id' => $user_id,
            'activity_type' => 'reaction|post|' . $reaction
        );
        $add_activity = Wo_RegisterActivity($activity_data);
        $notification_data_array = array(
            'recipient_id' => $user_id,
            'post_id' => $post_id,
            'type' => 'reaction',
            'text' => $text,
            'type2' => $type2,
            'url' => 'index.php?link1=post&id=' . $post_id
        );
        Wo_RegisterNotification($notification_data_array);
        //Register point level system for reaction
        Wo_RegisterPoint($post_id, "reaction");
        return 'reacted';
    }
}

function Wo_AddReplayReactions($user_id, $reply_id, $reaction)
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($reply_id) || empty($reaction) || !is_numeric($reply_id) || $reply_id < 1) {
        return false;
    }
    $reply_id = Wo_Secure($reply_id);
    $page_id = 0;
    $logged_user_id = Wo_Secure($wo['user']['user_id']);
    $comment = Wo_GetCommentIdFromReplyId($reply_id);
    $post_id = Wo_GetPostIdFromCommentId($comment);
    $text = 'replay';
    $type2 = $reaction;
    if (empty($user_id)) {
        return false;
    }
    if (Wo_IsReacted($reply_id, $wo['user']['user_id'], "replay") == true) {
        $query_one = "DELETE FROM " . T_REACTIONS . " WHERE `replay_id` = {$reply_id} AND `user_id` = {$logged_user_id}";
        $query_delete_one = mysqli_query($sqlConnect, "DELETE FROM " . T_NOTIFICATION . " WHERE `reply_id` = {$reply_id} AND `recipient_id` = {$user_id} AND `type` = 'reaction'");
        $delete_activity = Wo_DeleteActivity($reply_id, $logged_user_id, 'reaction');
        $sql_query_one = mysqli_query($sqlConnect, $query_one);
        //Register point level system for reaction
        //Wo_RegisterPoint($post_id, "reaction" , "-");
    }
    $query_two = "INSERT INTO " . T_REACTIONS . " (`user_id`, `replay_id`, `reaction`) VALUES ({$logged_user_id}, {$reply_id},'{$reaction}')";
    $sql_query_two = mysqli_query($sqlConnect, $query_two);
    if ($sql_query_two) {
        $post_data = Wo_PostData($post_id);
        if ($wo['config']['shout_box_system'] == 1 && !empty($post_data) && $post_data['postPrivacy'] == 4 && $post_data['user_id'] == $logged_user_id) {
            $type2 = 'anonymous';
        }
        // $activity_data = array(
        //     'post_id' => $post_id,
        //     'reply_id' => $reply_id,
        //     'user_id' => $logged_user_id,
        //     'post_user_id' => $user_id,
        //     'activity_type' => 'reaction|replay|'.$reaction
        // );
        // $add_activity  = Wo_RegisterActivity($activity_data);
        $notification_data_array = array(
            'recipient_id' => $user_id,
            'reply_id' => $reply_id,
            'type' => 'reaction',
            'text' => $text,
            'type2' => $type2,
            'url' => 'index.php?link1=post&id=' . $post_id . '&ref=' . $comment
        );
        Wo_RegisterNotification($notification_data_array);
        //Register point level system for reaction
        //Wo_RegisterPoint($post_id, "reaction");
        return 'reacted';
    }
}

function Wo_AddCommentReactions($comment_id, $reaction)
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($comment_id) || empty($reaction) || !is_numeric($comment_id) || $comment_id < 1) {
        return false;
    }
    $comment_id = Wo_Secure($comment_id);
    $user_id = Wo_GetUserIdFromCommentId($comment_id);
    $page_id = 0;
    $logged_user_id = Wo_Secure($wo['user']['user_id']);
    $post_id = Wo_GetPostIdFromCommentId($comment_id);
    $text = 'comment';
    $type2 = $reaction;
    if (empty($user_id)) {
        return false;
    }
    if (Wo_IsReacted($comment_id, $logged_user_id, "comment") == true) {
        $query_one = "DELETE FROM " . T_REACTIONS . " WHERE `comment_id` = {$comment_id} AND `user_id` = {$logged_user_id}";
        $query_delete_one = mysqli_query($sqlConnect, "DELETE FROM " . T_NOTIFICATION . " WHERE `comment_id` = {$comment_id} AND `recipient_id` = {$user_id} AND `type` = 'reaction'");
        $delete_activity = Wo_DeleteActivity($comment_id, $logged_user_id, 'reaction');
        $sql_query_one = mysqli_query($sqlConnect, $query_one);
        //Register point level system for reaction
        //Wo_RegisterPoint($post_id, "reaction" , "-");
    }
    $query_two = "INSERT INTO " . T_REACTIONS . " (`user_id`, `comment_id`, `reaction`) VALUES ({$logged_user_id}, {$comment_id},'{$reaction}')";
    $sql_query_two = mysqli_query($sqlConnect, $query_two);
    if ($sql_query_two) {
        $post_data = Wo_PostData($post_id);
        if ($wo['config']['shout_box_system'] == 1 && !empty($post_data) && $post_data['postPrivacy'] == 4 && $post_data['user_id'] == $logged_user_id) {
            $type2 = 'anonymous';
        }
        // $activity_data = array(
        //     'post_id' => $post_id,
        //     'comment_id' => $comment_id,
        //     'user_id' => $logged_user_id,
        //     'post_user_id' => $user_id,
        //     'activity_type' => 'reaction|comment|'.$reaction
        // );
        //$add_activity  = Wo_RegisterActivity($activity_data);
        $notification_data_array = array(
            'recipient_id' => $user_id,
            'comment_id' => $comment_id,
            'type' => 'reaction',
            'text' => $text,
            'type2' => $type2,
            'url' => 'index.php?link1=post&id=' . $post_id . '&ref=' . $comment_id
        );
        Wo_RegisterNotification($notification_data_array);
        //Register point level system for reaction
        //Wo_RegisterPoint($post_id, "reaction");
        return 'reacted';
    }
}

function Wo_IsReacted($object_id, $user_id, $col = "post", $type = '')
{
    global $sqlConnect;
    if (empty($object_id) or !is_numeric($object_id) or $object_id < 1) {
        return false;
    }
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $object_id = Wo_Secure($object_id);
    if ($type == 'blog') {
        $query_one = "SELECT `id` FROM " . T_BLOG_REACTION . " WHERE `{$col}_id` = {$object_id} AND `user_id` = {$user_id}";
    } else {
        $query_one = "SELECT `id` FROM " . T_REACTIONS . " WHERE `{$col}_id` = {$object_id} AND `user_id` = {$user_id}";
    }
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) >= 1) {
        return true;
    }
}

function Wo_GetReactedTextIcon($object_id, $user_id, $col = "post")
{
    global $sqlConnect, $wo;
    if (empty($object_id) or !is_numeric($object_id) or $object_id < 1) {
        return false;
    }
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $object_id = Wo_Secure($object_id);
    $query_one = "SELECT `reaction` FROM " . T_REACTIONS . " WHERE `{$col}_id` = {$object_id} AND `user_id` = {$user_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if ($sql_query_one) {
        if (mysqli_num_rows($sql_query_one) == 1) {
            $sql_fetch_one = mysqli_fetch_assoc($sql_query_one);
            $reaction_icon = "";
            $reaction_color = "";
            $reaction_type = "";
            switch (strtolower($sql_fetch_one['reaction'])) {
                case 1:
                    $reaction_type = "-1";
                    break;
                case 2:
                    $reaction_type = "-2";
                    break;
                case 3:
                    $reaction_type = "-3";
                    break;
                case 4:
                    $reaction_type = "-4";
                    break;
                case 5:
                    $reaction_type = "-5";
                    break;
                case 6:
                    $reaction_type = "-6";
                    break;
            }
            if (!empty($wo['reactions_types'][$sql_fetch_one['reaction']]['wowonder_small_icon'])) {
                $reaction_icon = "<div class='inline_post_count_emoji reaction'><img src='{$wo['reactions_types'][$sql_fetch_one['reaction']]['wowonder_small_icon']}' alt=\"" . $wo['reactions_types'][$sql_fetch_one['reaction']]['name'] . "\"></div>";
            }
            return '<span class="status-reaction-' . $object_id . ' rea active-like' . $reaction_type . ' active-like">' . $reaction_icon . ' &nbsp;' . $wo['reactions_types'][strtolower($sql_fetch_one['reaction'])]['name'] . '</span>';
        }
    }
}

function Wo_CountReactions($object_id, $reaction, $col = "post")
{
    global $sqlConnect;
    if (empty($object_id) or !is_numeric($object_id) or $object_id < 1) {
        return false;
    }
    if (empty($reaction)) {
        return false;
    }
    $object_id = Wo_Secure($object_id);
    $query_one = "SELECT COUNT(`id`) AS `reactions` FROM " . T_REACTIONS . " WHERE `{$col}_id` = {$object_id} AND `reaction` = '{$reaction}'";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if ($sql_query_one) {
        if (mysqli_num_rows($sql_query_one) == 1) {
            $sql_fetch_one = mysqli_fetch_assoc($sql_query_one);
            return $sql_fetch_one['reactions'];
        }
    }
}

function Wo_GetPostReactions($object_id, $col = "post", $type = '')
{
    global $sqlConnect, $wo;
    if (empty($object_id) or !is_numeric($object_id) or $object_id < 1) {
        return false;
    }
    $reactions_html = "";
    $reactions = array();
    $reactions_count = 0;
    $object_id = Wo_Secure($object_id);
    if ($type == 'blog') {
        $query_one = "SELECT `reaction` FROM " . T_BLOG_REACTION . " WHERE `{$col}_id` = {$object_id}";
    } else {
        $query_one = "SELECT `reaction` FROM " . T_REACTIONS . " WHERE `{$col}_id` = {$object_id}";
    }
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query_one)) {
            $reactions[$fetched_data['reaction']] = $fetched_data['reaction'];
            $reactions_count++;
        }
    }
    if (!empty($reactions)) {
        foreach ($reactions as $key => $val) {
            if ($type == 'blog' || $col == 'message') {
                $first = "<span class=\"how_reacted like-btn-" . strtolower($key) . "\" id=\"_" . $col . $object_id . "\">";
            } else {
                $first = "<span class=\"how_reacted like-btn-" . strtolower($key) . "\" id=\"_" . $col . $object_id . "\" onclick=\"Wo_OpenPostReactedUsers(" . $object_id . ",'" . strtolower($key) . "','" . $col . "');\">";
            }
            if (!file_exists('./themes/' . $wo['config']['theme'] . '/reaction/like-sm.png')) {
                if ($wo['reactions_types'][$key]['is_html'] == 1) {
                    switch (strtolower($key)) {
                        case 1:
                            $reactions_html .= $first . "<div class='inline_post_count_emoji no_anim'><div class='emoji emoji--like'><div class='emoji__hand'><div class='emoji__thumb'></div></div></div></div></span>";
                            break;
                        case 2:
                            $reactions_html .= $first . "<div class='inline_post_count_emoji no_anim'><div class='emoji emoji--love'><div class='emoji__heart'></div></div></div></span>";
                            break;
                        case 3:
                            $reactions_html .= $first . "<div class='inline_post_count_emoji no_anim'><div class='emoji emoji--haha'><div class='emoji__face'><div class='emoji__eyes'></div><div class='emoji__mouth'><div class='emoji__tongue'></div></div></div></div></div></span>";
                            break;
                        case 4:
                            $reactions_html .= $first . "<div class='inline_post_count_emoji no_anim'><div class='emoji emoji--wow'><div class='emoji__face'><div class='emoji__eyebrows'></div><div class='emoji__eyes'></div><div class='emoji__mouth'></div></div></div></div></span>";
                            break;
                        case 5:
                            $reactions_html .= $first . "<div class='inline_post_count_emoji no_anim'><div class='emoji emoji--sad'><div class='emoji__face'><div class='emoji__eyebrows'></div><div class='emoji__eyes'></div><div class='emoji__mouth'></div></div></div></div></span>";
                            break;
                        case 6:
                            $reactions_html .= $first . "<div class='inline_post_count_emoji no_anim'><div class='emoji emoji--angry'><div class='emoji__face'><div class='emoji__eyebrows'></div><div class='emoji__eyes'></div><div class='emoji__mouth'></div></div></div></div></span>";
                            break;
                    }
                } else {
                    if (!empty($wo['reactions_types'][$key]['wowonder_small_icon'])) {
                        $reactions_html .= $first . "<div class='inline_post_count_emoji reaction'><img src='{$wo['reactions_types'][$key]['wowonder_small_icon']}' alt=\"" . $wo['reactions_types'][$key]['name'] . "\"></div></span>";
                    }
                }
            } else {
                if (!empty($wo['reactions_types'][$key]['sunshine_small_icon'])) {
                    $reactions_html .= $first . "<div class='inline_post_count_emoji'><img src='{$wo['reactions_types'][$key]['sunshine_small_icon']}' alt=\"" . $wo['reactions_types'][$key]['name'] . "\"></div></span>";
                }
                // switch (strtolower($key)) {
                //     case 1:
                //         $reactions_html .= $first."<div class='inline_post_count_emoji'><img src='{$wo['config']['theme_url']}/reaction/like-sm.png' alt=\"" . $wo['lang']['like'] . "\"></div></span>";
                //         break;
                //     case 2:
                //         $reactions_html .= $first."<div class='inline_post_count_emoji'><img src='{$wo['config']['theme_url']}/reaction/love-sm.png' alt=\"" . $wo['lang']['love'] . "\"></div></span>";
                //         break;
                //     case 3:
                //        $reactions_html .= $first."<div class='inline_post_count_emoji'><img src='{$wo['config']['theme_url']}/reaction/haha-sm.png' alt=\"" . $wo['lang']['haha'] . "\"></div></span>";
                //         break;
                //     case 4:
                //         $reactions_html .= $first."<div class='inline_post_count_emoji'><img src='{$wo['config']['theme_url']}/reaction/wow-sm.png' alt=\"" . $wo['lang']['wow'] . "\"></div></span>";
                //         break;
                //     case 5:
                //         $reactions_html .= $first."<div class='inline_post_count_emoji'><img src='{$wo['config']['theme_url']}/reaction/sad-sm.png' alt=\"" . $wo['lang']['sad'] . "\"></div></span>";
                //         break;
                //     case 6:
                //         $reactions_html .= $first."<div class='inline_post_count_emoji'><img src='{$wo['config']['theme_url']}/reaction/angry-sm.png' alt=\"" . $wo['lang']['angry'] . "\"></div></span>";
                //         break;
                // }
            }
            //$reactions_html .= "<span class=\"like-btn-".strtolower($key)."\" id=\"_".$col.$object_id."\" onclick=\"Wo_OpenPostReactedUsers(".$object_id.",'".strtolower($key)."');\"></span>";
        }
        if ($col != 'message') {
            return $reactions_html . "<span class=\"how_many_reacts\">" . $reactions_count . "</span>";
        } else {
            return $reactions_html;
        }
    } else {
        return "";
    }
}

function Wo_GetPostReactionsTypes($object_id, $col = "post", $type = "post")
{
    global $sqlConnect, $wo;
    if (empty($object_id) or !is_numeric($object_id) or $object_id < 1) {
        return false;
    }
    $reactions_html = "";
    $reactions = array();
    $reactions_count = 0;
    $object_id = Wo_Secure($object_id);
    if ($type == 'blog') {
        $query_one = "SELECT * FROM " . T_BLOG_REACTION . " WHERE `{$col}_id` = {$object_id}";
    } else {
        $query_one = "SELECT * FROM " . T_REACTIONS . " WHERE `{$col}_id` = {$object_id}";
    }
    //$query_one     = "SELECT * FROM " . T_REACTIONS . " WHERE `{$col}_id` = {$object_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query_one)) {
            $reactions[$fetched_data['reaction']] = 1;
            if ($wo['loggedin'] && $fetched_data['user_id'] == $wo['user']['id']) {
                $reactions['is_reacted'] = true;
                $reactions['type'] = $fetched_data['reaction'];
            }
            $reactions_count++;
        }
    }
    if (empty($reactions['is_reacted'])) {
        $reactions['is_reacted'] = false;
        $reactions['type'] = '';
    }
    $reactions['count'] = $reactions_count;
    return $reactions;
}

function Wo_AddLikes($post_id)
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($post_id) || !is_numeric($post_id) || $post_id < 1) {
        return false;
    }
    $post_id = Wo_Secure($post_id);
    $user_id = Wo_GetUserIdFromPostId($post_id);
    $page_id = 0;
    $logged_user_id = Wo_Secure($wo['user']['user_id']);
    $post = Wo_PostData($post_id);
    $text = '';
    $type2 = '';
    if (empty($user_id)) {
        $user_id = Wo_GetUserIdFromPageId($post['page_id']);
        if (empty($user_id)) {
            return false;
        }
    }
    if (isset($post['postText']) && !empty($post['postText'])) {
        $text = substr($post['postText'], 0, 10) . '..';
    }
    if (isset($post['postYoutube']) && !empty($post['postYoutube'])) {
        $type2 = 'post_youtube';
    } elseif (isset($post['postSoundCloud']) && !empty($post['postSoundCloud'])) {
        $type2 = 'post_soundcloud';
    } elseif (isset($post['postVine']) && !empty($post['postVine'])) {
        $type2 = 'post_vine';
    } elseif (isset($post['postFile']) && !empty($post['postFile'])) {
        if (strpos($post['postFile'], '_image') !== false) {
            $type2 = 'post_image';
        } else if (strpos($post['postFile'], '_video') !== false) {
            $type2 = 'post_video';
        } else if (strpos($post['postFile'], '_avatar') !== false) {
            $type2 = 'post_avatar';
        } else if (strpos($post['postFile'], '_sound') !== false) {
            $type2 = 'post_soundFile';
        } else if (strpos($post['postFile'], '_cover') !== false) {
            $type2 = 'post_cover';
        } else if (strpos($post['postFile'], '_cover') !== false) {
            $type2 = 'post_cover';
        } else {
            $type2 = 'post_file';
        }
    }
    if (Wo_IsLiked($post_id, $wo['user']['user_id']) === true) {
        $query_one = "DELETE FROM " . T_LIKES . " WHERE `post_id` = {$post_id} AND `user_id` = {$logged_user_id}";
        $query_delete_one = mysqli_query($sqlConnect, "DELETE FROM " . T_NOTIFICATION . " WHERE `post_id` = {$post_id} AND `recipient_id` = {$user_id} AND `type` = 'liked_post'");
        $delete_activity = Wo_DeleteActivity($post_id, $logged_user_id, 'liked_post');
        $sql_query_one = mysqli_query($sqlConnect, $query_one);
        if ($sql_query_one) {
            //Register point level system for unlikes
            Wo_RegisterPoint($post_id, "likes", "-");
            return 'unliked';
        }
    } else {
        if ($wo['config']['second_post_button'] == 'dislike' && Wo_IsWondered($post_id, $wo['user']['user_id'])) {
            Wo_AddWonders($post_id);
        }
        $query_two = "INSERT INTO " . T_LIKES . " (`user_id`, `post_id`) VALUES ({$logged_user_id}, {$post_id})";
        $sql_query_two = mysqli_query($sqlConnect, $query_two);
        if ($sql_query_two) {
            if ($type2 != 'post_avatar') {
                $activity_data = array(
                    'post_id' => $post_id,
                    'user_id' => $logged_user_id,
                    'post_user_id' => $user_id,
                    'activity_type' => 'liked_post'
                );
                $add_activity = Wo_RegisterActivity($activity_data);
            }
            $notification_data_array = array(
                'recipient_id' => $user_id,
                'post_id' => $post_id,
                'type' => 'liked_post',
                'text' => $text,
                'type2' => $type2,
                'url' => 'index.php?link1=post&id=' . $post_id
            );
            Wo_RegisterNotification($notification_data_array);
            //Register point level system for likes
            Wo_RegisterPoint($post_id, "likes");
            return 'liked';
        }
    }
}

function Wo_CountLikes($post_id)
{
    global $sqlConnect;
    if (empty($post_id) or !is_numeric($post_id) or $post_id < 1) {
        return false;
    }
    $post_id = Wo_Secure($post_id);
    $query_one = "SELECT COUNT(`id`) AS `likes` FROM " . T_LIKES . " WHERE `post_id` = {$post_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if ($sql_query_one) {
        if (mysqli_num_rows($sql_query_one) == 1) {
            $sql_fetch_one = mysqli_fetch_assoc($sql_query_one);
            return $sql_fetch_one['likes'];
        }
    }
    return false;
}

function Wo_IsLiked($post_id, $user_id)
{
    global $sqlConnect;
    if (empty($post_id) or !is_numeric($post_id) or $post_id < 1) {
        return false;
    }
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $post_id = Wo_Secure($post_id);
    $query_one = "SELECT `id` FROM " . T_LIKES . " WHERE `post_id` = {$post_id} AND `user_id` = {$user_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) >= 1) {
        return true;
    }
}

function Wo_IsUserPostReacted($post_id, $user_id)
{
    global $sqlConnect;
    if (empty($post_id) or !is_numeric($post_id) or $post_id < 1) {
        return false;
    }
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $post_id = Wo_Secure($post_id);
    $query_one = "SELECT `id` FROM " . T_REACTIONS . " WHERE `post_id` = {$post_id} AND `user_id` = {$user_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) >= 1) {
        return true;
    }
}

function Wo_IsCommented($post_id, $user_id)
{
    global $sqlConnect;
    if (empty($post_id) or !is_numeric($post_id) or $post_id < 1) {
        return false;
    }
    if (empty($user_id) or !is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $query_one = "SELECT `id` FROM " . T_COMMENTS . " WHERE `post_id` = {$post_id} AND `user_id` = {$user_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) >= 1) {
        return true;
    }
    return false;
}

function Wo_AddWonders($post_id)
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (!isset($post_id) or empty($post_id) or !is_numeric($post_id) or $post_id < 1) {
        return false;
    }
    $post_id = Wo_Secure($post_id);
    $user_id = Wo_GetUserIdFromPostId($post_id);
    $logged_user_id = Wo_Secure($wo['user']['user_id']);
    $post = Wo_PostData($post_id);
    if (empty($user_id)) {
        $user_id = Wo_GetUserIdFromPageId($post['page_id']);
        if (empty($user_id)) {
            return false;
        }
    }
    $text = '';
    $type2 = '';
    if (isset($post['postText']) && !empty($post['postText'])) {
        $text = substr($post['postText'], 0, 10) . '..';
    }
    if (isset($post['postYoutube']) && !empty($post['postYoutube'])) {
        $type2 = 'post_youtube';
    } elseif (isset($post['postSoundCloud']) && !empty($post['postSoundCloud'])) {
        $type2 = 'post_soundcloud';
    } elseif (isset($post['postVine']) && !empty($post['postVine'])) {
        $type2 = 'post_vine';
    } elseif (isset($post['postFile']) && !empty($post['postFile'])) {
        if (strpos($post['postFile'], '_image') !== false) {
            $type2 = 'post_image';
        } else if (strpos($post['postFile'], '_video') !== false) {
            $type2 = 'post_video';
        } else if (strpos($post['postFile'], '_avatar') !== false) {
            $type2 = 'post_avatar';
        } else if (strpos($post['postFile'], '_sound') !== false) {
            $type2 = 'post_soundFile';
        } else if (strpos($post['postFile'], '_cover') !== false) {
            $type2 = 'post_cover';
        } else {
            $type2 = 'post_file';
        }
    }
    if (Wo_IsWondered($post_id, $logged_user_id) === true) {
        $query_one = "DELETE FROM " . T_WONDERS . " WHERE `post_id` = {$post_id} AND `user_id` = {$logged_user_id}";
        $query_delete_one = mysqli_query($sqlConnect, "DELETE FROM " . T_NOTIFICATION . " WHERE `post_id` = {$post_id} AND `recipient_id` = {$user_id} AND `type` = 'wondered_post' ");
        $delete_activity = Wo_DeleteActivity($post_id, $logged_user_id, 'wondered_post');
        $sql_query_one = mysqli_query($sqlConnect, $query_one);
        if ($sql_query_one) {
            if ($wo['config']['second_post_button'] == 'dislike') {
                //Register point level system for dislikes -
                Wo_RegisterPoint($post_id, "dislikes", "-");
            } else if ($wo['config']['second_post_button'] == 'wonder') {
                //Register point level system for wonders -
                Wo_RegisterPoint($post_id, "wonders", "-");
            }
            return 'unwonder';
        }
    } else {
        if ($wo['config']['second_post_button'] == 'dislike' && Wo_IsLiked($post_id, $wo['user']['user_id'])) {
            Wo_AddLikes($post_id);
        }
        $query_two = "INSERT INTO " . T_WONDERS . " (`user_id`, `post_id`) VALUES ({$logged_user_id}, {$post_id})";
        $sql_query_two = mysqli_query($sqlConnect, $query_two);
        if ($sql_query_two) {
            if ($type2 != 'post_avatar') {
                $activity_data = array(
                    'post_id' => $post_id,
                    'user_id' => $logged_user_id,
                    'post_user_id' => $user_id,
                    'activity_type' => 'wondered_post'
                );
                $add_activity = Wo_RegisterActivity($activity_data);
            }
            $notification_data_array = array(
                'recipient_id' => $user_id,
                'post_id' => $post_id,
                'type' => 'wondered_post',
                'text' => $text,
                'type2' => $type2,
                'url' => 'index.php?link1=post&id=' . $post_id
            );
            Wo_RegisterNotification($notification_data_array);
            if ($wo['config']['second_post_button'] == 'dislike') {
                //Register point level system for dislikes +
                Wo_RegisterPoint($post_id, "dislikes");
            } else if ($wo['config']['second_post_button'] == 'wonder') {
                //Register point level system for wonders +
                Wo_RegisterPoint($post_id, "wonders");
            }
            return 'wonder';
        }
    }
}

function Wo_CountWonders($post_id)
{
    global $sqlConnect;
    if (empty($post_id) or !is_numeric($post_id) or $post_id < 1) {
        return false;
    }
    $post_id = Wo_Secure($post_id);
    $query_one = "SELECT COUNT(`id`) AS `wonders` FROM " . T_WONDERS . " WHERE `post_id` = {$post_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if ($sql_query_one) {
        if (mysqli_num_rows($sql_query_one) == 1) {
            $sql_fetch_one = mysqli_fetch_assoc($sql_query_one);
            return $sql_fetch_one['wonders'];
        }
    }
}

function Wo_IsWondered($post_id, $user_id)
{
    global $sqlConnect;
    if (empty($post_id) or !is_numeric($post_id) or $post_id < 1) {
        return false;
    }
    $post_id = Wo_Secure($post_id);
    $query_one = "SELECT `id` FROM " . T_WONDERS . " WHERE `post_id` = {$post_id} AND `user_id` = {$user_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) >= 1) {
        return true;
    }
}

function Wo_GetPostLikes($post_id = 0, $limit = 20, $offset = 0)
{
    global $sqlConnect;
    if (empty($post_id) or !is_numeric($post_id) or $post_id < 1) {
        return false;
    }
    $post_id = Wo_Secure($post_id);
    $data = array();
    $offset_query = '';
    if (!empty($offset)) {
        $offset_query = " AND `id` > '" . $offset . "'";
    }
    $query_one = "SELECT `id`,`user_id` FROM " . T_LIKES . " WHERE `post_id` = {$post_id} {$offset_query} ORDER BY `id` ASC LIMIT {$limit}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query_one)) {
            $user_data = Wo_UserData($fetched_data['user_id']);
            $user_data['row_id'] = $fetched_data['id'];
            $data[] = $user_data;
        }
    }
    return $data;
}

function Wo_GetPostCommentLikes($comment_id = 0, $limit = 20, $offset = 0)
{
    global $sqlConnect;
    if (empty($comment_id) or !is_numeric($comment_id) or $comment_id < 1) {
        return false;
    }
    $comment_id = Wo_Secure($comment_id);
    $data = array();
    $offset_query = '';
    if (!empty($offset)) {
        $offset_query = " AND `id` > '" . $offset . "'";
    }
    $query_one = "SELECT `id`,`user_id` FROM " . T_COMMENT_LIKES . " WHERE `comment_id` = {$comment_id} {$offset_query} ORDER BY `id` ASC LIMIT {$limit}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query_one)) {
            $user_data = Wo_UserData($fetched_data['user_id']);
            $user_data['row_id'] = $fetched_data['id'];
            $data[] = $user_data;
        }
    }
    return $data;
}

function Wo_GetPostCommentReplyLikes($reply_id = 0, $limit = 20, $offset = 0)
{
    global $sqlConnect;
    if (empty($reply_id) or !is_numeric($reply_id) or $reply_id < 1) {
        return false;
    }
    $reply_id = Wo_Secure($reply_id);
    $data = array();
    $offset_query = '';
    if (!empty($offset)) {
        $offset_query = " AND `id` > '" . $offset . "'";
    }
    $query_one = "SELECT `id`,`user_id` FROM " . T_COMMENT_REPLIES_LIKES . " WHERE `reply_id` = {$reply_id} {$offset_query} ORDER BY `id` ASC LIMIT {$limit}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query_one)) {
            $user_data = Wo_UserData($fetched_data['user_id']);
            $user_data['row_id'] = $fetched_data['id'];
            $data[] = $user_data;
        }
    }
    return $data;
}

function Wo_GetPostCommentWonders($comment_id = 0, $limit = 20, $offset = 0)
{
    global $sqlConnect;
    if (empty($comment_id) or !is_numeric($comment_id) or $comment_id < 1) {
        return false;
    }
    $comment_id = Wo_Secure($comment_id);
    $data = array();
    $offset_query = '';
    if (!empty($offset)) {
        $offset_query = " AND `id` > '" . $offset . "'";
    }
    $query_one = "SELECT `id`,`user_id` FROM " . T_COMMENT_WONDERS . " WHERE `comment_id` = {$comment_id} {$offset_query} ORDER BY `id` ASC LIMIT {$limit}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query_one)) {
            $user_data = Wo_UserData($fetched_data['user_id']);
            $user_data['row_id'] = $fetched_data['id'];
            $data[] = $user_data;
        }
    }
    return $data;
}

function Wo_GetPostCommentReplyWonders($reply_id = 0, $limit = 20, $offset = 0)
{
    global $sqlConnect;
    if (empty($reply_id) or !is_numeric($reply_id) or $reply_id < 1) {
        return false;
    }
    $reply_id = Wo_Secure($reply_id);
    $data = array();
    $offset_query = '';
    if (!empty($offset)) {
        $offset_query = " AND `id` > '" . $offset . "'";
    }
    $query_one = "SELECT `id`,`user_id` FROM " . T_COMMENT_REPLIES_WONDERS . " WHERE `reply_id` = {$reply_id} {$offset_query} ORDER BY `id` ASC LIMIT {$limit}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query_one)) {
            $user_data = Wo_UserData($fetched_data['user_id']);
            $user_data['row_id'] = $fetched_data['id'];
            $data[] = $user_data;
        }
    }
    return $data;
}

function Wo_GetPostShared($post_id = 0, $limit = 20, $offset = 0)
{
    global $sqlConnect;
    if (empty($post_id) or !is_numeric($post_id) or $post_id < 1) {
        return false;
    }
    $post_id = Wo_Secure($post_id);
    $data = array();
    $offset_query = '';
    if (!empty($offset)) {
        $offset_query = " AND `id` > '" . $offset . "'";
    }
    $query_one = "SELECT * FROM " . T_POSTS . " WHERE `parent_id` = {$post_id} {$offset_query} ORDER BY `id` ASC LIMIT {$limit}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query_one)) {
            if (!empty($fetched_data['page_id'])) {
                $page = Wo_PageData($fetched_data['page_id']);
                $user_data = Wo_UserData($fetched_data['user_id']);
                $user_data['row_id'] = $fetched_data['id'];
                $data[] = $user_data;
            } else {
                $user_data = Wo_UserData($fetched_data['user_id']);
                $user_data['row_id'] = $fetched_data['id'];
                $data[] = $user_data;
            }
        }
    }
    return $data;
}

function Wo_GetPostReactionUsers($post_id = 0, $type = "1", $limit = 20, $offset = 0, $col = 'post')
{
    global $sqlConnect;
    if (empty($post_id) or !is_numeric($post_id) or $post_id < 1) {
        return false;
    }
    $post_id = Wo_Secure($post_id);
    $data = array();
    $offset_query = '';
    if (!empty($offset)) {
        $offset_query = " AND `id` > '" . $offset . "'";
    }
    $query_one = "SELECT `id`,`user_id`,`reaction` FROM " . T_REACTIONS . " WHERE `{$col}_id` = {$post_id} AND `reaction` = '" . $type . "' {$offset_query} ORDER BY `id` ASC LIMIT {$limit}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query_one)) {
            //if( strtolower( $fetched_data['reaction'] ) == $type ){
            $ud = Wo_UserData($fetched_data['user_id']);
            $ud['reaction'] = $fetched_data['reaction'];
            $ud['row_id'] = $fetched_data['id'];
            $data[] = $ud;
            //}
        }
    }
    return $data;
}

function Wo_GetPostWonders($post_id = 0, $limit = 20, $offset = 0)
{
    global $sqlConnect;
    if (empty($post_id) or !is_numeric($post_id) or $post_id < 1) {
        return false;
    }
    $post_id = Wo_Secure($post_id);
    $data = array();
    $offset_query = '';
    if (!empty($offset)) {
        $offset_query = " AND `id` > '" . $offset . "'";
    }
    $query_one = "SELECT `id`,`user_id` FROM " . T_WONDERS . " WHERE `post_id` = {$post_id} {$offset_query} ORDER BY `id` ASC LIMIT {$limit}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query_one)) {
            $user_data = Wo_UserData($fetched_data['user_id']);
            $user_data['row_id'] = $fetched_data['id'];
            $data[] = $user_data;
        }
    }
    return $data;
}

function Wo_AddShare($post_id = 0)
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] !== true) {
        return false;
    }
    if (!isset($post_id) or empty($post_id) or !is_numeric($post_id) or $post_id < 1) {
        return false;
    }
    $post_id = Wo_Secure($post_id);
    $user_id = Wo_GetUserIdFromPostId($post_id);
    $logged_user_id = Wo_Secure($wo['user']['user_id']);
    $post = Wo_PostData($post_id);
    if (empty($user_id)) {
        $user_id = Wo_GetUserIdFromPageId($post['page_id']);
        if (empty($user_id)) {
            return false;
        }
    }
    $text = '';
    $type2 = '';
    if (isset($post['postText']) && !empty($post['postText'])) {
        $text = substr($post['postText'], 0, 10) . '..';
    }
    if (isset($post['postYoutube']) && !empty($post['postYoutube'])) {
        $type2 = 'post_youtube';
    } elseif (isset($post['postSoundCloud']) && !empty($post['postSoundCloud'])) {
        $type2 = 'post_soundcloud';
    } elseif (isset($post['postVine']) && !empty($post['postVine'])) {
        $type2 = 'post_vine';
    } elseif (isset($post['postFile']) && !empty($post['postFile'])) {
        if (strpos($post['postFile'], '_image') !== false) {
            $type2 = 'post_image';
        } else if (strpos($post['postFile'], '_video') !== false) {
            $type2 = 'post_video';
        } else if (strpos($post['postFile'], '_avatar') !== false) {
            $type2 = 'post_avatar';
        } else if (strpos($post['postFile'], '_sound') !== false) {
            $type2 = 'post_soundFile';
        } else if (strpos($post['postFile'], '_cover') !== false) {
            $type2 = 'post_cover';
        } else {
            $type2 = 'post_file';
        }
    }
    if (Wo_IsShared($post_id, $logged_user_id)) {
        $query_one = "DELETE FROM " . T_POSTS . " WHERE `post_id` = {$post_id} AND `user_id` = {$logged_user_id} AND `postShare` = 1";
        $query_delete_one = mysqli_query($sqlConnect, "DELETE FROM " . T_NOTIFICATION . " WHERE `post_id` = {$post_id} AND `recipient_id` = {$user_id} AND `type` = 'share_post'");
        $delete_activity = Wo_DeleteActivity($post_id, $logged_user_id, 'shared_post');
        $sql_query_one = mysqli_query($sqlConnect, $query_one);
        if ($sql_query_one) {
            return 'unshare';
        }
    } else {
        $query_two = "INSERT INTO " . T_POSTS . " (`user_id`, `post_id`, `time`, `postShare`) VALUES ({$logged_user_id}, {$post_id}, " . time() . ", 1)";
        $sql_query_two = mysqli_query($sqlConnect, $query_two);
        $inserted_post_id = mysqli_insert_id($sqlConnect);
        if ($sql_query_two) {
            if ($type2 != 'post_avatar') {
                $activity_data = array(
                    'post_id' => $post_id,
                    'user_id' => $logged_user_id,
                    'post_user_id' => $user_id,
                    'activity_type' => 'shared_post'
                );
                $add_activity = Wo_RegisterActivity($activity_data);
            }
            $notification_data_array = array(
                'recipient_id' => $user_id,
                'post_id' => $post_id,
                'type' => 'share_post',
                'text' => $text,
                'type2' => $type2,
                'url' => 'index.php?link1=post&id=' . $inserted_post_id
            );
            Wo_RegisterNotification($notification_data_array);
            return 'share';
        }
    }
}

function Wo_CountShares($post_id = 0)
{
    global $sqlConnect;
    if (empty($post_id) or !is_numeric($post_id) or $post_id < 1) {
        return false;
    }
    $post_id = Wo_Secure($post_id);
    $query_one = "SELECT COUNT(`id`) AS `shares` FROM " . T_POSTS . " WHERE `post_id` = {$post_id} AND `postShare` = 1";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if ($sql_query_one) {
        if (mysqli_num_rows($sql_query_one) == 1) {
            $sql_fetch_one = mysqli_fetch_assoc($sql_query_one);
            return $sql_fetch_one['shares'];
        }
    }
}

function Wo_IsShared($post_id, $user_id)
{
    global $sqlConnect;
    if (empty($post_id) or !is_numeric($post_id) or $post_id < 1) {
        return false;
    }
    $post_id = Wo_Secure($post_id);
    $query_one = "SELECT `id` FROM " . T_POSTS . " WHERE `post_id`= {$post_id} AND `postShare` = 1 AND `user_id` = {$user_id}";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($sql_query_one) >= 1) {
        return true;
    }
}

function Wo_RegisterPostComment($data = array())
{
    global $sqlConnect, $wo, $db;
    if (empty($data['post_id']) || !is_numeric($data['post_id']) || $data['post_id'] < 0) {
        return false;
    }
    if (empty($data['text']) && empty($data['c_file']) && empty($data['record'])) {
        return false;
    }
    if (empty($data['user_id']) || !is_numeric($data['user_id']) || $data['user_id'] < 0) {
        return false;
    }
    if (!empty($data['page_id'])) {
        if (Wo_IsPageOnwer($data['page_id']) === false) {
            $data['page_id'] = 0;
        }
    }
    $getPost = Wo_PostData($data['post_id']);
    if ($getPost['comments_status'] == 0) {
        return false;
    }
    if (!empty($data['text'])) {
        if ($wo['config']['maxCharacters'] > 0 && 10000 > $wo['config']['maxCharacters']) {
            if (mb_strlen($data['text']) - 10 > $wo['config']['maxCharacters']) {
                return false;
            }
        }
        $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
        $i = 0;
        preg_match_all($link_regex, $data['text'], $matches);
        foreach ($matches[0] as $match) {
            $match_url = strip_tags($match);
            $syntax = '[a]' . urlencode($match_url) . '[/a]';
            $data['text'] = str_replace($match, $syntax, $data['text']);
        }
        $mention_regex = '/@([A-Za-z0-9_]+)/i';
        preg_match_all($mention_regex, $data['text'], $matches);
        foreach ($matches[1] as $match) {
            $match = Wo_Secure($match);
            $match_user = Wo_UserData(Wo_UserIdFromUsername($match));
            $match_search = '@' . $match;
            $match_replace = '@[' . $match_user['user_id'] . ']';
            if (isset($match_user['user_id'])) {
                $data['text'] = str_replace($match_search, $match_replace, $data['text']);
                $mentions[] = $match_user['user_id'];
            }
        }
    }
    $hashtag_regex = '/#([^`~!@$%^&*\#()\-+=\\|\/\.,<>?\'\":;{}\[\]* ]+)/i';
    preg_match_all($hashtag_regex, $data['text'], $matches);
    foreach ($matches[1] as $match) {
        if (!is_numeric($match)) {
            $hashdata = Wo_GetHashtag($match);
            if (is_array($hashdata)) {
                $match_search = '#' . $match;
                $match_replace = '#[' . $hashdata['id'] . ']';
                if (mb_detect_encoding($match_search, 'ASCII', true)) {
                    $data['text'] = preg_replace("/$match_search\b/i", $match_replace, $data['text']);
                } else {
                    $data['text'] = str_replace($match_search, $match_replace, $data['text']);
                }
                //$data['text']      = preg_replace("/$match_search\b/i", $match_replace,  $data['text']);
                //$data['text']      = str_replace($match_search, $match_replace, $data['text']);
                // $hashtag_query     = "UPDATE " . T_HASHTAGS . " SET `last_trend_time` = " . time() . ", `trend_use_num` = " . ($hashdata['trend_use_num'] + 1) . " WHERE `id` = " . $hashdata['id'];
                // $hashtag_sql_query = mysqli_query($sqlConnect, $hashtag_query);
            }
        }
    }
    $post = Wo_PostData($data['post_id']);
    $text = '';
    $type2 = '';
    $page_id = 0;
    if (!empty($post['page_id']) && $post['page_id'] > 0) {
        $page_id = $post['page_id'];
    }
    if (isset($post['postText']) && !empty($post['postText'])) {
        $text = substr($post['postText'], 0, 10) . '..';
    }
    if (isset($post['postYoutube']) && !empty($post['postYoutube'])) {
        $type2 = 'post_youtube';
    } elseif (isset($post['postSoundCloud']) && !empty($post['postSoundCloud'])) {
        $type2 = 'post_soundcloud';
    } elseif (isset($post['postVine']) && !empty($post['postVine'])) {
        $type2 = 'post_vine';
    } elseif (isset($post['postFile']) && !empty($post['postFile'])) {
        if (strpos($post['postFile'], '_image') !== false) {
            $type2 = 'post_image';
        } else if (strpos($post['postFile'], '_video') !== false) {
            $type2 = 'post_video';
        } else if (strpos($post['postFile'], '_avatar') !== false) {
            $type2 = 'post_avatar';
        } else if (strpos($post['postFile'], '_sound') !== false) {
            $type2 = 'post_soundFile';
        } else if (strpos($post['postFile'], '_cover') !== false) {
            $type2 = 'post_cover';
        } else if ($post['postType'] == 'live') {
            $type2 = 'post_video';
        } else {
            $type2 = 'post_file';
        }
    }
    $user_id = Wo_GetUserIdFromPostId($data['post_id']);
    if (empty($user_id)) {
        $user_id = Wo_GetUserIdFromPageId($post['page_id']);
        if (empty($user_id)) {
            return false;
        }
    }
    if (empty($data['page_id'])) {
        $data['page_id'] = 0;
    }
    $fields = '`' . implode('`, `', array_keys($data)) . '`';
    $comment_data = '\'' . implode('\', \'', $data) . '\'';
    $check_if_comment_is_spam = $db->where('text', $data['text'])->where('time', (time() - 3600), ">")->getValue(T_COMMENTS, "COUNT(*)");
    if ($check_if_comment_is_spam >= 5) {
        return false;
    }
    $check_last_comment_exists = $db->where('text', $data['text'])->where('user_id', $data['user_id'])->where('post_id', $data['post_id'])->getValue(T_COMMENTS, "COUNT(*)");
    if ($check_last_comment_exists >= 2) {
        return false;
    }
    // $check_last_comment = $db->where('user_id', $data['user_id'])->where('post_id', $data['post_id'])->where('time', (time() - 3600), ">=")->getValue(T_COMMENTS, "COUNT(*)");
    // if ($check_last_comment >= 5) {
    //     return false;
    // }
    $query = mysqli_query($sqlConnect, "INSERT INTO  " . T_COMMENTS . " ({$fields}) VALUES ({$comment_data})");
    if ($query) {
        $inserted_comment_id = mysqli_insert_id($sqlConnect);
        $activity_data = array(
            'post_id' => $data['post_id'],
            'user_id' => $data['user_id'],
            'post_user_id' => $user_id,
            'activity_type' => 'commented_post'
        );
        $add_activity = Wo_RegisterActivity($activity_data);
        $notification_data_array = array(
            'recipient_id' => $user_id,
            'post_id' => $data['post_id'],
            'type' => 'comment',
            'text' => $text,
            'type2' => $type2,
            'url' => 'index.php?link1=post&id=' . $data['post_id'] . '&ref=' . $inserted_comment_id
        );
        Wo_RegisterNotification($notification_data_array);
        if (isset($mentions) && is_array($mentions)) {
            foreach ($mentions as $mention) {
                $notification_data_array = array(
                    'recipient_id' => $mention,
                    'type' => 'comment_mention',
                    'post_id' => $data['post_id'],
                    'page_id' => $page_id,
                    'url' => 'index.php?link1=post&id=' . $data['post_id']
                );
                Wo_RegisterNotification($notification_data_array);
            }
        }
        //Register point level system for comments
        if ($getPost['user_id'] != $wo['user']['id']) {
            Wo_RegisterPoint(Wo_Secure($data['post_id']), "comments");
        }
        return $inserted_comment_id;
    }
}

function Wo_GetGroupsListAPP($fetch_array = array())
{
    global $sqlConnect, $wo;
    if ($wo['loggedin'] == false) {
        return false;
    }
    $user = Wo_Secure($wo['user']['id']);
    $data = array();
    $offset_query = "";
    $limit = 20;
    if (!empty($fetch_array['offset'])) {
        $offset_query = " AND `time` < " . $fetch_array['offset'];
    }
    if (!empty($fetch_array['limit'])) {
        $limit = Wo_Secure($fetch_array['limit']);
    }
    if (!empty($fetch_array['type'])) {
        $offset_query = " AND `type` = '" . $fetch_array['type'] . "'";
    }
    $sql = "SELECT * FROM " . T_GROUP_CHAT . "
                WHERE (`user_id` = {$user} OR `group_id` IN
                   (SELECT `group_id` FROM Wo_GroupChatUsers  WHERE `user_id` = {$user} AND `active` = 1)) {$offset_query}  ORDER BY `time` DESC LIMIT {$limit}";
    $query = mysqli_query($sqlConnect, $sql);
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $fetched_data['user_data'] = Wo_UserData($fetched_data['user_id']);
            $fetched_data['owner'] = ($fetched_data['user_id'] == $user) ? true : false;
            $fetched_data['last_message'] = Wo_GetChatGroupLastMessage($fetched_data['group_id']);
            $fetched_data['parts'] = Wo_GetGChatMemebers($fetched_data['group_id']);
            $fetched_data['avatar'] = Wo_GetMedia($fetched_data['avatar']);
            $fetched_data['last_seen'] = Wo_CheckLastGroupAction();
            if (!empty($fetched_data['time'])) {
                $fetched_data['chat_time'] = $fetched_data['time'];
            }
            $fetched_data['chat_id'] = $fetched_data['group_id'];
            $data[] = $fetched_data;
        }
    }
    return $data;
    // else {
    //        $query_one = "SELECT * FROM " . T_U_CHATS . " WHERE `user_id` = '$user_id' AND (`conversation_user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$user_id}') AND `conversation_user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$user_id}')) {$offset_query}  ORDER BY `time` DESC";
    //    }
    //    if (!empty($fetch_array['limit'])) {
    //        $limit = Wo_Secure($fetch_array['limit']);
    //        $query_one .= " LIMIT {$limit}";
    //    }
    //    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    //    if (mysqli_num_rows($sql_query_one) > 0) {
    //        while ($sql_fetch_one = mysqli_fetch_assoc($sql_query_one)) {
    //            $new_data = Wo_UserData($sql_fetch_one['conversation_user_id']);
    //            $new_data['chat_time'] = $sql_fetch_one['time'];
    //            $data[] = $new_data;
    //        }
    //    }
    //    return $data;
}

function Wo_GetPostCommentsSort($post_id = 0, $limit = 5, $type = 'latest')
{
    global $sqlConnect, $wo;
    if (empty($post_id) || !is_numeric($post_id) || $post_id < 0) {
        return false;
    }
    if ($wo['loggedin'] == false) {
        return false;
    }
    $logged_user_id = Wo_Secure($wo['user']['user_id']);
    $post_id = Wo_Secure($post_id);
    $data = array();
    if ($type == 'top') {
        if ($wo['config']['second_post_button'] == 'reaction') {
            $query = "SELECT `id` FROM " . T_COMMENTS . " WHERE `post_id` = {$post_id} AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}') ORDER BY `id` ASC";
            $query_one = mysqli_query($sqlConnect, $query);
            $ids = array();
            if (mysqli_num_rows($query_one)) {
                while ($fetched_data = mysqli_fetch_assoc($query_one)) {
                    $ids[] = $fetched_data['id'];
                }
            }
            $ids_line = implode(',', $ids);
            $query = "SELECT COUNT(*) AS count,`comment_id` AS id FROM " . T_REACTIONS . " WHERE `comment_id` IN ({$ids_line}) AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}') GROUP BY `comment_id` ORDER BY count DESC";
        } else {
            $query = "SELECT COUNT(*) AS count,`comment_id` AS id FROM " . T_COMMENT_LIKES . " WHERE `post_id` = {$post_id} AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}') GROUP BY `comment_id` ORDER BY count DESC";
        }
    } else {
        $query = "SELECT `id` FROM " . T_COMMENTS . " WHERE `post_id` = {$post_id} AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}') ORDER BY `id` ASC";
    }
    $query_one = mysqli_query($sqlConnect, $query);
    if (mysqli_num_rows($query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($query_one)) {
            $data[] = Wo_GetPostComment($fetched_data['id']);
        }
    }
    return $data;
}

function Wo_GetPostCommentsLimited($post_id = 0, $comment_id = 0)
{
    global $sqlConnect, $wo;
    if (empty($post_id) || !is_numeric($post_id) || $post_id < 0) {
        return false;
    }
    // if ($wo['loggedin'] == false) {
    //     return false;
    // }
    $logged_user_id = Wo_Secure($wo['user']['user_id']);
    $post_id = Wo_Secure($post_id);
    $data = array();
    $max = $comment_id + 3;
    $query = "SELECT `id` FROM " . T_COMMENTS . " WHERE `id` >= {$comment_id} AND `id` < {$max} AND `post_id` = {$post_id} AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}') ORDER BY `id` ASC";
    $query_one = mysqli_query($sqlConnect, $query);
    if (mysqli_num_rows($query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($query_one)) {
            $data[] = Wo_GetPostComment($fetched_data['id']);
        }
    }
    return $data;
}

function Wo_GetPostComments($post_id = 0, $limit = 5, $offset = 0)
{
    global $sqlConnect, $wo;
    if (empty($post_id) || !is_numeric($post_id) || $post_id < 0) {
        return false;
    }
    // if ($wo['loggedin'] == false) {
    //     return false;
    // }
    $offset_query = "";
    if (!empty($offset)) {
        $offset_query = " AND `id` > " . $offset;
    }
    $logged_user_id = 0;
    if ($wo['loggedin']) {
        $logged_user_id = Wo_Secure($wo['user']['user_id']);
    }
    $post_id = Wo_Secure($post_id);
    $data = array();
    $query = "SELECT `id` FROM " . T_COMMENTS . " WHERE `post_id` = {$post_id} AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}') {$offset_query} ORDER BY `id` ASC";
    if (($comments_num = Wo_CountPostComment($post_id)) > $limit) {
        //$query .= " LIMIT " . ($comments_num - $limit) . ", {$limit} ";
        $query .= " LIMIT {$limit} ";
    }
    $query_one = mysqli_query($sqlConnect, $query);
    if (mysqli_num_rows($query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($query_one)) {
            $data[] = Wo_GetPostComment($fetched_data['id']);
        }
    }
    return $data;
}

// API
function Wo_GetPostCommentsAPI($post_id = 0, $limit = 5, $offset = 0)
{
    global $sqlConnect, $wo;
    if (empty($post_id) || !is_numeric($post_id) || $post_id < 0) {
        return false;
    }
    if ($wo['loggedin'] == false) {
        return false;
    }
    $offset_query = "";
    if (!empty($offset)) {
        $offset_query = " AND `id` > " . $offset;
    }
    $logged_user_id = Wo_Secure($wo['user']['user_id']);
    $post_id = Wo_Secure($post_id);
    $data = array();
    $query = "SELECT `id` FROM " . T_COMMENTS . " WHERE `post_id` = {$post_id} AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}') {$offset_query} ORDER BY `id` ASC";
    if (($comments_num = Wo_CountPostComment($post_id)) > $limit) {
        $query .= " LIMIT {$limit} ";
    }
    $query_one = mysqli_query($sqlConnect, $query);
    if (mysqli_num_rows($query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($query_one)) {
            $data[] = Wo_GetPostComment($fetched_data['id']);
        }
    }
    return $data;
}

function Wo_GetCommentRepliesAPI($comment_id = 0, $limit = 5, $order_by = 'ASC', $offset = 0)
{
    global $sqlConnect, $wo;
    if (empty($comment_id) || !is_numeric($comment_id) || $comment_id < 0) {
        return false;
    }
    if ($wo['loggedin'] == false) {
        return false;
    }
    $offset_query = "";
    if (!empty($offset)) {
        $offset_query = " AND `id` > " . $offset;
    }
    $logged_user_id = Wo_Secure($wo['user']['user_id']);
    $comment_id = Wo_Secure($comment_id);
    $data = array();
    $query = "SELECT `id` FROM " . T_COMMENTS_REPLIES . " WHERE `comment_id` = {$comment_id} AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$logged_user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$logged_user_id}') {$offset_query} ORDER BY `id` {$order_by}";
    if (($comments_num = Wo_CountCommentReplies($comment_id)) > $limit) {
        $query .= " LIMIT {$limit} ";
    }
    $query_one = mysqli_query($sqlConnect, $query);
    if (mysqli_num_rows($query_one)) {
        while ($fetched_data = mysqli_fetch_assoc($query_one)) {
            $data[] = Wo_GetCommentReply($fetched_data['id']);
        }
    }
    return $data;
}

// API
function Wo_GetPostComment($comment_id = 0)
{
    global $wo, $sqlConnect;
    if (empty($comment_id) || !is_numeric($comment_id) || $comment_id < 0) {
        return false;
    }
    $query_one = mysqli_query($sqlConnect, "SELECT * FROM " . T_COMMENTS . " WHERE `id` = {$comment_id} ");
    if (mysqli_num_rows($query_one)) {
        $fetched_data = mysqli_fetch_assoc($query_one);
        if (!empty($fetched_data['page_id'])) {
            $fetched_data['publisher'] = Wo_PageData($fetched_data['page_id']);
            $fetched_data['url'] = Wo_SeoLink('index.php?link1=timeline&u=' . $fetched_data['publisher']['page_name']);
            if ($fetched_data['publisher']['user_id'] != $fetched_data['user_id'] && !Wo_IsPageAdminExists($fetched_data['user_id'], $fetched_data['page_id'])) {
                $fetched_data['publisher'] = Wo_UserData($fetched_data['user_id']);
                $fetched_data['url'] = Wo_SeoLink('index.php?link1=timeline&u=' . $fetched_data['publisher']['username']);
            }
        } else {
            $fetched_data['publisher'] = Wo_UserData($fetched_data['user_id']);
            $fetched_data['url'] = Wo_SeoLink('index.php?link1=timeline&u=' . $fetched_data['publisher']['username']);
        }
        $fetched_data['fullurl'] = Wo_SeoLink("index.php?link1=post&id=" . $fetched_data['post_id'] . "&ref=" . $comment_id);
        $fetched_data['Orginaltext'] = Wo_EditMarkup($fetched_data['text'], true, true, true, 0, $comment_id);
        $fetched_data['Orginaltext'] = str_replace('<br>', "\n", $fetched_data['Orginaltext']);
        $fetched_data['text'] = Wo_Markup($fetched_data['text'], true, true, true, 0, $comment_id);
        $fetched_data['text'] = Wo_Emo($fetched_data['text']);
        $fetched_data['onwer'] = false;
        $fetched_data['post_onwer'] = false;
        $fetched_data['comment_likes'] = Wo_CountCommentLikes($fetched_data['id']);
        $fetched_data['comment_wonders'] = Wo_CountCommentWonders($fetched_data['id']);
        $fetched_data['is_comment_wondered'] = false;
        $fetched_data['is_comment_liked'] = false;
        if ($wo['loggedin'] == true) {
            $fetched_data['onwer'] = ($fetched_data['publisher']['user_id'] == $wo['user']['user_id']) ? true : false;
            $fetched_data['post_onwer'] = (Wo_IsPostOnwer($fetched_data['post_id'], $wo['user']['user_id'])) ? true : false;
            $fetched_data['is_comment_wondered'] = (Wo_IsCommentWondered($fetched_data['id'], $wo['user']['user_id'])) ? true : false;
            $fetched_data['is_comment_liked'] = (Wo_IsCommentLiked($fetched_data['id'], $wo['user']['user_id'])) ? true : false;
        }
        if ($wo['config']['second_post_button'] == 'reaction') {
            $fetched_data['reaction'] = Wo_GetPostReactionsTypes($fetched_data['id'], 'comment');
        }
        $fetched_data['replies_count'] = Wo_CountCommentReplies($fetched_data['id']);
        return $fetched_data;
    }
    return false;
}

function Wo_CountPostComment($post_id = '')
{
    global $sqlConnect;
    if (empty($post_id) || !is_numeric($post_id) || $post_id < 0) {
        return false;
    }
    $query = mysqli_query($sqlConnect, "SELECT COUNT(`id`) AS `comments` FROM " . T_COMMENTS . " WHERE `post_id` = {$post_id} ");
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        return $fetched_data['comments'];
    }
    return false;
}

function Wo_CountUserPostComment($post_id = '', $user_id = '')
{
    global $sqlConnect;
    if (empty($post_id) || !is_numeric($post_id) || $post_id < 0) {
        return false;
    }
    $query = mysqli_query($sqlConnect, "SELECT COUNT(`id`) AS `comments` FROM " . T_COMMENTS . " WHERE `post_id` = {$post_id} AND `user_id` = {$user_id} ");
    if (mysqli_num_rows($query)) {
        $fetched_data = mysqli_fetch_assoc($query);
        return $fetched_data['comments'];
    }
    return false;
}

function Wo_DeletePostComment($comment_id = '')
{
    global $wo, $sqlConnect;
    if ($comment_id < 0 || empty($comment_id) || !is_numeric($comment_id)) {
        return false;
    }
    if ($wo['loggedin'] == false) {
        return false;
    }
    $logged_user_id = Wo_Secure($wo['user']['user_id']);
    $post_id = Wo_GetPostIdFromCommentId($comment_id);
    $query_one = mysqli_query($sqlConnect, "SELECT `id`, `user_id`, `c_file` FROM " . T_COMMENTS . " WHERE `id` = {$comment_id} AND `user_id` = {$logged_user_id}");
    if (mysqli_num_rows($query_one) > 0 || Wo_IsPostOnwer($post_id, $logged_user_id) === true || Wo_IsAdmin()) {
        if ($query_one) {
            $query_img = mysqli_fetch_assoc($query_one);
            if (!empty($query_img['c_file'])) {
                @unlink($query_img['c_file']);
            }
        }
        if (mysqli_num_rows($query_one) > 0) {
            Wo_RegisterPoint($post_id, "comments", "-");
        }
        $query_delete = mysqli_query($sqlConnect, "DELETE FROM " . T_COMMENTS . " WHERE `id` = {$comment_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_COMMENT_LIKES . " WHERE `comment_id` = {$comment_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_COMMENT_WONDERS . " WHERE `comment_id` = {$comment_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_REACTIONS . " WHERE `comment_id` = '{$comment_id}'");
        if ($query_delete) {
            $query_two = mysqli_query($sqlConnect, "SELECT `id` FROM " . T_COMMENTS_REPLIES . " WHERE `comment_id` = {$comment_id}");
            if ($query_two) {
                while ($fetched_data = mysqli_fetch_assoc($query_two)) {
                    Wo_DeleteCommentReply($fetched_data['id']);
                }
            }
            $delete_activity = Wo_DeleteActivity($post_id, $logged_user_id, 'commented_post');
            $delete_reports = mysqli_query($sqlConnect, "DELETE FROM " . T_REPORTS . " WHERE `comment_id` = {$comment_id}");
            return true;
        }
    } else {
        return false;
    }
}

function Wo_DeletePostReplyComment($comment_id = '')
{
    global $wo, $sqlConnect;
    if ($comment_id < 0 || empty($comment_id) || !is_numeric($comment_id)) {
        return false;
    }
    if ($wo['loggedin'] == false) {
        return false;
    }
    $logged_user_id = Wo_Secure($wo['user']['user_id']);
    $query_one = mysqli_query($sqlConnect, "SELECT `id`, `user_id`,`c_file` FROM " . T_COMMENTS_REPLIES . " WHERE `id` = {$comment_id} AND `user_id` = {$logged_user_id}");
    if (mysqli_num_rows($query_one) > 0 || Wo_IsAdmin()) {
        if ($query_one) {
            $query_img = mysqli_fetch_assoc($query_one);
            if (!empty($query_img['c_file'])) {
                @unlink($query_img['c_file']);
            }
        }
        $query_delete = mysqli_query($sqlConnect, "DELETE FROM " . T_COMMENTS_REPLIES . " WHERE `id` = {$comment_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_REACTIONS . " WHERE `replay_id` = '{$comment_id}'");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_COMMENT_REPLIES_WONDERS . " WHERE `reply_id` = {$comment_id}");
        $query_delete .= mysqli_query($sqlConnect, "DELETE FROM " . T_COMMENT_REPLIES_LIKES . " WHERE `reply_id` = {$comment_id}");
        return true;
    } else {
        return false;
    }
}

function Wo_UpdateComment($data = array())
{
    global $wo, $sqlConnect;
    if ($data['comment_id'] < 0 || empty($data['comment_id']) || !is_numeric($data['comment_id'])) {
        return false;
    }
    if (empty($data['text'])) {
        return false;
    }
    if ($wo['loggedin'] == false) {
        return false;
    }
    $page_id = 0;
    if (!empty($data['page_id'])) {
        $page_id = Wo_Secure($data['page_id']);
    }
    $user_id = Wo_Secure($wo['user']['user_id']);
    $comment_id = Wo_Secure($data['comment_id']);
    $comment_text = Wo_Secure($data['text'], 1);
    $query = mysqli_query($sqlConnect, "SELECT `id`, `user_id` FROM " . T_COMMENTS . " WHERE `id` = {$comment_id} AND `user_id` = {$user_id}");
    if (mysqli_num_rows($query) > 0) {
        if (!empty($comment_text)) {
            if ($wo['config']['maxCharacters'] > 0) {
                if (strlen($data['text']) > $wo['config']['maxCharacters']) {
                    return false;
                }
            }
            $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
            $i = 0;
            preg_match_all($link_regex, $comment_text, $matches);
            foreach ($matches[0] as $match) {
                $match_url = strip_tags($match);
                $syntax = '[a]' . urlencode($match_url) . '[/a]';
                $comment_text = str_replace($match, $syntax, $comment_text);
            }
            $mention_regex = '/@([A-Za-z0-9_]+)/i';
            preg_match_all($mention_regex, $comment_text, $matches);
            foreach ($matches[1] as $match) {
                $match = Wo_Secure($match);
                $match_user = Wo_UserData(Wo_UserIdFromUsername($match));
                $match_search = '@' . $match;
                $match_replace = '@[' . $match_user['user_id'] . ']';
                if (isset($match_user['user_id'])) {
                    $comment_text = str_replace($match_search, $match_replace, $comment_text);
                    $mentions[] = $match_user['user_id'];
                }
            }
        }
        $hashtag_regex = '/#([^`~!@$%^&*\#()\-+=\\|\/\.,<>?\'\":;{}\[\]* ]+)/i';
        preg_match_all($hashtag_regex, $comment_text, $matches);
        foreach ($matches[1] as $match) {
            if (!is_numeric($match)) {
                $hashdata = Wo_GetHashtag($match);
                if (is_array($hashdata)) {
                    $match_search = '#' . $match;
                    $match_replace = '#[' . $hashdata['id'] . ']';
                    if (mb_detect_encoding($match_search, 'ASCII', true)) {
                        $comment_text = preg_replace("/$match_search\b/i", $match_replace, $comment_text);
                    } else {
                        $comment_text = str_replace($match_search, $match_replace, $comment_text);
                    }
                    //$comment_text      = preg_replace("/$match_search\b/i", $match_replace,  $comment_text);
                    // $hashtag_query     = "UPDATE " . T_HASHTAGS . " SET `last_trend_time` = " . time() . ", `trend_use_num` = " . ($hashdata['trend_use_num'] + 1) . " WHERE `id` = " . $hashdata['id'];
                    // $hashtag_sql_query = mysqli_query($sqlConnect, $hashtag_query);
                }
            }
        }
        $query_one = mysqli_query($sqlConnect, "UPDATE " . T_COMMENTS . " SET `text` = '{$comment_text}' WHERE `id` = {$comment_id}");
        if ($query_one) {
            if (isset($mentions) && is_array($mentions)) {
                foreach ($mentions as $mention) {
                    $notification_data_array = array(
                        'recipient_id' => $mention,
                        'type' => 'comment_mention',
                        'page_id' => $page_id,
                        'post_id' => Wo_GetPostIdFromCommentId($data['comment_id']),
                        'url' => 'index.php?link1=post&id=' . Wo_GetPostIdFromCommentId($data['comment_id'])
                    );
                    Wo_RegisterNotification($notification_data_array);
                }
            }
            $query = mysqli_query($sqlConnect, "SELECT `text` FROM " . T_COMMENTS . " WHERE `id` = {$comment_id}");
            if (mysqli_num_rows($query)) {
                $fetched_data = mysqli_fetch_assoc($query);
                $fetched_data['text'] = Wo_Markup($fetched_data['text']);
                $fetched_data['text'] = Wo_Emo($fetched_data['text']);
                return $fetched_data['text'];
            }
            return false;
        }
    } else {
        return false;
    }
}

function Wo_UpdatePostPrivacy($data = array())
{
    global $wo, $sqlConnect, $cache;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if ($data['post_id'] < 0 || empty($data['post_id']) || !is_numeric($data['post_id'])) {
        return false;
    }
    if (!is_numeric($data['privacy_type'])) {
        return false;
    }
    $privacy_type = Wo_Secure($data['privacy_type']);
    $user_id = Wo_Secure($wo['user']['user_id']);
    $post_id = Wo_Secure($data['post_id']);
    if (Wo_IsPostOnwer($post_id, $user_id) === false) {
        return false;
    }
    $query_one = mysqli_query($sqlConnect, "UPDATE " . T_POSTS . " SET `postPrivacy` = '{$privacy_type}' WHERE `id` = {$post_id}");
    if ($query_one) {
        return $privacy_type;
    }
}

function Wo_UpdatePost($data = array())
{
    global $wo, $sqlConnect, $cache;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if ($data['post_id'] < 0 || empty($data['post_id']) || !is_numeric($data['post_id'])) {
        return false;
    }
    if (empty($data['text'])) {
        return false;
    }
    $page_id = 0;
    if (!empty($data['page_id'])) {
        $page_id = Wo_Secure($data['page_id']);
    }
    $post_text = Wo_Secure($data['text'], 1);
    $user_id = Wo_Secure($wo['user']['user_id']);
    $post_id = Wo_Secure($data['post_id']);
    if (Wo_IsPostOnwer($post_id, $user_id) === false) {
        return false;
    }
    if (!empty($post_text)) {
        if ($wo['config']['maxCharacters'] > 0) {
            if (strlen($post_text) > $wo['config']['maxCharacters']) {
            }
        }
        $link_regex = '/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i';
        $i = 0;
        preg_match_all($link_regex, $post_text, $matches);
        foreach ($matches[0] as $match) {
            $match_url = strip_tags($match);
            $syntax = '[a]' . urlencode($match_url) . '[/a]';
            $post_text = str_replace($match, $syntax, $post_text);
        }
        $mention_regex = '/@([A-Za-z0-9_]+)/i';
        preg_match_all($mention_regex, $post_text, $matches);
        foreach ($matches[1] as $match) {
            $match = Wo_Secure($match);
            $match_user = Wo_UserData(Wo_UserIdFromUsername($match));
            $match_search = '@' . $match;
            $match_replace = '@[' . $match_user['user_id'] . ']';
            if (isset($match_user['user_id'])) {
                $post_text = str_replace($match_search, $match_replace, $post_text);
                $mentions[] = $match_user['user_id'];
            }
        }
    }
    $hashtag_regex = '/#([^`~!@$%^&*\#()\-+=\\|\/\.,<>?\'\":;{}\[\]* ]+)/i';
    preg_match_all($hashtag_regex, $post_text, $matches);
    foreach ($matches[1] as $match) {
        if (!is_numeric($match)) {
            $post_text = str_replace('#' . $match, '#' . mb_strtolower($match, 'UTF-8'), $post_text);
            $match = mb_strtolower($match, 'UTF-8');
            $hashdata = Wo_GetHashtag($match);
            if (is_array($hashdata)) {
                $match_search = '#' . $match;
                $match_replace = '#[' . $hashdata['id'] . ']';
                if (mb_detect_encoding($match_search, 'ASCII', true)) {
                    $post_text = preg_replace("/$match_search\b/i", $match_replace, $post_text);
                } else {
                    $post_text = str_replace($match_search, $match_replace, $post_text);
                }
                $hashtag_query = "UPDATE " . T_HASHTAGS . " SET `last_trend_time` = " . time() . ", `trend_use_num` = " . ($hashdata['trend_use_num'] + 1) . " WHERE `id` = " . $hashdata['id'];
                $hashtag_sql_query = mysqli_query($sqlConnect, $hashtag_query);
            }
        }
    }
    $query_one = mysqli_query($sqlConnect, "UPDATE " . T_POSTS . " SET `postText` = '{$post_text}' WHERE `id` = {$post_id}");
    if ($query_one) {
        if (isset($mentions) && is_array($mentions)) {
            foreach ($mentions as $mention) {
                if (empty($wo['no_mention']) || (!empty($wo['no_mention']) && !in_array($mention, $wo['no_mention']))) {
                    $notification_data_array = array(
                        'recipient_id' => $mention,
                        'type' => 'post_mention',
                        'page_id' => $page_id,
                        'post_id' => $post_id,
                        'url' => 'index.php?link1=post&id=' . $post_id
                    );
                    Wo_RegisterNotification($notification_data_array);
                }
            }
        }
        $query = mysqli_query($sqlConnect, "SELECT `postText` FROM " . T_POSTS . " WHERE `id` = {$post_id}");
        if (mysqli_num_rows($query)) {
            $fetched_data = mysqli_fetch_assoc($query);
            $fetched_data['postText'] = Wo_Markup($fetched_data['postText']);
            $fetched_data['postText'] = Wo_Emo($fetched_data['postText']);
            return $fetched_data['postText'];
        }
        return false;
    }
}

function Wo_SavePosts($post_data = array())
{
    global $wo, $sqlConnect;
    if (empty($post_data)) {
        return false;
    }
    $user_id = Wo_Secure($wo['user']['user_id']);
    $post_id = Wo_Secure($post_data['post_id']);
    if (Wo_IsPostSaved($post_id, $user_id)) {
        $query_one = "DELETE FROM " . T_SAVED_POSTS . " WHERE `post_id` = {$post_id} AND `user_id` = {$user_id}";
        $sql_query_one = mysqli_query($sqlConnect, $query_one);
        if ($sql_query_one) {
            return 'unsaved';
        }
    } else {
        $query_two = "INSERT INTO " . T_SAVED_POSTS . " (`user_id`, `post_id`) VALUES ({$user_id}, {$post_id})";
        $sql_query_two = mysqli_query($sqlConnect, $query_two);
        if ($sql_query_two) {
            return 'saved';
        }
    }
}

function Wo_GetChatColor($user_id = 0, $conversation_user_id = 0, $page_id = 0)
{
    global $sqlConnect, $wo;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($user_id) || empty($conversation_user_id)) {
        return false;
    }
    if (!is_numeric($conversation_user_id) || !is_numeric($user_id)) {
        return false;
    }
    $page_query = " AND `page_id` = 0 ";
    if (!empty($page_id)) {
        $page_id = Wo_Secure($page_id);
        $page_query = " AND `page_id` = '$page_id' ";
    }
    $user_id = Wo_Secure($user_id);
    $conversation_user_id = Wo_Secure($conversation_user_id);
    $sql_queryset = mysqli_query($sqlConnect, "SELECT color FROM " . T_U_CHATS . " WHERE `user_id` = '$user_id' AND `conversation_user_id` = '$conversation_user_id' $page_query LIMIT 1");
    if (mysqli_num_rows($sql_queryset)) {
        $fetched_data = mysqli_fetch_assoc($sql_queryset);
        $color = (!empty($fetched_data['color'])) ? $fetched_data['color'] : $wo['config']['btn_background_color'];
        if (file_exists('./themes/' . $wo['config']['theme'] . '/reaction/like-sm.png') && empty($fetched_data['color'])) {
            $color = '';
        }
        return $color;
    }
    return false;
}

function Wo_UpdateChatColor($user_id = 0, $conversation_user_id = 0, $color = '', $page_id = 0)
{
    global $sqlConnect, $wo;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($user_id) || empty($conversation_user_id)) {
        return false;
    }
    if (!is_numeric($conversation_user_id) || !is_numeric($user_id)) {
        return false;
    }
    if (empty($color)) {
        return false;
    }
    $user_id = Wo_Secure($user_id);
    $conversation_user_id = Wo_Secure($conversation_user_id);
    $color = Wo_Secure($color);
    $set_color_query = "";
    if (!empty($page_id)) {
        $page_id = Wo_Secure($page_id);
        $page = Wo_PageData($page_id);
        if ($user_id == $conversation_user_id) {
            $user_id = $page['user_id'];
        }
        $query_one = mysqli_query($sqlConnect, "SELECT COUNT(`id`) as count FROM " . T_U_CHATS . " WHERE `conversation_user_id` = '$user_id' AND `user_id` = '$conversation_user_id' AND `page_id` = '$page_id'");
        $set_color_query = "  AND `page_id` = '$page_id' ";
    } else {
        $query_one = mysqli_query($sqlConnect, "SELECT COUNT(`id`) as count FROM " . T_U_CHATS . " WHERE `conversation_user_id` = '$user_id' AND `user_id` = '$conversation_user_id'");
    }
    if (mysqli_num_rows($query_one)) {
        $query_one_fetch = mysqli_fetch_assoc($query_one);
        if ($query_one_fetch['count'] == 0) {
            if (!empty($page_id)) {
                $update_ = Wo_CreateUserChat($conversation_user_id, $user_id, $page_id);
            } else {
                $update_ = Wo_CreateUserChat($conversation_user_id, $user_id);
            }
        }
    }
    $query = "UPDATE " . T_U_CHATS . " SET `color` = '$color'
            WHERE (`user_id` = '$user_id' AND `conversation_user_id` = '$conversation_user_id' $set_color_query)
            OR (`user_id` = '$conversation_user_id' AND `conversation_user_id` = '$user_id' $set_color_query)";
    $sql_queryset = mysqli_query($sqlConnect, $query);
    return $sql_queryset;
}

function Wo_ProfileCompletion()
{
    global $sqlConnect, $wo;
    if ($wo['loggedin'] == false) {
        return false;
    }
    $data = array(
        1 => 0,
        2 => 0,
        3 => 0,
        4 => 0,
        5 => 0
    );
    if (!empty($wo['user']['startup_image'])) {
        $data[1] = 20;
    }
    if (!empty($wo['user']['first_name']) && !empty($wo['user']['first_name'])) {
        $data[2] = 20;
    }
    if (!empty($wo['user']['working'])) {
        $data[3] = 20;
    }
    if (!empty($wo['user']['country_id'])) {
        $data[4] = 20;
    }
    if (!empty($wo['user']['address'])) {
        $data[5] = 20;
    }
    return $data;
}

function Wo_GetLastAttachments($user_id)
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (!is_numeric($user_id) or $user_id < 1) {
        return false;
    }
    $user_id = Wo_Secure($user_id);
    $logged_user_id = Wo_Secure($wo['user']['user_id']);
    $query = " SELECT * FROM " . T_MESSAGES . " WHERE ((`from_id` = {$user_id} AND (`to_id` = {$logged_user_id} AND `deleted_two` = '0') OR (`from_id` = {$logged_user_id} AND `to_id` = {$user_id} AND `deleted_one` = '0')) AND (`from_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$user_id}') AND `from_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$user_id}') AND ( mediaFileName like '%jpg' OR mediaFileName like '%PNG' OR mediaFileName like '%jpeg'))) ORDER BY id DESC limit 6";
    $sql_query = mysqli_query($sqlConnect, $query);
    $data = array();
    if (mysqli_num_rows($sql_query)) {
        while ($fetched_data = mysqli_fetch_assoc($sql_query)) {
            $data[] = Wo_GetMedia($fetched_data['media']);
        }
    }
    return $data;
}

function Wo_GetMessagesPagesAPP($fetch_array = array())
{
    global $wo, $sqlConnect;
    if (empty($fetch_array['session_id'])) {
        if ($wo['loggedin'] == false) {
            return false;
        }
    }
    if (!is_numeric($fetch_array['user_id']) or $fetch_array['user_id'] < 1) {
        return false;
    }
    if (!isset($fetch_array['user_id'])) {
        $user_id = $wo['user']['user_id'];
    }
    $user_id = Wo_Secure($fetch_array['user_id']);
    $searchQuery = '';
    if (!empty($fetch_array['searchQuery'])) {
        $searchQuery = Wo_Secure($fetch_array['searchQuery']);
    }
    $data = array();
    $excludes = array();
    $offset_query = "";
    if (!empty($fetch_array['offset'])) {
        $offset_query = " AND `time` < " . $fetch_array['offset'];
    }
    if (isset($searchQuery) and !empty($searchQuery)) {
        $query_one = "SELECT `user_id` as `conversation_user_id` FROM " . T_USERS . " WHERE (`user_id` IN (SELECT `from_id` FROM " . T_MESSAGES . " WHERE `to_id` = {$user_id} AND `page_id` > 0 AND `user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$user_id}') AND `user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$user_id}') AND `active` = '1' ";
        if (isset($fetch_array['new']) && $fetch_array['new'] == true) {
            $query_one .= " AND `seen` = 0";
        }
        $query_one .= " ORDER BY `user_id` DESC)";
        if (!isset($fetch_array['new']) or $fetch_array['new'] == false) {
            $query_one .= " OR `user_id` IN (SELECT `to_id` FROM " . T_MESSAGES . " WHERE `from_id` = {$user_id} AND `page_id` > 0 ORDER BY `id` DESC)";
        }
        $query_one .= ") AND ((`username` LIKE '%{$searchQuery}%') OR CONCAT( `first_name`,  ' ', `last_name` ) LIKE  '%{$searchQuery}%')";
        if (!empty($fetch_array['limit'])) {
            $limit = Wo_Secure($fetch_array['limit']);
            $query_one .= "LIMIT {$limit}";
        }
    } else {
        $query_one = "SELECT * FROM " . T_U_CHATS . " WHERE `user_id` = '$user_id' AND `page_id` > 0 AND (`conversation_user_id` NOT IN (SELECT `blocked` FROM " . T_BLOCKS . " WHERE `blocker` = '{$user_id}') AND `conversation_user_id` NOT IN (SELECT `blocker` FROM " . T_BLOCKS . " WHERE `blocked` = '{$user_id}')) {$offset_query}  ORDER BY `time` DESC";
    }
    if (!empty($fetch_array['limit'])) {
        $limit = Wo_Secure($fetch_array['limit']);
        $query_one .= " LIMIT {$limit}";
    }
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    if ($sql_query_one) {
        if (mysqli_num_rows($sql_query_one) > 0) {
            while ($sql_fetch_one = mysqli_fetch_assoc($sql_query_one)) {
                $new_data = Wo_UserData($sql_fetch_one['conversation_user_id']);
                $new_data['chat_id'] = $sql_fetch_one['id'];
                if (!empty($new_data) && !empty($new_data['username'])) {
                    $new_data['chat_time'] = $sql_fetch_one['time'];
                    $new_data['message'] = $sql_fetch_one;
                    $data[] = $new_data;
                }
            }
        }
    }
    return $data;
}

function Wo_AddCommentBlogReactions($comment_id, $reaction)
{
    global $wo, $sqlConnect, $db;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($comment_id) || empty($reaction) || !is_numeric($comment_id) || $comment_id < 1) {
        return false;
    }
    $comment_id = Wo_Secure($comment_id);
    $comment = $db->where('id', $comment_id)->getOne(T_BLOG_COMM);
    if (empty($comment)) {
        return false;
    }
    $user_id = $comment->user_id;
    $blog_id = $comment->blog_id;
    $logged_user_id = $wo['user']['user_id'];
    //$post_id        = Wo_GetPostIdFromCommentId($comment_id);
    $text = 'comment';
    $type2 = $reaction;
    if (empty($user_id)) {
        return false;
    }
    $is_reacted = $db->where('user_id', $logged_user_id)->where('comment_id', $comment_id)->getValue(T_BLOG_REACTION, 'COUNT(*)');
    if ($is_reacted > 0) {
        $db->where('user_id', $logged_user_id)->where('comment_id', $comment_id)->delete(T_BLOG_REACTION);
        $db->where('recipient_id', $user_id)->where('comment_id', $comment_id)->where('type', 'reaction')->delete(T_NOTIFICATION);
        // $query_one        = "DELETE FROM " . T_REACTIONS . " WHERE `comment_id` = {$comment_id} AND `user_id` = {$logged_user_id}";
        // $query_delete_one = mysqli_query($sqlConnect, "DELETE FROM " . T_NOTIFICATION . " WHERE `comment_id` = {$comment_id} AND `recipient_id` = {$user_id} AND `type` = 'reaction'");
        // $delete_activity  = Wo_DeleteActivity($comment_id, $logged_user_id, 'reaction');
        // $sql_query_one    = mysqli_query($sqlConnect, $query_one);
        //Register point level system for reaction
        //Wo_RegisterPoint($post_id, "reaction" , "-");
    }
    $query_two = "INSERT INTO " . T_BLOG_REACTION . " (`user_id`, `comment_id`, `reaction`, `blog_id`) VALUES ({$logged_user_id}, {$comment_id},'{$reaction}','{$blog_id}')";
    $sql_query_two = mysqli_query($sqlConnect, $query_two);
    if ($sql_query_two) {
        // $activity_data = array(
        //     'post_id' => $post_id,
        //     'comment_id' => $comment_id,
        //     'user_id' => $logged_user_id,
        //     'post_user_id' => $user_id,
        //     'activity_type' => 'reaction|comment|'.$reaction
        // );
        //$add_activity  = Wo_RegisterActivity($activity_data);
        $notification_data_array = array(
            'recipient_id' => $user_id,
            'comment_id' => $comment_id,
            'type' => 'reaction',
            'text' => $text,
            'type2' => $type2,
            'url' => 'index.php?link1=read-blog&id=' . $blog_id
        );
        Wo_RegisterNotification($notification_data_array);
        //Register point level system for reaction
        //Wo_RegisterPoint($post_id, "reaction");
        return 'reacted';
    }
}

function Wo_AddBlogReplyReactions($user_id, $reply_id, $reaction)
{
    global $wo, $sqlConnect, $db;
    if ($wo['loggedin'] == false) {
        return false;
    }
    if (empty($reply_id) || empty($reaction) || !is_numeric($reply_id) || $reply_id < 1) {
        return false;
    }
    $reply_id = Wo_Secure($reply_id);
    $comment = $db->where('id', $reply_id)->getOne(T_BLOG_COMM_REPLIES);
    if (empty($comment)) {
        return false;
    }
    $user_id = $comment->user_id;
    $blog_id = $comment->blog_id;
    $logged_user_id = $wo['user']['user_id'];
    $text = 'replay';
    $type2 = $reaction;
    if (empty($user_id)) {
        return false;
    }
    $is_reacted = $db->where('user_id', $logged_user_id)->where('reply_id', $reply_id)->getValue(T_BLOG_REACTION, 'COUNT(*)');
    if ($is_reacted > 0) {
        $db->where('user_id', $logged_user_id)->where('reply_id', $reply_id)->delete(T_BLOG_REACTION);
        $db->where('recipient_id', $user_id)->where('reply_id', $reply_id)->where('type', 'reaction')->delete(T_NOTIFICATION);
    }
    $query_two = "INSERT INTO " . T_BLOG_REACTION . " (`user_id`, `reply_id`, `reaction`, `blog_id`) VALUES ({$logged_user_id}, {$reply_id},'{$reaction}','{$blog_id}')";
    $sql_query_two = mysqli_query($sqlConnect, $query_two);
    if ($sql_query_two) {
        // $activity_data = array(
        //     'post_id' => $post_id,
        //     'reply_id' => $reply_id,
        //     'user_id' => $logged_user_id,
        //     'post_user_id' => $user_id,
        //     'activity_type' => 'reaction|replay|'.$reaction
        // );
        // $add_activity  = Wo_RegisterActivity($activity_data);
        $notification_data_array = array(
            'recipient_id' => $user_id,
            'reply_id' => $reply_id,
            'type' => 'reaction',
            'text' => $text,
            'type2' => $type2,
            'url' => 'index.php?link1=read-blog&id=' . $blog_id
        );
        Wo_RegisterNotification($notification_data_array);
        //Register point level system for reaction
        //Wo_RegisterPoint($post_id, "reaction");
        return 'reacted';
    }
}

function WoAddBadLoginLog()
{
    global $wo, $sqlConnect;
    if ($wo['loggedin'] == true) {
        return false;
    }
    $ip = get_ip_address();
    if (empty($ip)) {
        return true;
    }
    $time = time();
    $query = mysqli_query($sqlConnect, "INSERT INTO " . T_BAD_LOGIN . " (`ip`, `time`) VALUES ('{$ip}', '{$time}')");
    if ($query) {
        return true;
    }
}

function Wo_DeleteBadLogins()
{
    global $wo, $sqlConnect, $db;
    if ($wo['loggedin'] == true) {
        return false;
    }
    $ip = get_ip_address();
    if (empty($ip)) {
        return true;
    }
    $db->where('ip', $ip)->delete(T_BAD_LOGIN);
    return true;
}

function WoCanLogin()
{
    global $wo, $sqlConnect, $db;
    if ($wo['loggedin'] == true && !isset($_POST['add_account'])) {
        return false;
    }
    $ip = get_ip_address();
    if (empty($ip)) {
        return true;
    }
    if ($wo['config']['lock_time'] < 1) {
        return true;
    }
    if ($wo['config']['bad_login_limit'] < 1) {
        return true;
    }
    $time = time() - (60 * $wo['config']['lock_time']);
    $login = $db->where('ip', $ip)->get(T_BAD_LOGIN);
    if (count($login) >= $wo['config']['bad_login_limit']) {
        $last = end($login);
        if ($last->time >= $time) {
            return false;
        }
    }
    $db->where('time', time() - (60 * $wo['config']['lock_time'] * 2), '<')->delete(T_BAD_LOGIN);
    return true;
}

function Wo_GetMessagesAPPN($data = array(), $limit = 50)
{
    global $wo, $sqlConnect, $db;
    $message_data = array();
    $user_id = Wo_Secure($data['recipient_id']);
    $logged_user_id = Wo_Secure($data['user_id']);
    if (empty($user_id) || !is_numeric($user_id) || $user_id < 0) {
        return false;
    }
    $query_one = " SELECT * FROM " . T_MESSAGES;
    if (isset($data['new']) && $data['new'] == true) {
        $query_one .= " WHERE `seen` = 0 AND `from_id` = {$user_id} AND `to_id` = {$logged_user_id} AND `deleted_two` = '0'";
    } else {
        $query_one .= " WHERE ((`from_id` = {$user_id} AND `to_id` = {$logged_user_id} AND `deleted_two` = '0') OR (`from_id` = {$logged_user_id} AND `to_id` = {$user_id} AND `deleted_one` = '0'))";
    }
    if (!empty($data['message_id'])) {
        $data['message_id'] = Wo_Secure($data['message_id']);
        $query_one .= " AND `id` = " . $data['message_id'];
    } else if (!empty($data['before_message_id']) && is_numeric($data['before_message_id']) && $data['before_message_id'] > 0) {
        $data['before_message_id'] = Wo_Secure($data['before_message_id']);
        $query_one .= " AND `id` < " . $data['before_message_id'] . " AND `id` <> " . $data['before_message_id'];
    } else if (!empty($data['after_message_id']) && is_numeric($data['after_message_id']) && $data['after_message_id'] > 0) {
        $data['after_message_id'] = Wo_Secure($data['after_message_id']);
        $query_one .= " AND `id` > " . $data['after_message_id'] . " AND `id` <> " . $data['after_message_id'];
    }
    $query_one .= " AND `page_id` = '0' ";
    $sql_query_one = mysqli_query($sqlConnect, $query_one);
    $query_limit_from = mysqli_num_rows($sql_query_one) - 50;
    if ($query_limit_from < 1) {
        $query_limit_from = 0;
    }
    if (isset($limit)) {
        // if (!empty($data['before_message_id']) && is_numeric($data['before_message_id']) && $data['before_message_id'] > 0) {
        //     $query_one .= " ORDER BY `id` DESC LIMIT {$query_limit_from}, 50";
        // } else {
        //     $query_one .= " ORDER BY `id` ASC LIMIT {$query_limit_from}, 50";
        // }
        if (!empty($data['before_message_id']) && is_numeric($data['before_message_id']) && $data['before_message_id'] > 0) {
            $query_one .= " ORDER BY `id` DESC LIMIT {$limit}";
        } else {
            $query_one .= " ORDER BY `id` DESC LIMIT {$limit}";
        }
    }
    $query = mysqli_query($sqlConnect, $query_one);
    if (mysqli_num_rows($query)) {
        while ($fetched_data = mysqli_fetch_assoc($query)) {
            $fetched_data['messageUser'] = Wo_UserData($fetched_data['from_id']);
            $fetched_data['messageUser'] = array(
                'user_id' => $fetched_data['messageUser']['user_id'],
                'avatar' => $fetched_data['messageUser']['avatar']
            );
            $fetched_data['text'] = Wo_EditMarkup($fetched_data['text']);
            if ($fetched_data['messageUser']['user_id'] == $user_id && $fetched_data['seen'] == 0) {
                mysqli_query($sqlConnect, " UPDATE " . T_MESSAGES . " SET `seen` = " . time() . " WHERE `id` = " . $fetched_data['id']);
            }
            $fetched_data['pin'] = 'no';
            $mute = $db->where('user_id', $wo['user']['id'])->where('message_id', $fetched_data['id'])->where('pin', 'yes')->getOne(T_MUTE);
            if (!empty($mute)) {
                $fetched_data['pin'] = 'yes';
            }
            $fetched_data['fav'] = 'no';
            $mute = $db->where('user_id', $wo['user']['id'])->where('message_id', $fetched_data['id'])->where('fav', 'yes')->getOne(T_MUTE);
            if (!empty($mute)) {
                $fetched_data['fav'] = 'yes';
            }
            $fetched_data['reply'] = array();
            if (!empty($fetched_data['reply_id'])) {
                $fetched_data['reply'] = GetMessageById($fetched_data['reply_id']);
                $fetched_data['reply']['messageUser'] = array(
                    'user_id' => $fetched_data['reply']['messageUser']['user_id'],
                    'avatar' => $fetched_data['reply']['messageUser']['avatar']
                );
            }
            $fetched_data['story'] = array();
            if (!empty($fetched_data['story_id'])) {
                $fetched_data['story'] = Wo_GetStroies(array(
                    'id' => $fetched_data['story_id']
                ));
                if (!empty($fetched_data['story']) && !empty($fetched_data['story'][0])) {
                    $fetched_data['story'] = $fetched_data['story'][0];
                }
            }
            $fetched_data['reaction'] = Wo_GetPostReactionsTypes($fetched_data['id'], 'message');
            $message_data[] = $fetched_data;
        }
    }
    return $message_data;
}

function nofollow($html, $skip = null)
{
    return preg_replace_callback("#(<a[^>]+?)>#is", function ($mach) use ($skip) {
        return (!($skip && strpos($mach[1], $skip) !== false) && strpos($mach[1], 'rel=') === false) ? $mach[1] . ' rel="nofollow">' : $mach[0];
    }, $html);
}

function Wo_ReplaceText($html = '', $replaces = array())
{
    global $wo;
    $lang = $wo['lang'];
    $html = preg_replace_callback("/{{LANG (.*?)}}/", function ($m) use ($lang) {
        return (isset($lang[$m[1]])) ? $lang[$m[1]] : '';
    }, $html);
    foreach ($replaces as $key => $replace) {
        $object_to_replace = "{{" . $key . "}}";
        $html = str_replace($object_to_replace, $replace, $html);
    }
    return $html;
}

function GetNgeniusToken()
{
    global $wo, $sqlConnect, $db;
    $ch = curl_init();
    if ($wo['config']['ngenius_mode'] == 'sandbox') {
        curl_setopt($ch, CURLOPT_URL, "https://api-gateway.sandbox.ngenius-payments.com/identity/auth/access-token");
    } else {
        curl_setopt($ch, CURLOPT_URL, "https://identity-uat.ngenius-payments.com/auth/realms/ni/protocol/openid-connect/token");
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "accept: application/vnd.ni-identity.v1+json",
        "authorization: Basic " . $wo['config']['ngenius_api_key'],
        "content-type: application/vnd.ni-identity.v1+json"
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"realmName\":\"ni\"}");
    $output = json_decode(curl_exec($ch));
    return $output;
}

function CreateNgeniusOrder($token, $postData)
{
    global $wo, $sqlConnect, $db;

    $json = json_encode($postData);
    $ch = curl_init();
    if ($wo['config']['ngenius_mode'] == 'sandbox') {
        curl_setopt($ch, CURLOPT_URL, "https://api-gateway.sandbox.ngenius-payments.com/transactions/outlets/" . $wo['config']['ngenius_outlet_id'] . "/orders");
    } else {
        curl_setopt($ch, CURLOPT_URL, "https://api-gateway-uat.ngenius-payments.com/transactions/outlets/" . $wo['config']['ngenius_outlet_id'] . "/orders");
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Authorization: Bearer " . $token,
        "Content-Type: application/vnd.ni-payment.v2+json",
        "Accept: application/vnd.ni-payment.v2+json"));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

    $output = json_decode(curl_exec($ch));
    curl_close($ch);
    return $output;
}

function coinpayments_api_call($req = array())
{
    global $wo, $sqlConnect, $db;
    $result = array('status' => 400);

    // Generate the query string
    $post_data = http_build_query($req, '', '&');
    // echo $post_data;
    // echo "<br>";
    // Calculate the HMAC signature on the POST data
    $hmac = hash_hmac('sha512', $post_data, $wo['config']['coinpayments_secret']);
    // echo $hmac;
    // exit();

    $ch = curl_init('https://www.coinpayments.net/api.php');
    curl_setopt($ch, CURLOPT_FAILONERROR, TRUE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('HMAC: ' . $hmac));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

    // Execute the call and close cURL handle
    $data = curl_exec($ch);
    // Parse and return data if successful.

    if ($data !== FALSE) {
        $info = json_decode($data, TRUE);
        if (!empty($info) && !empty($info['result'])) {
            $result = array('status' => 200,
                'data' => $info['result']);
        } else {
            $result['message'] = $info['error'];
        }
    } else {
        $result['message'] = 'cURL error: ' . curl_error($ch);
    }
    return $result;
}

function FilterStripTags($string = '')
{
    return filter_var(strip_tags($string), FILTER_SANITIZE_STRING);
}

function GetIso()
{
    global $wo, $db, $all_langs;
    $iso = array();
    foreach ($all_langs as $key => $value) {
        try {
            $info = $db->where('lang_name', $value)->getOne(T_LANG_ISO);
            if (!empty($info)) {
                $iso[$value] = $info;
            }
        } catch (Exception $e) {

        }
    }
    return $iso;
}

function BackblazeConnect($args = [])
{
    global $wo, $db;

    $session = curl_init($args['apiUrl'] . $args['uri']);
    $content_type = '';

    if ($args['uri'] == '/b2api/v2/b2_list_buckets') {
        $data = array("accountId" => $args['accountId']);
        $post_fields = json_encode($data);
        curl_setopt($session, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($session, CURLOPT_POST, true); // HTTP POST
    } else if ($args['uri'] == '/b2api/v2/b2_get_upload_url' || $args['uri'] == '/b2api/v2/b2_list_file_names') {
        $data = array("bucketId" => $wo['config']['backblaze_bucket_id']);
        $post_fields = json_encode($data);
        curl_setopt($session, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($session, CURLOPT_POST, true); // HTTP POST
    } else if ($args['uri'] == '/b2api/v2/b2_delete_file_version') {
        $data = array("fileId" => $args['fileId'], "fileName" => $args['fileName']);
        $post_fields = json_encode($data);
        curl_setopt($session, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($session, CURLOPT_POST, true); // HTTP POST
    } elseif (isset($args['file']) && !empty($args['file'])) {
        $handle = fopen($args['file'], 'r');
        $read_file = fread($handle, filesize($args['file']));
        curl_setopt($session, CURLOPT_POSTFIELDS, $read_file);
    }

    // Add post fields


    // Add headers
    $headers = array();

    if ($args['uri'] == '/b2api/v2/b2_authorize_account') {
        $credentials = base64_encode($wo['config']['backblaze_access_key_id'] . ":" . $wo['config']['backblaze_access_key']);
        $headers[] = "Accept: application/json";
        $headers[] = "Authorization: Basic " . $credentials;
        curl_setopt($session, CURLOPT_HTTPGET, true);
    } else if (isset($args['file']) && !empty($args['file'])) {
        $headers[] = "X-Bz-File-Name: " . $args['file'];
        $headers[] = "Content-Type: " . mime_content_type($args['file']);
        $headers[] = "X-Bz-Content-Sha1: " . sha1_file($args['file']);
        $headers[] = "X-Bz-Info-Author: " . "unknown";
        $headers[] = "X-Bz-Server-Side-Encryption: " . "AES256";
        $headers[] = "Authorization: " . $args['authorizationToken'];
    } else {
        $headers[] = "Authorization: " . $args['authorizationToken'];
    }

    curl_setopt($session, CURLOPT_HTTPHEADER, $headers);


    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);  // Receive server response
    $server_output = curl_exec($session); // Let's do this!
    curl_close($session); // Clean up

    return $server_output;
}

function file_upload_max_size()
{
    static $max_size = -1;

    if ($max_size < 0) {
        // Start with post_max_size.
        $post_max_size = parse_size(ini_get('post_max_size'));
        if ($post_max_size > 0) {
            $max_size = $post_max_size;
        }

        // If upload_max_size is less, then reduce. Except if upload_max_size is
        // zero, which indicates no limit.
        $upload_max = parse_size(ini_get('upload_max_filesize'));
        if ($upload_max > 0 && $upload_max < $max_size) {
            $max_size = $upload_max;
        }
    }
    return $max_size;
}

function formatBytes($size, $precision = 2)
{
    $base = log($size, 1024);
    $suffixes = array('', 'K', 'M', 'G', 'T');

    return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
}

function parse_size($size)
{
    $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
    $size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
    if ($unit) {
        // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
        return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
    } else {
        return round($size);
    }
}

function getDirContents($dir, &$results = array())
{
    global $db;
    $files = @scandir($dir);
    $forbiddenArray = ['.htaccess', 'index.html', 'step2.png', 'thumbnail.jpg', 'speed.jpg', 'parts.jpg', 'f-avatar.png', 'd-cover.jpg', 'd-avatar.jpg', 'blur.jpg', 'step1.png'];
    if (!empty($files)) {
        foreach ($files as $key => $value) {
            $path = $dir . "/" . $value;
            if (!is_dir($path) && !in_array($value, $forbiddenArray)) {
                $results[] = $path;
            } else if ($value != "." && $value != "..") {
                getDirContents($path, $results);
                if (!is_dir($path) && !in_array($path, $forbiddenArray)) {
                    $results[] = $path;
                }
            }
        }
    }
    return $results;
}

function filterFiles($results, $storage)
{
    global $db;
    $fianlToAdd = [];
    foreach ($results as $key => $fileName) {
        $checkIfFileExistsInUpload = $db->where('filename', Wo_Secure($fileName))->where('storage', $storage)->getOne(T_UPLOADED_MEDIA);

        if (empty($checkIfFileExistsInUpload)) {
            $fianlToAdd[] = $fileName;
        }
    }
    return $fianlToAdd;
}

function getStatus($config = array())
{
    global $wo, $db;

    $errors = [];

    if (!is_writable('./nodejs/models/wo_langs.js')) {
        $errors[] = ["type" => "error", "message" => "The file: <strong>nodejs/models/wo_langs.js</strong> is not writable, file permission should be <strong>777</strong>."];
    }
    if (!ini_get('allow_url_fopen')) {
        $errors[] = ["type" => "error", "message" => "PHP function <strong>allow_url_fopen</strong> is disabled on your server, it is required to be enabled."];
    }
    if (!function_exists('mime_content_type')) {
        $errors[] = ["type" => "error", "message" => "PHP <strong>FileInfo</strong> extension is disabled on your server, it is required to be enabled."];
    }
    if (!class_exists('DOMDocument')) {
        $errors[] = ["type" => "error", "message" => "PHP <strong>dom & xml</strong> extensions are disabled on your server, they are required to be enabled."];
    }
    if (!is_writable('./upload')) {
        $errors[] = ["type" => "error", "message" => "The folder: <strong>/upload</strong> is not writable, upload folder and all subfolder(s) permission should be set to <strong>777</strong>."];
    }
    if (!is_writable('./xml')) {
        $errors[] = ["type" => "error", "message" => "The folder: <strong>/xml</strong> is not writable, xml folder  permission should be set to <strong>777</strong>."];
    }

    if (!is_writable('./cache')) {
        $errors[] = ["type" => "error", "message" => "The folder: <strong>/cache</strong> is not writable, cache folder  permission should be set to <strong>777</strong>."];
    }
    if (!is_writable('./cache/users')) {
        $errors[] = ["type" => "error", "message" => "The folder: <strong>/cache/users</strong> is not writable, cache/users folder  permission should be set to <strong>777</strong>."];
    }
    if (!is_writable('./cache/groups')) {
        $errors[] = ["type" => "error", "message" => "The folder: <strong>/cache/groups</strong> is not writable, cache/groups folder  permission should be set to <strong>777</strong>."];
    }
    if ($wo['config']['amazone_s3'] == 1 || $wo['config']['ftp_upload'] == 1 || $wo['config']['spaces'] == 1 || $wo['config']['cloud_upload'] == 1 || $wo['config']['wasabi_storage'] == 1 || $wo['config']['backblaze_storage'] == 1) {
        if (!is_writable('./upload/photos/blur.jpg')) {
            $errors[] = ["type" => "error", "message" => "The file: <strong>./upload/photos/blur.jpg</strong> is not writable, the file permission should be set to <strong>777</strong>.<br> Also make sure the file exists."];
        }
        if (!is_writable('./upload/photos/d-avatar.jpg')) {
            $errors[] = ["type" => "error", "message" => "The file: <strong>./upload/photos/d-avatar.jpg</strong> is not writable, the file permission should be set to <strong>777</strong>.<br> Also make sure the file exists."];
        }
        if (!is_writable('./upload/photos/app-default-icon.png')) {
            $errors[] = ["type" => "error", "message" => "The file: <strong>./upload/photos/app-default-icon.png</strong> is not writable, the file permission should be set to <strong>777</strong>. <br> Also make sure the file exists."];
        }
        if (!is_writable('./upload/photos/d-blog.jpg')) {
            $errors[] = ["type" => "error", "message" => "The file: <strong>./upload/photos/d-blog.jpg</strong> is not writable, the file permission should be set to <strong>777</strong>.<br> Also make sure the file exists."];
        }
        if (!is_writable('./upload/photos/d-cover.jpg')) {
            $errors[] = ["type" => "error", "message" => "The file: <strong>./upload/photos/d-cover.jpg</strong> is not writable, the file permission should be set to <strong>777</strong>.<br> Also make sure the file exists."];
        }
        if (!is_writable('./upload/photos/d-film.jpg')) {
            $errors[] = ["type" => "error", "message" => "The file: <strong>./upload/photos/d-film.jpg</strong> is not writable, the file permission should be set to <strong>777</strong>.<br> Also make sure the file exists."];
        }
        if (!is_writable('./upload/photos/d-group.jpg')) {
            $errors[] = ["type" => "error", "message" => "The file: <strong>./upload/photos/d-group.jpg</strong> is not writable, the file permission should be set to <strong>777</strong>.<br> Also make sure the file exists."];
        }
        if (!is_writable('./upload/photos/d-page.jpg')) {
            $errors[] = ["type" => "error", "message" => "The file: <strong>./upload/photos/d-page.jpg</strong> is not writable, the file permission should be set to <strong>777</strong>.<br> Also make sure the file exists."];
        }
        if (!is_writable('./upload/photos/game-icon.png')) {
            $errors[] = ["type" => "error", "message" => "The file: <strong>./upload/photos/game-icon.png</strong> is not writable, the file permission should be set to <strong>777</strong>.<br> Also make sure the file exists."];
        }
        if (!is_writable('./upload/photos/incognito.png')) {
            $errors[] = ["type" => "error", "message" => "The file: <strong>./upload/photos/incognito.png</strong> is not writable, the file permission should be set to <strong>777</strong>.<br> Also make sure the file exists."];
        }
    }

    if ($wo['config']['ffmpeg_system'] == 'on') {
        if (!isfuncEnabled("shell_exec")) {
            $errors[] = ["type" => "error", "message" => "The function: <strong>shell_exec</strong> is not enabled, please contact your hosting provider to enable it, it's required for <strong>FFMPEG</strong>."];
        }
        if (!is_writable('./ffmpeg/ffmpeg')) {
            $errors[] = ["type" => "error", "message" => "The file: <strong>/ffmpeg/ffmpeg</strong> is not writable, file permission should be <strong>777</strong>."];
        }
    }


    if (!is_writable('./sitemap.xml')) {
        $errors[] = ["type" => "error", "message" => "The file: <strong>./sitemap.xml</strong> is not writable, the file permission should be set to <strong>777</strong>."];
    }
    if (!is_writable('./sitemap-index.xml')) {
        $errors[] = ["type" => "error", "message" => "The file: <strong>./sitemap-index.xml</strong> is not writable, the file permission should be set to <strong>777</strong>."];
    }


    if (session_status() == PHP_SESSION_NONE) {
        $errors[] = ["type" => "error", "message" => "PHP Session can't start, please check the session settings on your server, the session path should be writable, contact your server for more Information."];
    }

    if (!empty($config['curl'])) {
        $ch = curl_init();
        $timeout = 10;
        $myHITurl = "https://www.google.com";
        curl_setopt($ch, CURLOPT_URL, $myHITurl);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $file_contents = curl_exec($ch);
        if (curl_errno($ch)) {
            $errors[] = ["type" => "error", "message" => "<strong>cURL</strong> is not functioning, can't connect to the outside world, error found: <strong>" . curl_error($ch) . "</strong>, please contact your hosting provider to fix it."];
        }
        curl_close($ch);
    }

    if (!empty($config['htaccess'])) {
        if (!file_exists('./.htaccess')) {
            $errors[] = ["type" => "error", "message" => "The file: <strong>.htaccess</strong> is not uploaded to your server, make sure the file <strong>.htaccess</strong> is uploaded to your server."];
        } else {
            $file_gethtaccess = file_get_contents("./.htaccess");
            if (strpos($file_gethtaccess, "index.php?link1") === false) {
                $errors[] = ["type" => "error", "message" => "The file: <strong>.htaccess</strong> is not updated, please re-upload the original .htaccess file."];
            }
        }
    }


    if (!empty($config['nodejsport']) && $wo['config']['node_socket_flow'] == "1") {
        $parse = parse_url($wo['config']['site_url']);
        $host = $parse['host'];
        $ports = array($wo['config']['nodejs_port']);
        if ($wo['config']['nodejs_ssl'] == "1") {
            $ports = array($wo['config']['nodejs_ssl_port']);
        }

        foreach ($ports as $port) {
            $connection = @fsockopen($host, $port);

            if (!is_resource($connection)) {
                $errors[] = ["type" => "error", "message" => "<strong>NodeJS</strong>is enabled, but the system can't connect to NodeJS server, <strong> " . $host . ':' . $port . " </strong>is down or port <strong>$port</strong> is blocked."];
            }
        }
    }

    $list_ofFiles = [
        'upload/files/2022/09/EAufYfaIkYQEsYzwvZha_01_4bafb7db09656e1ecb54d195b26be5c3_file.svg',
        'upload/files/2022/09/2MRRkhb7rDhUNuClfOfc_01_76c3c700064cfaef049d0bb983655cd4_file.svg',
        'upload/files/2022/09/D91CP5YFfv74GVAbYtT7_01_288940ae12acf0198d590acbf11efae0_file.svg',
        'upload/files/2022/09/cFNOXZB1XeWRSdXXEdlx_01_7d9c4adcbe750bfc8e864c69cbed3daf_file.svg',
        'upload/files/2022/09/yKmDaNA7DpA7RkCRdoM6_01_eb391ca40102606b78fef1eb70ce3c0f_file.svg',
        'upload/files/2022/09/iZcVfFlay3gkABhEhtVC_01_771d67d0b8ae8720f7775be3a0cfb51a_file.svg'
    ];

    foreach ($list_ofFiles as $key => $file) {
        if (!file_exists($file)) {
            $errors[] = ["type" => "error", "message" => "The file: <strong>{$file}</strong> is required and not uploaded, please upload the 'upload/files/09' folder again."];

        }
        if ($wo['config']['amazone_s3'] == 1 || $wo['config']['ftp_upload'] == 1 || $wo['config']['spaces'] == 1 || $wo['config']['cloud_upload'] == 1 || $wo['config']['wasabi_storage'] == 1 || $wo['config']['backblaze_storage'] == 1) {
            if (!is_readable($file)) {
                $errors[] = ["type" => "error", "message" => "The file: <strong>{$file}</strong> is not readable, make sure the permission of this file is set to 777."];
            }
        }
    }


    $dirs = array_filter(glob('upload/*'), 'is_dir');
    foreach ($dirs as $key => $value) {
        if (!is_writable($value)) {
            $errors[] = ["type" => "error", "message" => "The folder: <strong>{$value}</strong> is not writable, folder permission should be set to <strong>777</strong>."];
        }
    }

    if (empty($wo['config']['smtp_host']) && empty($wo['config']['smtp_username'])) {
        $errors[] = ["type" => "error", "message" => "<strong>SMTP</strong> is not configured, it's recommended to setup <strong>SMTP</strong>, so the system can send e-mails from the server. <br> <a href=" . Wo_LoadAdminLinkSettings('email-settings') . ">Click Here To Setup SMTP</a>"];
    }


    if (!is_writable('./themes/' . $wo['config']['theme'] . '/img')) {
        $errors[] = ["type" => "error", "message" => "The folder: <strong>/themes/{$wo['config']['theme']}/img</strong> is not writable, the path and all subfolder(s) permission should be set to <strong>777</strong>, including <strong>logo.png</strong>"];
    }


    if (file_exists('./install')) {
        $errors[] = ["type" => "error", "message" => "The folder: <strong>./install</strong> is not deleted or renamed, make sure the folder <strong>./install</strong> is deleted."];
    }


    if (!empty($wo['config']['filesVersion'])) {
        if ($wo['config']['filesVersion'] > $wo['config']['version']) {
            $errors[] = ["type" => "error", "message" => "There is a conflict in database version and files version, your database version is: <strong>v{$wo['config']['version']}</strong>, but script version is: <strong>v{$wo['config']['filesVersion']}</strong>. <br> Please run <strong><a href='{$wo['config']['site_url']}/update.php'>{$wo['config']['site_url']}/update.php</a></strong> of <strong>v{$wo['config']['filesVersion']}</strong>. <br><br><a href='https://docs.wowonder.com/#updates'>Click Here For More Information.</a>"];
        } else if ($wo['config']['filesVersion'] < $wo['config']['version']) {
            $errors[] = ["type" => "error", "message" => "There is a conflict in database version and files version, your database version is: <strong>v{$wo['config']['version']}</strong>, but script version is: <strong>v{$wo['config']['filesVersion']}</strong>. <br>Please upload the files of <strong>v{$wo['config']['filesVersion']}</strong> using FTP or SFTP, file managers are not recommended."];
        }
    } else {
        $errors[] = ["type" => "error", "message" => "There is a conflict in database version and files version, your database version is: <strong>v{$wo['config']['version']}</strong>, but script version is: <strong>v{$wo['config']['filesVersion']}</strong>, <br>Please upload the files of <strong>v{$wo['config']['filesVersion']}</strong> using FTP or SFTP, file managers are not recommended."];
    }

    if (!empty($wo['config']['cronjob_last_run'])) {
        $now = strtotime("-15 minutes");
        if ($wo['config']['cronjob_last_run'] < $now) {
            $errors[] = ["type" => "error", "message" => "File <strong>cron-job.php</strong> last run exceeded 15 minutes, make sure it's added to cronjob list. <br> <a href=" . Wo_LoadAdminLinkSettings('cronjob_settings') . ">CronJob Settings</a>"];
        }
    }


    $getSqlModes = $db->rawQuery("SELECT @@sql_mode as modes;");
    if (!empty($getSqlModes[0]->modes)) {
        $results = @explode(',', strtolower($getSqlModes[0]->modes));
        if (in_array('strict_trans_tables', $results)) {
            $errors[] = ["type" => "error", "message" => "The sql-mode <b>strict_trans_tables</b> is enabled in your mysql server, please contact your host provider to disable it."];
        }
        if (in_array('only_full_group_by', $results)) {
            $errors[] = ["type" => "error", "message" => "The sql-mode <b>only_full_group_by</b> is enabled in your mysql server, this can cause some issues on your website, please contact your host provider to disable it."];
        }
    }

    $getUploadSize = file_upload_max_size();

    if ($getUploadSize < 1000000000) {
        $errors[] = ["type" => "warning", "message" => "Your server max upload size is less than 100MB, Current: <strong>" . formatBytes($getUploadSize) . "</strong> Recommended is <strong>1024MB</strong>. You should update both: upload_max_filesize, post_max_size."];
    }

    if (ini_get('max_execution_time') < 100 && ini_get('max_execution_time') > 0) {
        $errors[] = ["type" => "warning", "message" => "Your server max_execution_time is less than 100 seconds, Current: <strong>" . ini_get('max_execution_time') . "</strong> Recommended is <strong>3000</strong>."];
    }

    if ($wo['config']['developer_mode'] == "1") {
        $errors[] = ["type" => "warning", "message" => "<strong>Developer Mode</strong> is enabled in <strong>Settings -> General Configuration</strong>, it's not recommended to enable <strong>Developer Mode</strong> if your website is live, some errors may show."];
    }

    if (!function_exists('exif_read_data')) {
        $errors[] = ["type" => "warning", "message" => "PHP <strong>exif</strong> extension is disabled on your server, it is recommended to be enabled."];
    }

    try {
        $getSqlWait = $db->rawQuery("show variables where Variable_name='wait_timeout';");
        if (!empty($getSqlWait[0]->Value)) {
            if ($getSqlWait[0]->Value < 1000) {
                $errors[] = ["type" => "warning", "message" => "The MySQL variable <b>wait_timeout</b> is {$getSqlWait[0]->Value}, minumum required is <strong>1000</strong>, please contact your host provider to update it."];
            }
        }
    } catch (Exception $e) {

    }

    return $errors;
}

function checkIfThereIsError($object)
{
    foreach ($object as $key => $value) {
        if ($value['type'] == "error") {
            return true;
        }
    }
    return false;
}

function isfuncEnabled($func)
{
    return is_callable($func) && false === stripos(ini_get('disable_functions'), $func);
}

function getAISize($size = '128x128')
{
    $data['width'] = 128;
    $data['height'] = 128;
    if (!empty($size) && strpos($size, 'x') !== false) {
        $sizeArray = explode('x', $size);
        $data['width'] = (!empty($sizeArray[0]) && is_numeric($sizeArray[0])) ? $sizeArray[0] : 128;
        $data['height'] = (!empty($sizeArray[1]) && is_numeric($sizeArray[1])) ? $sizeArray[1] : 128;
    }
    return $data;
}

function getAIVersion($name = 'prompthero-openjourney')
{
    $versions = array(
        'prompthero-openjourney' => '9936c2001faa2194a261c01381f90e65261879985476014a0a37a334593a05eb',
        'stability-ai-stable-diffusion' => 'db21e45d3f7023abc2a46ee38a23973f6dce16bb082a930b0c49861f96d1e5bf',
        '22-hours-vintedois-diffusion' => '28cea91bdfced0e2dc7fda466cc0a46501c0edc84905b2120ea02e0707b967fd',
    );
    return $versions[$name];
}

function getMidJeournyJson($text = '', $size = '128x128', $num_outputs = 1)
{
    global $wo, $db;

    $js =
        '{
            "version":"' . getAIVersion($wo['config']['midjeourny_model']) . '",
            "input":{
                "prompt":"' . $text . '",
                "num_outputs":' . $num_outputs . ',
                "num_inference_steps":' . $wo['config']['num_inference_steps'] . ',
                "guidance_scale":' . $wo['config']['guidance_scale'] . '
        ';
    if (!empty($wo['config']['seed'])) {
        $js .= ',"seed": ' . $wo['config']['seed'];
    }

    if ($wo['config']['midjeourny_model'] == 'stability-ai-stable-diffusion') {
        $js .= ',"image_dimensions": "' . $size . '"';

    } else {
        $js .= ',"width": ' . $size['width'] . ',"height": ' . $size['height'] . '';
    }

    if ($wo['config']['midjeourny_model'] != 'prompthero-openjourney') {
        $js .= ',"scheduler": "' . $wo['config']['scheduler'] . '"';
        if (!empty($wo['config']['negative_prompt'])) {
            $js .= ',"negative_prompt": "' . $wo['config']['negative_prompt'] . '"';
        }
    }

    if ($wo['config']['midjeourny_model'] == '22-hours-vintedois-diffusion' && !empty($wo['config']['prompt_strength'])) {
        $js .= ',"prompt_strength": ' . $wo['config']['prompt_strength'];
    }

    $js .= '}}';

    return $js;
}

function requestMidJeourny($url, $js = '')
{
    global $wo, $db;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if (!empty($js)) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $js);
    }

    $headers = array();
    $headers[] = 'Authorization: Token ' . $wo['config']['replicate_token'];
    $headers[] = 'Content-Type: application/json';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new Exception(curl_error($ch));
    }
    curl_close($ch);

    $result = json_decode($result);

    return $result;
}

function getUserImageDataUri($image)
{
    global $wo, $db;

    $c = file_get_contents($image);
    $type = pathinfo('d-' . rand(1111, 9999) . '.jpg', PATHINFO_EXTENSION);
    $dataUri = 'data:image/' . $type . ';base64,' . base64_encode($c);
    return $dataUri;
}

function getMidJeournyUser($text, $type = 'avatar')
{
    global $wo, $db;

    if ($type != 'avatar') {
        $type .= '_full';
    }

    $dataUri = getUserImageDataUri($wo['user'][$type]);

    $url = 'https://api.replicate.com/v1/predictions';

    $js = '{"version":"30c1d0b916a6f8efce20493f5d61ee27491ab2a60437c13c588468b9810ec23f","input":{"image":"' . $dataUri . '","prompt":"' . $text . '","scheduler":"K_EULER_ANCESTRAL","num_inference_steps":500}}';

    $result = requestMidJeourny($url, $js);

    if (!empty($result->status) && in_array($result->status, ['succeeded', 'starting', 'processing'])) {
        if ($wo['config']['images_credit_system'] == 1 && $wo['config']['generated_image_price'] > 0) {
            $db->where('user_id', $wo['user']['id'])->update(T_USERS, [
                'credits' => $db->dec(($wo['config']['generated_image_price'] * 1))
            ]);
        }
        return [
            'status' => 200,
            'id' => $result->id,
            'status_text' => $wo['lang'][$result->status]
        ];
    } elseif (!empty($result->error)) {
        throw new Exception($result->error);
    } elseif (!empty($result->detail)) {
        throw new Exception($result->detail);
    } else {
        throw new Exception($result->error);
    }
}

function getMidJeournyImage($text, $size, $num_outputs = 1)
{
    global $wo, $db;

    $js = getMidJeournyJson($text, $size, $num_outputs);


    $url = 'https://api.replicate.com/v1/predictions';

    $result = requestMidJeourny($url, $js);

    if (!empty($result->status) && in_array($result->status, ['succeeded', 'starting', 'processing'])) {
        if ($wo['config']['images_credit_system'] == 1 && $wo['config']['generated_image_price'] > 0) {
            $db->where('user_id', $wo['user']['id'])->update(T_USERS, [
                'credits' => $db->dec(($wo['config']['generated_image_price'] * $num_outputs))
            ]);
        }
        return [
            'status' => 200,
            'id' => $result->id,
            'status_text' => $wo['lang'][$result->status],
        ];
    } elseif (!empty($result->error)) {
        throw new Exception($result->error);
    } elseif (!empty($result->detail)) {
        throw new Exception($result->detail);
    } else {
        throw new Exception($result->error);
    }
}

function checkMidJeourny($id = '')
{
    global $wo, $db;

    $url = 'https://api.replicate.com/v1/predictions/' . $id;

    $result = requestMidJeourny($url);

    if (!empty($result->status) && in_array($result->status, ['succeeded', 'starting', 'processing'])) {
        $output = null;
        if (!empty($result->output)) {
            $output = $result->output;
        }
        return [
            'status' => 200,
            'output' => $output,
            'status_text' => $wo['lang'][$result->status],
            'credits' => $wo['user']['credits']
        ];
    } elseif (!empty($result->error)) {
        throw new Exception($result->error);
    } elseif (!empty($result->detail)) {
        throw new Exception($result->detail);
    } else {
        throw new Exception($result->error);
    }
}

function requestOpenAi($url, $js = '')
{
    global $wo, $db;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if (!empty($js)) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $js);
    }

    $headers = array();
    $headers[] = 'Authorization: Bearer ' . $wo['config']['openai_token'];
    $headers[] = 'Content-Type: application/json';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new Exception(curl_error($ch));
    }
    curl_close($ch);

    $result = json_decode($result);
    return $result;
}

function getOpenAiImage($text, $size = '', $num_outputs = 1)
{
    global $wo, $db;

    $url = 'https://api.openai.com/v1/images/generations';

    $js = '{"prompt": "' . $text . '","n": ' . $num_outputs . ',"size":"' . $size . '"}';

    $result = requestOpenAi($url, $js);

    if (!empty($result->data)) {
        if ($wo['config']['images_credit_system'] == 1 && $wo['config']['generated_image_price'] > 0) {
            $db->where('user_id', $wo['user']['id'])->update(T_USERS, [
                'credits' => $db->dec(($wo['config']['generated_image_price'] * $num_outputs))
            ]);
        }
        return [
            'status' => 200,
            'data' => $result->data
        ];
    } elseif (!empty($result->error) && !empty($result->error->message)) {
        throw new Exception($result->error->message);
    } else {
        throw new Exception($wo['lang']['something_wrong']);
    }
}

function getOpenAiText($text, $count)
{
    global $wo, $db;

    if (getMaxAllowedWords() < $count) {
        throw new Exception(str_replace('{count}', getMaxAllowedWords(), $wo["lang"]["max_allowed_words"]));
    }

    $url = 'https://api.openai.com/v1/chat/completions';


    $js = '{"model": "' . $wo['config']['openai_text_model'] . '","messages": [{"role": "user", "content": "' . $text . '"}],"max_tokens": ' . $count . '}';

    $result = requestOpenAi($url, $js);
    if (!empty($result->choices)) {
        if ($wo['config']['text_credit_system'] == 1 && $wo['config']['generated_word_price'] > 0) {
            $db->where('user_id', $wo['user']['id'])->update(T_USERS, [
                'credits' => $db->dec(($wo['config']['generated_word_price'] * str_word_count($result->choices[0]->message->content)))
            ]);
        }
        return [
            'status' => 200,
            'output' => $result->choices[0]->message->content,
            'credits' => $db->where('user_id', $wo['user']['id'])->getValue(T_USERS, 'credits')
        ];
    } elseif (!empty($result->error) && !empty($result->error->message)) {
        throw new Exception($result->error->message);
    } else {
        throw new Exception($wo['lang']['something_wrong']);
    }
}

function loadImageContent($url = '')
{
    $ch = curl_init();
    $headers = array(
        'Range: bytes=0-',
    );
    $options = array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_FOLLOWLOCATION => true,
        CURLINFO_HEADER_OUT => true,
        CURLOPT_USERAGENT => 'okhttp',
        CURLOPT_ENCODING => "utf-8",
        CURLOPT_AUTOREFERER => true,
        CURLOPT_COOKIEJAR => 'cookie.txt',
        CURLOPT_COOKIEFILE => 'cookie.txt',
        CURLOPT_REFERER => 'https://oaidalleapiprodscus.blob.core.windows.net/',
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_MAXREDIRS => 10,
    );
    curl_setopt_array($ch, $options);

    $c = curl_exec($ch);
    $image = 'myimage.png';
    $type = pathinfo($image, PATHINFO_EXTENSION);
    $dataUri = 'data:image/' . $type . ';base64,' . base64_encode($c);
    return $dataUri;
}

function shouldTopUpImageCredits($credits = 0, $count = 1)
{
    global $wo, $db;

    if (($wo['config']['generated_image_price'] * $count) > $credits) {
        return true;
    }
    return false;
}

function getAllowedWords()
{
    global $wo, $db;

    $array = [
        20,
        50,
        100,
        250,
        350,
        500,
        1000
    ];

    $max = $wo['config']['maxCharacters'];

    return array_filter($array, function ($item) use ($max) {
        return $item < $max;
    });
}

function getAllowedImagesCount()
{
    global $wo, $db;

    $array = [
        1,
        2,
        3,
        4
    ];

    return array_filter($array, function ($item) use ($wo) {
        if ($item == 2 || $item == 3) {
            return ($wo['config']['images_ai'] == 'midjeourny' && $wo['config']['midjeourny_model'] != 'prompthero-openjourney') || $wo['config']['images_ai'] == 'openai';
        }
        return true;
    });
}

function getMaxAllowedWords()
{
    global $wo, $db;

    if ($wo['config']['text_credit_system'] == 1 && $wo['config']['generated_word_price'] > 0) {

        if ($wo['user']['credits'] < 1) {
            return 0;
        }

        $count = $wo['user']['credits'] / $wo['config']['generated_word_price'];

        if ($count > $wo['config']['maxCharacters']) {
            return $wo['config']['maxCharacters'];
        } else {
            return $count;
        }

    }
    return $wo['config']['maxCharacters'];
}

function getMaxAllowedBlogWords()
{
    global $wo, $db;

    if ($wo['config']['text_credit_system'] == 1 && $wo['config']['generated_word_price'] > 0) {

        if ($wo['user']['credits'] < 1) {
            return 0;
        }

        $count = $wo['user']['credits'] / $wo['config']['generated_word_price'];

        return $count;

    }
    return 10000;
}

function getMaxAllowedImages()
{
    global $wo, $db;

    if ($wo['config']['images_credit_system'] == 1 && $wo['config']['generated_image_price'] > 0) {

        if ($wo['user']['credits'] < 1) {
            return 0;
        }

        $count = $wo['user']['credits'] / $wo['config']['generated_image_price'];

        if ($count > end(getAllowedImagesCount())) {
            return end(getAllowedImagesCount());
        } else {
            return $count;
        }
    }
    return 4;
}

function getAvailableImageBalance()
{
    global $wo, $db;

    if ($wo['config']['images_credit_system'] == 1 && $wo['config']['generated_image_price'] > 0) {
        return $wo['user']['credits'] / $wo['config']['generated_image_price'];
    }
}

function getAvailableWordBalance()
{
    global $wo, $db;

    if ($wo['config']['text_credit_system'] == 1 && $wo['config']['generated_word_price'] > 0) {
        return $wo['user']['credits'] / $wo['config']['generated_word_price'];
    }
}

function getOpenAiBlog($text, $count, $thumbnail = false)
{
    global $wo, $db;

    if (getMaxAllowedBlogWords() < $count) {
        throw new Exception(str_replace('{count}', getMaxAllowedBlogWords(), $wo["lang"]["max_allowed_words"]));
    }

    $url = 'https://api.openai.com/v1/chat/completions';

    $titleText = 'write a title for this article (' . $text . ')';
    $titleJs = '{"model": "' . $wo['config']['openai_text_model'] . '","messages": [{"role": "user", "content": "' . $titleText . '"}]}';
    $titleResult = requestOpenAi($url, $titleJs);

    $desText = 'write a description for this article (' . $text . ')';
    $desJs = '{"model": "' . $wo['config']['openai_text_model'] . '","messages": [{"role": "user", "content": "' . $desText . '"}],"max_tokens": 50}';
    $desResult = requestOpenAi($url, $desJs);

    $tagsText = 'write 10 tags seperated by # for this article (' . $text . ')';
    $tagsJs = '{"model": "' . $wo['config']['openai_text_model'] . '","messages": [{"role": "user", "content": "' . $tagsText . '"}]}';
    $tagsResult = requestOpenAi($url, $tagsJs);

    $contentText = 'write a content for this article (' . $text . ') in ' . $count . ' word max and put it in html';
    $contentJs = '{"model": "' . $wo['config']['openai_text_model'] . '","messages": [{"role": "user", "content": "' . $contentText . '"}]}';
    $contentResult = requestOpenAi($url, $contentJs);

    if (!empty($titleResult->choices) && !empty($desResult->choices)) {
        if ($wo['config']['text_credit_system'] == 1 && $wo['config']['generated_word_price'] > 0 && !empty($contentResult->choices)) {
            $full_content = strip_tags($contentResult->choices[0]->message->content);
            $dec = ($wo['config']['generated_word_price'] * str_word_count($full_content));
            if ($dec > $wo['user']['credits']) {
                $dec = $wo['user']['credits'];
            }
            $db->where('user_id', $wo['user']['id'])->update(T_USERS, [
                'credits' => $db->dec($dec)
            ]);
        }

        $title = !empty($titleResult->choices) && !empty($titleResult->choices[0]) ? str_replace('"', '', $titleResult->choices[0]->message->content) : '';
        $description = !empty($desResult->choices) && !empty($desResult->choices[0]) ? $desResult->choices[0]->message->content : '';
        $content = !empty($contentResult->choices) && !empty($contentResult->choices[0]) ? $contentResult->choices[0]->message->content : '';
        $tags = !empty($tagsResult->choices) && !empty($tagsResult->choices[0]) && strpos($tagsResult->choices[0]->message->content, '#') !== false ? str_replace('#', ',', $tagsResult->choices[0]->message->content) : '';

        $output = null;
        if ($thumbnail == true && !empty($title)) {
            $result = getOpenAiImage($title, '1024x1024', 1);
            if (!empty($result['data'])) {
                $urls = array_map(function ($img) {
                    return loadImageContent($img->url);
                }, $result['data']);
                $output = $urls;
            }
        }

        return [
            'status' => 200,
            'title' => $title,
            'description' => $description,
            'content' => $content,
            'output' => $output,
            'tags' => $tags,
            'credits' => $db->where('user_id', $wo['user']['id'])->getValue(T_USERS, 'credits')
        ];
    } elseif (!empty($titleResult->error) && !empty($titleResult->error->message)) {
        throw new Exception($titleResult->error->message);
    } elseif (!empty($desResult->error) && !empty($desResult->error->message)) {
        throw new Exception($desResult->error->message);
    } elseif (!empty($contentResult->error) && !empty($contentResult->error->message)) {
        throw new Exception($contentResult->error->message);
    } else {
        throw new Exception($wo['lang']['something_wrong']);
    }
}

function getMidJeournyModels($type = 'stability-ai-stable-diffusion')
{
    $midJeournyModels = array(
        'prompthero-openjourney' => [
            'size' => [
                '128x128',
                '256x256',
                '512x512',
                '768x768',
                '1024x1024'
            ]
        ],
        'stability-ai-stable-diffusion' => [
            'size' => [
                '512x512',
                '768x768'
            ]
        ],
        '22-hours-vintedois-diffusion' => [
            'size' => [
                '128x128',
                '256x256',
                '384x384',
                '448x448',
                '512x512',
                '576x576',
                '640x640',
                '704x704',
                '768x768',
                '832x832',
                '896x896',
                '960x960',
                '1024x1024'
            ]
        ]
    );

    return $midJeournyModels[$type]['size'];
}

function getTwoFactorText()
{
    global $wo, $db;

    if ($wo['config']['two_factor_type'] == 'both') {
        return $wo['lang']['email'] . ' ' . $wo['lang']['sms'];
    } else if ($wo['config']['two_factor_type'] == 'email') {
        return $wo['lang']['email'];
    } else if ($wo['config']['two_factor_type'] == 'phone') {
        return $wo['lang']['sms'];
    }
}

function getCountriesCodes()
{
    return [
        '44' => 'UK (+44)',
        '1' => 'USA (+1)',
        '213' => 'Algeria (+213)',
        '376' => 'Andorra (+376)',
        '244' => 'Angola (+244)',
        '1264' => 'Anguilla (+1264)',
        '1268' => 'Antigua & Barbuda (+1268)',
        '54' => 'Argentina (+54)',
        '374' => 'Armenia (+374)',
        '297' => 'Aruba (+297)',
        '61' => 'Australia (+61)',
        '43' => 'Austria (+43)',
        '994' => 'Azerbaijan (+994)',
        '1242' => 'Bahamas (+1242)',
        '973' => 'Bahrain (+973)',
        '880' => 'Bangladesh (+880)',
        '1246' => 'Barbados (+1246)',
        '375' => 'Belarus (+375)',
        '32' => 'Belgium (+32)',
        '501' => 'Belize (+501)',
        '229' => 'Benin (+229)',
        '1441' => 'Bermuda (+1441)',
        '975' => 'Bhutan (+975)',
        '591' => 'Bolivia (+591)',
        '387' => 'Bosnia Herzegovina (+387)',
        '267' => 'Botswana (+267)',
        '55' => 'Brazil (+55)',
        '673' => 'Brunei (+673)',
        '359' => 'Bulgaria (+359)',
        '226' => 'Burkina Faso (+226)',
        '257' => 'Burundi (+257)',
        '855' => 'Cambodia (+855)',
        '237' => 'Cameroon (+237)',
        '1' => 'Canada (+1)',
        '238' => 'Cape Verde Islands (+238)',
        '1345' => 'Cayman Islands (+1345)',
        '236' => 'Central African Republic (+236)',
        '56' => 'Chile (+56)',
        '86' => 'China (+86)',
        '57' => 'Colombia (+57)',
        '269' => 'Comoros (+269)',
        '242' => 'Congo (+242)',
        '682' => 'Cook Islands (+682)',
        '506' => 'Costa Rica (+506)',
        '385' => 'Croatia (+385)',
        '53' => 'Cuba (+53)',
        '90392' => 'Cyprus North (+90392)',
        '357' => 'Cyprus South (+357)',
        '42' => 'Czech Republic (+42)',
        '45' => 'Denmark (+45)',
        '253' => 'Djibouti (+253)',
        '1809' => 'Dominica (+1809)',
        '1809' => 'Dominican Republic (+1809)',
        '593' => 'Ecuador (+593)',
        '20' => 'Egypt (+20)',
        '503' => 'El Salvador (+503)',
        '240' => 'Equatorial Guinea (+240)',
        '291' => 'Eritrea (+291)',
        '372' => 'Estonia (+372)',
        '251' => 'Ethiopia (+251)',
        '500' => 'Falkland Islands (+500)',
        '298' => 'Faroe Islands (+298)',
        '679' => 'Fiji (+679)',
        '358' => 'Finland (+358)',
        '33' => 'France (+33)',
        '594' => 'French Guiana (+594)',
        '689' => 'French Polynesia (+689)',
        '241' => 'Gabon (+241)',
        '220' => 'Gambia (+220)',
        '7880' => 'Georgia (+7880)',
        '49' => 'Germany (+49)',
        '233' => 'Ghana (+233)',
        '350' => 'Gibraltar (+350)',
        '30' => 'Greece (+30)',
        '299' => 'Greenland (+299)',
        '1473' => 'Grenada (+1473)',
        '590' => 'Guadeloupe (+590)',
        '671' => 'Guam (+671)',
        '502' => 'Guatemala (+502)',
        '224' => 'Guinea (+224)',
        '245' => 'Guinea - Bissau (+245)',
        '592' => 'Guyana (+592)',
        '509' => 'Haiti (+509)',
        '504' => 'Honduras (+504)',
        '852' => 'Hong Kong (+852)',
        '36' => 'Hungary (+36)',
        '354' => 'Iceland (+354)',
        '91' => 'India (+91)',
        '62' => 'Indonesia (+62)',
        '98' => 'Iran (+98)',
        '964' => 'Iraq (+964)',
        '353' => 'Ireland (+353)',
        '972' => 'Israel (+972)',
        '39' => 'Italy (+39)',
        '1876' => 'Jamaica (+1876)',
        '81' => 'Japan (+81)',
        '962' => 'Jordan (+962)',
        '7' => 'Kazakhstan (+7)',
        '254' => 'Kenya (+254)',
        '686' => 'Kiribati (+686)',
        '850' => 'Korea North (+850)',
        '82' => 'Korea South (+82)',
        '965' => 'Kuwait (+965)',
        '996' => 'Kyrgyzstan (+996)',
        '856' => 'Laos (+856)',
        '371' => 'Latvia (+371)',
        '961' => 'Lebanon (+961)',
        '266' => 'Lesotho (+266)',
        '231' => 'Liberia (+231)',
        '218' => 'Libya (+218)',
        '417' => 'Liechtenstein (+417)',
        '370' => 'Lithuania (+370)',
        '352' => 'Luxembourg (+352)',
        '853' => 'Macao (+853)',
        '389' => 'Macedonia (+389)',
        '261' => 'Madagascar (+261)',
        '265' => 'Malawi (+265)',
        '60' => 'Malaysia (+60)',
        '960' => 'Maldives (+960)',
        '223' => 'Mali (+223)',
        '356' => 'Malta (+356)',
        '692' => 'Marshall Islands (+692)',
        '596' => 'Martinique (+596)',
        '222' => 'Mauritania (+222)',
        '269' => 'Mayotte (+269)',
        '52' => 'Mexico (+52)',
        '691' => 'Micronesia (+691)',
        '373' => 'Moldova (+373)',
        '377' => 'Monaco (+377)',
        '976' => 'Mongolia (+976)',
        '1664' => 'Montserrat (+1664)',
        '212' => 'Morocco (+212)',
        '258' => 'Mozambique (+258)',
        '95' => 'Myanmar (+95)',
        '264' => 'Namibia (+264)',
        '674' => 'Nauru (+674)',
        '977' => 'Nepal (+977)',
        '31' => 'Netherlands (+31)',
        '687' => 'New Caledonia (+687)',
        '64' => 'New Zealand (+64)',
        '505' => 'Nicaragua (+505)',
        '227' => 'Niger (+227)',
        '234' => 'Nigeria (+234)',
        '683' => 'Niue (+683)',
        '672' => 'Norfolk Islands (+672)',
        '670' => 'Northern Marianas (+670)',
        '47' => 'Norway (+47)',
        '968' => 'Oman (+968)',
        '680' => 'Palau (+680)',
        '507' => 'Panama (+507)',
        '675' => 'Papua New Guinea (+675)',
        '595' => 'Paraguay (+595)',
        '51' => 'Peru (+51)',
        '63' => 'Philippines (+63)',
        '48' => 'Poland (+48)',
        '351' => 'Portugal (+351)',
        '1787' => 'Puerto Rico (+1787)',
        '974' => 'Qatar (+974)',
        '262' => 'Reunion (+262)',
        '40' => 'Romania (+40)',
        '7' => 'Russia (+7)',
        '250' => 'Rwanda (+250)',
        '378' => 'San Marino (+378)',
        '239' => 'Sao Tome & Principe (+239)',
        '966' => 'Saudi Arabia (+966)',
        '221' => 'Senegal (+221)',
        '381' => 'Serbia (+381)',
        '248' => 'Seychelles (+248)',
        '232' => 'Sierra Leone (+232)',
        '65' => 'Singapore (+65)',
        '421' => 'Slovak Republic (+421)',
        '386' => 'Slovenia (+386)',
        '677' => 'Solomon Islands (+677)',
        '252' => 'Somalia (+252)',
        '27' => 'South Africa (+27)',
        '34' => 'Spain (+34)',
        '94' => 'Sri Lanka (+94)',
        '290' => 'St. Helena (+290)',
        '1869' => 'St. Kitts (+1869)',
        '1758' => 'St. Lucia (+1758)',
        '249' => 'Sudan (+249)',
        '597' => 'Suriname (+597)',
        '268' => 'Swaziland (+268)',
        '46' => 'Sweden (+46)',
        '41' => 'Switzerland (+41)',
        '963' => 'Syria (+963)',
        '886' => 'Taiwan (+886)',
        '7' => 'Tajikstan (+7)',
        '66' => 'Thailand (+66)',
        '228' => 'Togo (+228)',
        '676' => 'Tonga (+676)',
        '1868' => 'Trinidad & Tobago (+1868)',
        '216' => 'Tunisia (+216)',
        '90' => 'Turkey (+90)',
        '7' => 'Turkmenistan (+7)',
        '993' => 'Turkmenistan (+993)',
        '1649' => 'Turks & Caicos Islands (+1649)',
        '688' => 'Tuvalu (+688)',
        '256' => 'Uganda (+256)',
        '380' => 'Ukraine (+380)',
        '971' => 'United Arab Emirates (+971)',
        '598' => 'Uruguay (+598)',
        '7' => 'Uzbekistan (+7)',
        '678' => 'Vanuatu (+678)',
        '379' => 'Vatican City (+379)',
        '58' => 'Venezuela (+58)',
        '84' => 'Vietnam (+84)',
        '84' => 'Virgin Islands - British (+1284)',
        '84' => 'Virgin Islands - US (+1340)',
        '681' => 'Wallis & Futuna (+681)',
        '969' => 'Yemen (North)(+969)',
        '967' => 'Yemen (South)(+967)',
        '260' => 'Zambia (+260)',
        '263' => 'Zimbabwe (+263)',
    ];
}

function getAuthyQR($authy_id = '')
{
    global $sqlConnect, $db, $wo;
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, 'https://api.authy.com/protected/json/users/' . $authy_id . '/secret');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "label=\"" . $wo['config']['siteTitle'] . "(" . $wo['user']['username'] . ")\"&qr_size=\"300\"");

    $headers = array();
    $headers[] = 'X-Authy-Api-Key: ' . $wo['config']['authy_token'];
    $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        return false;
    }
    curl_close($ch);
    $result = json_decode($result);
    if (!empty($result) && !empty($result->qr_code)) {
        return $result->qr_code;
    }
    return false;
}

function verifyAuthy($code = '', $authy_id = '')
{
    global $sqlConnect, $db, $wo;
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, 'https://api.authy.com/protected/json/verify/' . $code . '/' . $authy_id);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');


    $headers = array();
    $headers[] = 'X-Authy-Api-Key: ' . $wo['config']['authy_token'];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        return false;
    }
    curl_close($ch);
    $result = json_decode($result);
    if (!empty($result) && !empty($result->success)) {
        return true;
    }
    return false;
}

function getSunshineLogo()
{
    global $sqlConnect, $db, $wo;
    if (file_exists('themes/sunshine/img/night-logo.png') && !empty($_COOKIE['mode']) && $_COOKIE['mode'] == 'night') {
        return 'night-';
    }
    return '';
}

function createBackupCodes($count = 10)
{
    $backupCodes = array();
    for ($i = 1; $i <= 10; $i++) {
        $backupCodes[] = rand(111111, 999999);
    }
    return $backupCodes;
}

function createBackupCodesFile($backupCodes, $fileName)
{
    $fp = fopen('php://output', 'w');
    array_map(function ($code) use ($fp) {
        fputcsv($fp, array($code));
    }, $backupCodes);
    fclose($fp);
}

function fluttewavePay($amount, $email)
{
    global $sqlConnect, $wo, $db;

    //* Prepare our rave request
    $request = [
        'tx_ref' => time(),
        'amount' => $amount,
        'currency' => 'NGN',
        'payment_options' => 'card',
        'redirect_url' => $wo['config']['site_url'] . "/requests.php?f=fluttewave&s=success",
        'customer' => [
            'email' => $email,
            'name' => 'user_' . uniqid()
        ],
        'meta' => [
            'price' => $amount
        ],
        'customizations' => [
            'title' => 'Top Up Wallet',
            'description' => 'Top Up Wallet'
        ]
    ];

    //* Ca;; f;iterwave emdpoint
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.flutterwave.com/v3/payments',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($request),
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $wo['config']['fluttewave_secret_key'],
            'Content-Type: application/json'
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);

    return json_decode($response);
}

function fluttewaveVerify($txid)
{
    global $sqlConnect, $wo, $db;

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.flutterwave.com/v3/transactions/{$txid}/verify",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json",
            "Authorization: Bearer " . $wo['config']['fluttewave_secret_key']
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);

    return json_decode($response);
}

function createCoinbase($postdata)
{
    global $sqlConnect, $wo, $db;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, 'https://api.commerce.coinbase.com/charges');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata));

    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'X-Cc-Api-Key: ' . $wo['config']['coinbase_key'];
    $headers[] = 'X-Cc-Version: 2018-03-22';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new Exception(curl_error($ch));
    }
    curl_close($ch);

    return json_decode($result, true);
}

function chargeCoinbase($coinbase_code)
{
    global $sqlConnect, $wo, $db;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, 'https://api.commerce.coinbase.com/charges/' . $coinbase_code);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'X-Cc-Api-Key: ' . $wo['config']['coinbase_key'];
    $headers[] = 'X-Cc-Version: 2018-03-22';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new Exception(curl_error($ch));
    }
    curl_close($ch);
    return json_decode($result, true);
}

function payUsingAamarpay($amount, $name, $email, $phone)
{
    global $sqlConnect, $wo, $db;

    if ($wo['config']['aamarpay_mode'] == 'sandbox') {
        $url = 'https://sandbox.aamarpay.com/request.php'; // live url https://secure.aamarpay.com/request.php
    } else {
        $url = 'https://secure.aamarpay.com/request.php';
    }
    $tran_id = rand(1111111, 9999999);
    $fields = array(
        'store_id' => $wo['config']['aamarpay_store_id'], //store id will be aamarpay,  contact integration@aamarpay.com for test/live id
        'amount' => $amount, //transaction amount
        'payment_type' => 'VISA', //no need to change
        'currency' => 'BDT',  //currenct will be USD/BDT
        'tran_id' => $tran_id, //transaction id must be unique from your end
        'cus_name' => $name,  //customer name
        'cus_email' => $email, //customer email address
        'cus_add1' => '',  //customer address
        'cus_add2' => '', //customer address
        'cus_city' => '',  //customer city
        'cus_state' => '',  //state
        'cus_postcode' => '', //postcode or zipcode
        'cus_country' => 'Bangladesh',  //country
        'cus_phone' => $phone, //customer phone number
        'cus_fax' => 'Not¬Applicable',  //fax
        'ship_name' => '', //ship name
        'ship_add1' => '',  //ship address
        'ship_add2' => '',
        'ship_city' => '',
        'ship_state' => '',
        'ship_postcode' => '',
        'ship_country' => 'Bangladesh',
        'desc' => 'top up wallet',
        'success_url' => $wo['config']['site_url'] . "/requests.php?f=aamarpay&s=success_aamarpay", //your success route
        'fail_url' => $wo['config']['site_url'] . "/requests.php?f=aamarpay&s=cancel_aamarpay", //your fail route
        'cancel_url' => $wo['config']['site_url'] . "/requests.php?f=aamarpay&s=cancel_aamarpay", //your cancel url
        'opt_a' => $wo['user']['user_id'],  //optional paramter
        'opt_b' => '',
        'opt_c' => '',
        'opt_d' => '',
        'signature_key' => $wo['config']['aamarpay_signature_key'] //signature key will provided aamarpay, contact integration@aamarpay.com for test/live signature key
    );
    $fields_string = http_build_query($fields);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_URL, $url);

    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);
    $url_forward = str_replace('"', '', stripslashes($result));
    curl_close($ch);
    if ($wo['config']['aamarpay_mode'] == 'sandbox') {
        $base_url = 'https://sandbox.aamarpay.com/' . $url_forward;
    } else {
        $base_url = 'https://secure.aamarpay.com/' . $url_forward;
    }
    return $base_url;
}

function Wo_SubscriptionPay($monetization_id)  {
    global $wo, $sqlConnect, $cache, $db;

    $data     = array(
        'status' => 400
    );
    $monetization_id  = (!empty($monetization_id)) && is_numeric($monetization_id) ? $monetization_id : 0;
    $monetization = $db->where('id', Wo_Secure($monetization_id))->getOne(T_USER_MONETIZATION);

    $currency        = $monetization->currency;
    $commission_percentage = $wo['config']['monetization_commission_percentage'];
    $user_id = $monetization->user_id;
    $amount   = $monetization->price;
    $divide = 1;
    if (!empty($wo['config']['exchange']) && in_array($wo['currencies'][$monetization->currency]['text'], $wo['config']['exchange'])) {
        $divide = $wo['config']['exchange'][$wo['currencies'][$monetization->currency]['text']];
    }
    $amount = ($amount / $divide);

    $admin_commission = $amount * $commission_percentage / 100;
    $amount_to_be_sent = $amount - $admin_commission;
    $userdata = Wo_UserData($user_id);
    $wallet   = $wo['user']['wallet'];

    if ($user_id ==  $wo['user']['id']) {
        $data['message'] = $wo['lang']['please_check_details'];
    }
    if (empty($user_id) || empty($amount) || empty($userdata)) {
        $data['message'] = $wo['lang']['please_check_details'];
        return $data;
    } else if ($wallet < $amount) {
        $link = '<a href="'.Wo_SeoLink('index.php?link1=wallet').'" data-ajax="?link1=wallet">'.$wo['lang']['top_up'].'</a>';
        $data['message'] = str_replace('{topup}', $link, $wo['lang']['no_money_for_subscriptions']);
        return $data;
    } else {
        $amount          = ($amount <= $wallet) ? $amount : $wallet;
        $up_data1        = array(
            'balance' => sprintf('%.2f', $userdata['balance'] + $amount_to_be_sent)
        );
        $up_data2        = array(
            'wallet' => sprintf('%.2f', $wallet - $amount)
        );
        $recipient_name  = $userdata['username'];
        $success_msg     = str_replace('{text}', $recipient_name, $wo['lang']['subscribed_successfully']);;
        $notif_msg       = $wo['lang']['sent_you'];
        $notif_msg_for_sub       = $wo['lang']['subscribed_to_you'];
        $data['status']  = 200;
        $data['message'] = "$success_msg";
        $data['redirect_after_subscription'] = $userdata['url'];
        $extra = [
            'from_id' => $wo['user']['user_id'],
            'type' => 'monetization_subscription',
            'monetization_id' => $monetization_id,
        ];
        $extra = json_encode($extra);
        //$note1           = str_replace('{text}', $recipient_name, $wo['lang']['subscribed_to']);
        $note1           = $userdata['name'];
        //$note2           = str_replace('{text}', $wo['user']['username'], $wo['lang']['subscription_earnings']);
        $note2           = $wo['user']['name'];
        $db->where('user_id', $user_id)->update(T_USERS, $up_data1);

        mysqli_query($sqlConnect, "INSERT INTO " . T_PAYMENT_TRANSACTIONS . " (`userid`, `kind`, `amount`, `notes`, `admin_commission`, `extra`) VALUES ({$user_id}, 'RECEIVED', {$amount_to_be_sent}, '{$note2}', '{$admin_commission}', '{$extra}')");
        $db->where('user_id', $wo['user']['id'])->update(T_USERS, $up_data2);

        $extra = [
            'to_id' => $user_id,
            'type' => 'monetization_subscription',
            'monetization_id' => $monetization_id,
        ];
        $extra = json_encode($extra);

        mysqli_query($sqlConnect, "INSERT INTO " . T_PAYMENT_TRANSACTIONS . " (`userid`, `kind`, `amount`, `notes`, `extra`) VALUES ({$wo['user']['user_id']}, 'PURCHASE', {$amount}, '{$note1}', '{$extra}')");
        cache($user_id, 'users', 'delete');
        cache($wo['user']['id'], 'users', 'delete');

        $monetizationSubscription = $db->where('user_id', $wo['user']['id'])
            ->where('monetization_id', $monetization_id)
            ->getOne(T_MONETIZATION_SUBSCRIBTION);


        $expire = time() + (60 * 60 * 24 * $monetization->paid_every);

        if(!$monetizationSubscription) {
            $db->insert(T_MONETIZATION_SUBSCRIBTION, array(
                'user_id' => $wo['user']['user_id'],
                'monetization_id' => $monetization->id,
                'status' => 1,
                'expire' => $expire,
            ));
        } else {
            $db->where('monetization_id', $monetization_id)
                ->update(T_MONETIZATION_SUBSCRIBTION, array(
                    'status' => 1,
                    'last_payment_date' => date('Y-m-d H:i:s'),
                    'expire' => $expire,
                ));
        }


        $notification_data_array = array(
            'recipient_id' => $user_id,
            'type' => 'subscribed_to_you',
            'user_id' => $wo['user']['id'],
            'text' => $monetization->title,
            'url' => 'index.php?link1=wallet'
        );
        Wo_RegisterNotification($notification_data_array);


        return $data;
    }
}


function createCashfreeOrder($data = [])
{
    global $wo, $sqlConnect,$db,$lang_array;

    $customer_id = "customer" . uniqid();

    $info = array(
        'order_amount' => $data['amount'],
        'order_currency' => 'INR'
    );
    $info['customer_details'] = array(
        'customer_id' => $customer_id,
        'customer_email' => $data['email'],
        'customer_phone' => $data['phone']
    );
    $info['order_meta'] = array(
        'return_url' => $data['return_url'],
        'notify_url' => $data['notify_url'],
    );


    $ch = curl_init();

    $url = 'https://sandbox.cashfree.com';
    if ($wo['config']['cashfree_mode'] == 'live') {
        $url = 'https://api.cashfree.com';
    }

    curl_setopt($ch, CURLOPT_URL, $url . '/pg/orders');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($info));

    $headers = array();
    $headers[] = 'Accept: application/json';
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'X-Api-Version: 2022-09-01';
    $headers[] = 'X-Client-Id: ' . $wo['config']['cashfree_client_key'];
    $headers[] = 'X-Client-Secret: ' . $wo['config']['cashfree_secret_key'];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new Exception(curl_error($ch));
    }
    curl_close($ch);
    $result = json_decode($result,true);
    if (!empty($result['payment_session_id'])) {
        return $result['payment_session_id'];
    }
    elseif (!empty($result['message'])) {
        throw new Exception($result['message']);
    }
    else{
        throw new Exception($lang_array['error_msg']);
    }
}

function payCashfreeOrder($data = [])
{
    global $wo, $sqlConnect,$db,$lang_array;

    $card = array(
        'channel' => 'link',
        'card_number' => $data['card_number'],
        'card_holder_name' => $data['card_holder_name'],
        'card_expiry_mm' => $data['card_expiry_mm'],
        'card_expiry_yy' => $data['card_expiry_yy'],
        'card_cvv' => $data['card_cvv']
    );

    $info = array(
        'payment_session_id' => $data['payment_session_id'],
        'payment_method' => array(
            'card' => $card
        )
    );


    $ch = curl_init();

    $url = 'https://sandbox.cashfree.com';
    if ($wo['config']['cashfree_mode'] == 'live') {
        $url = 'https://api.cashfree.com';
    }

    curl_setopt($ch, CURLOPT_URL, $url . '/pg/orders/sessions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($info));

    $headers = array();
    $headers[] = 'Accept: application/json';
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'X-Api-Version: 2022-09-01';
    $headers[] = 'X-Client-Id: ' . $wo['config']['cashfree_client_key'];
    $headers[] = 'X-Client-Secret: ' . $wo['config']['cashfree_secret_key'];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new Exception(curl_error($ch));
    }
    curl_close($ch);
    $result = json_decode($result,true);
    if (!empty($result['data']) && !empty($result['data']['url'])) {
        return $result['data']['url'];
    }
    elseif (!empty($result['message'])) {
        throw new Exception($result['message']);
    }
    else{
        throw new Exception($lang_array['error_msg']);
    }
}

function getCashfreeOrder($order_id = '')
{
    global $wo, $sqlConnect,$db,$lang_array;

    $ch = curl_init();

    $url = 'https://sandbox.cashfree.com';
    if ($wo['config']['cashfree_mode'] == 'live') {
        $url = 'https://api.cashfree.com';
    }

    curl_setopt($ch, CURLOPT_URL, $url . '/pg/orders/' . $order_id);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');


    $headers = array();
    $headers[] = 'Accept: application/json';
    $headers[] = 'X-Api-Version: 2022-09-01';
    $headers[] = 'X-Client-Id: ' . $wo['config']['cashfree_client_key'];
    $headers[] = 'X-Client-Secret: ' . $wo['config']['cashfree_secret_key'];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new Exception(curl_error($ch));
    }
    curl_close($ch);
    $result = json_decode($result,true);
    if (!empty($result['order_status']) && $result['order_status'] == 'PAID' && !empty($result['order_amount'])) {
        return $result['order_amount'];
    }
    elseif (!empty($result['message'])) {
        throw new Exception($result['message']);
    }
    else{
        throw new Exception($lang_array['error_msg']);
    }
}

function cleanConfigData()
{
    global $wo, $sqlConnect,$db,$lang_array;

    foreach ($wo['encryptedKeys'] as $key => $value) {
        if (in_array($value, array_keys($wo['config']))) {
            $wo['config'][$value] = '';
        }
    }
}

function decryptConfigData()
{
    global $wo, $sqlConnect,$db,$siteEncryptKey;

    foreach ($wo['encryptedKeys'] as $key => $value) {
        if (in_array($value, array_keys($wo['config'])) && strpos($wo['config'][$value],'$Ap1_') !== false) {
            $tx = str_replace('$Ap1_', '', $wo['config'][$value]);
            $wo['config'][$value] = openssl_decrypt($tx, "AES-128-ECB", $siteEncryptKey);
        }
    }
}

// function getCountriesAds()
// {
//     global $wo, $sqlConnect, $db,$non_allowed;
//     $countriesData = [];
//     $countries = $db->get(T_COUNTRIES_ADS);
//     foreach ($countries as $key => $value) {
//         if (in_array($value->country_id, array_keys($wo['countries_name']))) {
//             $value->name = $wo['countries_name'][$value->country_id];
//             $countriesData[$value->country_id] = $value;
//         }
//     }
//     return $countriesData;
// }