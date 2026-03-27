<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class FirebaseHelper
{
    protected static function getAccessToken()
    {
        return Cache::remember('fcm_access_token', 3500, function () {

            $credentials = json_decode(file_get_contents(
                storage_path('app/firebase/firebase_credentials.json')
            ), true);

            $now = time();

            $header = [
                "alg" => "RS256",
                "typ" => "JWT"
            ];

            $payload = [
                "iss" => $credentials['client_email'],
                "scope" => "https://www.googleapis.com/auth/firebase.messaging",
                "aud" => "https://oauth2.googleapis.com/token",
                "exp" => $now + 3600,
                "iat" => $now
            ];

            $base64UrlEncode = function ($data) {
                return rtrim(strtr(base64_encode(json_encode($data)), '+/', '-_'), '=');
            };

            $jwtHeader = $base64UrlEncode($header);
            $jwtPayload = $base64UrlEncode($payload);

            $signatureInput = $jwtHeader . "." . $jwtPayload;

            openssl_sign($signatureInput, $signature, $credentials['private_key'], 'sha256WithRSAEncryption');

            $jwt = $signatureInput . '.' . rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

            // Exchange JWT for access token
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            return $response->json()['access_token'];
        });
    }

    protected static function getProjectId()
    {
        $json = json_decode(file_get_contents(
            storage_path('app/firebase/firebase_credentials.json')
        ), true);

        return $json['project_id'];
    }

    public static function sendNotification($deviceToken, $title, $body, $data = [])
    {
        try {
            $accessToken = self::getAccessToken();
            $projectId = self::getProjectId();

            $response = Http::withToken($accessToken)
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    "message" => [
                        "token" => $deviceToken,
                        "notification" => [
                            "title" => $title,
                            "body" => $body,
                        ],
                        "data" => (object) $data
                    ]
                ]);

            return $response->json();

        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }
}