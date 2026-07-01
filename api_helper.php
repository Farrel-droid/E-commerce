<?php
// api_helper.php

define('API_BASE_URL', 'http://localhost:3000/api');

/**
 * Perform an HTTP Request to the Node.js API server
 */
function makeApiRequest($endpoint, $method = 'GET', $data = null) {
    $url = API_BASE_URL . $endpoint;
    $ch = curl_init($url);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    
    // Set headers
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } elseif ($method === 'PUT' || $method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }

    // Optional: timeout
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        return [
            'success' => false,
            'error' => "Connection failed: $error_msg"
        ];
    }
    
    curl_close($ch);
    
    $decodedData = json_decode($response, true);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return [
            'success' => true,
            'data' => $decodedData
        ];
    } else {
        return [
            'success' => false,
            'error' => isset($decodedData['error']) ? $decodedData['error'] : 'Unknown server error'
        ];
    }
}

/**
 * Fetch products from the database API
 */
function getProducts($category = null, $search = null) {
    $params = [];
    if ($category) {
        $params['category'] = $category;
    }
    if ($search) {
        $params['search'] = $search;
    }
    
    $queryStr = !empty($params) ? '?' . http_build_query($params) : '';
    return makeApiRequest('/products' . $queryStr);
}

/**
 * Fetch a single product details
 */
function getProductById($id) {
    return makeApiRequest('/products/' . intval($id));
}

/**
 * Submit order to Node.js database
 */
function createOrder($orderData) {
    return makeApiRequest('/orders', 'POST', $orderData);
}

/**
 * Fetch all orders history
 */
function getOrders() {
    return makeApiRequest('/orders');
}

/**
 * Formats a currency number into IDR Rupiah format
 */
function formatRupiah($number) {
    return 'Rp ' . number_format($number, 0, ',', '.');
}

/**
 * Register a new user in the Node.js database
 */
function registerUser($userData) {
    return makeApiRequest('/register', 'POST', $userData);
}

/**
 * Log in an existing user
 */
function loginUser($email, $password) {
    return makeApiRequest('/login', 'POST', [
        'email' => $email,
        'password' => $password
    ]);
}
?>
