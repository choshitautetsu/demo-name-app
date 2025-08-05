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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name'] ?? '');
    $family_name = trim($_POST['family_name'] ?? '');

    if (empty($first_name) || empty($family_name)) {
        die("请输入有效的名和姓");
    }

    $full_name = $first_name . ' ' . $family_name;

    $stmt = $conn->prepare("INSERT INTO names (first_name, family_name, name) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $first_name, $family_name, $full_name);
    $stmt->execute();
    $stmt->close();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// 查询展示时优先使用new字段拼接显示
$result = $conn->query("SELECT first_name, family_name, name FROM names ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8" />
    <title>名字存储展示 - Green版本</title>
</head>
<body>
    <h1>请输入你的名字</h1>
    <form method="POST">
        <input type="text" name="first_name" placeholder="名 (First Name)" required />
        <input type="text" name="family_name" placeholder="姓 (Family Name)" required />
        <button type="submit">提交</button>
    </form>

    <h2>已有名字列表</h2>
    <?php 
    if ($result && $result->num_rows > 0) {
        echo "<ul>";
        while ($row = $result->fetch_assoc()) {
            // 如果first_name和family_name有值，则优先显示拼接的新格式
            if (!empty($row['first_name']) && !empty($row['family_name'])) {
                echo "<li>" . htmlspecialchars($row['first_name']) . " " . htmlspecialchars($row['family_name']) . "</li>";
            } else {
                // 兼容旧数据，显示旧的name字段
                echo "<li>" . htmlspecialchars($row['name']) . "</li>";
            }
        }
        echo "</ul>";
    } else {
        echo "<p>目前没有名字</p>";
    }
    $conn->close();
    ?>
</body>
</html>
