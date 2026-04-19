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

    // Состояние ошибок
    $errors = array();
    $fields = ['fullName', 'email', 'gender', 'languages', 'bio', 'privacy'];
    foreach ($fields as $f) {
        $errors[$f] = !empty($_COOKIE[$f . '_error']);
    }

    if ($errors['fullName']) {
        setcookie('fullName_error', '', 100000);
        $messages[] = '<div class="error-msg">Введите ФИО.</div>';
    }
    if ($errors['email']) {
        setcookie('email_error', '', 100000);
        $messages[] = '<div class="error-msg">Некорректный Email.</div>';
    }
    // Аналогичные проверки для остальных полей...

    // Значения полей
    $values = array();
    $all_fields = ['fullName', 'email', 'number', 'bdate', 'gender', 'bio', 'privacy'];
    foreach ($all_fields as $f) {
        $values[$f] = empty($_COOKIE[$f . '_value']) ? '' : $_COOKIE[$f . '_value'];
    }
    
    // Языки обрабатываем отдельно (массив через запятую)
    $values['languages'] = empty($_COOKIE['languages_value']) ? [] : explode(',', $_COOKIE['languages_value']);

    include('form.php');
} 
else {
    // МЕТОД POST
    $errors = FALSE;

    // Валидация ФИО
    if (empty($_POST['fullName'])) {
        setcookie('fullName_error', '1', time() + 24 * 3600);
        $errors = TRUE;
    }
    setcookie('fullName_value', $_POST['fullName'], time() + 30 * 24 * 3600);

    // Валидация Email
    if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        setcookie('email_error', '1', time() + 24 * 3600);
        $errors = TRUE;
    }
    setcookie('email_value', $_POST['email'], time() + 30 * 24 * 3600);

    // Валидация Био
    if (empty($_POST['bio'])) {
        setcookie('bio_error', '1', time() + 24 * 3600);
        $errors = TRUE;
    }
    setcookie('bio_value', $_POST['bio'], time() + 30 * 24 * 3600);

    // Валидация Языков
    if (empty($_POST['languages'])) {
        setcookie('languages_error', '1', time() + 24 * 3600);
        $errors = TRUE;
    } else {
        setcookie('languages_value', implode(',', $_POST['languages']), time() + 30 * 24 * 3600);
    }

    // Сохраняем остальные (необязательные) поля
    setcookie('number_value', $_POST['number'], time() + 30 * 24 * 3600);
    setcookie('bdate_value', $_POST['bdate'], time() + 30 * 24 * 3600);
    setcookie('gender_value', $_POST['gender'], time() + 30 * 24 * 3600);
    setcookie('privacy_value', $_POST['privacy'], time() + 30 * 24 * 3600);

    if ($errors) {
        header('Location: index.php');
        exit();
    }

    // Если всё Ок - сохраняем в БД
    try {
        $db = new PDO("mysql:host=$host;dbname=$db_name", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        $stmt = $db->prepare("INSERT INTO application (name, email, number, bday, sex, bio) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['fullName'], $_POST['email'], $_POST['number'], $_POST['bdate'], $_POST['gender'], $_POST['bio']]);

        $id = $db->lastInsertId();
        $stmt_l = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
        foreach ($_POST['languages'] as $l) {
            $stmt_l->execute([$id, $l]);
        }

        // Очищаем куки ошибок после успеха
        foreach (['fullName', 'email', 'languages', 'bio'] as $f) {
            setcookie($f . '_error', '', 100000);
        }

        setcookie('save', '1');
    } catch (PDOException $e) {
        die("Ошибка: " . $e->getMessage());
    }

    header('Location: index.php');
}
