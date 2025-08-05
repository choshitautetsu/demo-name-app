<?php
// 读取环境变量连接参数
$servername = getenv('MYSQL_HOST') ?: 'mysql';
$username = getenv('MYSQL_USER') ?: 'root';
$password = getenv('MYSQL_PASSWORD') ?: '';
$dbname = getenv('MYSQL_DATABASE') ?: 'namedb';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("连接数据库失败: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['username'])) {
    $name = trim($_POST['username']);
    $stmt = $conn->prepare("INSERT INTO names (name) VALUES (?)");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $stmt->close();
    // 重定向避免重复提交
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$result = $conn->query("SELECT name FROM names ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8" />
    <title>名字存储展示</title>
</head>
<body>
    <h1>请输入名字</h1>
    <form method="POST">
        <input type="text" name="username" placeholder="请输入名字" required />
        <button type="submit">提交</button>
    </form>

    <h2>已有名字列表</h2>
    <?php 
    if($result->num_rows > 0) {
        echo "<ul>";
        while($row = $result->fetch_assoc()) {
            echo "<li>" . htmlspecialchars($row['name']) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>目前没有名字</p>";
    }
    $conn->close();
    ?>
</body>
</html>
