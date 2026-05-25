<?php

declare(strict_types=1);

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    'theme' => [
        'path' => './assets/js/theme.js',
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@symfony/ux-live-component' => [
        'path' => './vendor/symfony/ux-live-component/assets/dist/live_controller.js',
    ],
    'bootstrap' => [
        'version' => '5.3.8',
    ],
    '@popperjs/core' => [
        'version' => '2.11.8',
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    'tom-select' => [
        'version' => '2.6.1',
    ],
    '@orchidjs/sifter' => [
        'version' => '1.1.0',
    ],
    '@orchidjs/unicode-variants' => [
        'version' => '1.1.2',
    ],
    'tom-select/dist/css/tom-select.default.min.css' => [
        'version' => '2.6.1',
        'type' => 'css',
    ],
    'bootstrap/dist/css/bootstrap.min.css' => [
        'version' => '5.3.8',
        'type' => 'css',
    ],
    'tom-select/dist/css/tom-select.bootstrap5.min.css' => [
        'version' => '2.6.1',
        'type' => 'css',
    ],
    'apexcharts' => [
        'version' => '5.13.0',
    ],
    '@toast-ui/editor' => [
        'version' => '3.2.2',
    ],
    'prosemirror-model' => [
        'version' => '1.25.7',
    ],
    'prosemirror-view' => [
        'version' => '1.41.8',
    ],
    'prosemirror-transform' => [
        'version' => '1.12.0',
    ],
    'prosemirror-state' => [
        'version' => '1.4.4',
    ],
    'prosemirror-keymap' => [
        'version' => '1.2.3',
    ],
    'prosemirror-commands' => [
        'version' => '1.7.1',
    ],
    'prosemirror-inputrules' => [
        'version' => '1.5.1',
    ],
    'prosemirror-history' => [
        'version' => '1.5.0',
    ],
    'orderedmap' => [
        'version' => '2.1.1',
    ],
    'w3c-keyname' => [
        'version' => '2.2.8',
    ],
    'rope-sequence' => [
        'version' => '1.3.4',
    ],
    'prosemirror-view/style/prosemirror.min.css' => [
        'version' => '1.41.8',
        'type' => 'css',
    ],
    '@toast-ui/editor/dist/toastui-editor.min.css' => [
        'version' => '3.2.2',
        'type' => 'css',
    ],
];
