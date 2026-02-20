<?php
if (strpos($_SERVER['REQUEST_URI'], '/api/url') !== false || 
    preg_match('/^\/([a-zA-Z0-9]+)$/', $_SERVER['REQUEST_URI'])) {
    require_once __DIR__ . '/src/URLController.php';
    $controller = new URLController();
    $controller->handleRequest();
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Acortador de URLs</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .caso { border: 1px solid #ccc; padding: 15px; margin: 10px 0; }
        input, button { padding: 8px; margin: 5px; }
        .resultado { background: #eee; padding: 10px; margin-top: 10px; }
    </style>
</head>
<body>
    <h1>Acortador de URLs</h1>
    
    <div class="caso">
        <h3>Acortar URL</h3>
        <input type="url" id="url" placeholder="https://ejemplo.com" size="50"><br>
        <input type="number" id="longitud" placeholder="Longitud (5-10)" value="6" min="5" max="10"><br>
        <input type="text" id="expiracion" placeholder="Fecha expiración (YYYY-MM-DD) opcional"><br>
        <input type="number" id="max_usos" placeholder="Máximo de usos (opcional)"><br>
        <button onclick="acortar()">Acortar URL</button>
        <div id="result1" class="resultado"></div>
    </div>
    
    <div class="caso">
        <h3>Ver estadísticas</h3>
        <input type="text" id="codigo" placeholder="Código (ej: abc123)"><br>
        <button onclick="estadisticas()">Ver estadísticas</button>
        <div id="result2" class="resultado"></div>
    </div>
    
    <div class="caso">
        <h3>Probar redirección</h3>
        <p>Después de acortar, abre: <span id="url_corta"></span></p>
    </div>

    <script>
        async function llamarAPI(url, options = {}) {
            const res = await fetch(url, options);
            const data = await res.json();
            return data;
        }

        function mostrar(id, data) {
            document.getElementById(id).innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        }

        async function acortar() {
            const data = await llamarAPI('/api/url', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    url: document.getElementById('url').value,
                    longitud: parseInt(document.getElementById('longitud').value),
                    expiracion: document.getElementById('expiracion').value || null,
                    max_usos: document.getElementById('max_usos').value ? parseInt(document.getElementById('max_usos').value) : null
                })
            });
            mostrar('result1', data);
            if (data.url_corta) {
                document.getElementById('url_corta').innerHTML = '<a href="' + data.url_corta + '" target="_blank">' + data.url_corta + '</a>';
            }
        }

        async function estadisticas() {
            const codigo = document.getElementById('codigo').value;
            const data = await llamarAPI('/api/url/' + codigo + '/stats');
            mostrar('result2', data);
        }
    </script>
</body>
</html>