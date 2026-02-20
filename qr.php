<?php
if (strpos($_SERVER['REQUEST_URI'], '/api/qr/') !== false) {
    require_once __DIR__ . '/src/QRController.php';
    $controller = new QRController();
    $controller->handleRequest();
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Generador QR</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .caso { border: 1px solid #ccc; padding: 15px; margin-bottom: 20px; }
        input, select { padding: 5px; margin: 5px 0; width: 300px; }
        button { padding: 10px; }
        .resultado { background: #eee; padding: 10px; margin-top: 10px; }
        img { max-width: 300px; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <h1>Generador de QR</h1>
    
    <div class="caso">
        <h3>Ver tipos disponibles</h3>
        <button onclick="verTipos()">Ver tipos</button>
        <div id="resultTipos"></div>
    </div>

    <div class="caso">
        <h3>QR de Texto</h3>
        <input type="text" id="texto" value="Hola mundo"><br>
        <input type="number" id="tamano1" value="300" min="100" max="1000"><br>
        <select id="correccion1">
            <option value="L">Bajo (L)</option>
            <option value="M" selected>Medio (M)</option>
            <option value="Q">Alto (Q)</option>
            <option value="H">Muy alto (H)</option>
        </select><br>
        <button onclick="generarTexto()">Generar QR</button>
        <div id="resultTexto"></div>
    </div>

    <div class="caso">
        <h3>QR de URL</h3>
        <input type="url" id="url" value="https://google.com"><br>
        <input type="number" id="tamano2" value="300" min="100" max="1000"><br>
        <select id="correccion2">
            <option value="L">Bajo</option>
            <option value="M" selected>Medio</option>
            <option value="Q">Alto</option>
            <option value="H">Muy alto</option>
        </select><br>
        <button onclick="generarURL()">Generar QR</button>
        <div id="resultURL"></div>
    </div>

    <div class="caso">
        <h3>QR de WiFi</h3>
        <input type="text" id="ssid" placeholder="SSID" value="MiWifi"><br>
        <input type="text" id="wifiPass" placeholder="Contraseña" value="12345678"><br>
        <select id="tipoWifi">
            <option value="WPA">WPA/WPA2</option>
            <option value="WEP">WEP</option>
            <option value="nopass">Sin contraseña</option>
        </select><br>
        <input type="number" id="tamano3" value="300" min="100" max="1000"><br>
        <button onclick="generarWifi()">Generar QR</button>
        <div id="resultWifi"></div>
    </div>

    <div class="caso">
        <h3>QR de Geolocalización</h3>
        <input type="number" id="lat" value="19.4326" placeholder="Latitud"><br>
        <input type="number" id="lon" value="-99.1332" placeholder="Longitud"><br>
        <input type="number" id="tamano4" value="300" min="100" max="1000"><br>
        <button onclick="generarGeo()">Generar QR</button>
        <div id="resultGeo"></div>
    </div>

    <script>
        async function llamarAPI(url, options = {}) {
            const res = await fetch(url, options);
            const contentType = res.headers.get('content-type');
            
            if (contentType && contentType.includes('image/png')) {
                const blob = await res.blob();
                return { imagen: URL.createObjectURL(blob) };
            } else {
                const data = await res.json();
                return { data: data };
            }
        }

        function mostrar(id, resultado) {
            const div = document.getElementById(id);
            if (resultado.imagen) {
                div.innerHTML = '<img src="' + resultado.imagen + '"><br>' +
                               '<a href="' + resultado.imagen + '" download="qr.png">Descargar</a>';
            } else {
                div.innerHTML = '<pre>' + JSON.stringify(resultado.data, null, 2) + '</pre>';
            }
        }

        async function verTipos() {
            const res = await llamarAPI('/api/qr/tipos');
            mostrar('resultTipos', res);
        }

        async function generarTexto() {
            const res = await llamarAPI('/api/qr/tipo/texto', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    texto: document.getElementById('texto').value,
                    tamano: parseInt(document.getElementById('tamano1').value),
                    correccion: document.getElementById('correccion1').value
                })
            });
            mostrar('resultTexto', res);
        }

        async function generarURL() {
            const res = await llamarAPI('/api/qr/tipo/url', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    url: document.getElementById('url').value,
                    tamano: parseInt(document.getElementById('tamano2').value),
                    correccion: document.getElementById('correccion2').value
                })
            });
            mostrar('resultURL', res);
        }

        async function generarWifi() {
            const res = await llamarAPI('/api/qr/tipo/wifi', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    ssid: document.getElementById('ssid').value,
                    password: document.getElementById('wifiPass').value,
                    tipo_wifi: document.getElementById('tipoWifi').value,
                    tamano: parseInt(document.getElementById('tamano3').value)
                })
            });
            mostrar('resultWifi', res);
        }

        async function generarGeo() {
            const res = await llamarAPI('/api/qr/tipo/geo', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    latitud: parseFloat(document.getElementById('lat').value),
                    longitud: parseFloat(document.getElementById('lon').value),
                    tamano: parseInt(document.getElementById('tamano4').value)
                })
            });
            mostrar('resultGeo', res);
        }
    </script>
</body>
</html>