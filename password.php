<?php

if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
    require_once __DIR__ . '/src/GenPassword.php';
    require_once __DIR__ . '/src/controllers/PasswordController.php';
    $controller = new PasswordController();
    $controller->handleRequest();
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Generador de Contraseñas</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .caso { border: 1px solid #ccc; padding: 15px; margin-bottom: 20px; }
        button { padding: 10px; margin: 5px 0; }
        input { padding: 5px; margin: 5px 0; width: 200px; }
        .resultado { background: #eee; padding: 10px; margin-top: 10px; }
        .exito { color: green; }
        .error { color: red; }
        hr { margin: 20px 0; }
    </style>
</head>
<body>
    <h1>Generador de Contraseñas</h1>

    <div class="caso">
        <h3>Caso 1: Generar una contraseña</h3>
        <label>Longitud: <input type="number" id="length" value="12" min="4" max="128"></label><br>
        <label><input type="checkbox" id="upper" checked> Mayusculas</label><br>
        <label><input type="checkbox" id="lower" checked> Minusculas</label><br>
        <label><input type="checkbox" id="numbers" checked> Numeros</label><br>
        <label><input type="checkbox" id="symbols"> Simbolos</label><br>
        <label><input type="checkbox" id="ambiguous"> Evitar ambiguos</label><br>
        <button onclick="generarUna()">Generar</button>
        <div id="result1" class="resultado"></div>
    </div>

    <div class="caso">
        <h3>Caso 2: Generar varias contraseñas</h3>
        <label>Cantidad: <input type="number" id="count" value="5" min="1" max="100"></label><br>
        <label>Longitud: <input type="number" id="length2" value="16" min="4" max="128"></label><br>
        <button onclick="generarMultiples()">Generar</button>
        <div id="result2" class="resultado"></div>
    </div>

    <div class="caso">
        <h3>Caso 3: Validar contraseña</h3>
        <label>Contraseña: <input type="text" id="password" value="MiContraseña123!"></label><br>
        <label>Longitud minima: <input type="number" id="minLength" value="8"></label><br>
        <label><input type="checkbox" id="reqUpper" checked> Requiere mayusculas</label><br>
        <label><input type="checkbox" id="reqNumbers" checked> Requiere numeros</label><br>
        <label><input type="checkbox" id="reqSymbols" checked> Requiere simbolos</label><br>
        <button onclick="validar()">Validar</button>
        <div id="result3" class="resultado"></div>
    </div>

    <div class="caso">
        <h3>Caso 4: Error (longitud 1000)</h3>
        <button onclick="probarError()">Probar error</button>
        <div id="result4" class="resultado"></div>
    </div>

    <script>
        async function llamarAPI(url, options = {}) {
            try {
                const res = await fetch(url, options);
                const data = await res.json();
                return { ok: res.ok, status: res.status, data: data };
            } catch (e) {
                return { ok: false, error: e.message };
            }
        }

        function mostrarResultado(id, resultado) {
            const div = document.getElementById(id);
            let html = '';
            if (resultado.ok) {
                html += '<span class="exito">✓ Status ' + resultado.status + '</span><br>';
            } else {
                html += '<span class="error">✗ Status ' + (resultado.status || 'Error') + '</span><br>';
            }
            html += '<pre>' + JSON.stringify(resultado.data || resultado.error, null, 2) + '</pre>';
            div.innerHTML = html;
        }

        async function generarUna() {
            const length = document.getElementById('length').value;
            const upper = document.getElementById('upper').checked;
            const lower = document.getElementById('lower').checked;
            const numbers = document.getElementById('numbers').checked;
            const symbols = document.getElementById('symbols').checked;
            const ambiguous = document.getElementById('ambiguous').checked;
            
            const url = '/api/password?length=' + length + 
                       '&includeUppercase=' + upper + 
                       '&includeLowercase=' + lower + 
                       '&includeNumbers=' + numbers + 
                       '&includeSymbols=' + symbols + 
                       '&excludeAmbiguous=' + ambiguous;
            
            const res = await llamarAPI(url);
            mostrarResultado('result1', res);
        }

        async function generarMultiples() {
            const count = document.getElementById('count').value;
            const length = document.getElementById('length2').value;
            
            const res = await llamarAPI('/api/passwords', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    count: parseInt(count),
                    length: parseInt(length),
                    includeSymbols: true,
                    excludeAmbiguous: true
                })
            });
            
            mostrarResultado('result2', res);
        }

        async function validar() {
            const password = document.getElementById('password').value;
            const minLength = document.getElementById('minLength').value;
            const reqUpper = document.getElementById('reqUpper').checked;
            const reqNumbers = document.getElementById('reqNumbers').checked;
            const reqSymbols = document.getElementById('reqSymbols').checked;
            
            const res = await llamarAPI('/api/password/validate', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    password: password,
                    requirements: {
                        minLength: parseInt(minLength),
                        requireUppercase: reqUpper,
                        requireNumbers: reqNumbers,
                        requireSymbols: reqSymbols
                    }
                })
            });
            
            mostrarResultado('result3', res);
        }

        async function probarError() {
            const res = await llamarAPI('/api/password?length=1000');
            mostrarResultado('result4', res);
        }
    </script>
</body>
</html>