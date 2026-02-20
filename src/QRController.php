<?php
require_once __DIR__ . '/phpqrcode.php';

class QRController
{
    private $tiposPermitidos = ['texto', 'url', 'wifi', 'geo'];
    private $nivelesCorreccion = ['L', 'M', 'Q', 'H'];

    public function handleRequest(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = str_replace('/index.php', '', $path);
        
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        
        if ($method === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
        
        try {
            if ($method === 'GET' && $path === '/api/qr/tipos') {
                $this->listarTipos();
            }
            else if ($method === 'POST' && $path === '/api/qr/tipo/texto') {
                $this->generarQRTexto();
            }
            else if ($method === 'POST' && $path === '/api/qr/tipo/url') {
                $this->generarQRUrl();
            }
            else if ($method === 'POST' && $path === '/api/qr/tipo/wifi') {
                $this->generarQRWifi();
            }
            else if ($method === 'POST' && $path === '/api/qr/tipo/geo') {
                $this->generarQRGeo();
            }
            else {
                $this->sendError(404, 'Endpoint no encontrado');
            }
        } catch (Exception $e) {
            $this->sendError(400, $e->getMessage());
        }
    }

    private function listarTipos(): void
    {
        $this->sendResponse(200, [
            'tipos' => $this->tiposPermitidos,
            'niveles' => $this->nivelesCorreccion
        ]);
    }

    private function generarQRTexto(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $texto = $input['texto'] ?? '';
        $tamano = $input['tamano'] ?? 300;
        $correccion = $input['correccion'] ?? 'M';
        
        if (empty($texto)) {
            throw new Exception("El texto es requerido");
        }
        
        $this->generarImagenQR($texto, $tamano, $correccion);
    }

    private function generarQRUrl(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $url = $input['url'] ?? '';
        $tamano = $input['tamano'] ?? 300;
        $correccion = $input['correccion'] ?? 'M';
        
        if (empty($url)) {
            throw new Exception("La URL es requerida");
        }
        
        $this->generarImagenQR($url, $tamano, $correccion);
    }

    private function generarQRWifi(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $ssid = $input['ssid'] ?? '';
        $password = $input['password'] ?? '';
        $tipoWifi = $input['tipo_wifi'] ?? 'WPA';
        $tamano = $input['tamano'] ?? 300;
        $correccion = $input['correccion'] ?? 'M';
        
        if (empty($ssid)) {
            throw new Exception("El SSID es requerido");
        }
        
        $contenido = "WIFI:T:{$tipoWifi};S:{$ssid};";
        if (!empty($password)) {
            $contenido .= "P:{$password};";
        }
        $contenido .= ";";
        
        $this->generarImagenQR($contenido, $tamano, $correccion);
    }

    private function generarQRGeo(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $lat = $input['latitud'] ?? '';
        $lon = $input['longitud'] ?? '';
        $tamano = $input['tamano'] ?? 300;
        $correccion = $input['correccion'] ?? 'M';
        
        if (!is_numeric($lat) || !is_numeric($lon)) {
            throw new Exception("Latitud y longitud deben ser números");
        }
        
        $contenido = "geo:{$lat},{$lon}";
        $this->generarImagenQR($contenido, $tamano, $correccion);
    }

    private function generarImagenQR($texto, $tamano, $correccion): void
    {
        if (!in_array($correccion, $this->nivelesCorreccion)) {
            $correccion = 'M';
        }
        
        $nivelMap = [
            'L' => QR_ECLEVEL_L,
            'M' => QR_ECLEVEL_M,
            'Q' => QR_ECLEVEL_Q,
            'H' => QR_ECLEVEL_H
        ];
        
        ob_start();
        QRcode::png($texto, false, $nivelMap[$correccion], 4, 2);
        $imagenQR = ob_get_clean();
        
        if ($tamano != 300) {
            $imagen = imagecreatefromstring($imagenQR);
            $nuevaImagen = imagecreatetruecolor($tamano, $tamano);
            imagecopyresampled($nuevaImagen, $imagen, 0, 0, 0, 0, $tamano, $tamano, imagesx($imagen), imagesy($imagen));
            
            ob_start();
            imagepng($nuevaImagen);
            $imagenQR = ob_get_clean();
        }
        
        header('Content-Type: image/png');
        echo $imagenQR;
        exit;
    }

    private function sendResponse($status, $data): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    private function sendError($status, $message): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
        exit;
    }
}