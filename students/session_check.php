if (!isset($_SESSION['user-id'])) {
    header("Location: ../auth/signin.php");
    exit;
}
