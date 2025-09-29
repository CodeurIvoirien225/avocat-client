<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Accueil - Avocat & Client</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            background-color: #f5f5f5;
            padding: 50px;
        }
        h1 {
            color: #222;
        }
        .container {
            margin-top: 30px;
        }
        .btn {
            display: inline-block;
            padding: 15px 25px;
            margin: 10px;
            font-size: 16px;
            color: #fff;
            background-color: #007BFF;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .links {
            margin-top: 20px;
        }
        .links a {
            margin: 0 10px;
            text-decoration: none;
            color: #007BFF;
        }
        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    
    <h1>Bienvenue sur la plateforme Avocat-Client</h1>

    <div class="container">
        <h2>Connexion</h2>
        <a class="btn" href="connexion_avocat.php">Se connecter en tant qu'avocat</a>
        <a class="btn" href="connexion_client.php">Se connecter en tant que client</a>

        <div class="links">
            <h2>Pas encore inscrit ?</h2>
            <a href="inscription_avocat.php">S'inscrire en tant qu'avocat</a> |
            <a href="inscription_client.php">S'inscrire en tant que client</a>
        </div>
    </div>
</body>
</html>
