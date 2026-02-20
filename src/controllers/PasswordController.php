<?php


require_once __DIR__ . '/../GenPassword.php';

class PasswordController
{
    public function handleRequest(): void
    {
        header('Content-Type: application/json');
        
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        error_log("Ruta solicitada: " . $path);
        
        try {
         
            if ($method === 'GET' && preg_match('/\/api\/password$/', $path)) {
                $this->generateSingle();
            }
            else if ($method === 'POST' && preg_match('/\/api\/passwords$/', $path)) {
                $this->generateMultiple();
            }
            else if ($method === 'POST' && preg_match('/\/api\/password\/validate$/', $path)) {
                $this->validatePassword();
            }
            else {
                $this->sendResponse(404, ['error' => 'Endpoint no encontrado', 'path' => $path]);
            }
        } catch (Exception $e) {
            $this->sendResponse(400, ['error' => $e->getMessage()]);
        }
    }

    private function generateSingle(): void
    {
        $length = $_GET['length'] ?? 16;
        
        if (!is_numeric($length) || $length < 4 || $length > 128) {
            throw new Exception("La longitud debe ser entre 4 y 128 caracteres");
        }
        
        $options = [
            'upper' => isset($_GET['includeUppercase']) ? filter_var($_GET['includeUppercase'], FILTER_VALIDATE_BOOLEAN) : true,
            'lower' => isset($_GET['includeLowercase']) ? filter_var($_GET['includeLowercase'], FILTER_VALIDATE_BOOLEAN) : true,
            'digits' => isset($_GET['includeNumbers']) ? filter_var($_GET['includeNumbers'], FILTER_VALIDATE_BOOLEAN) : true,
            'symbols' => isset($_GET['includeSymbols']) ? filter_var($_GET['includeSymbols'], FILTER_VALIDATE_BOOLEAN) : false,
            'avoid_ambiguous' => isset($_GET['excludeAmbiguous']) ? filter_var($_GET['excludeAmbiguous'], FILTER_VALIDATE_BOOLEAN) : false,
            'exclude' => $_GET['exclude'] ?? '',
            'require_each' => true
        ];
        
        $password = generate_password((int)$length, $options);
        
        $this->sendResponse(200, [
            'password' => $password,
            'length' => (int)$length,
            'options' => $options
        ]);
    }

    private function generateMultiple(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $count = $input['count'] ?? 5;
        $length = $input['length'] ?? 16;
        
        if (!is_numeric($count) || $count < 1 || $count > 100) {
            throw new Exception("La cantidad debe ser entre 1 y 100");
        }
        if (!is_numeric($length) || $length < 4 || $length > 128) {
            throw new Exception("La longitud debe ser entre 4 y 128 caracteres");
        }
        
        $options = [
            'upper' => $input['includeUppercase'] ?? true,
            'lower' => $input['includeLowercase'] ?? true,
            'digits' => $input['includeNumbers'] ?? true,
            'symbols' => $input['includeSymbols'] ?? false,
            'avoid_ambiguous' => $input['excludeAmbiguous'] ?? false,
            'exclude' => $input['exclude'] ?? '',
            'require_each' => true
        ];
        
        $passwords = [];
        for ($i = 0; $i < $count; $i++) {
            $passwords[] = generate_password((int)$length, $options);
        }
        
        $this->sendResponse(200, [
            'passwords' => $passwords,
            'count' => (int)$count,
            'length' => (int)$length,
            'options' => $options
        ]);
    }

    private function validatePassword(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        if (!isset($input['password'])) {
            throw new Exception("El campo 'password' es requerido");
        }
        
        $reqs = $input['requirements'] ?? [];
        $password = $input['password'];
        
        $minLength = $reqs['minLength'] ?? 8;
        $requireUppercase = $reqs['requireUppercase'] ?? false;
        $requireNumbers = $reqs['requireNumbers'] ?? false;
        $requireSymbols = $reqs['requireSymbols'] ?? false;
        
        $checks = [
            'length' => strlen($password) >= $minLength,
            'hasUppercase' => !$requireUppercase || preg_match('/[A-Z]/', $password),
            'hasLowercase' => true,
            'hasNumbers' => !$requireNumbers || preg_match('/[0-9]/', $password),
            'hasSymbols' => !$requireSymbols || preg_match('/[!@#$%^&*()\-_=+\[\]{}|;:,.<>?]/', $password)
        ];
        
        $isValid = !in_array(false, $checks, true);
        
        $this->sendResponse(200, [
            'password' => $password,
            'validation' => [
                'isValid' => $isValid,
                'checks' => $checks
            ]
        ]);
    }

    private function sendResponse(int $statusCode, array $data): void
    {
        http_response_code($statusCode);
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}