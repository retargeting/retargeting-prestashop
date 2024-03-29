<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInite1b5796f992ca352ee030cd054b0825d
{
    public static $prefixLengthsPsr4 = array (
        'R' => 
        array (
            'RetargetingSDK\\' => 15,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'RetargetingSDK\\' => 
        array (
            0 => __DIR__ . '/..' . '/retargeting/retargeting-sdk/lib',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInite1b5796f992ca352ee030cd054b0825d::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInite1b5796f992ca352ee030cd054b0825d::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInite1b5796f992ca352ee030cd054b0825d::$classMap;

        }, null, ClassLoader::class);
    }
}
