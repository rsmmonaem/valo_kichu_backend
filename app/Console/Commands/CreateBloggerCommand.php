<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateBloggerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:blogger {phone : Phone number of the user} {password? : Password for the user} {--name= : Full name of the user} {--email= : Email of the user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or assign a Blogger employee user who has access only to Blog CRUD';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $phone = $this->argument('phone');
        $password = $this->argument('password') ?: '123456';
        $name = $this->option('name') ?: 'Blog Employee';
        $email = $this->option('email');

        $user = User::where('phone_number', $phone)->first();

        if ($user) {
            $user->role = 'blogger';
            $user->is_active = true;
            $user->is_approved = true;
            if ($this->argument('password')) {
                $user->password = Hash::make($password);
            }
            if ($email) {
                $user->email = $email;
            }
            $user->save();

            $this->info("User with phone {$phone} has been updated to role 'blogger' successfully.");
        } else {
            $nameParts = explode(' ', trim($name), 2);
            $firstName = $nameParts[0] ?? 'Blog';
            $lastName = $nameParts[1] ?? 'Writer';

            $user = User::create([
                'phone_number' => $phone,
                'email' => $email ?: 'blogger_' . time() . '@valokichu.com',
                'first_name' => $firstName,
                'last_name' => $lastName,
                'password' => Hash::make($password),
                'role' => 'blogger',
                'is_active' => true,
                'is_approved' => true,
                'is_verified' => true,
                'refer_code' => Str::random(10),
            ]);

            $this->info("Blogger user created successfully!");
        }

        $this->table(
            ['Field', 'Value'],
            [
                ['Name', $user->first_name . ' ' . $user->last_name],
                ['Phone', $user->phone_number],
                ['Email', $user->email],
                ['Role', $user->role],
                ['Password', $password],
                ['Permissions', 'Blog CRUD, Image Uploads, Categories (Read) only'],
            ]
        );

        return 0;
    }
}
