<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_versao1',
        'origem_versao1',
        'clinica_id',
        'nome',
        'email',
        'password',
        'role',
        'coren',
        'imagem',
        'imagem_carimbo',
        'senha_certificado',
        'dashboard_secretaria',
        'dashboard_enfermagem',
        'controle_medicamentos',
        'pacientes',
        'procedimentos',
        'financeiro',
        'ativo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'ativo' => 'boolean',
        'dashboard_secretaria' => 'boolean',
        'dashboard_enfermagem' => 'boolean',
        'controle_medicamentos' => 'boolean',
        'pacientes' => 'boolean',
        'procedimentos' => 'boolean',
        'financeiro' => 'boolean',
    ];

    public function clinica()
    {
        return $this->belongsTo(Clinica::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSecretaria(): bool
    {
        return $this->role === 'secretaria';
    }

    public function isEnfermagem(): bool
    {
        return $this->role === 'enfermagem';
    }
}
