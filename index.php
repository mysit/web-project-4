<?php
header('Content-Type: text/html; charset=UTF-8');

$user = 'u82196';
$pass = '4736526';
$db_name = 'u82196';
$host = 'localhost';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $messages = array();
    if (!empty($_COOKIE['save'])) {
        setcookie('save', '', 100000);
        $messages[] = '<div class="success-msg">Спасибо, результаты сохранены.</div>';
    }

    $errors = array();
    // Заменили phone на number
    $fields = ['fullName', 'email', 'number', 'bdate', 'gender', 'bio', 'languages', 'privacy'];
    foreach ($fields as $f) {
        $errors[$f] = !empty($_COOKIE[$f . '_error']);
    }

    if ($errors['fullName']) {
        setcookie('fullName_error', '', 100000);
        $messages[] = '<div class="error-msg">Заполните ФИО.</div>';
    }
    // Можно добавить сообщения для других ошибок здесь...

    $values = array();
    foreach ($fields as $f) {
        $values[$f] = empty($_COOKIE[$f . '_value']) ? '' : $_COOKIE[$f . '_value'];
    }
    $values['languages'] = empty($_COOKIE['languages_value']) ? [] : explode(',', $_COOKIE['languages_value']);

    include('form.php');
} 
else {
    $errors = FALSE;

    // Валидация
    if (empty($_POST['fullName'])) {
        setcookie('fullName_error', '1', time() + 24 * 3600);
        $errors = TRUE;
    }
    setcookie('fullName_value', $_POST['fullName'], time() + 30 * 24 * 3600);

    if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        setcookie('email_error', '1', time() + 24 * 3600);
        $errors = TRUE;
    }
    setcookie('email_value', $_POST['email'], time() + 30 * 24 * 3600);

    // Для number (бывший phone) просто сохраняем значение
    setcookie('number_value', $_POST['number'], time() + 30 * 24 * 3600);
    setcookie('bdate_value', $_POST['bdate'], time() + 30 * 24 * 3600);
    setcookie('gender_value', $_POST['gender'], time() + 30 * 24 * 3600);
    setcookie('bio_value', $_POST['bio'], time() + 30 * 24 * 3600);
    setcookie('privacy_value', $_POST['privacy'], time() + 30 * 24 * 3600);

    if (!empty($_POST['languages'])) {
        setcookie('languages_value', implode(',', $_POST['languages']), time() + 30 * 24 * 3600);
    }

    if ($errors) {
        header('Location: index.php');
        exit();
    }

    // Сохранение в БД
    try {
        $db = new PDO("mysql:host=$host;dbname=$db_name", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // ВАЖНО: Тут теперь колонка number вместо phone
        $stmt = $db->prepare("INSERT INTO application (name, email, number, bday, sex, bio) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['fullName'], 
            $_POST['email'], 
            $_POST['number'], 
            $_POST['bdate'], 
            $_POST['gender'], 
            $_POST['bio']
        ]);

        $id = $db->lastInsertId();
        $stmt_l = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
        if (!empty($_POST['languages'])) {
            foreach ($_POST['languages'] as $l) {
                $stmt_l->execute([$id, $l]);
            }
        }

        setcookie('save', '1');
    } catch (PDOException $e) {
        die("Ошибка БД: " . $e->getMessage());
    }

    header('Location: index.php');
}
