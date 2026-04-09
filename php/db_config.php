<?php
// db_config.php - 数据库连接配置

$servername = "localhost"; // 本地服务器
$username = "root";        // XAMPP 默认用户名
$password = "";            // XAMPP 默认密码为空
$dbname = "myliferepo";    // 你在 phpMyAdmin 里创建的数据库名

// 1. 创建连接
$conn = new mysqli($servername, $username, $password, $dbname);

// 2. 检查连接是否成功
if ($conn->connect_error) {
    // 如果连接失败，报错并停止
    die("数据库连接失败: " . $conn->connect_error);
}

// 3. 设置编码为 utf8mb4，确保中文不乱码
$conn->set_charset("utf8mb4");

// 这个文件被其他 PHP 文件引用后，就可以直接使用 $conn 变量操作数据库了
?>