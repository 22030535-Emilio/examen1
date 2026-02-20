<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>APIs</title>
    <style>
        body { font-family: Arial; margin: 20px; text-align: center; }
        .botones { margin-top: 50px; }
        button { 
            padding: 20px 40px; 
            margin: 20px; 
            font-size: 18px;
            cursor: pointer;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
        }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h1>APIs</h1>
    
    <div class="botones">
        <button onclick="window.location.href='/password.php'">Generador de Contraseñas</button>
        <button onclick="window.location.href='/qr.php'">Generador de Códigos QR</button>
        <button onclick="window.location.href='/url.php'">Acortador de URLs</button>
    </div>
</body>
</html>