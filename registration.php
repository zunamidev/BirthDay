<?php

/// DEBUG sudo rm -rf var/cache var/generation var/di
/// php bin/magento c:c
/// php bin/magento setup:upgrade
/// php bin/magento setup:di:compile

\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'Zunami_BirthDay',
    __DIR__
);
