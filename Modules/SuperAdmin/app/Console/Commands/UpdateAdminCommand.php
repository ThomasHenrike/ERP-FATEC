<?php

declare(strict_types=1);

namespace Modules\SuperAdmin\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Modules\SuperAdmin\App\Models\Admin;

class UpdateAdminCommand extends Command
{
    protected $signature = 'admin:update {email : Email atual da conta} {--name=} {--new-email=} {--password=}';

    protected $description = 'Atualiza nome, email ou senha de uma conta do Admin Supremo ja existente';

    public function handle(): int
    {
        $admin = Admin::where('email', $this->argument('email'))->first();

        if ($admin === null) {
            $this->error("Nenhum admin encontrado com o email {$this->argument('email')}.");

            return self::FAILURE;
        }

        $data = array_filter([
            'name' => $this->option('name'),
            'email' => $this->option('new-email'),
            'password' => $this->option('password'),
        ], fn ($value) => $value !== null);

        if ($data === []) {
            $this->error('Informe pelo menos --name, --new-email ou --password.');

            return self::FAILURE;
        }

        $validator = Validator::make($data, [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:admins,email,' . $admin->id],
            'password' => ['sometimes', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $admin->update($data);

        $this->info("Admin atualizado: {$admin->email}");

        return self::SUCCESS;
    }
}
