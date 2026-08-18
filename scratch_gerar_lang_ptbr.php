<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\File;

$origem = File::get(base_path('vendor/laravel-lang/lang/locales/pt_BR/php.json'));
$traducoes = json_decode($origem, true);

// Seções que não pertencem ao validation.php
$secoes = [
    'auth' => ['failed', 'password', 'throttle'],
    'pagination' => ['previous', 'next'],
    'passwords' => ['reset', 'sent', 'throttled', 'token', 'user'],
];

// validation.php = todas as chaves menos as das seções acima
$validation = $traducoes;
foreach ($secoes as $chaves) {
    foreach ($chaves as $chave) {
        unset($validation[$chave]);
    }
}

// Atributos comuns em pt-BR
$atributos = [
    'nome' => 'nome',
    'email' => 'e-mail',
    'password' => 'senha',
    'password_confirmation' => 'confirmação de senha',
    'senha_atual' => 'senha atual',
    'imagem' => 'foto de perfil',
    'clinica_id' => 'clínica',
    'role' => 'papel',
    'tipo' => 'tipo',
];
$validation['attributes'] = $atributos;

File::ensureDirectoryExists(lang_path('pt_BR'));
File::put(lang_path('pt_BR/validation.php'), "<?php\n\nreturn " . var_export($validation, true) . ";\n");

foreach ($secoes as $secao => $chaves) {
    $conteudo = [];
    foreach ($chaves as $chave) {
        if (isset($traducoes[$chave])) {
            $conteudo[$chave] = $traducoes[$chave];
        }
    }
    File::put(lang_path("pt_BR/{$secao}.php"), "<?php\n\nreturn " . var_export($conteudo, true) . ";\n");
}

// pt_BR.json (traduções JSON usadas p/ nomes de atributos)
File::put(lang_path('pt_BR.json'), json_encode($atributos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");

echo "Arquivos gerados em lang/pt_BR:\n";
foreach (File::files(lang_path('pt_BR')) as $f) {
    echo "  - " . $f->getFilename() . "\n";
}
echo "  - pt_BR.json\n";
