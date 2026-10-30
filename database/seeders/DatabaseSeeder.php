<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Note;
use App\Models\Response;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Users (the password for all of them is "password")
        $admin = User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'phone_number' => '0612345678',
            'admin' => true,
        ]);

        // A second administrator, so tickets can be assigned to another administrator.
        User::factory()->create([
            'first_name' => 'Second',
            'last_name' => 'Admin',
            'email' => 'admin2@example.com',
            'phone_number' => '0611223344',
            'admin' => true,
        ]);

        $customer = User::factory()->create([
            'first_name' => 'Normal',
            'last_name' => 'User',
            'email' => 'user@example.com',
            'phone_number' => '0687654321',
        ]);

        $otherCustomer = User::factory()->create([
            'first_name' => 'Other',
            'last_name' => 'Customer',
            'email' => 'other@example.com',
        ]);

        // Categories
        $billing = Category::create(['title' => 'Billing']);
        $account = Category::create(['title' => 'Account']);
        $technical = Category::create(['title' => 'Technical issue']);
        Category::create(['title' => 'Feature request']);

        // Tickets
        $invoice = Ticket::factory()->for($customer, 'creator')->create([
            'title' => 'Cannot download my invoice',
            'description' => 'The download button on the invoices page does nothing.',
        ]);
        $invoice->categories()->attach($billing);

        $login = Ticket::factory()->for($customer, 'creator')->create([
            'title' => 'Login error after changing my password',
            'description' => 'I changed my password and now the login page shows an error.',
            'status' => 'in_progress',
            'assigned_to' => $admin->id,
        ]);
        $login->categories()->attach([$account->id, $technical->id]);

        $email = Ticket::factory()->for($customer, 'creator')->create([
            'title' => 'Wrong email address on my account',
            'description' => 'My account still shows my old email address.',
            'status' => 'resolved',
            'assigned_to' => $admin->id,
        ]);
        $email->categories()->attach($account);

        // A ticket from another customer, without a category
        Ticket::factory()->for($otherCustomer, 'creator')->create([
            'title' => 'Question about my subscription',
            'description' => 'Which plan am I on, and when does it renew?',
        ]);

        // Replies from the administrator
        Response::factory()->for($login, 'ticket')->for($admin, 'user')->create([
            'body' => 'Thanks for reporting this. We can reproduce the error and are looking into it.',
        ]);

        Response::factory()->for($email, 'ticket')->for($admin, 'user')->create([
            'body' => 'The email address has been updated. Please log in again.',
        ]);

        Note::factory()->for($login, 'ticket')->for($admin, 'user')->create([
            'body' => 'Check the password reset logs before contacting the customer.',
        ]);
    }
}
