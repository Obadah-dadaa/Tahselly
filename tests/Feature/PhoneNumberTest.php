<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Phone;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PhoneNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_numbers_from_across_the_gulf_are_stored_in_international_format(): void
    {
        $this->assertSame('+971501234567', Phone::normalize('971', '050 123 4567'));
        $this->assertSame('+966501234567', Phone::normalize('966', '0501234567'));
        $this->assertSame('+96550012345', Phone::normalize('965', '5001 2345'));
        $this->assertSame('+97433123456', Phone::normalize('974', '3312 3456'));
        $this->assertSame('+966501234567', Phone::normalize('971', '+966 50 123 4567'), 'A full number with + wins over the picked country');
        $this->assertSame('+966501234567', Phone::normalize('971', '00966501234567'));
        $this->assertSame('+966501234567', Phone::normalize('966', '966501234567'), 'The code typed again is not doubled');
        $this->assertNull(Phone::normalize('971', '123'));
        $this->assertNull(Phone::normalize('971', 'abc'));
    }

    public function test_sign_up_with_a_saudi_number(): void
    {
        $this->seed(PlatformSeeder::class);
        Mail::fake();

        $this->get(route('trial.create'))->assertOk()->assertSee('name="phone_country"', false)->assertDontSee('05XXXXXXXX');

        $this->post(route('trial.store'), [
            'company_name' => 'Riyadh Co', 'name' => 'Faisal', 'email' => 'faisal@riyadh.test',
            'phone_country' => '966', 'phone' => '050 123 4567',
            'password' => 'secret123', 'password_confirmation' => 'secret123', 'terms' => '1',
        ])->assertRedirect(route('home'));
        $this->assertSame('+966501234567', User::query()->where('email', 'faisal@riyadh.test')->value('phone'));

        $this->post(route('logout'));
        $this->post(route('trial.store'), [
            'company_name' => 'Bad Co', 'name' => 'X', 'email' => 'x@bad.test', 'phone_country' => '971', 'phone' => '12',
            'password' => 'secret123', 'password_confirmation' => 'secret123', 'terms' => '1',
        ])->assertSessionHasErrors('phone');
    }
}
