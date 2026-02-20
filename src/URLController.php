<?php
class URLController
{
    private $db;
    private $caracteres = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    
    public function __construct()
    {
        $this->db = new SQLite3('/var/www/html/src/data/urls.db');
        $this->crearTabla();
    }
    
    private function crearTabla()
    {
        $this->db->exec("CREATE TABLE IF NOT EXISTS urls (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            codigo TEXT UNIQUE NOT NULL,
            url_original TEXT NOT NULL,
            fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
            ip_creador TEXT,
            expiracion DATETIME NULL,
            max_usos INTEGER DEFAULT NULL,
            usos_actuales INTEGER DEFAULT 0
        )");
        
        $this->db->exec("CREATE TABLE IF NOT EXISTS visitas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            url_id INTEGER,
            fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
            ip TEXT,
            user_agent TEXT,
            FOREIGN KEY(url_id) REFERENCES urls(id)
        )");
    }
    
    public function handleRequest(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = str_replace('/index.php', '', $path);
        
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        
        if ($method === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
        
        try {
            if ($method === 'POST' && $path === '/api/url') {
                $this->acortarURL();
            }
            else if ($method === 'GET' && preg_match('/\/api\/url\/([a-zA-Z0-9]+)\/stats/', $path, $matches)) {
                $this->estadisticas($matches[1]);
            }
            else if ($method === 'GET' && preg_match('/^\/([a-zA-Z0-9]+)$/', $path, $matches)) {
                $this->redirigir($matches[1]);
            }
            else {
                $this->sendError(404, 'Endpoint no encontrado');
            }
        } catch (Exception $e) {
            $this->sendError(400, $e->getMessage());
        }
    }
    
    private function acortarURL()
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $url = $input['url'] ?? '';
        $expiracion = $input['expiracion'] ?? null;
        $max_usos = $input['max_usos'] ?? null;
        $longitud = $input['longitud'] ?? 6;
        
        if (empty($url)) throw new Exception("URL requerida");
        if (!filter_var($url, FILTER_VALIDATE_URL)) throw new Exception("URL no válida");
        if ($longitud < 5 || $longitud > 10) $longitud = 6;
        
        $codigo = $this->generarCodigoUnico($longitud);
        
        $stmt = $this->db->prepare("INSERT INTO urls (codigo, url_original, ip_creador, expiracion, max_usos) 
                                    VALUES (:codigo, :url, :ip, :expiracion, :max_usos)");
        $stmt->bindValue(':codigo', $codigo);
        $stmt->bindValue(':url', $url);
        $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? '');
        $stmt->bindValue(':expiracion', $expiracion);
        $stmt->bindValue(':max_usos', $max_usos);
        $stmt->execute();
        
        $url_corta = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/' . $codigo;
        
        $this->sendResponse(201, [
            'url_corta' => $url_corta,
            'codigo' => $codigo,
            'url_original' => $url
        ]);
    }
    
    private function generarCodigoUnico($longitud)
    {
        do {
            $codigo = '';
            for ($i = 0; $i < $longitud; $i++) {
                $codigo .= $this->caracteres[random_int(0, strlen($this->caracteres) - 1)];
            }
            $stmt = $this->db->prepare("SELECT id FROM urls WHERE codigo = :codigo");
            $stmt->bindValue(':codigo', $codigo);
            $result = $stmt->execute();
            $existe = $result->fetchArray();
        } while ($existe);
        
        return $codigo;
    }
    
    private function redirigir($codigo)
    {
        $stmt = $this->db->prepare("SELECT id, url_original, expiracion, max_usos, usos_actuales FROM urls WHERE codigo = :codigo");
        $stmt->bindValue(':codigo', $codigo);
        $result = $stmt->execute();
        $url = $result->fetchArray(SQLITE3_ASSOC);
        
        if (!$url) $this->sendError(404, 'URL no encontrada');
        if ($url['expiracion'] && strtotime($url['expiracion']) < time()) $this->sendError(410, 'URL expirada');
        if ($url['max_usos'] && $url['usos_actuales'] >= $url['max_usos']) $this->sendError(410, 'Límite de usos alcanzado');
        
        $stmt2 = $this->db->prepare("INSERT INTO visitas (url_id, ip, user_agent) VALUES (:url_id, :ip, :ua)");
        $stmt2->bindValue(':url_id', $url['id']);
        $stmt2->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? '');
        $stmt2->bindValue(':ua', $_SERVER['HTTP_USER_AGENT'] ?? '');
        $stmt2->execute();
        
        $stmt3 = $this->db->prepare("UPDATE urls SET usos_actuales = usos_actuales + 1 WHERE id = :id");
        $stmt3->bindValue(':id', $url['id']);
        $stmt3->execute();
        
        header('Location: ' . $url['url_original'], true, 302);
        exit;
    }
    
    private function estadisticas($codigo)
    {
        $stmt = $this->db->prepare("SELECT * FROM urls WHERE codigo = :codigo");
        $stmt->bindValue(':codigo', $codigo);
        $result = $stmt->execute();
        $url = $result->fetchArray(SQLITE3_ASSOC);
        
        if (!$url) $this->sendError(404, 'URL no encontrada');
        
        $stmt2 = $this->db->prepare("SELECT COUNT(*) as total FROM visitas WHERE url_id = :url_id");
        $stmt2->bindValue(':url_id', $url['id']);
        $result2 = $stmt2->execute();
        $total = $result2->fetchArray(SQLITE3_ASSOC);
        
        $stmt3 = $this->db->prepare("SELECT fecha, ip, user_agent FROM visitas WHERE url_id = :url_id ORDER BY fecha DESC LIMIT 10");
        $stmt3->bindValue(':url_id', $url['id']);
        $result3 = $stmt3->execute();
        
        $accesos = [];
        while ($row = $result3->fetchArray(SQLITE3_ASSOC)) {
            $accesos[] = $row;
        }
        
        $url['total_visitas'] = $total['total'];
        $url['ultimos_accesos'] = $accesos;
        
        $this->sendResponse(200, $url);
    }
    
    private function sendResponse($status, $data)
    {
        http_response_code($status);
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    private function sendError($status, $message)
    {
        http_response_code($status);
        echo json_encode(['error' => $message], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}