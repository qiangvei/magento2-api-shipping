<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitf767c5439d3e2422a51941ea7eab06f0
{
    public static $prefixLengthsPsr4 = array (
        'C' => 
        array (
            'Curl\\' => 5,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Curl\\' => 
        array (
            0 => __DIR__ . '/..' . '/php-curl-class/php-curl-class/src/Curl',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitf767c5439d3e2422a51941ea7eab06f0::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitf767c5439d3e2422a51941ea7eab06f0::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitf767c5439d3e2422a51941ea7eab06f0::$classMap;

        }, null, ClassLoader::class);
    }
}