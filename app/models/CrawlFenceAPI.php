<?php
namespace App\Models;

use Exception;

/**
 * CrawlFenceAPI Class
 *
 * This class provides methods to interact with the CrawlFence Anti-bot API.
 */
class CrawlFenceAPI
{
  /**
   * Your CrawlFence API key.
   *
   * @var string
   */
  private $api_key;

  /**
   * API base URL.
   *
   * @var string
   */
  private $api_url = 'https://api.crawlfence.com/api';

  /**
   * Constructor
   *
   * Initializes the CrawlFenceAPI with the provided API key.
   *
   * @param string $api_key Your API key for authentication.
   * @throws Exception If the API key is not provided.
   */
  public function __construct($api_key)
  {
    if (empty($api_key)) {
      throw new Exception('API key is required.');
    }

    $this->api_key = $api_key;
  }

  /**
   * Handles the request by checking access and processing the response.
   *
   * @throws Exception If an error occurs during the process.
   */
  public function handleRequest()
  {
    // Get the client's IP address
    $ip_address = $this->getClientIp();

    // Collect received headers
    $headers_received = $this->getHeadersReceived();

    // User-Agent string
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // ISP information (if available)
    $isp = ''; // You can implement a method to obtain the ISP if necessary

    // Call the API to check access
    $response = $this->checkAccess($ip_address, $headers_received, $user_agent, $isp);

    // Process the response
    $this->processResponse($response);
  }

  /**
   * Retrieves the client's IP address.
   *
   * @return string Client's IP address.
   */
  private function getClientIp()
  {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
      $ip_address = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
      $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    return $ip_address;
  }

  /**
   * Collects headers received from the client.
   *
   * @return array Received headers.
   */
  private function getHeadersReceived()
  {
    $headers_received = [
      'Accept'      => $_SERVER['HTTP_ACCEPT'] ?? '',
      'Accept-Language'  => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
      'Accept-Encoding'  => $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '',
      'User-Agent'    => $_SERVER['HTTP_USER_AGENT'] ?? '',
      'Content-Length'  => $_SERVER['CONTENT_LENGTH'] ?? '',
      'Host'       => $_SERVER['HTTP_HOST'] ?? '',
      'Cache-Control'   => $_SERVER['HTTP_CACHE_CONTROL'] ?? '',
      'Forwarded'     => $_SERVER['HTTP_FORWARDED'] ?? '',
      'X-Forwarded-For'  => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
    ];

    // Remove empty headers
    $headers_received = array_filter($headers_received);

    return $headers_received;
  }

  /**
   * Processes the API response.
   *
   * @param array $response API response.
   */
  private function processResponse($response)
  {
    // Trim whitespace from array keys
    $response = $this->trimArrayKeys($response);

    $status = $response['status'] ?? 'UNKNOWN';

    switch ($status) {
      case 'ALLOWED':
        // Access granted
        // Add your logic here
        // For example, log the access, display a message, etc.
        break;

      case 'CAPTCHA_REQUIRED':
        // Redirect to CAPTCHA URL
        $captcha_url = $response['captcha_url'] ?? '';
        if (!empty($captcha_url)) {
          header("Location: $captcha_url");
          exit;
        } else {
          // Handle missing CAPTCHA URL
          echo "A CAPTCHA is required, but no CAPTCHA URL was provided.";
          exit;
        }
        break;

      case 'BLOCKED':
        // Access denied
        echo $response['message'] ?? "Access has been blocked due to security policies.";
        // Optional: Set HTTP 403 status code
        http_response_code(403);
        exit;

      default:
        // Unknown status
        echo "An unknown error occurred.";
        // Optional: Log the response for debugging
        // error_log(print_r($response, true));
        exit;
    }
  }

  /**
   * Recursively trims whitespace from array keys.
   *
   * @param array $array The array to process.
   * @return array The array with trimmed keys.
   */
  private function trimArrayKeys($array)
  {
    $trimmed_array = [];
    foreach ($array as $key => $value) {
      $trimmed_key = trim($key);
      if (is_array($value)) {
        $value = $this->trimArrayKeys($value);
      }
      $trimmed_array[$trimmed_key] = $value;
    }
    return $trimmed_array;
  }

  function get_domain() {
    $host = $_SERVER['HTTP_HOST'];

    // Supprimer 'www.' s'il existe
    $host = preg_replace('/^www\./', '', $host);


    return $host; 
}


  /**
   * Sends a request to the CrawlFence API.
   *
   * @param array $params Parameters to include in the request.
   * @return array    Decoded JSON response from the API.
   * @throws Exception  If the API request fails.
   */
  private function sendRequest($params = [])
  {
    // Include the API key in the parameters
    $params['api_key'] = $this->api_key;
    $params['domain'] = $this->get_domain();

    // Encode the parameters
    $query_string = http_build_query($params);

    // Build the full URL
    $url = $this->api_url . '?' . $query_string;

    // Initialize cURL
    $ch = curl_init();

    // Set cURL options for a GET request
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Optional: Set HTTP headers if necessary
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Accept: application/json',
    ]);

    // Execute the request
    $response = curl_exec($ch);

    // Error handling
    if ($response === false) {
      $error_msg = curl_error($ch);
      curl_close($ch);
      throw new Exception('cURL Error: ' . $error_msg);
    }

    // Get HTTP status code
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Check for HTTP errors
    if ($http_code >= 400) {
      throw new Exception("HTTP Error: {$http_code}\nResponse: {$response}");
    }

    // Decode JSON response
    $decoded_response = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new Exception('JSON Decode Error: ' . json_last_error_msg() . "\nResponse: {$response}");
    }

    return $decoded_response;
  }

  /**
   * Checks the access status of a given IP address.
   *
   * @param string $ip_address    IP address to check.
   * @param array $headers_received Array of HTTP headers received.
   * @param string $user_agent    User-Agent string.
   * @param string $isp       ISP information.
   * @return array          API response.
   */
  public function checkAccess($ip_address, $headers_received = [], $user_agent = '', $isp = '')
  {
    $params = [
      'ip'        => $ip_address,
      'headers_received' => json_encode($headers_received),
      'user_agent'    => $user_agent,
      'isp'       => $isp,
    ];

    return $this->sendRequest($params);
  }
}
?>