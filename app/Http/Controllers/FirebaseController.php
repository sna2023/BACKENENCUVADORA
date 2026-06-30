<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Notificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use GuzzleHttp\Client;

class FirebaseController extends Controller
{
    private string $projectId = 'incuvadoraauthen';

    /**
     * Login con token de Firebase (Google Auth).
     * El frontend envía el idToken de Firebase, lo verificamos y creamos/busacamos el usuario.
     */
    public function loginWithFirebase(Request $request)
    {
        $request->validate([
            'idToken' => 'required|string',
        ]);

        try {
            $payload = $this->verifyFirebaseToken($request->idToken);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Token de Firebase inválido o expirado.',
            ], 401);
        }

        $correo = $payload['email'] ?? null;
        $nombre = $payload['name'] ?? $correo;
        $googleId = $payload['sub'] ?? null;

        if (!$correo) {
            return response()->json(['message' => 'No se pudo obtener el correo del token.'], 400);
        }

        // Verificar que sea correo institucional UNESUM
        $allowedDomains = ['unesum.edu.ec'];
        $domain = explode('@', $correo)[1] ?? '';

        if (!in_array($domain, $allowedDomains)) {
            return response()->json([
                'message' => 'Solo se permiten correos institucionales UNESUM (@unesum.edu.ec).',
            ], 403);
        }

        // Buscar o crear usuario
        $user = User::where('correo', $correo)->first();

        if ($user) {
            if (!$user->google_id) {
                $user->google_id = $googleId;
                $user->save();
            }
        } else {
            $user = User::create([
                'nombre'         => $nombre,
                'correo'         => $correo,
                'clave'          => Hash::make(uniqid('firebase_', true)),
                'clave_visible'  => '(Firebase Auth)',
                'google_id'      => $googleId,
                'rol'            => 'emprendedor',
                'estado'         => 'activo',
                'fecha_registro' => now(),
            ]);

            $admins = User::where('rol', 'administrador')->pluck('id_usuario');
            foreach ($admins as $adminId) {
                Notificacion::create([
                    'id_usuario' => $adminId,
                    'tipo'       => 'nuevo_usuario',
                    'mensaje'    => "Nuevo usuario registrado vía Google: \"{$user->nombre}\" ({$user->correo}).",
                    'url'        => '/admin/usuarios',
                ]);
            }
        }

        $token = $user->createToken('firebase-token')->plainTextToken;

        return response()->json([
            'user' => [
                'id_usuario'     => $user->id_usuario,
                'nombre'         => $user->nombre,
                'correo'         => $user->correo,
                'rol'            => $user->rol,
                'estado'         => $user->estado,
                'fecha_registro' => $user->fecha_registro,
            ],
            'token' => $token,
        ]);
    }

    /**
     * Verifica un Firebase ID Token descargando las public keys de Google.
     */
    private function verifyFirebaseToken(string $idToken): array
    {
        // Decodificar el header del JWT para obtener el kid
        $parts = explode('.', $idToken);
        if (count($parts) !== 3) {
            throw new \Exception('Invalid token format');
        }

        $header = json_decode(base64_decode(strtr($parts[0], '-_', '+/')), true);
        $kid = $header['kid'] ?? null;
        if (!$kid) {
            throw new \Exception('Missing kid in token header');
        }

        // Descargar public keys de Google
        $client = new Client();
        $response = $client->get('https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com');
        $keys = json_decode($response->getBody()->getContents(), true);

        $publicKey = $keys[$kid] ?? null;
        if (!$publicKey) {
            throw new \Exception('Public key not found for kid: ' . $kid);
        }

        // Verificar el token
        $decoded = JWT::decode($idToken, new Key($publicKey, 'RS256'));

        // Verificar el project ID
        if (($decoded->aud ?? null) !== $this->projectId) {
            throw new \Exception('Invalid audience');
        }

        if (($decoded->iss ?? null) !== 'https://securetoken.google.com/' . $this->projectId) {
            throw new \Exception('Invalid issuer');
        }

        return (array) $decoded;
    }
}
