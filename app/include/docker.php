<?php
class DockerManager
{
    private $socketPath;
    private $apiVersion;

    /**
     * Constructor
     * 
     * @param string $socketPath Docker socket path (default: /var/run/docker.sock)
     * @param string $apiVersion Docker API version (default: v1.41)
     */
    public function __construct($socketPath = '/var/run/docker.sock', $apiVersion = 'v1.41')
    {
        $this->socketPath = $socketPath;
        $this->apiVersion = $apiVersion;

        if (!file_exists($this->socketPath)) {
            throw new Exception("Docker socket not found at {$this->socketPath}. Please make sure the Docker socket is mounted into the container.");
        }
    }

    /**
     * Make a request to the Docker API
     * 
     * @param string $method HTTP method (GET, POST, DELETE)
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @return array Response data
     */
    private function request($method, $endpoint, $data = null)
    {
        $curl = curl_init();

        $url = "http://localhost/{$this->apiVersion}{$endpoint}";
        $headers = ['Content-Type: application/json'];

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_UNIX_SOCKET_PATH, $this->socketPath);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);

        if ($data) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($curl);
        $error = curl_error($curl);

        if ($error) {
            throw new Exception("cURL Error: $error");
        }

        curl_close($curl);

        if ($response === false) {
            throw new Exception("Failed to connect to Docker daemon");
        }

        $responseData = json_decode($response, true);

        if (isset($responseData['message'])) {
            throw new Exception("Docker API error: " . $responseData['message']);
        }

        return $responseData;
    }

    /**
     * List containers
     * 
     * @param bool $all Include stopped containers
     * @return array List of containers
     */
    public function listContainers($all = false)
    {
        $query = $all ? '?all=1' : '';
        return $this->request('GET', "/containers/json{$query}");
    }

    /**
     * Get detailed information about a container
     * 
     * @param string $containerId Container ID or name
     * @return array Container details
     */
    public function inspectContainer($containerId)
    {
        return $this->request('GET', "/containers/{$containerId}/json");
    }

    /**
     * Create a container
     * 
     * @param array $config Container configuration
     * @return array Response containing the ID of the created container
     */
    public function createContainer($config)
    {
        return $this->request('POST', "/containers/create", $config);
    }

    /**
     * Start a container
     * 
     * @param string $containerId Container ID or name
     * @return bool Success status
     */
    public function startContainer($containerId)
    {
        $this->request('POST', "/containers/{$containerId}/start");
        return true;
    }

    /**
     * Stop a container
     * 
     * @param string $containerId Container ID or name
     * @param int $timeout Seconds to wait before killing the container
     * @return bool Success status
     */
    public function stopContainer($containerId, $timeout = 10)
    {
        $this->request('POST', "/containers/{$containerId}/stop?t={$timeout}");
        return true;
    }

    /**
     * Restart a container
     * 
     * @param string $containerId Container ID or name
     * @param int $timeout Seconds to wait before killing the container
     * @return bool Success status
     */
    public function restartContainer($containerId, $timeout = 10)
    {
        $this->request('POST', "/containers/{$containerId}/restart?t={$timeout}");
        return true;
    }

    /**
     * Pause a container
     * 
     * @param string $containerId Container ID or name
     * @return bool Success status
     */
    public function pauseContainer($containerId)
    {
        $this->request('POST', "/containers/{$containerId}/pause");
        return true;
    }

    /**
     * Unpause a container
     * 
     * @param string $containerId Container ID or name
     * @return bool Success status
     */
    public function unpauseContainer($containerId)
    {
        $this->request('POST', "/containers/{$containerId}/unpause");
        return true;
    }

    /**
     * Remove a container
     * 
     * @param string $containerId Container ID or name
     * @param bool $force Force remove running container
     * @param bool $removeVolumes Remove volumes attached to the container
     * @return bool Success status
     */
    public function removeContainer($containerId, $force = false, $removeVolumes = false)
    {
        $query = [];
        if ($force)
            $query[] = 'force=1';
        if ($removeVolumes)
            $query[] = 'v=1';

        $queryString = !empty($query) ? '?' . implode('&', $query) : '';
        $this->request('DELETE', "/containers/{$containerId}{$queryString}");
        return true;
    }

    /**
     * Get container logs
     * 
     * @param string $containerId Container ID or name
     * @param bool $stdout Include stdout logs
     * @param bool $stderr Include stderr logs
     * @param int $tail Number of lines to return from the end of the logs
     * @return string Container logs
     */
    public function getContainerLogs($containerId, $stdout = true, $stderr = true, $tail = 100)
    {
        $query = [];
        if ($stdout)
            $query[] = 'stdout=1';
        if ($stderr)
            $query[] = 'stderr=1';
        if ($tail)
            $query[] = "tail={$tail}";

        $queryString = !empty($query) ? '?' . implode('&', $query) : '';

        $context = stream_context_create([
            'socket' => [
                'protocol' => 'unix',
            ],
            'http' => [
                'method' => 'GET',
                'ignore_errors' => true
            ]
        ]);

        $url = "http://localhost/{$this->apiVersion}/containers/{$containerId}/logs{$queryString}";
        $logs = file_get_contents("unix://{$this->socketPath}", false, $context, 0, null);

        if ($logs === false) {
            throw new Exception("Failed to get container logs");
        }

        $cleanedLogs = '';
        $rawLogs = str_split($logs, 8);
        foreach ($rawLogs as $line) {
            if (strlen($line) > 8) {
                $cleanedLogs .= substr($line, 8);
            }
        }

        return $cleanedLogs;
    }

    /**
     * Execute a command in a container
     * 
     * @param string $containerId Container ID or name
     * @param array $cmd Command to execute
     * @param bool $detach Run in background
     * @return array|string Exec ID or output
     */
    public function execContainer($containerId, $cmd, $detach = false)
    {
        $execConfig = [
            'AttachStdin' => false,
            'AttachStdout' => true,
            'AttachStderr' => true,
            'Tty' => false,
            'Cmd' => $cmd,
            'Detach' => $detach
        ];

        $exec = $this->request('POST', "/containers/{$containerId}/exec", $execConfig);

        if ($detach) {
            $this->request('POST', "/exec/{$exec['Id']}/start", ['Detach' => true]);
            return $exec['Id'];
        } else {
            $context = stream_context_create([
                'socket' => [
                    'protocol' => 'unix',
                ],
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\n",
                    'content' => json_encode(['Detach' => false]),
                    'ignore_errors' => true
                ]
            ]);

            $url = "http://localhost/{$this->apiVersion}/exec/{$exec['Id']}/start";
            $output = file_get_contents("unix://{$this->socketPath}", false, $context, 0, null);

            if ($output === false) {
                throw new Exception("Failed to execute command in container");
            }

            return $output;
        }
    }

    /**
     * List volumes
     * 
     * @return array List of volumes
     */
    public function listVolumes()
    {
        $response = $this->request('GET', '/volumes');
        return $response['Volumes'] ?? [];
    }

    /**
     * Get detailed information about a volume
     * 
     * @param string $volumeName Volume name
     * @return array Volume details
     */
    public function inspectVolume($volumeName)
    {
        return $this->request('GET', "/volumes/{$volumeName}");
    }

    /**
     * Create a volume
     * 
     * @param string $name Volume name
     * @param string $driver Volume driver
     * @param array $labels Volume labels
     * @return array Created volume information
     */
    public function createVolume($name, $driver = 'local', $labels = [])
    {
        $config = [
            'Name' => $name,
            'Driver' => $driver,
            'Labels' => $labels
        ];

        return $this->request('POST', '/volumes/create', $config);
    }

    /**
     * Remove a volume
     * 
     * @param string $volumeName Volume name
     * @param bool $force Force remove volume
     * @return bool Success status
     */
    public function removeVolume($volumeName, $force = false)
    {
        $query = $force ? '?force=1' : '';
        $this->request('DELETE', "/volumes/{$volumeName}{$query}");
        return true;
    }

    /**
     * List Docker networks
     * 
     * @return array List of networks
     */
    public function listNetworks()
    {
        return $this->request('GET', '/networks');
    }

    /**
     * Get system information
     * 
     * @return array System information
     */
    public function getSystemInfo()
    {
        return $this->request('GET', '/info');
    }

    /**
     * Get Docker version
     * 
     * @return array Version information
     */
    public function getVersion()
    {
        return $this->request('GET', '/version');
    }

    /**
     * Pull an image
     * 
     * @param string $image Image name
     * @return bool Success status
     */
    public function pullImage($image)
    {
        $this->request('POST', "/images/create?fromImage={$image}");
        return true;
    }

    /**
     * List images
     * 
     * @param bool $all Show all images (including intermediates)
     * @return array List of images
     */
    public function listImages($all = false)
    {
        $query = $all ? '?all=1' : '';
        return $this->request('GET', "/images/json{$query}");
    }

    /**
     * Remove an image
     * 
     * @param string $imageId Image ID or name
     * @param bool $force Force remove
     * @return bool Success status
     */
    public function removeImage($imageId, $force = false)
    {
        $query = $force ? '?force=1' : '';
        $this->request('DELETE', "/images/{$imageId}{$query}");
        return true;
    }
}

class ProjectData
{
    public $name;

    function __construct($name)
    {
        $this->name = $name;
        if(!file_exists($_SERVER["DOCUMENT_ROOT"] . '/projects.json')) {
            $projectsJson = [];
            file_put_contents($_SERVER["DOCUMENT_ROOT"] . '/projects.json', json_encode($projectsJson));
        }
    }

    function get($name)
    {
        $projectsJson = json_decode(file_get_contents($_SERVER["DOCUMENT_ROOT"] . '/projects.json'), true);
        if (isset($projectsJson[$this->name][$name])) {
            return $projectsJson[$this->name][$name];
        }
        return null;
    }

    function set($name, $value)
    {
        $projectsJson = json_decode(file_get_contents($_SERVER["DOCUMENT_ROOT"] . '/projects.json'), true);
        $projectsJson[$this->name][$name] = $value;
        file_put_contents($_SERVER["DOCUMENT_ROOT"] . '/projects.json', json_encode($projectsJson));
    }
}