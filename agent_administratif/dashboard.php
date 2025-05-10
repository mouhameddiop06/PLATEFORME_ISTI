<?php
session_start();

// Vérification de l'accès
if (!isset($_SESSION['user_id']) || !in_array('agent_admin', $_SESSION['user_roles'])) {
    header("Location: ../shared/login.php");
    exit;
}

$nom = $_SESSION['user_name'] ?? 'Agent';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Agent Administratif</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-3xl mx-auto mt-10 p-6 bg-white shadow-md rounded-lg">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Bienvenue, <?php echo htmlspecialchars($nom); ?> 👋</h1>
            <a href="../shared/logout.php" class="text-sm bg-red-700 hover:bg-red-800 text-white px-3 py-1 rounded">Déconnexion</a>
        </div>

        <p class="text-gray-600">Vous êtes sur le tableau de bord de l’agent administratif.</p>

        <!-- Contenu du tableau de bord ici -->
    </div>
</body>
</html>
