<?php
namespace App\Controllers;

use InvalidArgumentException;

require_once __DIR__ . '/../GenPassword.php';

class PasswordController
{
    public function handleRequest(): void
    {
        header('Content-Type: application/json');
        
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        try {
            switch ("$method $path") {
                case 'GET /api/password':
                    $this->generateSingle();
                    break;
                    
                case 'POST /api/passwords':
                    $this->generateMultiple();
                    break;
                    
                case 'POST /api/password/validate':
                    $this->validatePassword();
                    break;
                    
                default:
                    $this->sendResponse(404, ['error' => 'Endpoint no encontrado']);
            }
        } catch (InvalidArgumentException $e) {
            $this->sendResponse(400, ['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            $this->sendResponse(500, ['error' => 'Error interno del servidor']);
        }
    }

    private function generateSingle(): void
    {
        $length = $this->getQueryParam('length', 16, 'int');
        
        if ($length < 4 || $length > 128) {
            throw new InvalidArgumentException("La longitud debe estar entre 4 y 128 caracteres");
        }
        
        $options = $this->buildOptionsFromParams();

        $password = generate_password($length, $options);
        
        $this->sendResponse(200, [
            'password' => $password,
            'length' => $length,
            'options' => $options
        ]);
    }

    private function generateMultiple(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $count = $input['count'] ?? 5;
        $length = $input['length'] ?? 16;
        
        if ($count < 1 || $count > 100) {
            throw new InvalidArgumentException("La cantidad debe estar entre 1 y 100");
        }
        if ($length < 4 || $length > 128) {
            throw new InvalidArgumentException("La longitud debe estar entre 4 y 128 caracteres");
        }
        
        $options = $this->buildOptionsFromInput($input);
        
     
        $passwords = generate_passwords($count, $length, $options);
        
        $this->sendResponse(200, [
            'passwords' => $passwords,
            'count' => $count,
            'length' => $length,
            'options' => $options
        ]);
    }

    private function validatePassword(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        if (!isset($input['password'])) {
            throw new InvalidArgumentException("El campo 'password' es requerido");
        }
        
        $requirements = $input['requirements'] ?? [];
        $result = $this->checkPasswordStrength($input['password'], $requirements);
        
        $this->sendResponse(200, [
            'password' => $input['password'],
            'validation' => $result
        ]);
    }

    private function checkPasswordStrength(string $password, array $requirements): array
    {
        $minLength = $requirements['minLength'] ?? 8;
        $requireUppercase = $requirements['requireUppercase'] ?? false;
        $requireNumbers = $requirements['requireNumbers'] ?? false;
        $requireSymbols = $requirements['requireSymbols'] ?? false;

        $checks = [
            'length' => strlen($password) >= $minLength,
            'hasUppercase' => !$requireUppercase || preg_match('/[A-Z]/', $password),
            'hasLowercase' => true, // Siemlo consideramos que puede tener minúsculas
            'hasNumbers' => !$requireNumbers || preg_match('/[0-9]/', $password),
            'hasSymbols' => !$requireSymbols || preg_match('/[!@#$%^&*()\-_=+\[\]{}|;:,.<>?]/', $password)
        ];

        $isValid = !in_array(false, $checks, true);
        
        // Calcular fortaleza
        $score = 0;
        if (strlen($password) >= 12) $score += 2;
        elseif (strlen($password) >= 8) $score += 1;
        
        if (preg_match('/[A-Z]/', $password)) $score += 1;
        if (preg_match('/[a-z]/', $password)) $score += 1;
        if (preg_match('/[0-9]/', $password)) $score += 1;
        if (preg_match('/[!@#$%^&*()\-_=+\[\]{}|;:,.<>?]/', $password)) $score += 2;

        $strength = 'Débil';
        if ($score >= 7) $strength = 'Muy Fuerte';
        elseif ($score >= 5) $strength = 'Fuerte';
        elseif ($score >= 3) $strength = 'Media';

        return [
            'isValid' => $isValid,
            'strength' => $strength,
            'score' => $score,
            'checks' => $checks
        ];
    }

    private function buildOptionsFromParams(): array
    {
        return [
            'upper' => filter_var($this->getQueryParam('includeUppercase', true), FILTER_VALIDATE_BOOLEAN),
            'lower' => filter_var($this->getQueryParam('includeLowercase', true), FILTER_VALIDATE_BOOLEAN),
            'digits' => filter_var($this->getQueryParam('includeNumbers', true), FILTER_VALIDATE_BOOLEAN),
            'symbols' => filter_var($this->getQueryParam('includeSymbols', false), FILTER_VALIDATE_BOOLEAN),
            'avoid_ambiguous' => filter_var($this->getQueryParam('excludeAmbiguous', false), FILTER_VALIDATE_BOOLEAN),
            'exclude' => $this->getQueryParam('exclude', ''),
            'require_each' => true
        ];
    }

    private function buildOptionsFromInput(array $input): array
    {
        return [
            'upper' => $input['includeUppercase'] ?? true,
            'lower' => $input['includeLowercase'] ?? true,
            'digits' => $input['includeNumbers'] ?? true,
            'symbols' => $input['includeSymbols'] ?? false,
            'avoid_ambiguous' => $input['excludeAmbiguous'] ?? false,
            'exclude' => $input['exclude'] ?? '',
            'require_each' => true
        ];
    }

    private function getQueryParam(string $key, $default = null, $type = 'string')
    {
        if (!isset($_GET[$key])) {
            return $default;
        }
        
        $value = $_GET[$key];
        
        if ($type === 'int') {
            return filter_var($value, FILTER_VALIDATE_INT) ? (int)$value : $default;
        }
        
        return $value;
    }

    private function sendResponse(int $statusCode, array $data): void
    {
        http_response_code($statusCode);
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}