<?php
require_once '../config/database.php';
require_once '../config/utils.php';

$errors = [];
$success = false;

// Traitement du formulaire d'inscription
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupération des données du formulaire
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = sanitize($_POST['phone'] ?? '');
    $role = sanitize($_POST['role'] ?? '');
    
    // Validation des données
    if (empty($name)) {
        $errors[] = "Le nom complet est requis";
    }
    
    if (empty($email)) {
        $errors[] = "L'email est requis";
    } elseif (!isValidEmail($email)) {
        $errors[] = "Veuillez saisir un email valide";
    }
    
    if (empty($password)) {
        $errors[] = "Le mot de passe est requis";
    } elseif (strlen($password) < 8) {
        $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Les mots de passe ne correspondent pas";
    }
    
    if (empty($phone)) {
        $errors[] = "Le numéro de téléphone est requis";
    } elseif (!preg_match('/^[0-9]{9,15}$/', $phone)) {
        $errors[] = "Veuillez saisir un numéro de téléphone valide";
    }
    
    if (empty($role)) {
        $errors[] = "Veuillez sélectionner un rôle";
    }
    
    // Si aucune erreur, procéder à l'inscription
    if (empty($errors)) {
        try {
            // Connexion à la base de données
            $database = new Database();
            $db = $database->getConnection();
            
            // Vérifier si l'email existe déjà
            $check_query = "SELECT id FROM users WHERE email = :email";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(":email", $email);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                $errors[] = "Cet email est déjà utilisé";
            } else {
                // Hachage du mot de passe
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insertion de l'utilisateur dans la base de données
                $user_query = "INSERT INTO users (name, email, password_hash, phone) VALUES (:name, :email, :password_hash, :phone)";
                $user_stmt = $db->prepare($user_query);
                $user_stmt->bindParam(":name", $name);
                $user_stmt->bindParam(":email", $email);
                $user_stmt->bindParam(":password_hash", $password_hash);
                $user_stmt->bindParam(":phone", $phone);
                
                if ($user_stmt->execute()) {
                    $user_id = $db->lastInsertId();
                    
                    // Ajout du rôle à l'utilisateur
                    $role_query = "INSERT INTO user_roles (user_id, role) VALUES (:user_id, :role)";
                    $role_stmt = $db->prepare($role_query);
                    $role_stmt->bindParam(":user_id", $user_id);
                    $role_stmt->bindParam(":role", $role);
                    
                    if ($role_stmt->execute()) {
                        // Création d'une entrée dans la table d'audit pour cette inscription
                        $audit_query = "INSERT INTO audit_logs (user_id, action, table_cible) VALUES (:user_id, :action, :table_cible)";
                        $audit_stmt = $db->prepare($audit_query);
                        $action = "Création de compte avec rôle: " . $role;
                        $table = "users, user_roles";
                        $audit_stmt->bindParam(":user_id", $user_id);
                        $audit_stmt->bindParam(":action", $action);
                        $audit_stmt->bindParam(":table_cible", $table);
                        $audit_stmt->execute();
                        
                        $success = true;
                        
                        // Redirection vers la page de connexion avec un message de succès
                        $_SESSION['alert_message'] = "Compte créé avec succès. Vous pouvez maintenant vous connecter.";
                        $_SESSION['alert_type'] = "success";
                        header("Location: login.php");
                        exit;
                    } else {
                        $errors[] = "Erreur lors de l'attribution du rôle";
                    }
                } else {
                    $errors[] = "Erreur lors de la création du compte";
                }
            }
        } catch (PDOException $e) {
            $errors[] = "Erreur de base de données: " . $e->getMessage();
        }
    }
}

// Inclusion du header
include_once 'includes/header.php';
?>

<div class="max-w-2xl mx-auto">
    <h1 class="text-3xl font-bold text-center mb-8 text-gray-800">Inscription</h1>
    
    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <p class="font-bold">Des erreurs ont été détectées :</p>
            <ul class="list-disc ml-5">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
            <p>Votre compte a été créé avec succès ! Vous allez être redirigé vers la page de connexion.</p>
        </div>
        <script>
            setTimeout(function() {
                window.location.href = "login.php";
            }, 3000);
        </script>
    <?php endif; ?>
    
    <div class="bg-white shadow-md rounded-lg p-8">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="space-y-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Nom complet</label>
                <input type="text" name="name" id="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
            </div>
            
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" id="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
            </div>
            
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700">Téléphone</label>
                <input type="tel" name="phone" id="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
            </div>
            
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Mot de passe</label>
                <input type="password" name="password" id="password" class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                <p class="text-xs text-gray-500 mt-1">Le mot de passe doit contenir au moins 8 caractères.</p>
            </div>
            
            <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirmer le mot de passe</label>
                <input type="password" name="confirm_password" id="confirm_password" class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
            </div>
            
            <div>
    <label for="role" class="block text-sm font-medium text-gray-700">Rôle</label>
    <select name="role" id="role" class="mt-1 block w-full p-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
        <option value="">Sélectionnez un rôle</option>
        <option value="resp_dept" <?php echo (isset($_POST['role']) && $_POST['role'] === 'resp_dept') ? 'selected' : ''; ?>>Responsable de département</option>
        <option value="resp_filiere" <?php echo (isset($_POST['role']) && $_POST['role'] === 'resp_filiere') ? 'selected' : ''; ?>>Responsable de filière</option>
        <option value="resp_classe" <?php echo (isset($_POST['role']) && $_POST['role'] === 'resp_classe') ? 'selected' : ''; ?>>Responsable de classe</option>
        <option value="etudiant" <?php echo (isset($_POST['role']) && $_POST['role'] === 'etudiant') ? 'selected' : ''; ?>>Étudiant</option>
        <option value="enseignant" <?php echo (isset($_POST['role']) && $_POST['role'] === 'enseignant') ? 'selected' : ''; ?>>Enseignant</option>
        <option value="agent_admin" <?php echo (isset($_POST['role']) && $_POST['role'] === 'agent_admin') ? 'selected' : ''; ?>>Agent administratif</option>
    </select>
</div>

            
            <div id="student_fields" class="hidden space-y-6">
                <!-- Champs spécifiques aux étudiants seront ajoutés dynamiquement -->
            </div>
            
            <div id="teacher_fields" class="hidden space-y-6">
                <!-- Champs spécifiques aux enseignants seront ajoutés dynamiquement -->
            </div>
            
            <div class="flex items-center">
                <input id="terms" name="terms" type="checkbox" class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" required>
                <label for="terms" class="ml-2 block text-sm text-gray-700">
                    J'accepte les <a href="#" class="text-blue-600 hover:underline">termes et conditions</a>
                </label>
            </div>
            
            <div>
                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    S'inscrire
                </button>
            </div>
        </form>
        
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                Vous avez déjà un compte ? 
                <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500">
                    Connectez-vous
                </a>
            </p>
        </div>
    </div>
</div>

<script>
    // Affichage conditionnel des champs selon le rôle sélectionné
    document.addEventListener('DOMContentLoaded', function() {
        const roleSelect = document.getElementById('role');
        const studentFields = document.getElementById('student_fields');
        const teacherFields = document.getElementById('teacher_fields');
        
        roleSelect.addEventListener('change', function() {
            const selectedRole = this.value;
            
            // Cacher tous les champs spécifiques au rôle
            studentFields.classList.add('hidden');
            teacherFields.classList.add('hidden');
            
            // Afficher les champs selon le rôle sélectionné
            if (selectedRole === 'etudiant') {
                studentFields.classList.remove('hidden');
                // Charger dynamiquement les départements et filières disponibles
                loadDepartments();
            } else if (selectedRole === 'enseignant') {
                teacherFields.classList.remove('hidden');
                // Charger dynamiquement les départements disponibles
                loadDepartments();
            }
        });
        
        // Fonction pour charger les départements (à implémenter plus tard)
        function loadDepartments() {
            // Cette fonction sera implémentée quand nous aurons des données dans la base
            // Pour l'instant, on ajoute des champs statiques pour la démonstration
            
            if (roleSelect.value === 'etudiant') {
                studentFields.innerHTML = `
                    <div>
                        <p class="text-sm text-yellow-600 mb-2">
                            <i class="fas fa-info-circle"></i> Après validation de votre compte, vous pourrez choisir votre filière et niveau d'études.
                        </p>
                    </div>
                `;
            } else if (roleSelect.value === 'enseignant') {
                teacherFields.innerHTML = `
                    <div>
                        <p class="text-sm text-yellow-600 mb-2">
                            <i class="fas fa-info-circle"></i> Après validation de votre compte, vous pourrez spécifier vos matières et disponibilités.
                        </p>
                    </div>
                `;
            }
        }
        
        // Si un rôle est déjà sélectionné (par exemple après une soumission avec erreurs)
        if (roleSelect.value) {
            roleSelect.dispatchEvent(new Event('change'));
        }
    });
</script>

<?php
// Inclusion du footer
include_once 'includes/footer.php';
?>