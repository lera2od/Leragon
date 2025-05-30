<?php

$projectName = $_GET['name'] ?? null;
if ($projectName === null) {
    header('Location: /index.php');
    exit;
}

require "include/lib.php";

$Docker = new DockerManager();
$containers = $Docker->listContainers(true);

$projects = [];
foreach ($containers as $container) {
    $containername = $container['Names'][0];
    $containername = str_replace('-', '_', $containername);
    $containername = ltrim($containername, '/');

    $name = explode('_', $containername)[0];
    if (!isset($projects[$name])) {
        $projects[$name] = [
            'containers' => [],
            'status' => 'running',
            'children' => 0,
        ];
    }
    $projects[$name]['containers'][] = $container;
    if ($container['State'] != 'running') {
        $projects[$name]['status'] = 'stopped';
    }
    $projects[$name]['children']++;
}

$project = $projects[$projectName] ?? null;
if ($project === null) {
    header('Location: /index.php');
    exit;
}


$projectDetails;
$projectDetails = [
    'containers' => [],
    'status' => $project['status'],
    'children' => $project['children'],
];

foreach ($project['containers'] as $container) {
    $projectDetails['containers'][] = [
        'name' => $container['Names'][0],
        'id' => $container['Id'],
        'ports' => array_unique(array_map(function ($port) {
            if (isset($port['PublicPort'])) {
            return $port['PublicPort'] . '->' . $port['PrivatePort'];
            } else {
            return $port['PrivatePort'];
            }
        }, $container['Ports'])),
        'status' => ($container['State'] === 'running') ? 'running' : 'stopped',
        'uptime' => $container['Status'],
        'image' => $container['Image'],
    ];  
}

function prettifyName($name)
{
    global $projectName;
    $name = str_replace('_', ' ', $name);
    $name = str_replace('-', ' ', $name);
    $name = str_replace("/" . $projectName . ' ', '', $name);
    if (substr($name, -2) == ' 1') {
        $name = substr($name, 0, -2);
    }
    $name = ucwords($name);
    return $name;
}


$ProjectData = new ProjectData($projectName);