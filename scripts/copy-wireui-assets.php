<?php

$destDir = __DIR__.'/../public/vendor/wireui';
$css = __DIR__.'/../vendor/wireui/wireui/dist/wireui.css';
$js = __DIR__.'/../vendor/wireui/wireui/dist/wireui.js';

if (! is_file($css) || ! is_file($js)) {
    fwrite(STDERR, "Arquivos WireUI ausentes. Em vendor/wireui/wireui rode: npm install && npm run build:css\n");
    exit(1);
}

if (! is_dir($destDir)) {
    mkdir($destDir, 0755, true);
}

copy($css, $destDir.'/wireui.css');
copy($js, $destDir.'/wireui.js');

echo "WireUI assets copiados para public/vendor/wireui\n";
