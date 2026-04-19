<?php
header('Content-Type: text/html; charset=UTF-8');

//подключение к бд
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
    //проверяем каждое поле формы на ошибки
    foreach ($fields as $f) {
        $errors[$f] = !empty($_COOKIE[$f . '_error']);
    }

    //для обязательных полей выводим специальные ошибки
    if ($errors['fullName']) {
    setcookie('fullName_error', '', 100000);
    $messages[] = '<div class="error-msg">Имя заполнено неверно или пустое.</div>';
}

    if ($errors['email']) {
        setcookie('email_error', '', 100000);
        $messages[] = '<div class="error-msg">Email указан некорректно.</div>';
    }

    if ($errors['languages']) {
        setcookie('languages_error', '', 100000);
        $messages[] = '<div class="error-msg">Выберите хотя бы один язык программирования.</div>';
    }

    if ($errors['bio']) {
        setcookie('bio_error', '', 100000);
        $messages[] = '<div class="error-msg">Расскажите что-нибудь о себе в биографии.</div>';
    }
    

    // значения полей
    $values = array();
    $all_fields = ['fullName', 'email', 'phone', 'bdate', 'gender', 'bio', 'privacy'];
    //если среди них есть ошибочные, заполняются пустотой
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
    setcookie('phone_value', $_POST['phone'], time() + 30 * 24 * 3600);
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

        $stmt = $db->prepare("INSERT INTO application (name, email, phone, bday, sex, bio) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['fullName'], $_POST['email'], $_POST['phone'], $_POST['bdate'], $_POST['gender'], $_POST['bio']]);

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
