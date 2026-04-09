<?php
header('Content-Type: application/json');

// 1. 数据库连接 (请确保 db.php 路径正确，或者直接写连接代码)
$host = 'localhost';
$dbuser = 'root';
$dbpass = '';
$dbname = 'myliferepo'; // 请修改为你的数据库名
$conn = new mysqli($host, $dbuser, $dbpass, $dbname);

if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'reply' => '数据库连接失败']);
    exit;
}

// 2. 接收前端数据
$json = file_get_contents('php://input');
$data = json_decode($json, true);
$userMsg = $data['message'] ?? '';
$username = $data['username'] ?? 'MYJINS';

if (!$userMsg) {
    echo json_encode(['status' => 'error', 'reply' => '没有收到消息']);
    exit;
}

// 3. 获取用户资料背景
$u_sql = "SELECT nickname, hometown, hobby, bio FROM users WHERE username = '$username'";
$u_res = $conn->query($u_sql);
$u = $u_res->fetch_assoc();

// 4. 获取最近 3 条动态
$p_sql = "SELECT content FROM posts WHERE username = '$username' ORDER BY created_at DESC LIMIT 3";
$p_res = $conn->query($p_sql);
$recent_posts = "";
if($p_res) {
    while($row = $p_res->fetch_assoc()) {
        $recent_posts .= " - " . $row['content'] . "\n";
    }
}

// 5. 构造系统提示词 (注入上下文)
$nickname = $u['nickname'] ?? $username;
$hometown = $u['hometown'] ?? '未知位置';
$hobby = $u['hobby'] ?? '生活';
$bio = $u['bio'] ?? '';

$systemPrompt = "你是一个名为 MyLifeRepo 的私人助手。
当前用户昵称：$nickname
来自：$hometown
爱好：$hobby
个人简介：$bio
他最近发的动态如下：
$recent_posts
请结合以上信息与用户交流，说话要亲切、简短、像个老朋友。";

// 6. 调用 DeepSeek API
$api_key = "sk-741c969e10fe4f89a9278c29dbc26df6"; // 建议调试完后更换
$api_url = "https://api.deepseek.com/chat/completions";

$post_data = [
    "model" => "deepseek-chat",
    "messages" => [
        ["role" => "system", "content" => $systemPrompt],
        ["role" => "user", "content" => $userMsg]
    ],
    "stream" => false
];

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $api_key
]);

// 本地开发跳过 SSL 检查
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code == 200) {
    $res = json_decode($response, true);
    $ai_reply = $res['choices'][0]['message']['content'] ?? 'AI 暂时没想好怎么回。';
    echo json_encode(['status' => 'success', 'reply' => $ai_reply]);
} else {
    echo json_encode(['status' => 'error', 'reply' => 'API 响应异常，代码：' . $http_code, 'debug' => $response]);
}

$conn->close();
?>