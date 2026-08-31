<?php

declare(strict_types=1);

namespace Modules\SuperAdmin\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Modules\SuperAdmin\App\Models\Admin;

class CreateAdminCommand extends Command
{
    protected $signature = 'admin:create {name} {email} {password}';

    protected $description = 'Cria uma conta do Admin Supremo (acesso total a plataforma)';

    public function handle(): int
    {
        $data = [
            'name' => $this->argument('name'),
            'email' => $this->argument('email'),
            'password' => $this->argument('password'),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:admins,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $admin = Admin::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $this->info("Admin Supremo criado: {$admin->email}");

        return self::SUCCESS;
    }
}
